<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    use HasFactory;

    protected $table = 't_pesanan';
    protected $primaryKey = 'id_pesanan';
    protected $fillable = [
        'id_user', 'id_penjual', 'id_produk', 'jumlah', 'harga_satuan', 
        'total_harga', 'snap_token', 'status_pesanan', 'status_pembayaran',
        'no_referensi_pembayaran', 'waktu_pembayaran'
    ];
    public $timestamps = true;

    protected $casts = [
        'waktu_pembayaran' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function penjual()
    {
        return $this->belongsTo(User::class, 'id_penjual', 'id_user');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_pesanan', 'id_pesanan');
    }
}
