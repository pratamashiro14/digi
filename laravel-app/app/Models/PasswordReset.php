<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    protected $table = 't_password_reset';
    protected $fillable = ['email', 'token', 'expiry'];
    public $timestamps = true;

    protected $casts = [
        'expiry' => 'datetime',
    ];

    public static function findByToken($token)
    {
        return self::where('token', $token)
            ->where('expiry', '>', now())
            ->first();
    }
}
