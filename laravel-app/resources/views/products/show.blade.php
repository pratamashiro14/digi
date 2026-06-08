@extends('layouts.app')

@section('title', $produk->nama_produk . ' - Digi')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <img src="{{ asset('uploads/' . ($produk->gambar ?? 'placeholder.jpg')) }}" class="img-fluid rounded" alt="{{ $produk->nama_produk }}">
        </div>
        <div class="col-md-6">
            <h2>{{ $produk->nama_produk }}</h2>
            <p class="text-muted">Kategori: {{ $produk->kategori->nama_kategori }}</p>
            <p class="text-muted">Penjual: <a href="{{ route('profile.designer', $produk->user->id_user) }}">{{ $produk->user->nama }}</a></p>

            <div class="mb-4">
                <h4 class="text-danger">Rp {{ number_format($produk->harga, 0, ',', '.') }}</h4>
            </div>

            <h5>Deskripsi</h5>
            <p>{{ $produk->deskripsi }}</p>

            @if ($produk->status === 'aktif')
                @auth
                    <form action="{{ route('cart.tambah') }}" method="POST" class="d-inline me-2">
                        @csrf
                        <input type="hidden" name="id_produk" value="{{ $produk->id_produk }}">
                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" name="jumlah" class="form-control" value="1" min="1">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">Tambah ke Keranjang</button>
                    </form>
                    <a href="{{ route('messages.detail', $produk->user->id_user) }}" class="btn btn-outline-primary btn-lg">Chat Penjual</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Login untuk Membeli</a>
                @endauth
            @else
                <div class="alert alert-warning">Produk tidak tersedia untuk dibeli</div>
            @endif

            @if ($lelang && $lelang->isAktif())
                <div class="alert alert-info mt-4">
                    <h6>Ada Lelang untuk Produk Ini</h6>
                    <p>Harga tertinggi: Rp {{ number_format($lelang->harga_tertinggi ?: $lelang->harga_awal, 0, ',', '.') }}</p>
                    <a href="{{ route('lelang.show', $lelang->id_lelang) }}" class="btn btn-sm btn-info">Ikuti Lelang</a>
                </div>
            @endif
        </div>
    </div>

    <hr class="my-5">

    <h5>Produk Lainnya dari Desainer Ini</h5>
    <div class="row">
        @foreach ($produk->user->produk()->where('id_produk', '!=', $produk->id_produk)->where('status', 'aktif')->limit(4)->get() as $produk_lain)
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="{{ asset('uploads/' . ($produk_lain->gambar ?? 'placeholder.jpg')) }}" class="card-img-top" alt="{{ $produk_lain->nama_produk }}" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h6 class="card-title">{{ $produk_lain->nama_produk }}</h6>
                        <p class="text-danger">Rp {{ number_format($produk_lain->harga, 0, ',', '.') }}</p>
                        <a href="{{ route('product.show', $produk_lain->id_produk) }}" class="btn btn-sm btn-outline-primary w-100">Lihat</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
