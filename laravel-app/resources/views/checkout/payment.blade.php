@extends('layouts.app')

@section('title', 'Pembayaran - Digi')

@section('css')
<script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Pembayaran</h4>
                    
                    <div class="mb-4">
                        <h6>Detail Pesanan:</h6>
                        @foreach ($pesanan_list as $pesanan)
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ $pesanan->produk->nama_produk }} ({{ $pesanan->jumlah }}x)</span>
                                <span>Rp {{ number_format($pesanan->total_harga, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-4">
                        <h6>Total Pembayaran:</h6>
                        <h6 class="text-danger">Rp {{ number_format($total_harga, 0, ',', '.') }}</h6>
                    </div>

                    <button class="btn btn-primary w-100" id="pay-button">
                        <i class="fa fa-credit-card"></i> Bayar Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    document.getElementById('pay-button').addEventListener('click', function() {
        snap.pay('{{ $snap_token }}', {
            onSuccess: function(result){
                alert("Pembayaran berhasil!");
                window.location.href = "{{ route('orders.history') }}";
            },
            onPending: function(result){
                alert("Pembayaran pending!");
            },
            onError: function(result){
                alert("Pembayaran gagal!");
            },
            onClose: function(){
                alert("Anda menutup popup tanpa menyelesaikan pembayaran");
            }
        });
    });
</script>
@endsection
