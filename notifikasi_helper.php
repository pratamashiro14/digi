<?php
/**
 * ============================================================
 *  NOTIFIKASI_HELPER.PHP — Notifikasi in-app (fitur premium)
 * ============================================================
 * Tabel t_notifikasi dibuat otomatis di admin/koneksi.php.
 * Dipakai untuk "Notifikasi Custom": memberi tahu pembeli premium
 * saat desainer favoritnya mengunggah karya baru, dll.
 */

/** Simpan satu notifikasi untuk seorang penerima. Aman lewat prepared statement. */
function kirim_notifikasi($koneksi, $id_user, $pesan, $link = null, $tipe = 'info') {
    $id_user = (int) $id_user;
    if (!$id_user || $pesan === '') return false;
    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO t_notifikasi (id_user, tipe_notifikasi, pesan, url, created_at) VALUES (?, ?, ?, ?, NOW())");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'isss', $id_user, $tipe, $pesan, $link);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * Kirim notifikasi ke SEMUA pembeli PREMIUM yang memfavoritkan desainer ini.
 * Dipakai saat desainer mengunggah karya baru.
 * Premium aktif = t_user.status_member='premium' OR t_user.premium=1
 *                 OR ada langganan t_premium aktif & belum kedaluwarsa.
 */
function notif_karya_baru_ke_favorit($koneksi, $id_designer, $nama_designer, $id_design, $judul) {
    $id_designer = (int) $id_designer;
    $id_design   = (int) $id_design;
    if (!$id_designer || !$id_design) return 0;

    $sql = "SELECT f.id_buyer
            FROM t_favorit_desainer f
            JOIN t_user u ON u.id_user = f.id_buyer
            LEFT JOIN t_premium p
                   ON p.id_user = u.id_user
                  AND p.status = 'aktif'
                  AND p.tanggal_berakhir >= CURDATE()
            WHERE f.id_designer = $id_designer
              AND (u.status_member = 'premium' OR u.premium = 1 OR p.id_premium IS NOT NULL)";
    $res = mysqli_query($koneksi, $sql);
    if (!$res) return 0;

    $pesan = 'Desainer favoritmu ' . $nama_designer . ' baru saja mengunggah karya baru: ' . $judul;
    $link  = 'product-detail.php?id=' . $id_design;

    $terkirim = 0;
    while ($r = mysqli_fetch_assoc($res)) {
        if (kirim_notifikasi($koneksi, $r['id_buyer'], $pesan, $link, 'karya_baru')) {
            $terkirim++;
        }
    }
    return $terkirim;
}

/** Jumlah notifikasi belum dibaca milik seorang user. */
function notif_unread_count($koneksi, $id_user) {
    $id_user = (int) $id_user;
    if (!$id_user) return 0;
    $r = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM t_notifikasi WHERE id_user=$id_user AND dibaca=0");
    return $r ? (int) (mysqli_fetch_assoc($r)['c'] ?? 0) : 0;
}

/** Daftar notifikasi terbaru (default 8) milik seorang user. */
function notif_recent($koneksi, $id_user, $limit = 8) {
    $id_user = (int) $id_user;
    $limit   = (int) $limit;
    $out = [];
    if (!$id_user) return $out;
    $r = mysqli_query($koneksi,
        "SELECT * FROM t_notifikasi WHERE id_user=$id_user ORDER BY id_notifikasi DESC LIMIT $limit");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) $out[] = $row;
    }
    return $out;
}
