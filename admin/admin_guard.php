<?php
/**
 * ============================================================
 *  admin_guard.php — Penjaga akses panel admin
 * ============================================================
 * Sertakan di PALING ATAS setiap aksi admin (hapus/tambah/edit/ban),
 * dari folder admin/tables/:
 *     require_once __DIR__ . '/../admin_guard.php';
 *
 * Hanya admin yang sudah login (punya $_SESSION['admin']) yang boleh lanjut.
 * Selain itu akan diarahkan ke halaman login admin.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin'])) {
    header('Location: ../index.php');
    exit;
}
