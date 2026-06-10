<?php 
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";

session_start();
if(empty($_SESSION['admin'])){
  header('location:../logout.php');
  exit;
}

// Check if id_chat parameter exists
if(!isset($_GET['id']) || empty($_GET['id'])){
  header('location:datachat.php');
  exit;
}

$id_chat = mysqli_real_escape_string($koneksi, $_GET['id']);

// Delete the chat message
$hapus = mysqli_query($koneksi, "DELETE FROM t_chat WHERE id_chat = '$id_chat'");

if($hapus){
  sweetalert_redirect('Pesan berhasil dihapus.', 'datachat.php', 'success', 'Berhasil Dihapus!');
} else {
  sweetalert_redirect('Pesan gagal dihapus.', 'datachat.php', 'error', 'Gagal!');
}
?>
