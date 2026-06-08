@extends('layouts.app')

@section('title', 'Pasar Desain - Digi')

@section('content')

<!-- BREADCRUMB -->
<div class="sec-banner bg0 p-t-40 p-b-40">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('home') }}" class="stext-109 cl8 hov-cl1 trans-04">
                Beranda
            </a>
            <span class="stext-109 cl5 p-l-15 p-r-15">/</span>
            <span class="stext-109 cl5">Pasar Desain</span>
        </div>
    </div>
</div>

<!-- CONTENT -->
<section class="bg0 p-t-23 p-b-140">
    <div class="container">
        <div class="p-b-10"><h3 class="ltext-103 cl5">Pasar Desain</h3></div>
        <div class="flex-w flex-sb-m p-b-52">
            <div class="flex-w flex-l-m filter-tope-group m-tb-10">
                <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 how-active1" data-filter="*">Semua Karya</button>
                @foreach ($kategori as $cat)
                <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".{{ strtolower(str_replace(' ', '', $cat->nama_kategori)) }}">
                    {{ $cat->nama_kategori }}
                </button>
                @endforeach
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
                    <form method="GET" action="{{ route('product.search') }}">
                        <input class="mtext-107 cl2 size-114 plh2 p-r-15" type="text" name="keyword" placeholder="Search product...">
                    </form>
                </div>	
            </div>
        </div>

        <div class="row isotope-grid">
            @forelse($produk as $product)
            <div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item {{ strtolower(str_replace(' ', '', $product->kategori->nama_kategori ?? '')) }}">
                <div class="block2">
                    <div class="block2-pic hov-img0" style="position: relative;"> 
                        @php
                            $imagePath = $product->gambar ? 'admin/uploads/' . $product->gambar : 'images/product-' . ($loop->index % 16 + 1) . '.jpg';
                        @endphp
                        <img src="{{ asset($imagePath) }}" alt="{{ $product->nama_produk }}" style="width: 100%; aspect-ratio: 4/5; object-fit: cover;">
                        
                        <a href="{{ route('product.show', $product->id_produk) }}" class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04">
                            Lihat Detail
                        </a>
                    </div>
                    <div class="block2-txt flex-w flex-t p-t-14">
                        <div class="block2-txt-child1 flex-col-l ">
                            <a href="{{ route('product.show', $product->id_produk) }}" class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">{{ $product->nama_produk }}</a>
                            <span class="stext-102 cl3 p-b-5">{{ $product->kategori->nama_kategori }}</span>
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
            @if($produk->hasMorePages())
                <a href="{{ $produk->nextPageUrl() }}" class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">Lihat Lebih Banyak</a>
            @endif
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script src="{{ asset('vendor/isotope/isotope.pkgd.min.js') }}"></script>
<script>
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
