<?php
// fetch_new_customers.php
include "koneksi.php";

// 1. MENGAMBIL DATA PELANGGAN BARU (NEW CUSTOMERS)
$query_new_customers = mysqli_query($koneksi, "
    SELECT id_user, nama, role 
    FROM t_user
    ORDER BY id_user DESC 
    LIMIT 6
");

if (mysqli_num_rows($query_new_customers) > 0) {
    while ($customer = mysqli_fetch_assoc($query_new_customers)) {
        
        // Ambil huruf pertama dari nama untuk avatar default
        $initial = strtoupper(substr($customer['nama'], 0, 1));
        
        // Output HTML untuk setiap item list
        ?>
        <div class="item-list">
            <div class="avatar">
                <span class="avatar-title rounded-circle border border-white bg-primary">
                    <?= htmlspecialchars($initial); ?>
                </span>
            </div>
            <div class="info-user ms-3">
                <div class="username"><?= htmlspecialchars($customer['nama']); ?></div>
                <div class="status"><?= htmlspecialchars($customer['role']); ?></div>
            </div>
            </div>
        <?php
    }
} else {
    echo "<div class='alert alert-info text-center'>Tidak ada pengguna baru.</div>";
}
?>