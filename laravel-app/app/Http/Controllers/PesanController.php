<?php

namespace App\Http\Controllers;

use App\Models\Pesan;
use App\Models\User;
use Illuminate\Http\Request;

class PesanController extends Controller
{
    public function index()
    {
        $user_id = auth()->id();
        
        // Ambil percakapan unik (dengan siapa saja)
        $pesan = Pesan::where('id_pengirim', $user_id)
            ->orWhere('id_penerima', $user_id)
            ->with('pengirim', 'penerima')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique(function($item) {
                return min($item->id_pengirim, $item->id_penerima) . '-' . max($item->id_pengirim, $item->id_penerima);
            });

        return view('messages.index', compact('pesan'));
    }

    public function detail($id_user)
    {
        $user_id = auth()->id();
        $kontak = User::findOrFail($id_user);

        $pesan = Pesan::where(function($q) use ($user_id, $id_user) {
            $q->where('id_pengirim', $user_id)->where('id_penerima', $id_user);
        })->orWhere(function($q) use ($user_id, $id_user) {
            $q->where('id_pengirim', $id_user)->where('id_penerima', $user_id);
        })->with('pengirim', 'penerima')
        ->orderBy('created_at', 'asc')
        ->get();

        // Tandai pesan sebagai sudah dibaca
        Pesan::where('id_penerima', $user_id)
            ->where('id_pengirim', $id_user)
            ->where('status', 'baru')
            ->update(['status' => 'dibaca']);

        return view('messages.detail', compact('pesan', 'kontak'));
    }

    public function send(Request $request, $id_penerima)
    {
        $request->validate([
            'isi_pesan' => 'required|min:1',
        ]);

        Pesan::create([
            'id_pengirim' => auth()->id(),
            'id_penerima' => $id_penerima,
            'isi_pesan' => $request->isi_pesan,
            'status' => 'baru',
        ]);

        return back()->with('success', 'Pesan berhasil dikirim!');
    }
}
