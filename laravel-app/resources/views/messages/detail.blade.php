@extends('layouts.app')

@section('title', 'Chat dengan ' . $kontak->nama . ' - Digi')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <h6>Percakapan</h6>
            <a href="{{ route('messages.index') }}" class="btn btn-sm btn-secondary w-100 mb-3">← Kembali</a>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Chat dengan {{ $kontak->nama }}</h6>
                </div>
                <div class="card-body" style="height: 400px; overflow-y: auto;">
                    @foreach ($pesan as $p)
                        <div class="mb-3 {{ $p->id_pengirim == auth()->id() ? 'text-end' : 'text-start' }}">
                            <div class="d-inline-block p-2 rounded" style="background-color: {{ $p->id_pengirim == auth()->id() ? '#007bff' : '#e9ecef' }}; max-width: 70%;">
                                <p class="mb-0" style="color: {{ $p->id_pengirim == auth()->id() ? '#fff' : '#000' }};">
                                    {{ $p->isi_pesan }}
                                </p>
                                <small style="color: {{ $p->id_pengirim == auth()->id() ? 'rgba(255,255,255,0.7)' : '#666' }};">
                                    {{ $p->created_at->format('H:i') }}
                                </small>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer">
                    <form action="{{ route('messages.send', $kontak->id_user) }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="isi_pesan" class="form-control" placeholder="Ketik pesan..." required>
                            <button type="submit" class="btn btn-primary">Kirim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
