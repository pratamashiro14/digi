@extends('layouts.app')

@section('title', 'Profil Saya - Digi')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">{{ $user->nama }}</h5>
                    <p class="text-muted">{{ $user->email }}</p>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-primary w-100">Edit Profil</a>
                </div>
            </div>

            <div class="list-group">
                <a href="{{ route('profile.show') }}" class="list-group-item list-group-item-action active">Profil</a>
                <a href="{{ route('orders.history') }}" class="list-group-item list-group-item-action">Riwayat Pesanan</a>
                <a href="{{ route('cart.index') }}" class="list-group-item list-group-item-action">Keranjang</a>
                <a href="{{ route('messages.index') }}" class="list-group-item list-group-item-action">Pesan</a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Profil</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label"><strong>Nama</strong></label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">{{ $user->nama }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label"><strong>Email</strong></label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label"><strong>No HP</strong></label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">{{ $user->nohp ?? 'Belum diatur' }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label"><strong>Alamat</strong></label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">{{ $user->alamat ?? 'Belum diatur' }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label"><strong>Role</strong></label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">
                                @if ($user->isDesainer())
                                    <span class="badge bg-success">Desainer</span>
                                @else
                                    <span class="badge bg-info">Pembeli</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label"><strong>Status Premium</strong></label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">
                                @if ($user->isPremium())
                                    <span class="badge bg-warning">Premium (Aktif)</span>
                                @else
                                    <span class="badge bg-secondary">Reguler</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Ubah Password</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.changePassword') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="password_lama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_baru_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Ubah Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
