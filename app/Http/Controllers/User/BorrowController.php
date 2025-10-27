<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\ThongBao;
use App\Models\Sach;
use App\Models\PhieuMuon;
use App\Models\PhieuMuonChiTiet;
use App\Models\DatCho;
use App\Models\NguoiDung;

class BorrowController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'sachdangmuon');
        $user = Auth::user();

        if (!($user instanceof NguoiDung)) {
            abort(403, "Người dùng hiện tại không hợp lệ hoặc chưa đăng nhập đúng.");
        }

        $muonChiTiets = $muonChiTietsMoi = $datChos = collect();

        if ($activeTab === 'sachdangmuon') {
            $muonChiTiets = $user->muonChiTiets()
                ->where('trangThaiCT', 'borrowed')
                ->with('sach')
                ->get();
        }

        if ($activeTab === 'muonsachmoi') {
            $muonChiTietsMoi = $user->muonChiTiets()
                ->where('trangThaiCT', 'pending')
                ->with('sach')
                ->get();
        }

        if ($activeTab === 'datcho') {
            $datChos = $user->datChos()->with('sach')->get();
        }

        return view('user.trangmuontra(sachdangmuon)', [
            'activeTab' => $activeTab,
            'muonChiTiets' => $muonChiTiets,   // thêm dòng này
            'muonChiTietsMoi' => $muonChiTietsMoi,
            'datChos' => $datChos
        ]);
    }

    // Nội dung tab Sách đang mượn (AJAX)
    public function contentSachdangMuon()
    {
        $user = Auth::user();
        if (!($user instanceof NguoiDung)) {
            abort(403, "Người dùng hiện tại không hợp lệ hoặc chưa đăng nhập đúng.");
        }

        $muonChiTiets = $user->muonChiTiets()
            ->where('phieu_muon_chi_tiet.trangThaiCT', 'approved')
            ->where('phieu_muon_chi_tiet.ghiChu', 'borrow')
            ->with('sach', 'phieuMuon.nguoiDung')
            ->get();

        $soSachDangMuon = $muonChiTiets->count();

        $activeTab = 'sachdangmuon';
        $books = collect();
        return view('user.content-mtra-sachdangmuon', compact('muonChiTiets', 'soSachDangMuon', 'activeTab', 'books'));
    }

    // Nội dung tab Mượn sách mới (AJAX)
    public function contentMuonSachMoi()
    {
        $user = Auth::user();
        if (!($user instanceof NguoiDung)) {
            abort(403, "Người dùng không hợp lệ.");
        }

        $books = Sach::where('trangThai', 'available')->get();

        $activeTab = 'muonsachmoi';
        return view('user.content-mtra-muonsachmoi', compact('books', 'activeTab'));
    }


    public function returnBook($idChiTiet)
    {
        $user = Auth::user();
        if (!($user instanceof NguoiDung)) {
            abort(403, "Người dùng không hợp lệ hoặc chưa đăng nhập.");
        }

        $chiTiet = PhieuMuonChiTiet::where('idPhieuMuonChiTiet', $idChiTiet)
            ->whereHas('phieuMuon', fn($q) => $q->where('idNguoiDung', $user->idNguoiDung))
            ->first();

        if (!$chiTiet) {
            return response()->json(['message' => 'Không tìm thấy sách cần trả.'], 404);
        }

        $chiTiet->trangThaiCT = 'pending';
        $chiTiet->ghiChu = 'return';
        $chiTiet->save();

        ThongBao::create([
            'idNguoiDung' => $user->idNguoiDung,
            'idSach' => $chiTiet->idSach,
            'idPhieuMuon' => $chiTiet->idPhieuMuon,
            'loaiThongBao' => "Thông báo trả sách",
            'noiDung' => "Bạn đã gửi yêu cầu trả sách {$chiTiet->sach->tenSach}.",
            'thoiGianGui' => now(),
            'trangThai' => 'unread'
        ]);

        return response()->json(['message' => 'Yêu cầu trả sách đã được gửi, vui lòng chờ quản trị viên duyệt.']);
    }

    // Nội dung tab Đặt chỗ (AJAX)
    public function contentDatCho()
    {
        $user = Auth::user();
        if (!($user instanceof NguoiDung)) {
            abort(403, "Người dùng hiện tại không hợp lệ hoặc chưa đăng nhập đúng.");
        }

        $datChos = $user->datChos()->with('sach')->get();

        return view('user.content-datcho', [
            'datChos' => $datChos,
            'activeTab' => 'datcho'
        ]);
    }

    // Action mượn sách
    public function borrow($idSach)
    {
        $user = Auth::user();
        $userId = $user->idNguoiDung;
        $today = Carbon::today();
        $dueDate = $today->copy()->addDays(14);

        DB::transaction(function () use ($userId, $idSach, $today, $dueDate, $user) {
            $phieuMuonId = DB::table('phieu_muon')->insertGetId([
                'idNguoiDung' => $userId,
                'ngayMuon' => $today,
                'hanTra' => $dueDate,
                'trangThai' => 'pending',
                'ghiChu' => "Phiếu mượn của {$user->hoTen}",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('phieu_muon_chi_tiet')->insert([
                'idPhieuMuon' => $phieuMuonId,
                'idSach' => $idSach,
                'borrow_date' => $today,
                'due_date' => $dueDate,
                'trangThaiCT' => 'pending',
                'ghiChu' => 'borrow',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $sach = Sach::find($idSach);
            ThongBao::create([
                'idNguoiDung' => $userId,
                'idSach' => $idSach,
                'idPhieuMuon' => $phieuMuonId,
                'loaiThongBao' => 'borrow',
                'noiDung' => "Yêu cầu mượn sách {$sach->tenSach} đã được gửi, vui lòng chờ quản trị viên duyệt",
                'thoiGianGui' => now(),
                'trangThai' => 'unread'
            ]);
        });

        return response()->json(['message' => 'Yêu cầu mượn sách đã được gửi, vui lòng chờ quản trị viên duyệt.']);
    }

    // Action đặt chỗ
    public function reserve($idSach)
    {
        $user = Auth::user();
        $userId = $user->idNguoiDung;
        $today = Carbon::today();
        $queueOrder = DB::table('dat_cho')->where('idSach', $idSach)->count() + 1;
        $expireDate = $today->copy()->addDays(14);

        $datChoId = DB::table('dat_cho')->insertGetId([
            'idNguoiDung' => $userId,
            'idSach' => $idSach,
            'ngayDat' => $today,
            'queueOrder' => $queueOrder,
            'status' => 'waiting',
            'thoiGianHetHan' => $expireDate,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $sach = Sach::find($idSach);
        ThongBao::create([
            'idNguoiDung' => $userId,
            'idSach' => $idSach,
            'idDatCho' => $datChoId,
            'loaiThongBao' => 'reserve',
            'noiDung' => "Bạn đã đặt chỗ sách {$sach->tenSach} thành công! Hết hạn: {$expireDate->format('d/m/Y')}",
            'thoiGianGui' => now(),
            'trangThai' => 'unread'
        ]);

        return response()->json(['message' => 'Bạn đã đặt chỗ sách thành công!']);
    }
}
