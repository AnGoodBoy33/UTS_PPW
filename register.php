<?php
$errors = [];

// Firefox belum mendukung -webkit-text-security, jadi di sana dipakai input password biasa
$pakaiSamarkan = stripos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Firefox') === false;

// KUNCI ENKRIPSI AES-256 (Disisipkan)
$kunci_sistem = "SelaRuangCeritaBersama2026!#$";
$metode_aes   = "AES-256-CBC";
$iv_aes       = substr(hash('sha256', $kunci_sistem), 0, 16);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['mail'] ?? '');
    $password = $_POST['pswd'] ?? '';

    // Validasi per kolom, pesan ditampilkan di bawah kolom yang bermasalah
    if ($nama_lengkap === '') {
        $errors['fullname'] = 'Nama tidak boleh kosong!';
    }

    if ($email === '') {
        $errors['mail'] = 'Email tidak boleh kosong!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['mail'] = 'Format email tidak valid.';
    }

    if ($password === '') {
        $errors['pswd'] = 'Kata sandi tidak boleh kosong!';
    } elseif (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}/', $password)) {
        $errors['pswd'] = 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.';
    }

    if (empty($errors)) {
        // Ambil data database yang sudah ada (termasuk menfess & polling yang ada)
        include 'database.php';

        if (!isset($account) || !is_array($account)) {
            $account = [];
        }
        if (!isset($menfess_data)) { $menfess_data = []; }
        if (!isset($reports_data)) { $reports_data = []; }
        if (!isset($poll_data)) {
            $poll_data = [
                0 => ['id' => 1, 'opsi' => 'OOP (C++)', 'jumlah_vote' => 0],
                1 => ['id' => 2, 'opsi' => 'Database', 'jumlah_vote' => 0],
                2 => ['id' => 3, 'opsi' => 'Pemrograman Web', 'jumlah_vote' => 0],
                3 => ['id' => 4, 'opsi' => 'Lainnya', 'jumlah_vote' => 0],
            ];
        }

        // --- ENKRIPSI DATA FORM SEBELUM DI-CHECK & DI-INPUT (Disisipkan) ---
        $email_aman = openssl_encrypt($email, $metode_aes, $kunci_sistem, 0, $iv_aes);
        $nama_aman  = openssl_encrypt($nama_lengkap, $metode_aes, $kunci_sistem, 0, $iv_aes);

        if (isset($account[$email_aman]) || isset($account[$email])) {
            $errors['mail'] = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            // Tambahkan akun baru dengan data terenkripsi
            $account[$email_aman] = [
                "nama" => $nama_aman,
                "password" => password_hash($password, PASSWORD_DEFAULT)
            ];

            // TULIS ULANG DATABASE DENGAN MENYERTAKAN AKUN + MENFESS YANG ADA
            $kontenBaru = "<?php\n\n";
            $kontenBaru .= "\$account = " . var_export($account, true) . ";\n\n";
            $kontenBaru .= "\$menfess_data = " . var_export($menfess_data, true) . ";\n\n";
            $kontenBaru .= "\$reports_data = " . var_export($reports_data, true) . ";\n\n";
            $kontenBaru .= "\$poll_data = " . var_export($poll_data, true) . ";\n";

            if (file_put_contents('database.php', $kontenBaru, LOCK_EX) !== false) {
                header("Location: login.php?pesan=registrasi_sukses");
                exit();
            } else {
                $errors['umum'] = 'Registrasi gagal. Periksa izin akses file database.php.';
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
    <link rel="icon" type="image/x-icon" href="icon_SELA.ico">
</head>
<body>

    <header class="topbar">
        <div class="brand">
            <a href="index.php" class="logo">Sela.</a>
            <span class="tagline">Cerita kecil, ruang bersama.</span>
        </div>
        <a href="index.php" class="back-link"><span>&larr;</span> Kembali ke awal</a>
    </header>

    <main class="register-container">
        <section class="story-panel">
            <div class="story-top">
                <div class="eyebrow"><span class="eyebrow-line"></span>RUANG UNTUK CERITA</div>
                <h1>Hal sederhana<br>pun layak<br>diceritakan.</h1>
                <p class="story-description">Kadang cuma butuh satu pesan anonim buat bikin suasana kelas jadi lebih cair dan seru.</p>
            </div>
            <div class="story-bottom">
                <span class="quote-label">SEPOTONG CERITA DI SELA</span>
                <blockquote>"UTS kok ngasih projek + ujian tertulis juga 🤭"</blockquote>
                <p class="quote-author">Zaki soleh &middot; Curhat</p>
            </div>
        </section>

        <section class="form-panel">
            <div class="form-heading">
                <span class="form-eyebrow">BERKENALAN DULU, YUK</span>
                <h2>Selamat datang di Sela.</h2>
                <p>Buat akun untuk masuk dan menemukan cerita-cerita kecil di sekitar kita.</p>
            </div>

            <?php if (isset($errors['umum'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['umum'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="register-form" novalidate>
                <div class="form-group">
                    <label for="fullname">Nama</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Nama yang ingin kamu gunakan"
                        value="<?= htmlspecialchars($_POST['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= isset($errors['fullname']) ? 'is-error' : '' ?>" required>
                    <?php if (isset($errors['fullname'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['fullname'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="mail">Email</label>
                    <input type="email" id="mail" name="mail" placeholder="nama@contoh.com"
                        value="<?= htmlspecialchars($_POST['mail'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= isset($errors['mail']) ? 'is-error' : '' ?>" required>
                    <?php if (isset($errors['mail'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['mail'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="pswd">Kata sandi</label>
                    <div class="pw-wrap">
                        <input type="<?= $pakaiSamarkan ? 'text' : 'password' ?>"
                            id="pswd" name="pswd"
                            class="pw-field<?= isset($errors['pswd']) ? ' is-error' : '' ?>"
                            placeholder="Buat kata sandi"
                            autocomplete="new-password" autocapitalize="off" spellcheck="false" required>

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
                    <small>Gunakan minimal 8 karakter, gabungkan huruf besar, huruf kecil, angka, dan simbol.</small>
                </div>

                <button type="submit" class="submit-button">Daftar</button>
            </form>

            <p class="login-prompt">Sudah punya akun? <a href="login.php">Masuk</a></p>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2026 Sela. Ruang cerita kelas.</span>
            <span class="footer-quote">Saling sapa di sela waktu kuliah.</span>
        </div>
    </footer>
</body>
</html>