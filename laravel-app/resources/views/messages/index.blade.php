@extends('layouts.app')

@section('title', 'Pesan - Digi')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <h5>Percakapan</h5>
            <div class="list-group">
                @forelse ($pesan as $p)
                    @php
                        $kontak_id = $p->id_pengirim == auth()->id() ? $p->id_penerima : $p->id_pengirim;
                        $kontak = $p->id_pengirim == auth()->id() ? $p->penerima : $p->pengirim;
                    @endphp
                    <a href="{{ route('messages.detail', $kontak_id) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $kontak->nama }}</h6>
                        </div>
                        <small class="text-muted">{{ Str::limit($p->isi_pesan, 50) }}</small>
                    </a>
                @empty
                    <div class="alert alert-info">Belum ada percakapan</div>
                @endforelse
            </div>
        </div>

        <div class="col-md-8">
            <div class="alert alert-info">
                Pilih percakapan dari daftar untuk melihat pesan
            </div>
        </div>
    </div>
</div>
@endsection
