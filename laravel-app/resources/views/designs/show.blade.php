@extends('layouts.app')

@section('title', $design->judul . ' - Digi')

@section('content')

<!-- BREADCRUMB -->
<div class="sec-banner bg0 p-t-40 p-b-40">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('home') }}" class="stext-109 cl8 hov-cl1 trans-04">
                Beranda
            </a>
            <span class="stext-109 cl5 p-l-15 p-r-15">/</span>
            <a href="{{ route('design.index') }}" class="stext-109 cl8 hov-cl1 trans-04">
                Pasar Karya
            </a>
            <span class="stext-109 cl5 p-l-15 p-r-15">/</span>
            <span class="stext-109 cl5">{{ $design->judul }}</span>
        </div>
    </div>
</div>

<!-- DETAIL -->
<section class="bg0 p-t-20 p-b-140">
    <div class="container">
        <div class="row">
            <div class="col-md-6 p-b-30">
                @php
                    $imagePath = $design->gambar ? 'admin/uploads/' . $design->gambar : 'images/product-1.jpg';
                @endphp
                <img src="{{ asset($imagePath) }}" alt="{{ $design->judul }}" class="w-full" style="max-height: 600px; object-fit: cover;">
            </div>
            <div class="col-md-6 p-b-30">
                <div class="p-r-50 p-tb-25">
                    <h2 class="mtext-105 cl2 js-name-detail p-b-14">{{ $design->judul }}</h2>
                    
                    <div class="p-b-14">
                        <span class="mtext-106 cl2">Kategori: <strong>{{ $design->kategori }}</strong></span>
                    </div>

                    <div class="p-b-14">
                        <span class="mtext-105 cl3">Harga:</span>
                        <span class="mtext-105 cl11">Rp {{ number_format($design->harga_awal, 0, ',', '.') }}</span>
                    </div>

                    @if($design->designer)
                    <div class="p-b-14">
                        <span class="stext-102 cl3">Desainer:</span>
                        <br>
                        <span class="stext-104 cl4">{{ $design->designer->nama ?? 'Anonymous' }}</span>
                    </div>
                    @endif

                    <div class="p-b-20">
                        <h5 class="stext-102 cl3 p-b-12">Deskripsi:</h5>
                        <p class="stext-105 cl3">{{ $design->deskripsi }}</p>
                    </div>

                    <div class="flex-w p-b-33">
                        @auth
                        <button class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">
                            + Keranjang
                        </button>
                        @else
                        <a href="{{ route('login') }}" class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">
                            Login untuk Beli
                        </a>
                        @endauth
                    </div>

                    @if($design->status)
                    <div class="p-t-32">
                        <span class="stext-102 cl3">Status: </span>
                        <span class="badge badge-success">{{ ucfirst($design->status) }}</span>
                    </div>
                    @endif

                    @if($design->tanggal_upload)
                    <div class="p-t-10">
                        <span class="stext-102 cl3">Diupload: </span>
                        <span class="stext-102">{{ date('d/m/Y', strtotime($design->tanggal_upload)) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
