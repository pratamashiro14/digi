<?php

namespace App\Http\Controllers;

use App\Models\Lelang;
use App\Models\PenawaranLelang;
use App\Models\Produk;
use Illuminate\Http\Request;

class LelangController extends Controller
{
    public function index()
    {
        $lelang = Lelang::with('produk', 'penjual')
            ->where('status', 'aktif')
            ->where('waktu_berakhir', '>', now())
            ->orderBy('waktu_berakhir', 'asc')
            ->paginate(12);

        return view('auctions.index', compact('lelang'));
    }

    public function show($id)
    {
        $lelang = Lelang::with('produk', 'penjual', 'penawaran.user')->findOrFail($id);
        $penawaran_tertinggi = $lelang->penawaran()->orderBy('nominal', 'desc')->first();

        return view('auctions.show', compact('lelang', 'penawaran_tertinggi'));
    }

    public function submitBid(Request $request, $id)
    {
        $user_id = auth()->id();
        $lelang = Lelang::findOrFail($id);

        $request->validate([
            'nominal' => 'required|numeric|min:' . $lelang->harga_awal,
        ]);

        if (!$lelang->isAktif()) {
            return back()->with('error', 'Lelang sudah berakhir!');
        }

        $penawaran_terakhir = $lelang->penawaran()
            ->where('id_user', $user_id)
            ->orderBy('nominal', 'desc')
            ->first();

        if ($penawaran_terakhir && $request->nominal <= $penawaran_terakhir->nominal) {
            return back()->with('error', 'Nominal penawaran harus lebih tinggi dari penawaran sebelumnya!');
        }

        PenawaranLelang::create([
            'id_lelang' => $id,
            'id_user' => $user_id,
            'nominal' => $request->nominal,
        ]);

        // Update harga tertinggi
        $lelang->harga_tertinggi = $request->nominal;
        $lelang->save();

        return back()->with('success', 'Penawaran berhasil diajukan!');
    }

    public function riwayat()
    {
        $user_id = auth()->id();
        $lelang = Lelang::with('produk')
            ->where('id_user_penjual', $user_id)
            ->orderBy('waktu_berakhir', 'desc')
            ->paginate(10);

        return view('auctions.history', compact('lelang'));
    }
}
