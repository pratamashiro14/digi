<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 't_produk';
    protected $primaryKey = 'id_produk';
    protected $fillable = ['id_kategori', 'id_user', 'nama_produk', 'deskripsi', 'harga', 'gambar', 'status'];
    public $timestamps = true;

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function keranjang()
    {
        return $this->hasMany(Keranjang::class, 'id_produk', 'id_produk');
    }

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_produk', 'id_produk');
    }

    public function lelang()
    {
        return $this->hasOne(Lelang::class, 'id_produk', 'id_produk');
    }
}
