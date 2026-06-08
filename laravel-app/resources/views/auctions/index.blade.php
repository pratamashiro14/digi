@extends('layouts.app')

@section('title', 'Lelang - Digi')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">Lelang Desain</h2>
    <p class="text-muted">Tawar harga desain favorit Anda dan menangkan dengan penawaran terbaik</p>

    @if ($lelang->count() > 0)
        <div class="row">
            @foreach ($lelang as $l)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="{{ asset('uploads/' . ($l->produk->gambar ?? 'placeholder.jpg')) }}" class="card-img-top" alt="{{ $l->produk->nama_produk }}" style="height: 250px; object-fit: cover;">
                        <div class="card-body">
                            <h6 class="card-title">{{ $l->produk->nama_produk }}</h6>
                            <p class="text-muted small">{{ $l->penjual->nama }}</p>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Harga Awal:</span>
                                    <strong>Rp {{ number_format($l->harga_awal, 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Harga Tertinggi:</span>
                                    <strong class="text-danger">Rp {{ number_format($l->harga_tertinggi ?: $l->harga_awal, 0, ',', '.') }}</strong>
                                </div>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">
                                    Berakhir: {{ $l->waktu_berakhir->diffForHumans() }}
                                </small>
                            </div>

                            <a href="{{ route('lelang.show', $l->id_lelang) }}" class="btn btn-primary w-100">Lihat & Tawar</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $lelang->links() }}
        </div>
    @else
        <div class="alert alert-info">
            Tidak ada lelang yang sedang berlangsung. Coba kembali nanti.
        </div>
    @endif
</div>
@endsection
