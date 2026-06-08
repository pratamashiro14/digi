<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Keranjang;
use App\Models\Produk;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function checkout(Request $request)
    {
        $user_id = auth()->id();
        $user = auth()->user();
        
        $keranjang = Keranjang::where('id_user', $user_id)
            ->with('produk')
            ->get();

        if ($keranjang->isEmpty()) {
            return back()->with('error', 'Keranjang Anda kosong!');
        }

        $pesanan_list = [];
        
        foreach ($keranjang as $item) {
            $produk = $item->produk;
            
            $pesanan = Pesanan::create([
                'id_user' => $user_id,
                'id_penjual' => $produk->id_user,
                'id_produk' => $produk->id_produk,
                'jumlah' => $item->jumlah,
                'harga_satuan' => $produk->harga,
                'total_harga' => $produk->harga * $item->jumlah,
                'status_pesanan' => 'pending',
                'status_pembayaran' => 'belum_bayar',
            ]);

            $pesanan_list[] = $pesanan;
        }

        $total_harga = array_sum(array_map(function($p) { return $p->total_harga; }, $pesanan_list));

        try {
            $snap_token = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id' => 'DIGI-' . time(),
                    'gross_amount' => intval($total_harga),
                ],
                'customer_details' => [
                    'first_name' => $user->nama,
                    'email' => $user->email,
                    'phone' => $user->nohp ?? '000000',
                ],
            ]);

            foreach ($pesanan_list as $pesanan) {
                $pesanan->snap_token = $snap_token;
                $pesanan->save();
            }

            return view('checkout.payment', compact('snap_token', 'pesanan_list', 'total_harga'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat snap token: ' . $e->getMessage());
        }
    }

    public function riwayat()
    {
        try {
            $user_id = auth()->id();
            $pesanan = Pesanan::where('id_user', $user_id)
                ->with('produk', 'penjual')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('orders.history', compact('pesanan'));
        } catch (\Illuminate\Database\QueryException $e) {
            // If table doesn't exist, return empty view
            return view('orders.history', ['pesanan' => collect()]);
        }
    }

    public function detail($id)
    {
        $pesanan = Pesanan::with('produk', 'user', 'penjual')->findOrFail($id);
        
        return view('orders.detail', compact('pesanan'));
    }

    public function webhookMidtrans(Request $request)
    {
        $data = $request->all();
        $order_id = $data['order_id'] ?? null;

        $pesanan = Pesanan::where('snap_token', $order_id)->first();

        if ($pesanan) {
            if ($data['transaction_status'] == 'capture' || $data['transaction_status'] == 'settlement') {
                $pesanan->status_pembayaran = 'berhasil';
                $pesanan->status_pesanan = 'dibayar';
                $pesanan->no_referensi_pembayaran = $data['transaction_id'] ?? null;
                $pesanan->waktu_pembayaran = now();
                $pesanan->save();
            } elseif ($data['transaction_status'] == 'deny' || $data['transaction_status'] == 'expire') {
                $pesanan->status_pembayaran = 'gagal';
                $pesanan->save();
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
