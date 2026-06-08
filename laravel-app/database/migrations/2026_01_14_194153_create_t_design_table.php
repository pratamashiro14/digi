<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('t_design', function (Blueprint $table) {
            $table->bigInteger('id_design')->primary();
            $table->bigInteger('id_designer')->nullable();
            $table->string('judul', 255);
            $table->longText('deskripsi')->nullable();
            $table->string('kategori', 100);
            $table->decimal('harga_awal', 15, 2);
            $table->string('gambar', 255)->nullable();
            $table->timestamp('tanggal_upload')->useCurrent();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('waktu_berakhir')->nullable();
            $table->string('file_master', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_design');
    }
};
