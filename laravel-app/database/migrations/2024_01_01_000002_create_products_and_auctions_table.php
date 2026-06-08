<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_kategori', function (Blueprint $table) {
            $table->id('id_kategori');
            $table->string('nama_kategori', 100);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('t_produk', function (Blueprint $table) {
            $table->id('id_produk');
            $table->unsignedBigInteger('id_kategori');
            $table->unsignedBigInteger('id_user');
            $table->string('nama_produk', 255);
            $table->text('deskripsi');
            $table->decimal('harga', 12, 2);
            $table->string('gambar', 255)->nullable();
            $table->enum('status', ['aktif', 'nonaktif', 'terjual'])->default('aktif');
            $table->timestamps();
            
            $table->foreign('id_kategori')->references('id_kategori')->on('t_kategori')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->index('id_kategori');
            $table->index('id_user');
        });

        Schema::create('t_lelang', function (Blueprint $table) {
            $table->id('id_lelang');
            $table->unsignedBigInteger('id_produk');
            $table->unsignedBigInteger('id_user_penjual');
            $table->decimal('harga_awal', 12, 2);
            $table->decimal('harga_tertinggi', 12, 2)->default(0);
            $table->unsignedBigInteger('id_user_pemenang')->nullable();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_berakhir');
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->timestamps();
            
            $table->foreign('id_produk')->references('id_produk')->on('t_produk')->onDelete('cascade');
            $table->foreign('id_user_penjual')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->foreign('id_user_pemenang')->references('id_user')->on('t_user')->onDelete('set null');
            $table->index('id_produk');
            $table->index('id_user_penjual');
        });

        Schema::create('t_penawaran_lelang', function (Blueprint $table) {
            $table->id('id_penawaran');
            $table->unsignedBigInteger('id_lelang');
            $table->unsignedBigInteger('id_user');
            $table->decimal('nominal', 12, 2);
            $table->timestamps();
            
            $table->foreign('id_lelang')->references('id_lelang')->on('t_lelang')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->index('id_lelang');
            $table->index('id_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_penawaran_lelang');
        Schema::dropIfExists('t_lelang');
        Schema::dropIfExists('t_produk');
        Schema::dropIfExists('t_kategori');
    }
};
