<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('t_design')->insert([
            [
                'id_design' => 1,
                'id_designer' => 1,
                'judul' => 'Desain Logo Profesional untuk Startup',
                'deskripsi' => 'Desain logo modern dan profesional yang cocok untuk startup teknologi. Mencakup berbagai varian penggunaan dan file format lengkap.',
                'kategori' => 'Ilustrasi',
                'harga_awal' => 500000,
                'gambar' => 'IMG_2932.JPG',
                'tanggal_upload' => now(),
                'status' => 'approved',
                'waktu_berakhir' => null,
            ],
            [
                'id_design' => 2,
                'id_designer' => 2,
                'judul' => 'Website E-Commerce Responsive',
                'deskripsi' => 'Desain website e-commerce yang fully responsive dan modern. Dilengkapi dengan mockup dan dokumentasi lengkap untuk development.',
                'kategori' => 'Tipografi',
                'harga_awal' => 3000000,
                'gambar' => 'digidesain.png',
                'tanggal_upload' => now(),
                'status' => 'approved',
                'waktu_berakhir' => null,
            ],
            [
                'id_design' => 3,
                'id_designer' => 3,
                'judul' => 'Poster Promosi Event Musik',
                'deskripsi' => 'Desain poster eye-catching untuk event musik dengan tema modern dan dinamis. File ready to print dalam berbagai ukuran.',
                'kategori' => 'UI/UX',
                'harga_awal' => 250000,
                'gambar' => null,
                'tanggal_upload' => now(),
                'status' => 'approved',
                'waktu_berakhir' => null,
            ],
            [
                'id_design' => 4,
                'id_designer' => 4,
                'judul' => 'UI Kit untuk Mobile App',
                'deskripsi' => 'Komprehensif UI kit untuk aplikasi mobile dengan ratusan komponen yang dapat dikustomisasi. Tersedia dalam Figma dan Adobe XD.',
                'kategori' => 'Animasi',
                'harga_awal' => 2000000,
                'gambar' => null,
                'tanggal_upload' => now(),
                'status' => 'approved',
                'waktu_berakhir' => null,
            ],
            [
                'id_design' => 5,
                'id_designer' => 5,
                'judul' => 'Paket Banner Social Media',
                'deskripsi' => 'Paket lengkap banner dan template untuk berbagai platform social media. Desain minimalis dengan warna-warna trendy.',
                'kategori' => 'Ilustrasi',
                'harga_awal' => 1500000,
                'gambar' => null,
                'tanggal_upload' => now(),
                'status' => 'approved',
                'waktu_berakhir' => null,
            ],
        ]);
    }
}
