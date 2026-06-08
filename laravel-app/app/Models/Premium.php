<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Premium extends Model
{
    use HasFactory;

    protected $table = 't_premium';
    protected $primaryKey = 'id_premium';
    protected $fillable = [
        'id_user', 'harga', 'durasi_hari', 'tanggal_mulai',
        'tanggal_berakhir', 'status', 'no_referensi_pembayaran'
    ];
    public $timestamps = true;

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_berakhir' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function isAktif()
    {
        return $this->status === 'aktif' && now()->lessThan($this->tanggal_berakhir);
    }
}
