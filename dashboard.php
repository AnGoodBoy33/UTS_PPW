<?php
session_start();

// Ambil Nama Lengkap dari session
$namaLengkap = $_SESSION['nama_user'] ?? $_COOKIE['user_ingat'] ?? null;

if ($namaLengkap === null) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Utama</title>
</head>
<body>
    <h1>Selamat Datang, <?php echo $namaLengkap; ?>!</h1>
    <p>Anda berhasil masuk ke dashboard dengan sistem database array multidimensi.</p>
    <br>
    <a href="logout.php">Logout</a>
</body>
</html>
