<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_pesan', function (Blueprint $table) {
            $table->id('id_pesan');
            $table->unsignedBigInteger('id_pengirim');
            $table->unsignedBigInteger('id_penerima');
            $table->unsignedBigInteger('id_produk')->nullable();
            $table->text('isi_pesan');
            $table->enum('status', ['baru', 'dibaca', 'dibalas'])->default('baru');
            $table->timestamps();
            
            $table->foreign('id_pengirim')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->foreign('id_penerima')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->foreign('id_produk')->references('id_produk')->on('t_produk')->onDelete('set null');
            $table->index('id_pengirim');
            $table->index('id_penerima');
        });

        Schema::create('t_notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->unsignedBigInteger('id_user');
            $table->string('tipe_notifikasi', 50);
            $table->text('pesan');
            $table->string('url', 255)->nullable();
            $table->boolean('dibaca')->default(false);
            $table->timestamps();
            
            $table->foreign('id_user')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->index('id_user');
            $table->index('dibaca');
        });

        Schema::create('t_premium', function (Blueprint $table) {
            $table->id('id_premium');
            $table->unsignedBigInteger('id_user');
            $table->decimal('harga', 12, 2);
            $table->integer('durasi_hari');
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_berakhir');
            $table->enum('status', ['aktif', 'kadaluarsa'])->default('aktif');
            $table->string('no_referensi_pembayaran', 100)->nullable();
            $table->timestamps();
            
            $table->foreign('id_user')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->index('id_user');
            $table->index('status');
        });

        Schema::create('t_password_reset', function (Blueprint $table) {
            $table->id();
            $table->string('email', 100);
            $table->string('token', 255)->unique();
            $table->dateTime('expiry');
            $table->timestamps();
            
            $table->index('token');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_password_reset');
        Schema::dropIfExists('t_premium');
        Schema::dropIfExists('t_notifikasi');
        Schema::dropIfExists('t_pesan');
    }
};
