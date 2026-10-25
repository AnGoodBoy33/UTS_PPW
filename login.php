<?php
session_start();

// Cek status user: kalau udah ada sesi atau cookie, langsung lempar ke dashboard
if (isset($_SESSION['nama_user']) || isset($_COOKIE['user_ingat'])) {
    if (!isset($_SESSION['nama_user']) && isset($_COOKIE['user_ingat'])) {
        $_SESSION['nama_user'] = $_COOKIE['user_ingat'];
    }
    header("Location: dashboard.php");
    exit();
}

// Siapin variabel bawaan buat nampung error dan status
$pesan = $_GET['pesan'] ?? '';
$errors = [];
$loginBerhasil = false;

// Deteksi browser: Firefox belum support -webkit-text-security, jadi disesuaikan
$pakaiSamarkan = stripos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Firefox') === false;

// Mulai proses kalau user nekan tombol masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['mail'] ?? '');
    $password = $_POST['pswd'] ?? '';
    $rememberMe = $_POST['remember'] ?? '';

    // Validasi input email dan password
    if ($email === '') {
        $errors['mail'] = 'Email tidak boleh kosong!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['mail'] = 'Format email tidak valid.';
    }

    if ($password === '') {
        $errors['pswd'] = 'Kata sandi tidak boleh kosong!';
    } elseif (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}/', $password)) {
        $errors['pswd'] = 'Password yang anda masukan salah';
    }

    // Kalau semua input aman, lanjut cek ke database
    if (empty($errors)) {
        include 'database.php';

        // Siapin kunci dan metode buat buka enkripsi datanya
        $kunci_sistem = "SelaRuangCeritaBersama2026!#$";
        $metode_aes   = "AES-256-CBC";
        $iv_aes       = substr(hash('sha256', $kunci_sistem), 0, 16);

        // Ubah email dari form ke bentuk enkripsi buat nyocokin sama yang di database
        $email_aman = openssl_encrypt($email, $metode_aes, $kunci_sistem, 0, $iv_aes);

        $dataUser = $account[$email_aman] ?? $account[$email] ?? null;

        // Cek kecocokan password
        if ($dataUser !== null && password_verify($password, $dataUser['password'])) {

            // Terjemahin nama dari database ke teks biasa buat ditampilin
            $nama_asli = openssl_decrypt($dataUser['nama'], $metode_aes, $kunci_sistem, 0, $iv_aes);
            if ($nama_asli === false) {
                $nama_asli = $dataUser['nama'];
            }

            // Simpan data ke sesi biar user tetep login
            $_SESSION['nama_user'] = $nama_asli;
            $_SESSION['email_user'] = $email;
            $_SESSION['notif_login'] = true;

            // Kalau user centang 'remember me', simpan juga di cookie
            if ($rememberMe === '1') {
                setcookie("user_ingat", $nama_asli, time() + (86400 * 30), "/");
                setcookie("email_ingat", $email, time() + (86400 * 30), "/");
            }

            $loginBerhasil = true;
        } else {
            header("Location: login.php?pesan=gagal");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Sela.</title>
    
    <!-- Kasih jeda dikit sebelum pindah ke dashboard kalau sukses login -->
    <?php if ($loginBerhasil): ?>
        <meta http-equiv="refresh" content="2;url=dashboard.php">
    <?php endif; ?>
    
    <link rel="stylesheet" href="login.css">
    <link rel="icon" type="image/x-icon" href="icon_SELA.ico">
</head>
<body>
    
    <!-- Bagian Atas: Logo dan link kembali -->
    <header class="site-header">
        <div class="header-inner">
            <div class="brand-group">
                <a href="index.php" class="brand-logo">Sela.</a>
                <span class="brand-divider"></span>
                <span class="brand-tagline">Cerita kecil, ruang bersama.</span>
            </div>
            <a href="index.php" class="back-link">&larr; Kembali ke awal</a>
        </div>
    </header>

    <!-- Area Utama Layar -->
    <main class="main-container">
        
        <!-- Sisi Kiri: Sambutan dan kutipan -->
        <section class="story-panel">
            <div class="panel-top">
                <div class="eyebrow-with-line">RUANG UNTUK CERITA</div>
                <h1 class="panel-title">Mengisi jeda<br>dengan cerita.</h1>
                <p class="panel-desc">Tarik napas sejenak dari padatnya jadwal. Bagikan keluh kesah atau sekadar menyapa teman.</p>
            </div>
            <div class="panel-bottom">
                <div class="quote-label">SEPOTONG CERITA DI SELA</div>
                <p class="quote-text">“Kurang bngt waktu uts Basdat 🥲”</p>
                <div class="quote-author">Ahmad Adriansa &middot; Curhat</div>
            </div>
        </section>

        <!-- Sisi Kanan: Area form login -->
        <section class="form-section">
            <div class="form-eyebrow">SENANG BERTEMU LAGI</div>
            <h2 class="form-title">Masuk ke Sela.</h2>
            <p class="form-subtitle">Gunakan email dan kata sandimu.<br>Cerita-cerita kecil menanti di dalam.</p>

            <!-- Tampilan Notifikasi (Sukses, Gagal, atau Habis Daftar) -->
            <?php if ($loginBerhasil): ?>
                <div class="alert-box alert-success">Berhasil login! Mengalihkan ke ruang ceritamu...</div>
            <?php elseif ($pesan === 'registrasi_sukses'): ?>
                <div class="alert-box alert-success">Registrasi Berhasil! Silahkan Login.</div>
            <?php elseif ($pesan === 'gagal'): ?>
                <div class="alert-box alert-error">Login Gagal! Email atau Password salah.</div>
            <?php endif; ?>

            <!-- Form Isi Data Login -->
            <form action="login.php" method="POST" novalidate>
                
                <!-- Input Email -->
                <div class="form-group">
                    <label for="mail">Email</label>
                    <input type="email" id="mail" name="mail" placeholder="nama@contoh.com"
                        value="<?= htmlspecialchars($_POST['mail'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= isset($errors['mail']) ? 'is-error' : '' ?>" required>
                    
                    <?php if (isset($errors['mail'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['mail'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <!-- Input Password (Ada fitur 'lihat password' kalau bukan di Firefox) -->
                <div class="form-group">
                    <label for="pswd">Kata sandi</label>
                    <div class="pw-wrap">
                        <input type="<?= $pakaiSamarkan ? 'text' : 'password' ?>"
                            id="pswd" name="pswd"
                            class="pw-field<?= isset($errors['pswd']) ? ' is-error' : '' ?>"
                            placeholder="Masukkan kata sandi"
                            autocomplete="current-password" autocapitalize="off" spellcheck="false" required>

                        <?php if ($pakaiSamarkan): ?>
                            <input type="checkbox" id="show-pw" class="pw-toggle" aria-label="Tampilkan kata sandi">
                            <label for="show-pw" class="pw-eye" title="Tampilkan kata sandi">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2 12C4.5 7.5 8 5 12 5s7.5 2.5 10 7c-2.5 4.5-6 7-10 7S4.5 16.5 2 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path class="pw-slash" d="M3 3l18 18"/>
                                </svg>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($errors['pswd'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['pswd'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">Masuk</button>
            </form>

            <p class="form-note">Setelah masuk, kamu akan tiba di ruang ceritamu.</p>
            <p class="register-prompt">Belum punya akun? <a href="register.php">Daftar</a></p>
        </section>
    </main>

    <!-- Bagian Paling Bawah -->
    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2026 Sela. Ruang cerita kelas.</span>
            <span class="footer-quote">Saling sapa di sela waktu kuliah.</span>
        </div>
    </footer>
</body>
</html>