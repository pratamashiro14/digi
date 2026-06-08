@extends('layouts.app')

@section('title', 'Riwayat Pesanan - Digi')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">Riwayat Pesanan</h2>

    @if ($pesanan->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Produk</th>
                        <th>Total</th>
                        <th>Status Pesanan</th>
                        <th>Status Pembayaran</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pesanan as $p)
                        <tr>
                            <td>#{{ $p->id_pesanan }}</td>
                            <td>{{ $p->produk->nama_produk }}</td>
                            <td>Rp {{ number_format($p->total_harga, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($p->status_pesanan) }}</span>
                            </td>
                            <td>
                                @if ($p->status_pembayaran === 'berhasil')
                                    <span class="badge bg-success">Berhasil</span>
                                @elseif ($p->status_pembayaran === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-danger">Belum Bayar</span>
                                @endif
                            </td>
                            <td>{{ $p->created_at->format('d-m-Y H:i') }}</td>
                            <td>
                                <a href="{{ route('orders.detail', $p->id_pesanan) }}" class="btn btn-sm btn-primary">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $pesanan->links() }}
        </div>
    @else
        <div class="alert alert-info">
            Anda belum memiliki pesanan. <a href="{{ route('product.index') }}">Mulai belanja</a>
        </div>
    @endif
</div>
@endsection
