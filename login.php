<?php
session_start();

if (isset($_SESSION['nama_user']) || isset($_COOKIE['user_ingat'])) {
    if (!isset($_SESSION['nama_user']) && isset($_COOKIE['user_ingat'])) {
        $_SESSION['nama_user'] = $_COOKIE['user_ingat'];
    }
    header("Location: dashboard.php");
    exit();
}
if (($_GET['pesan'] ?? '') === 'registrasi_sukses') {
    echo "<p style='color: green;'>Registrasi Berhasil! Silahkan Login.</p>";
}
if (($_GET['pesan'] ?? '') === 'gagal') {
    echo "<p style='color: red;'>Login Gagal! Email atau Password salah.</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['mail'] ?? '');
    $password = htmlspecialchars($_POST['pswd'] ?? '');

    include 'database.php';

    $dataUser = $account[$email] ?? null;

    if ($dataUser !== null && $dataUser['password'] === $password) {
        $_SESSION['nama_user'] = $dataUser['nama'];
        // time() + (86400 * 30) artinya cookie ini akan disimpan di harddisk browser selama 30 HARI ke depan
         if ($rememberMe === '1') {
            // Simpan cookie selama 30 hari (86400 detik = 1 hari)
            // Tanda "/" artinya cookie ini berlaku di seluruh halaman website
            setcookie("user_ingat", $dataUser['nama'], time() + (86400 * 30), "/");
        }

        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: login.php?pesan=gagal");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login Member</title></head>
<body>
    <h2>Formulir Login</h2>
    <form action="login.php" method="POST">
        <label>Email:</label><br>
        <input type="email" name="mail" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="pswd" required><br><br>

        <input type="checkbox" name="remember" value="1" id="remember">
        <label for="remember">Ingat Saya (Remember Me)</label><br><br>

        <input type="submit" value="Masuk">
    </form>
    <br>
    <a href="register.php">Belum punya akun? Daftar</a>
</body>
</html>
