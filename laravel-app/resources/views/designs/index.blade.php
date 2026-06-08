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
                <a href="{{ route('design.index') }}" class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 how-active1">Semua Karya</a>
                @foreach ($categories as $cat)
                <a href="{{ route('design.category', urlencode($cat)) }}" class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 {{ isset($activeCategory) && $activeCategory === $cat ? 'how-active1' : '' }}">
                    {{ $cat }}
                </a>
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
                    <form method="GET" action="{{ route('design.search') }}" class="w-full">
                        <input class="mtext-107 cl2 size-114 plh2 p-r-15 w-full" type="text" name="keyword" placeholder="Cari karya desain...">
                    </form>
                </div>	
            </div>
        </div>

        <div class="row isotope-grid">
            @forelse($designs as $design)
            <div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item">
                <div class="block2">
                    <div class="block2-pic hov-img0" style="position: relative;"> 
                        @php
                            $imagePath = $design->gambar ? 'admin/uploads/' . $design->gambar : 'images/product-' . rand(1, 16) . '.jpg';
                        @endphp
                        <img src="{{ asset($imagePath) }}" alt="{{ $design->judul }}" style="width: 100%; aspect-ratio: 4/5; object-fit: cover;">
                        
                        <a href="{{ route('design.show', $design->id_design) }}" class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04">
                            Lihat Detail
                        </a>
                    </div>
                    <div class="block2-txt flex-w flex-t p-t-14">
                        <div class="block2-txt-child1 flex-col-l ">
                            <a href="{{ route('design.show', $design->id_design) }}" class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">{{ $design->judul }}</a>
                            <span class="stext-102 cl3 p-b-5">{{ $design->kategori }}</span>
                            <span class="stext-105 cl3">Rp {{ number_format($design->harga_awal, 0, ',', '.') }}</span>
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
                <p class="stext-107 cl6">Belum ada desain yang ditemukan.</p>
            </div>
            @endforelse
        </div>

        <div class="flex-c-m flex-w w-full p-t-45">
            @if($designs->hasMorePages())
                <a href="{{ $designs->nextPageUrl() }}" class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">Lihat Lebih Banyak</a>
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
    });
</script>
@endpush
