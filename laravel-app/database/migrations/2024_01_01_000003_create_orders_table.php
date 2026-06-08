<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_keranjang', function (Blueprint $table) {
            $table->id('id_keranjang');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_produk');
            $table->integer('jumlah')->default(1);
            $table->timestamps();
            
            $table->foreign('id_user')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->foreign('id_produk')->references('id_produk')->on('t_produk')->onDelete('cascade');
            $table->unique(['id_user', 'id_produk']);
        });

        Schema::create('t_pesanan', function (Blueprint $table) {
            $table->id('id_pesanan');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_penjual');
            $table->unsignedBigInteger('id_produk');
            $table->integer('jumlah');
            $table->decimal('harga_satuan', 12, 2);
            $table->decimal('total_harga', 12, 2);
            $table->string('snap_token', 255)->nullable();
            $table->enum('status_pesanan', ['pending', 'dibayar', 'dikirim', 'diterima', 'dibatalkan'])->default('pending');
            $table->enum('status_pembayaran', ['belum_bayar', 'pending', 'berhasil', 'gagal'])->default('belum_bayar');
            $table->string('no_referensi_pembayaran', 100)->nullable();
            $table->dateTime('waktu_pembayaran')->nullable();
            $table->timestamps();
            
            $table->foreign('id_user')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->foreign('id_penjual')->references('id_user')->on('t_user')->onDelete('cascade');
            $table->foreign('id_produk')->references('id_produk')->on('t_produk')->onDelete('cascade');
            $table->index('id_user');
            $table->index('id_penjual');
            $table->index('status_pembayaran');
        });

        Schema::create('t_transaksi', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->unsignedBigInteger('id_pesanan');
            $table->string('jenis_transaksi', 50);
            $table->decimal('nominal', 12, 2);
            $table->string('metode_pembayaran', 50)->nullable();
            $table->enum('status_transaksi', ['pending', 'sukses', 'gagal'])->default('pending');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            
            $table->foreign('id_pesanan')->references('id_pesanan')->on('t_pesanan')->onDelete('cascade');
            $table->index('id_pesanan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_transaksi');
        Schema::dropIfExists('t_pesanan');
        Schema::dropIfExists('t_keranjang');
    }
};
