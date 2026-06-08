<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lelang extends Model
{
    use HasFactory;

    protected $table = 't_lelang';
    protected $primaryKey = 'id_lelang';
    protected $fillable = [
        'id_produk', 'id_user_penjual', 'harga_awal', 'harga_tertinggi',
        'id_user_pemenang', 'waktu_mulai', 'waktu_berakhir', 'status'
    ];
    public $timestamps = true;

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_berakhir' => 'datetime',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    public function penjual()
    {
        return $this->belongsTo(User::class, 'id_user_penjual', 'id_user');
    }

    public function pemenang()
    {
        return $this->belongsTo(User::class, 'id_user_pemenang', 'id_user');
    }

    public function penawaran()
    {
        return $this->hasMany(PenawaranLelang::class, 'id_lelang', 'id_lelang');
    }

    public function isAktif()
    {
        return $this->status === 'aktif' && now()->lessThan($this->waktu_berakhir);
    }
}
