<?php

namespace App\Http\Controllers;

use App\Models\Keranjang;
use App\Models\Produk;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    public function index()
    {
        $user_id = auth()->id();
        $keranjang = Keranjang::where('id_user', $user_id)
            ->with('produk')
            ->get();

        $total = $keranjang->sum(function($item) {
            return $item->produk->harga * $item->jumlah;
        });

        return view('cart.index', compact('keranjang', 'total'));
    }

    public function tambah(Request $request)
    {
        $user_id = auth()->id();
        $id_produk = $request->id_produk;
        $jumlah = $request->jumlah ?? 1;

        $keranjang = Keranjang::where('id_user', $user_id)
            ->where('id_produk', $id_produk)
            ->first();

        if ($keranjang) {
            $keranjang->jumlah += $jumlah;
            $keranjang->save();
        } else {
            Keranjang::create([
                'id_user' => $user_id,
                'id_produk' => $id_produk,
                'jumlah' => $jumlah,
            ]);
        }

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    public function hapus($id)
    {
        Keranjang::destroy($id);
        return back()->with('success', 'Produk dihapus dari keranjang!');
    }

    public function updateJumlah(Request $request, $id)
    {
        $keranjang = Keranjang::findOrFail($id);
        $keranjang->jumlah = $request->jumlah;
        $keranjang->save();

        return back()->with('success', 'Jumlah berhasil diupdate!');
    }
}
