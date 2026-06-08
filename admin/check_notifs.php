<?php
// check_notifs.php
include "koneksi.php";

$notifs = [];
$total_count = 0;

// Query Transaksi dan User Baru (diurutkan berdasarkan ID tertinggi)
$query = mysqli_query($koneksi, "
    (SELECT id_user AS id_ref, nama AS event_name, 'user' AS type, id_user AS sort_id 
     FROM t_user ORDER BY id_user DESC LIMIT 3)
    UNION
    (SELECT id_transaksi AS id_ref, CONCAT('Transaksi Berhasil: Rp ', FORMAT(harga_final, 0)) AS event_name, 'transaksi' AS type, id_transaksi AS sort_id 
     FROM t_transaksi WHERE status_pembayaran = 'berhasil' ORDER BY id_transaksi DESC LIMIT 3)
    ORDER BY sort_id DESC
    LIMIT 5
");

if (mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $notifs[] = $row;
    }
    $total_count = count($notifs);
}

// =======================================================
// OUTPUT HTML UNTUK DROPDOWN
// =======================================================
?>

<div class="dropdown-title">
    You have <?= $total_count; ?> new notification
</div>
<div class="notif-scroll scrollbar-outer">
    <div class="notif-center">
        <?php if ($total_count > 0): ?>
            <?php foreach ($notifs as $notif): 
                $icon = ($notif['type'] == 'user') ? 'fa fa-user-plus' : 'fas fa-money-bill-wave';
                $class = ($notif['type'] == 'user') ? 'notif-primary' : 'notif-success';
            ?>
                <a href="#">
                    <div class="notif-icon <?= $class; ?>">
                        <i class="<?= $icon; ?>"></i>
                    </div>
                    <div class="notif-content">
                        <span class="block">
                            <?= htmlspecialchars($notif['event_name']); ?>
                        </span>
                        <span class="time">Just now</span> 
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-3 text-center text-muted">No new activity.</div>
        <?php endif; ?>
    </div>
</div>