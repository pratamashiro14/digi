<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    use HasFactory;

    protected $table = 't_design';
    protected $primaryKey = 'id_design';
    public $timestamps = false;

    protected $fillable = ['id_designer', 'judul', 'deskripsi', 'kategori', 'harga_awal', 'gambar', 'tanggal_upload', 'status', 'waktu_berakhir', 'file_master'];

    public function designer()
    {
        return $this->belongsTo(User::class, 'id_designer', 'id_user');
    }
}
