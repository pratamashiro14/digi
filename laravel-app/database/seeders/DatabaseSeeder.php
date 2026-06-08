<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Categories
        $kategoris = [
            ['nama_kategori' => 'Logo Design', 'deskripsi' => 'Desain logo profesional untuk brand Anda'],
            ['nama_kategori' => 'Web Design', 'deskripsi' => 'Desain website modern dan responsif'],
            ['nama_kategori' => 'Graphic Design', 'deskripsi' => 'Desain grafis untuk berbagai kebutuhan'],
            ['nama_kategori' => 'UI/UX Design', 'deskripsi' => 'Desain antarmuka pengguna yang intuitif'],
            ['nama_kategori' => 'Banner & Social Media', 'deskripsi' => 'Desain banner dan konten media sosial'],
        ];

        foreach ($kategoris as $kat) {
            Kategori::create($kat);
        }

        // Create Test Users (Regular Users)
        $users = [
            [
                'nama' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'password' => Hash::make('password123'),
                'nohp' => '08123456789',
                'alamat' => 'Jakarta',
                'role' => 'user',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Siti Nurhaliza',
                'email' => 'siti@example.com',
                'password' => Hash::make('password123'),
                'nohp' => '08987654321',
                'alamat' => 'Bandung',
                'role' => 'user',
                'status' => 'aktif',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        // Create Test Designers
        $designers = [
            [
                'nama' => 'Ahmad Designer',
                'email' => 'ahmad.designer@example.com',
                'password' => Hash::make('password123'),
                'nohp' => '08111111111',
                'alamat' => 'Surabaya',
                'role' => 'designer',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Rina Creative',
                'email' => 'rina.creative@example.com',
                'password' => Hash::make('password123'),
                'nohp' => '08222222222',
                'alamat' => 'Yogyakarta',
                'role' => 'designer',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Toko Desain',
                'email' => 'toko.design@example.com',
                'password' => Hash::make('password123'),
                'nohp' => '08333333333',
                'alamat' => 'Medan',
                'role' => 'designer',
                'status' => 'aktif',
            ],
        ];

        foreach ($designers as $designer) {
            User::create($designer);
        }

        // Create Sample Products
        $products = [
            [
                'id_kategori' => 1,
                'id_user' => 3, // Ahmad Designer
                'nama_produk' => 'Desain Logo Profesional untuk Startup',
                'deskripsi' => 'Logo modern dan minimalis untuk brand startup teknologi Anda',
                'harga' => 500000,
                'status' => 'aktif',
            ],
            [
                'id_kategori' => 2,
                'id_user' => 3,
                'nama_produk' => 'Website E-Commerce Responsive',
                'deskripsi' => 'Website toko online yang responsif dan user-friendly',
                'harga' => 3000000,
                'status' => 'aktif',
            ],
            [
                'id_kategori' => 3,
                'id_user' => 4, // Rina Creative
                'nama_produk' => 'Poster Promosi Event',
                'deskripsi' => 'Desain poster menarik untuk promosi event Anda',
                'harga' => 250000,
                'status' => 'aktif',
            ],
            [
                'id_kategori' => 4,
                'id_user' => 4,
                'nama_produk' => 'UI Kit untuk Mobile App',
                'deskripsi' => 'Komprehensif UI Kit dengan 100+ component untuk aplikasi mobile',
                'harga' => 2000000,
                'status' => 'aktif',
            ],
            [
                'id_kategori' => 5,
                'id_user' => 5, // Toko Desain
                'nama_produk' => 'Paket Banner Social Media',
                'deskripsi' => 'Paket 20 design banner untuk semua platform media sosial',
                'harga' => 1500000,
                'status' => 'aktif',
            ],
        ];

        foreach ($products as $product) {
            Produk::create($product);
        }

        // Seed Design
        $this->call(DesignSeeder::class);
    }
}
