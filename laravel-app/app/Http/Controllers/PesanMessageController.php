<?php

namespace App\Http\Controllers;

use App\Models\Pesan;
use Illuminate\Http\Request;

class PesanController extends Controller
{
    public function index()
    {
        $user_id = auth()->id();
        $pesan = Pesan::where('id_pengirim', $user_id)
            ->orWhere('id_penerima', $user_id)
            ->with('pengirim', 'penerima', 'produk')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('messages.index', compact('pesan'));
    }

    public function show($id)
    {
        $pesan = Pesan::findOrFail($id);
        $pesan->status = 'dibaca';
        $pesan->save();

        return view('messages.show', compact('pesan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_penerima' => 'required|exists:t_user,id_user',
            'isi_pesan' => 'required|string',
            'id_produk' => 'nullable|exists:t_produk,id_produk',
        ]);

        Pesan::create([
            'id_pengirim' => auth()->id(),
            'id_penerima' => $request->id_penerima,
            'id_produk' => $request->id_produk,
            'isi_pesan' => $request->isi_pesan,
            'status' => 'baru',
        ]);

        return back()->with('success', 'Pesan berhasil dikirim!');
    }
}
