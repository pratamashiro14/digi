<?php
/**
 * VERIFIKASI.PHP — DIPERTAHANKAN HANYA UNTUK KOMPATIBILITAS LINK LAMA.
 *
 * Sejak revisi keamanan ini, pelanggan tidak lagi diwajibkan verifikasi
 * KTP untuk ikut lelang (lihat catatan trade-off di bidding_helper.php).
 * Jangkar identitasnya sekarang email terverifikasi + nomor HP unik, yang
 * keduanya dilengkapi lewat profil.php. File ini sengaja tidak dihapus
 * supaya tautan lama (bookmark, notifikasi lama, dsb.) tidak menampilkan
 * 404, melainkan diarahkan ke tempat yang benar.
 */
require_once __DIR__ . '/auth.php';

require_user();

redirect_with_alert(
    'Verifikasi KTP untuk pembeli sudah tidak diperlukan. Lengkapi nomor HP di halaman Profil untuk bisa mengikuti lelang.',
    'profil.php',
    'info',
    'Sudah Tidak Diperlukan'
);
