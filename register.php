<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['mail'] ?? '');
    $password = $_POST['pswd'] ?? '';

    if (empty($nama_lengkap) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}/', $password)) {
        $error = 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.';
    } else {
        include 'database.php';

        if (!isset($account) || !is_array($account)) {
            $account = [];
        }

        if (isset($account[$email])) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $account[$email] = [
                "nama" => $nama_lengkap,
                "password" => password_hash($password, PASSWORD_DEFAULT)
            ];

            $kontenBaru = "<?php\n\$account = " .
                var_export($account, true) . ";\n";

            if (file_put_contents('database.php', $kontenBaru, LOCK_EX) !== false) {
                header("Location: login.php?pesan=registrasi_sukses");
                exit();
            } else {
                $error = 'Registrasi gagal. Periksa izin akses file database.php.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sela.</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>

    <header class="topbar">
        <div class="brand">
            <a href="login.php" class="logo">Sela.</a>
            <span class="tagline">Cerita kecil, ruang bersama.</span>
        </div>

        <a href="login.php" class="back-link">
            <span>&larr;</span> Kembali ke awal
        </a>
    </header>

    <main class="register-container">

        <section class="story-panel">
            <div class="story-top">
                <div class="eyebrow">
                    <span class="eyebrow-line"></span>
                    RUANG UNTUK CERITA
                </div>

                <h1>Hal sederhana<br>
                    pun layak<br>
                    diceritakan.</h1>

                <p class="story-description">
                    Tentang pagi yang tenang, perjalanan pulang,
                    atau hal kecil yang membuatmu tersenyum.
                </p>
            </div>

            <div class="story-bottom">
                <span class="quote-label">SEPOTONG CERITA DI SELA</span>

                <blockquote>
                    “Ternyata, duduk sebentar tanpa terburu-buru
                    juga bagian dari menjalani hari.”
                </blockquote>

                <p class="quote-author">
                    Nadia Putri &middot; Catatan sore
                </p>
            </div>
        </section>

        <section class="form-panel">

            <div class="form-heading">
                <span class="form-eyebrow">BERKENALAN DULU, YUK</span>
                <h2>Selamat datang di Sela.</h2>
                <p>
                    Buat akun untuk masuk dan menemukan cerita-cerita
                    kecil di sekitar kita.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="register-form">

                <div class="form-group">
                    <label for="fullname">Nama</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        placeholder="Nama yang ingin kamu gunakan"
                        value="<?= htmlspecialchars($_POST['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="mail">Email</label>
                    <input
                        type="email"
                        id="mail"
                        name="mail"
                        placeholder="nama@contoh.com"
                        value="<?= htmlspecialchars($_POST['mail'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="pswd">Kata sandi</label>
                    <input
                        type="password"
                        id="pswd"
                        name="pswd"
                        placeholder="Buat kata sandi"
                        autocomplete="new-password"
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                        title="Minimal 8 karakter, dengan huruf besar, huruf kecil, angka, dan simbol."
                        required
                    >

                    <small>
                        Gunakan minimal 8 karakter, gabungkan huruf besar, huruf kecil, angka, dan simbol.
                    </small>
                </div>

                <button type="submit" class="submit-button">
                    Daftar
                </button>

            </form>

            <p class="login-prompt">
                Sudah punya akun?
                <a href="login.php">Masuk</a>
            </p>

        </section>

    </main>

    <footer class="footer">
        <span>&copy; 2026 Sela. Ruang berbagi cerita singkat.</span>
        <span class="footer-quote">Ada cerita di antara jeda.</span>
    </footer>

</body>
</html>