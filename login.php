<?php
session_start();

if (isset($_SESSION['nama_user']) || isset($_COOKIE['user_ingat'])) {
    if (!isset($_SESSION['nama_user']) && isset($_COOKIE['user_ingat'])) {
        $_SESSION['nama_user'] = $_COOKIE['user_ingat'];
    }
    header("Location: dashboard.php");
    exit();
}

$pesan = $_GET['pesan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars(trim($_POST['mail'] ?? ''));
    $password = $_POST['pswd'] ?? '';
    $rememberMe = $_POST['remember'] ?? '';

    include 'database.php';

    $dataUser = $account[$email] ?? null;

    if ($dataUser !== null && password_verify($password, $dataUser['password'])) {
        $_SESSION['nama_user'] = $dataUser['nama'];
        $_SESSION['email_user'] = $email;

        if ($rememberMe === '1') {
            setcookie("user_ingat", $dataUser['nama'], time() + (86400 * 30), "/");
            setcookie("email_ingat", $email, time() + (86400 * 30), "/");
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Sela.</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <div class="brand-group">
                <a href="login.php" class="brand-logo">Sela.</a>
                <span class="brand-divider"></span>
                <span class="brand-tagline">Cerita kecil, ruang bersama.</span>
            </div>
            <a href="login.php" class="back-link">&larr; Kembali ke awal</a>
        </div>
    </header>

    <main class="main-container">
        <!-- Panel Kiri -->
        <section class="story-panel">
            <div class="panel-top">
                <div class="eyebrow-with-line">RUANG UNTUK CERITA</div>
                <h1 class="panel-title">Selalu ada ruang<br>untuk kembali.</h1>
                <p class="panel-desc">Ambil jeda sebentar. Temukan kembali cerita-cerita kecil yang terasa dekat.</p>
            </div>
            <div class="panel-bottom">
                <div class="quote-label">SEPOTONG CERITA DI SELA</div>
                <p class="quote-text">“Ternyata, duduk sebentar tanpa terburu-buru juga bagian dari menjalani hari.”</p>
                <div class="quote-author">Nadia Putri &middot; Catatan sore</div>
            </div>
        </section>

        <!-- Form Login Kanan -->
        <section class="form-section">
            <div class="form-eyebrow">SENANG BERTEMU LAGI</div>
            <h2 class="form-title">Masuk ke Sela.</h2>
            <p class="form-subtitle">Gunakan email dan kata sandimu.<br>Cerita-cerita kecil menanti di dalam.</p>

            <?php if ($pesan === 'registrasi_sukses'): ?>
                <div class="alert-box alert-success">Registrasi Berhasil! Silahkan Login.</div>
            <?php elseif ($pesan === 'gagal'): ?>
                <div class="alert-box alert-error">Login Gagal! Email atau Password salah.</div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="mail">Email</label>
                    <input type="email" id="mail" name="mail" placeholder="nama@contoh.com" required>
                </div>

                <div class="form-group">
                    <label for="pswd">Kata sandi</label>
                    <input type="password" id="pswd" name="pswd" placeholder="Masukkan kata sandi" required>
                </div>

                <button type="submit" class="btn-submit">Masuk</button>
            </form>

            <p class="form-note">Setelah masuk, kamu akan tiba di ruang ceritamu.</p>
            <p class="register-prompt">Belum punya akun? <a href="register.php">Daftar</a></p>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2026 Sela. Ruang berbagi cerita singkat.</span>
            <span class="footer-quote">Ada cerita di antara jeda.</span>
        </div>
    </footer>
</body>
</html>