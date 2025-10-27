<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    {{-- ✅ Chuyển đường dẫn CSS sang cú pháp Blade --}}
    <link rel="stylesheet" href="{{ asset('css/styleguide.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/globals.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/header-homepage-user.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/tranglichsumuontra.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/tranglichsumuontra(tratre).css') }}" />
    <link rel="stylesheet" href="{{ asset('css/footer_login-admin.css') }}">

    <style>
        .trang-lch-s-mn-tr .rectangle-15 {
            position: absolute;
            top: 0;
            left: 0;
            height: 39px;
            border-radius: 14px;
            background: linear-gradient(90deg,
                    rgba(255, 255, 255, 1) 0%,
                    rgba(226, 226, 226, 1) 87%);
        }

        .trang-lch-s-mn-tr .group-8 .text-wrapper-31 {
            color: black;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="rectangle">
        <div class="group-2-choosen">
            <div class="div">
                {{-- ✅ Đổi đường dẫn sang route Laravel (thay vì .html) --}}
                <a class="group-6" href="{{ url('user/content-tratre-lsmn') }}">
                    <div class="rectangle-6">
                        <div class="text-wrapper-5">Lịch sử mượn trả</div>
                        <div>
                            <img class="iconstack-io-book-2" src="{{ asset('images/iconstack.io - (Book) - white.png') }}" />
                        </div>
                    </div>
                </a>
                <a class="group-7" href="{{ url('user/content-datcho') }}">
                    <div class="rectangle-7">
                        <div>
                            <img class="iconstack-io" src="{{ asset('images/iconstack.io - (Bookmark) - Purple.png') }}" />
                        </div>
                        <div class="text-wrapper-6">Lịch sử đặt chỗ</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="can-giua-group-chon-lenh">
            <div class="group-chon-lenh">
                {{-- ✅ Các liên kết thay bằng URL route --}}
                <a class="group-8" href="{{ url('user/content-all-lsmn') }}">
                    <div class="rectangle-15"></div>
                    <div class="text-wrapper-31">Tất cả</div>
                </a>
                <a class="group-9" href="{{ url('user/content-datra-lsmn') }}">
                    <div class="rectangle-16"></div>
                    <div class="text-wrapper-32">Đã trả</div>
                </a>
                <a class="group-10" href="{{ url('user/content-dangmuon-lsmn') }}">
                    <div class="rectangle-17"></div>
                    <div class="text-wrapper-33">Đang mượn</div>
                </a>
                <a class="group-11" href="{{ url('user/content-tratre-lsmn') }}">
                    <div class="rectangle-31"></div>
                    <div class="text-wrapper-34">Trả trễ</div>
                </a>
            </div>
        </div>

        {{-- resources/views/user/content-lsmn-tratre.blade.php --}}
        <div class="group-ngoai-khoi-sach">
            <div class="group-khoi-sach">

                @forelse ($muonChiTiets as $chiTiet)
                @php
                $dueDate = \Carbon\Carbon::parse($chiTiet->phieuMuon->hanTra ?? $chiTiet->due_date);
                $today = \Carbon\Carbon::today();
                $isLate = ($chiTiet->trangThaiCT === 'borrowed') && $today->gt($dueDate);
                $phat = $chiTiet->phieuMuon->phats?->sum('soTienPhat') ?? 0;
                @endphp

                @if($isLate)
                <div class="khung-chung-sach-lch">
                    <div class="khung-anh-sach-lch">
                        <img class="image"
                            src="{{ $chiTiet->sach->anhBia ? asset($chiTiet->sach->anhBia) : asset('images/default-book.jpg') }}"
                            alt="{{ $chiTiet->sach->tenSach }}">
                    </div>
                    <div class="khung-chu-sach-lch">
                        <div class="text-wrapper-7">
                            Ngày mượn: {{ \Carbon\Carbon::parse($chiTiet->phieuMuon->ngayMuon ?? $chiTiet->created_at)->format('d/m/Y') }}
                        </div>
                        <div class="text-wrapper-8">{{ $chiTiet->sach->tenSach }}</div>
                        <div class="text-wrapper-9">Tác giả: {{ $chiTiet->sach->tacGia }}</div>
                        <div class="text-wrapper-10">
                            Hạn trả: {{ $dueDate->format('d/m/Y') }}
                        </div>
                        <div class="rectangle-12"></div>
                        <div class="text-wrapper-12 tra-tre">Trả trễ</div>
                        @if($phat > 0)
                        <div class="text-wrapper-13">
                            Phạt: {{ number_format($phat, 0, ',', '.') }}đ
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                @empty
                <p>Hiện tại bạn chưa trễ sách nào.</p>
                @endforelse

            </div>
        </div>

    </div>
</body>

</html>