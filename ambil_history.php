<?php
include 'admin/koneksi.php';

$id_design = $_GET['id'];

// Mengambil data bidding digabung dengan nama user
// Menggunakan 'id_buyer' sesuai tabel kamu
$query = mysqli_query($koneksi, "
    SELECT t_bidding.*, t_user.nama 
    FROM t_bidding 
    JOIN t_user ON t_bidding.id_buyer = t_user.id_user 
    WHERE id_design = '$id_design' 
    ORDER BY harga_tawaran DESC 
    LIMIT 5
");

if(mysqli_num_rows($query) > 0){
    while($row = mysqli_fetch_assoc($query)){
        ?>
        <div class="bid-item">
            <div class="bid-user">
                <i class="fa fa-user-circle"></i> <?php echo $row['nama']; ?>
            </div>
            <div class="bid-amount" style="color: #4e8eff;">
                Rp <?php echo number_format($row['harga_tawaran'], 0, ',', '.'); ?>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div style="text-align:center; padding:10px; color:#999; font-size:13px;">Belum ada penawaran.<br>Jadilah yang pertama!</div>';
}
?>