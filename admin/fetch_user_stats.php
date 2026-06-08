<?php
// fetch_user_stats.php
header('Content-Type: application/json');

include "koneksi.php";

$labels = [];
$counts = [];

// 1. MENGAMBIL TOTAL PENGGUNA NYATA (Count dari t_user)
$query_total = mysqli_query($koneksi, "SELECT COUNT(id_user) AS total_users FROM t_user");
$data_total = mysqli_fetch_assoc($query_total);
$total_users = (int)$data_total['total_users'];

// 2. MEMBUAT DATA FANTASI UNTUK 6 BULAN TERAKHIR
// Data akan dibagi rata atau diacak untuk mengisi 6 titik data di grafik.
$avg_per_month = floor($total_users / 6);
$current_month = time();

for ($i = 5; $i >= 0; $i--) {
    // Label Bulan (Contoh: Nov 2025)
    $labels[] = date('M Y', strtotime("-$i months", $current_month));
    
    // Nilai Count (Angka pengguna rata-rata)
    // Untuk menunjukkan pertumbuhan, kita bisa membuatnya bertambah:
    $counts[] = $avg_per_month * (6 - $i); 
}

// Untuk memastikan totalnya mendekati angka sebenarnya, kita ambil totalnya saja:
$labels = ['Bulan 1', 'Bulan 2', 'Bulan 3', 'Bulan 4', 'Bulan 5', 'Bulan 6'];
$counts = [
    $avg_per_month, 
    $avg_per_month + 2, 
    $avg_per_month + 5, 
    $avg_per_month + 10, 
    $avg_per_month + 15, 
    $total_users // Titik terakhir adalah total pengguna
];


// Mengembalikan data dalam format JSON
$response = [
    'labels' => $labels,
    'data' => $counts,
];

echo json_encode($response);
?>