@extends('layouts.app')

@section('title', 'Keranjang - Digi')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">Keranjang Belanja</h2>

    @if ($keranjang->count() > 0)
        <div class="row">
            <div class="col-md-8">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($keranjang as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('product.show', $item->produk->id_produk) }}">
                                            {{ $item->produk->nama_produk }}
                                        </a>
                                    </td>
                                    <td>Rp {{ number_format($item->produk->harga, 0, ',', '.') }}</td>
                                    <td>
                                        <form action="{{ route('cart.update', $item->id_keranjang) }}" method="POST" class="d-flex">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" name="jumlah" class="form-control" style="width: 70px;" value="{{ $item->jumlah }}" min="1">
                                            <button type="submit" class="btn btn-sm btn-secondary ms-2">Update</button>
                                        </form>
                                    </td>
                                    <td>Rp {{ number_format($item->produk->harga * $item->jumlah, 0, ',', '.') }}</td>
                                    <td>
                                        <form action="{{ route('cart.hapus', $item->id_keranjang) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan Pesanan</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Harga:</span>
                            <strong>Rp {{ number_format($total, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Biaya Layanan:</span>
                            <strong>-</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <h6>Total:</h6>
                            <h6 class="text-danger">Rp {{ number_format($total, 0, ',', '.') }}</h6>
                        </div>
                        <form action="{{ route('checkout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Lanjut Pembayaran</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <p>Keranjang Anda kosong. <a href="{{ route('product.index') }}">Lanjut belanja</a></p>
        </div>
    @endif
</div>
@endsection
