<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Lelang;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index()
    {
        $produk = Produk::with('kategori', 'user')
            ->where('status', 'aktif')
            ->paginate(12);
        
        $kategori = Kategori::all();

        return view('products.index', compact('produk', 'kategori'));
    }

    public function show($id)
    {
        $produk = Produk::with('kategori', 'user')->findOrFail($id);
        $lelang = Lelang::where('id_produk', $id)->first();

        return view('products.show', compact('produk', 'lelang'));
    }

    public function byCategory($id)
    {
        $kategori = Kategori::findOrFail($id);
        $produk = Produk::where('id_kategori', $id)
            ->where('status', 'aktif')
            ->paginate(12);

        return view('products.index', compact('produk', 'kategori'));
    }

    public function search(Request $request)
    {
        $query = Produk::query();

        if ($request->keyword) {
            $query->where('nama_produk', 'like', '%' . $request->keyword . '%')
                ->orWhere('deskripsi', 'like', '%' . $request->keyword . '%');
        }

        if ($request->kategori_id) {
            $query->where('id_kategori', $request->kategori_id);
        }

        $query->where('status', 'aktif');
        
        $produk = $query->with('kategori', 'user')->paginate(12);
        $kategori = Kategori::all();

        return view('products.index', compact('produk', 'kategori'));
    }
}
