@extends('layouts.app')

@section('title', 'Detail Lelang - Digi')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <img src="{{ asset('uploads/' . ($lelang->produk->gambar ?? 'placeholder.jpg')) }}" class="img-fluid rounded mb-4" alt="{{ $lelang->produk->nama_produk }}">
        </div>
        <div class="col-md-6">
            <h2>{{ $lelang->produk->nama_produk }}</h2>
            <p class="text-muted">Penjual: {{ $lelang->penjual->nama }}</p>

            <div class="card mb-4">
                <div class="card-body">
                    <h6>Informasi Lelang</h6>
                    <div class="mb-3">
                        <small class="text-muted">Harga Awal</small>
                        <h5>Rp {{ number_format($lelang->harga_awal, 0, ',', '.') }}</h5>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Harga Tertinggi Saat Ini</small>
                        <h5 class="text-danger">Rp {{ number_format($lelang->harga_tertinggi ?: $lelang->harga_awal, 0, ',', '.') }}</h5>
                    </div>
                    @if ($penawaran_tertinggi)
                        <div class="mb-3">
                            <small class="text-muted">Penawaran Tertinggi dari</small>
                            <p>{{ $penawaran_tertinggi->user->nama }}</p>
                        </div>
                    @endif
                    <div class="mb-3">
                        <small class="text-muted">Berakhir</small>
                        <p><strong>{{ $lelang->waktu_berakhir->format('d-m-Y H:i') }}</strong></p>
                        <p class="text-muted">{{ $lelang->waktu_berakhir->diffForHumans() }}</p>
                    </div>
                </div>
            </div>

            @if ($lelang->isAktif())
                @auth
                    <div class="card">
                        <div class="card-body">
                            <h6>Ajukan Penawaran</h6>
                            <form action="{{ route('lelang.bid', $lelang->id_lelang) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Nominal Penawaran (minimal Rp {{ number_format(($lelang->harga_tertinggi ?: $lelang->harga_awal) + 50000, 0, ',', '.') }})</label>
                                    <input type="number" name="nominal" class="form-control" min="{{ ($lelang->harga_tertinggi ?: $lelang->harga_awal) + 50000 }}" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Tawar Sekarang</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <a href="{{ route('login') }}">Login terlebih dahulu</a> untuk mengajukan penawaran
                    </div>
                @endauth
            @else
                <div class="alert alert-warning">
                    Lelang ini sudah berakhir
                </div>
            @endif
        </div>
    </div>

    <hr class="my-5">

    <h5>Riwayat Penawaran</h5>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Penawar</th>
                    <th>Nominal</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lelang->penawaran()->orderBy('nominal', 'desc')->get() as $penawaran)
                    <tr>
                        <td>{{ $penawaran->user->nama }}</td>
                        <td class="text-danger"><strong>Rp {{ number_format($penawaran->nominal, 0, ',', '.') }}</strong></td>
                        <td>{{ $penawaran->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
