<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenawaranLelang extends Model
{
    use HasFactory;

    protected $table = 't_penawaran_lelang';
    protected $primaryKey = 'id_penawaran';
    protected $fillable = ['id_lelang', 'id_user', 'nominal'];
    public $timestamps = true;

    public function lelang()
    {
        return $this->belongsTo(Lelang::class, 'id_lelang', 'id_lelang');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
