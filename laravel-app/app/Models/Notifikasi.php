<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 't_notifikasi';
    protected $primaryKey = 'id_notifikasi';
    protected $fillable = ['id_user', 'tipe_notifikasi', 'pesan', 'url', 'dibaca'];
    public $timestamps = true;

    protected $casts = [
        'dibaca' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
