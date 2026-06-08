@extends('layouts.app')

@section('title', 'Beranda - Digi')

@section('content')

<!-- SLIDER SECTION -->
<section class="section-slide">
    <div class="wrap-slick1">
        <div class="slick1">
            <div class="item-slick1" style="background-image: url({{ asset('images/banner1.jpg') }});">
                <div class="container h-full">
                    <div class="flex-col-l-m h-full p-t-100 p-b-30 respon5">
                        <span class="ltext-101 cl2 respon2">Ilustrator Terbaik</span>
                        <h2 class="ltext-201 cl2 p-t-19 p-b-43 respon1">@digitalmagicianart</h2>
                        <a href="{{ route('product.index') }}" class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">Pesan Sekarang</a>
                    </div>
                </div>
            </div>
            <div class="item-slick1" style="background-image: url({{ asset('images/slide-02.jpg') }});">
                <div class="container h-full">
                    <div class="flex-col-l-m h-full p-t-100 p-b-30 respon5">
                        <span class="ltext-101 cl2 respon2">Desain Terbaru</span>
                        <h2 class="ltext-201 cl2 p-t-19 p-b-43 respon1">Koleksi 2025</h2>
                        <a href="{{ route('product.index') }}" class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">Lihat</a>
                    </div>
                </div>
            </div>
            <div class="item-slick1" style="background-image: url({{ asset('images/slide-03.jpg') }});">
                <div class="container h-full">
                    <div class="flex-col-l-m h-full p-t-100 p-b-30 respon5">
                        <span class="ltext-101 cl2 respon2">Desain Eksklusif</span>
                        <h2 class="ltext-201 cl2 p-t-19 p-b-43 respon1">Koleksi Premium</h2>
                        <a href="{{ route('lelang.index') }}" class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">Ikuti Lelang</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BANNER SECTION -->
<div class="sec-banner bg0 p-t-80 p-b-50">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-xl-4 p-b-30 m-lr-auto">
                <div class="block1 wrap-pic-w">
                    <img src="{{ asset('images/banner-01.png') }}" alt="IMG-BANNER">
                    <a href="{{ route('product.index') }}" class="block1-txt ab-t-l s-full flex-col-l-sb p-lr-38 p-tb-34 trans-03 respon3">
                        <div class="block1-txt-child1 flex-col-l">
                            <span class="block1-name ltext-102 trans-04 p-b-8">Ilustrasi</span>
                            <span class="block1-info stext-102 trans-04">Terpopuler</span>
                        </div>
                        <div class="block1-txt-child2 p-b-4 trans-05"><div class="block1-link stext-101 cl0 trans-09">Shop Now</div></div>
                    </a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4 p-b-30 m-lr-auto">
                <div class="block1 wrap-pic-w">
                    <img src="{{ asset('images/banner-02.png') }}" alt="IMG-BANNER">
                    <a href="{{ route('product.index') }}" class="block1-txt ab-t-l s-full flex-col-l-sb p-lr-38 p-tb-34 trans-03 respon3">
                        <div class="block1-txt-child1 flex-col-l">
                            <span class="block1-name ltext-102 trans-04 p-b-8">Tipografi</span>
                            <span class="block1-info stext-102 trans-04">Terbaru</span>
                        </div>
                        <div class="block1-txt-child2 p-b-4 trans-05"><div class="block1-link stext-101 cl0 trans-09">Shop Now</div></div>
                    </a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4 p-b-30 m-lr-auto">
                <div class="block1 wrap-pic-w">
                    <img src="{{ asset('images/banner-03.png') }}" alt="IMG-BANNER">
                    <a href="{{ route('product.index') }}" class="block1-txt ab-t-l s-full flex-col-l-sb p-lr-38 p-tb-34 trans-03 respon3">
                        <div class="block1-txt-child1 flex-col-l">
                            <span class="block1-name ltext-102 trans-04 p-b-8">UI/UX</span>
                            <span class="block1-info stext-102 trans-04">Trending</span>
                        </div>
                        <div class="block1-txt-child2 p-b-4 trans-05"><div class="block1-link stext-101 cl0 trans-09">Shop Now</div></div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PRODUCT SECTION -->
<section class="bg0 p-t-23 p-b-140">
    <div class="container">
        <div class="p-b-10"><h3 class="ltext-103 cl5">Pasar Karya</h3></div>
        <div class="flex-w flex-sb-m p-b-52">
            <div class="flex-w flex-l-m filter-tope-group m-tb-10">
                <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 how-active1" data-filter="*">Semua Karya</button>
                <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".ilustrasi">Ilustrasi</button>
                <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".tipografi">Tipografi</button>
                <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".uiux">UI/UX</button>
                <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".animasi">Animasi</button>
            </div>
            <div class="flex-w flex-c-m m-tb-10">
                <div class="flex-c-m stext-106 cl6 size-104 bor4 pointer hov-btn3 trans-04 m-r-8 m-tb-4 js-show-filter">
                    <i class="icon-filter cl2 m-r-6 fs-15 trans-04 zmdi zmdi-filter-list"></i> Filter
                </div>
                <div class="flex-c-m stext-106 cl6 size-105 bor4 pointer hov-btn3 trans-04 m-tb-4 js-show-search">
                    <i class="icon-search cl2 m-r-6 fs-15 trans-04 zmdi zmdi-search"></i> Pencarian
                </div>
            </div>
            <div class="dis-none panel-search w-full p-t-10 p-b-15">
                <div class="bor8 dis-flex p-l-15">
                    <button class="size-113 flex-c-m fs-16 cl2 hov-cl1 trans-04"><i class="zmdi zmdi-search"></i></button>
                    <input class="mtext-107 cl2 size-114 plh2 p-r-15" type="text" name="search-product" placeholder="Search">
                </div>	
            </div>
        </div>

        <div class="row isotope-grid">
            @forelse($products as $product)
            <div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item {{ strtolower(str_replace(' ', '', $product->kategori->nama_kategori ?? '')) }}">
                <div class="block2">
                    <div class="block2-pic hov-img0" style="position: relative;"> 
                        @php
                            $imagePath = $product->gambar ? 'admin/uploads/' . $product->gambar : 'images/product-' . ($loop->index % 16 + 1) . '.jpg';
                        @endphp
                        <img src="{{ asset($imagePath) }}" alt="{{ $product->nama_produk }}" style="width: 100%; aspect-ratio: 4/5; object-fit: cover;">
                        
                        @if(!empty($product->waktu_berakhir))
                        <div class="label-timer" data-waktu="{{ $product->waktu_berakhir }}">
                            <i class="fa fa-clock-o"></i> Loading...
                        </div>
                        @endif
                        
                        <a href="{{ route('product.show', $product->id_produk) }}" class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04">
                            Lihat Detail
                        </a>
                    </div>
                    <div class="block2-txt flex-w flex-t p-t-14">
                        <div class="block2-txt-child1 flex-col-l ">
                            <a href="{{ route('product.show', $product->id_produk) }}" class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">{{ $product->nama_produk }}</a>
                            <span class="stext-105 cl3">Rp {{ number_format($product->harga, 0, ',', '.') }}</span>
                        </div>
                        <div class="block2-txt-child2 flex-r p-t-3">
                            <a href="#" class="btn-addwish-b2 dis-block pos-relative js-addwish-b2">
                                <img class="icon-heart1 dis-block trans-04" src="{{ asset('images/icons/icon-heart-01.png') }}" alt="ICON">
                                <img class="icon-heart2 dis-block trans-04 ab-t-l" src="{{ asset('images/icons/icon-heart-02.png') }}" alt="ICON">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 p-l-15">
                <p class="stext-107 cl6">Belum ada produk.</p>
            </div>
            @endforelse
        </div>

        <div class="flex-c-m flex-w w-full p-t-45">
            <a href="{{ route('product.index') }}" class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">Lihat Lebih Banyak</a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script src="{{ asset('vendor/slick/slick.min.js') }}"></script>
<script src="{{ asset('vendor/isotope/isotope.pkgd.min.js') }}"></script>
<script src="{{ asset('vendor/countdowntime/countdowntime.js') }}"></script>
<script>
    $('.slick1').slick({
        arrows: true,
        autoplay: true,
        autoplaySpeed: 5000,
        infinite: true,
    });

    // Isotope filter
    $(function(){
        var $isotope = $('.isotope-grid').isotope({
            itemSelector: '.isotope-item',
            layoutMode: 'masonry'
        });

        $(".filter-tope-group").on("click", "button", function(e){
            e.preventDefault();
            var filterValue = $(this).attr("data-filter");
            $isotope.isotope({filter: filterValue});
            $(".filter-tope-group button").removeClass("how-active1");
            $(this).addClass("how-active1");
        });
    });
</script>
@endpush
