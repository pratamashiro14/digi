<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 't_transaksi';
    protected $primaryKey = 'id_transaksi';
    protected $fillable = [
        'id_pesanan', 'jenis_transaksi', 'nominal', 
        'metode_pembayaran', 'status_transaksi', 'keterangan'
    ];
    public $timestamps = true;

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan');
    }
}
