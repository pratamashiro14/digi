<?php
/**
 * ============================================================
 *  WANPRESTASI_HELPER.PHP — Strike, suspend bertingkat & runner-up
 * ============================================================
 * Kompensasi atas dihapusnya wajib-KTP pelanggan (lihat bidding_helper.php):
 * kalau pemenang lelang tidak menyelesaikan pembayaran sampai tenggat, dia
 * kena STRIKE. Akumulasi strike di KARYA BERBEDA men-suspend akun secara
 * bertingkat, dan penawar peringkat berikutnya otomatis dipromosikan jadi
 * pemenang baru (dengan tenggat bayar baru) sambil diberi tahu bahwa
 * pemenang utama dianggap mengundurkan diri.
 *
 * TANPA CRON: semua di bawah ini dievaluasi LAZY (baru diproses saat ada
 * pengunjung membuka salah satu halaman yang memanggil
 * jalankan_pemeliharaan_lelang()), meniru pola expire_pending_kedaluwarsa()
 * di pembayaran_helper.php. Idempotensi dijamin oleh constraint database
 * (UNIQUE (id_user, id_design) di t_wanprestasi + INSERT IGNORE), bukan
 * oleh flag aplikasi — sehingga aman dipanggil berkali-kali secara
 * bersamaan oleh pengunjung berbeda tanpa pernah dobel strike/notifikasi.
 */
require_once __DIR__ . '/bidding_helper.php';
require_once __DIR__ . '/pembayaran_helper.php';
require_once __DIR__ . '/notifikasi_helper.php';

/** Tenggat pemenang menyelesaikan pembayaran, dihitung sejak lelang berakhir
 *  (atau sejak dipromosikan jadi pemenang baru — lihat proses_wanprestasi()). */
const BATAS_BAYAR_PEMENANG_JAM = 24;
/** Maksimal karya diproses per pemanggilan sweep — membatasi beban per halaman. */
const SWEEP_LIMIT = 20;
/** Jeda antar-sweep per sesi pengunjung (detik) untuk mode tidak dipaksa. */
const SWEEP_THROTTLE_DETIK = 60;

/**
 * Ekspresi SQL tenggat bayar pemenang sebuah baris t_design. Sama pola
 * dengan batas_pembayaran_sql() di pembayaran_helper.php: pakai kolom bila
 * terisi, kalau NULL (lelang belum pernah punya pemenang dipromosikan)
 * hitung dari waktu_berakhir + jam limit.
 */
function batas_bayar_pemenang_sql($alias = 'd') {
    $p = $alias === '' ? '' : $alias . '.';
    $jam = (int) BATAS_BAYAR_PEMENANG_JAM;
    return "COALESCE({$p}batas_bayar_pemenang, {$p}waktu_berakhir + INTERVAL {$jam} HOUR)";
}

/** Jumlah strike AKTIF (belum dimaafkan admin) milik seorang user. */
function hitung_strike($koneksi, $id_user) {
    $id_user = (int) $id_user;
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS n FROM t_wanprestasi WHERE id_user = ? AND dimaafkan = 0");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (int) ($row['n'] ?? 0);
}

/**
 * Durasi suspend (hari) berdasarkan TOTAL strike setelah pelanggaran ini.
 * Strike ke-1 = peringatan saja (akun tetap aktif), makin sering makin lama.
 */
function durasi_suspend_hari($jumlah_strike) {
    if ($jumlah_strike >= 4) return 90;
    if ($jumlah_strike === 3) return 30;
    if ($jumlah_strike === 2) return 14;
    return 0;
}

/**
 * Terapkan sanksi setelah satu strike baru tercatat: peringatan (strike 1)
 * atau suspend berjangka (strike 2+), dengan notifikasi yang menjelaskan
 * alasannya. Durasi suspend BERTAMBAH dari yang lebih akhir antara sekarang
 * atau suspend yang sedang berjalan (bukan menimpa/memendekkan hukuman lama).
 */
function terapkan_sanksi_strike($koneksi, $id_user) {
    $id_user = (int) $id_user;
    $n = hitung_strike($koneksi, $id_user);
    $hari = durasi_suspend_hari($n);

    if ($hari <= 0) {
        kirim_notifikasi(
            $koneksi, $id_user,
            'Ini adalah pelanggaran pertamamu karena tidak menyelesaikan pembayaran lelang yang dimenangkan. Pelanggaran berikutnya di karya lain dapat membekukan akunmu.',
            'profil.php', 'peringatan'
        );
        return ['strike' => $n, 'hari' => 0];
    }

    $stmt = mysqli_prepare($koneksi,
        "UPDATE t_user
            SET status = 'nonaktif',
                suspend_sampai = GREATEST(COALESCE(suspend_sampai, NOW()), NOW()) + INTERVAL ? DAY
          WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $hari, $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    kirim_notifikasi(
        $koneksi, $id_user,
        "Akunmu dibekukan selama {$hari} hari karena tidak menyelesaikan pembayaran lelang (pelanggaran ke-{$n}).",
        'profil.php', 'suspend'
    );
    return ['strike' => $n, 'hari' => $hari];
}

/**
 * Kirim notifikasi "kamu menang, segera bayar" untuk lelang yang BARU SAJA
 * berakhir secara ALAMI (bukan dihentikan manual oleh desainer — jalur itu
 * sudah mengirim notifikasi & menandai notif_menang_terkirim sendiri, lihat
 * unggahan.php). Wajib dipanggil SEBELUM proses_wanprestasi(): menjatuhkan
 * strike ke orang yang tidak pernah diberi tahu adalah cacat fatal.
 */
function notif_pemenang_lelang_berakhir($koneksi) {
    $res = mysqli_query($koneksi,
        "SELECT id_design, judul
           FROM t_design
          WHERE waktu_berakhir IS NOT NULL
            AND waktu_berakhir <= NOW()
            AND notif_menang_terkirim = 0
            AND status <> 'sold'
          LIMIT " . (int) SWEEP_LIMIT);

    while ($d = mysqli_fetch_assoc($res)) {
        $id_design = (int) $d['id_design'];
        $leader = bid_leader_id($koneksi, $id_design);

        if ($leader) {
            kirim_notifikasi(
                $koneksi, $leader,
                'Selamat! Kamu memenangkan lelang "' . $d['judul'] . '". Segera selesaikan pembayaran dalam ' . BATAS_BAYAR_PEMENANG_JAM . ' jam.',
                'riwayat_bidding.php', 'menang_lelang'
            );
        }

        // Ditandai terkirim WALAU tidak ada leader (tidak ada yang bid sama
        // sekali) — supaya baris ini tidak diproses ulang selamanya.
        $stmt = mysqli_prepare($koneksi, "UPDATE t_design SET notif_menang_terkirim = 1 WHERE id_design = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id_design);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Sweep utama: gugurkan pemenang yang lewat tenggat tanpa membayar, catat
 * strike, lalu promosikan penawar berikutnya (atau beri tahu desainer bila
 * tidak ada penawar tersisa). Maksimal SATU pemenang digugurkan per karya
 * per pemanggilan — setelah promosi, tenggat karya itu jadi masa depan
 * sehingga otomatis keluar dari daftar kandidat berikutnya (bounded, tidak
 * ada risiko loop tak berujung).
 */
function proses_wanprestasi($koneksi) {
    $batas = batas_bayar_pemenang_sql('d');
    $res = mysqli_query($koneksi,
        "SELECT d.id_design, d.judul, d.id_designer
           FROM t_design d
          WHERE d.waktu_berakhir IS NOT NULL
            AND {$batas} < NOW()
            AND d.status <> 'sold'
            AND EXISTS (SELECT 1 FROM t_bidding b WHERE b.id_design = d.id_design AND b.status_bid <> 'ditolak')
            AND NOT EXISTS (
                  SELECT 1 FROM t_transaksi t
                   WHERE t.id_design = d.id_design AND t.status_pembayaran = 'berhasil'
                )
          LIMIT " . (int) SWEEP_LIMIT);

    $kandidat = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $kandidat[] = $row;
    }

    foreach ($kandidat as $d) {
        $id_design = (int) $d['id_design'];
        $judul = $d['judul'];
        $leader = bid_leader_id($koneksi, $id_design);

        if (!$leader) {
            // Semua penawar sudah gugur sebelumnya (mis. sweep sebelumnya
            // menggugurkan satu-satunya penawar) — beri tahu desainer sekali.
            kirim_notifikasi(
                $koneksi, (int) $d['id_designer'],
                'Lelang "' . $judul . '" berakhir tanpa pemenang: semua penawar tidak menyelesaikan pembayaran. Kamu bisa menayangkan ulang karya ini.',
                'unggahan.php', 'lelang_gagal'
            );
            continue;
        }

        // KELONGGARAN: pemenang sudah menekan Bayar & transaksi pending-nya
        // masih hidup (belum lewat tenggat 60 menitnya sendiri) — beri
        // kesempatan menyelesaikan, jangan digugurkan saat sedang mencoba bayar.
        if (pending_aktif_untuk_design($koneksi, $leader, $id_design)) {
            continue;
        }

        // Ambil bid tertinggi milik leader di karya ini untuk dicatat di jejak wanprestasi.
        $stmt = mysqli_prepare($koneksi,
            "SELECT id_bid, harga_tawaran FROM t_bidding
              WHERE id_design = ? AND id_buyer = ? AND status_bid <> 'ditolak'
              ORDER BY harga_tawaran DESC, id_bid ASC LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ii', $id_design, $leader);
        mysqli_stmt_execute($stmt);
        $bid_leader = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        // -- GUGURKAN --
        $id_bid_leader = $bid_leader['id_bid'] ?? null;
        $harga_leader = $bid_leader['harga_tawaran'] ?? null;
        $stmt = mysqli_prepare($koneksi,
            "INSERT IGNORE INTO t_wanprestasi (id_user, id_design, id_bid, harga_tawaran, batas_bayar, alasan)
             VALUES (?, ?, ?, ?, NOW(), 'Tidak menyelesaikan pembayaran sampai tenggat')");
        mysqli_stmt_bind_param($stmt, 'iiid', $leader, $id_design, $id_bid_leader, $harga_leader);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($koneksi,
            "UPDATE t_bidding SET status_bid = 'ditolak' WHERE id_design = ? AND id_buyer = ? AND status_bid <> 'ditolak'");
        mysqli_stmt_bind_param($stmt, 'ii', $id_design, $leader);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        kirim_notifikasi(
            $koneksi, $leader,
            'Kamu tidak menyelesaikan pembayaran untuk karya "' . $judul . '" sampai batas waktu, sehingga dianggap mengundurkan diri dan menerima 1 pelanggaran.',
            'profil.php', 'wanprestasi'
        );
        terapkan_sanksi_strike($koneksi, $leader);

        // -- PROMOSI RUNNER-UP -- ('ditolak' di atas otomatis tersaring oleh
        // bid_leader_id(), jadi pemanggilan kedua ini melompat ke peringkat berikutnya)
        $baru = bid_leader_id($koneksi, $id_design);
        if ($baru) {
            $jam = (int) BATAS_BAYAR_PEMENANG_JAM;
            $stmt = mysqli_prepare($koneksi,
                "UPDATE t_design SET batas_bayar_pemenang = NOW() + INTERVAL {$jam} HOUR, notif_menang_terkirim = 1 WHERE id_design = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id_design);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            kirim_notifikasi(
                $koneksi, $baru,
                'Pemenang sebelumnya dianggap mengundurkan diri karena tidak menyelesaikan pembayaran. Kamu kini menjadi pemenang lelang "' . $judul . '". Selesaikan pembayaran dalam ' . BATAS_BAYAR_PEMENANG_JAM . ' jam.',
                'riwayat_bidding.php', 'promosi_pemenang'
            );
            kirim_notifikasi(
                $koneksi, (int) $d['id_designer'],
                'Pemenang lelang "' . $judul . '" tidak menyelesaikan pembayaran. Penawar peringkat berikutnya telah dipromosikan menjadi pemenang baru.',
                'unggahan.php', 'info'
            );
        } else {
            kirim_notifikasi(
                $koneksi, (int) $d['id_designer'],
                'Lelang "' . $judul . '" berakhir tanpa pemenang: semua penawar tidak menyelesaikan pembayaran. Kamu bisa menayangkan ulang karya ini.',
                'unggahan.php', 'lelang_gagal'
            );
        }
    }
}

/** Pulihkan akun-akun yang masa suspend berjangkanya sudah lewat (global, semua user). */
function lepas_suspend_kedaluwarsa($koneksi) {
    mysqli_query($koneksi,
        "UPDATE t_user SET status = 'aktif', suspend_sampai = NULL
          WHERE status = 'nonaktif' AND suspend_sampai IS NOT NULL AND suspend_sampai <= NOW()");
}

/**
 * Satu pintu pemeliharaan lazy (tanpa cron). $paksa=true dipakai di
 * halaman yang menyangkut uang/status (shoping-cart, proses_bayar,
 * riwayat*) supaya SELALU akurat; di halaman lain di-throttle per sesi
 * biar tidak membebani setiap page load.
 */
function jalankan_pemeliharaan_lelang($koneksi, $paksa = false) {
    if (!$paksa) {
        $terakhir = $_SESSION['_maint_last'] ?? 0;
        if (time() - $terakhir < SWEEP_THROTTLE_DETIK) {
            return;
        }
    }
    $_SESSION['_maint_last'] = time();

    lepas_suspend_kedaluwarsa($koneksi);
    expire_pending_kedaluwarsa($koneksi);
    notif_pemenang_lelang_berakhir($koneksi);
    proses_wanprestasi($koneksi);

    if (function_exists('otp_bersihkan')) {
        otp_bersihkan($koneksi);
    }
}
