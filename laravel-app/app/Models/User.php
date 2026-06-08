<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 't_user';
    protected $primaryKey = 'id_user';
    public $timestamps = false;
    
    protected $fillable = [
        'nama',
        'email',
        'password',
        'nohp',
        'alamat',
        'role',
        'status',
        'foto',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function produk()
    {
        return $this->hasMany(Produk::class, 'id_user', 'id_user');
    }

    public function keranjang()
    {
        return $this->hasMany(Keranjang::class, 'id_user', 'id_user');
    }

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_user', 'id_user');
    }

    public function pesananSebagaiPenjual()
    {
        return $this->hasMany(Pesanan::class, 'id_penjual', 'id_user');
    }

    public function pesan()
    {
        return $this->hasMany(Pesan::class, 'id_pengirim', 'id_user');
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'id_user', 'id_user');
    }

    public function premium()
    {
        return $this->hasOne(Premium::class, 'id_user', 'id_user');
    }

    public function lelang()
    {
        return $this->hasMany(Lelang::class, 'id_user_penjual', 'id_user');
    }

    public function penawaran()
    {
        return $this->hasMany(PenawaranLelang::class, 'id_user', 'id_user');
    }

    public function isPremium()
    {
        $premium = $this->premium()
            ->where('status', 'aktif')
            ->where('tanggal_berakhir', '>', now())
            ->first();
        
        return !is_null($premium);
    }

    public function isDesainer()
    {
        return $this->role === 'designer';
    }
}

