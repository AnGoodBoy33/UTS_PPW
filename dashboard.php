<?php
session_start();

$namaLengkap = $_SESSION['nama_user'] ?? $_COOKIE['user_ingat'] ?? null;

if ($namaLengkap === null) {
    header("Location: login.php");
    exit();
}

// Cari email user dari session, cookie, atau database.php
$emailUser = $_SESSION['email_user'] ?? $_COOKIE['email_ingat'] ?? null;
if ($emailUser === null && file_exists('database.php')) {
    include 'database.php';
    foreach ($account as $emailKey => $data) {
        if (($data['nama'] ?? '') === $namaLengkap) {
            $emailUser = $emailKey;
            break;
        }
    }
}
$emailUser = $emailUser ?? 'pengguna@contoh.com';

// Ambil nama depan & inisial otomatis dari Nama Lengkap
$pecahNama = preg_split('/\s+/', trim($namaLengkap));
$namaDepan = $pecahNama[0] ?? $namaLengkap;

$inisial = '';
foreach (array_slice($pecahNama, 0, 2) as $kata) {
    $inisial .= strtoupper(substr($kata, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utama - Sela.</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <div class="brand-group">
                <a href="dashboard.php" class="brand-logo">Sela.</a>
                <span class="brand-divider"></span>
                <span class="brand-tagline">Cerita kecil, ruang bersama.</span>
            </div>
            <div class="header-actions">
                <a href="dashboard.php" class="back-link">&larr; Kembali ke awal</a>
                <a href="logout.php" class="btn-logout">Keluar</a>
            </div>
        </div>
    </header>

    <main class="dashboard-wrapper">
        <!-- Bagian Sapaan Atas -->
        <section class="greeting-section">
            <div class="status-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span>Berhasil masuk</span>
            </div>
            <h1 class="greeting-title">Halo, <?php echo htmlspecialchars($namaDepan); ?>.</h1>
            <div class="greeting-meta">
                <p>Selamat datang di Sela. Mari ambil jeda sejenak dari ramainya hari.</p>
                <span class="greeting-date">Minggu, 18 Oktober 2026</span>
            </div>
        </section>

        <!-- Konten Utama 2 Kolom -->
        <div class="content-grid">
            <!-- Sidebar Kiri -->
            <aside class="sidebar">
                <div class="profile-card">
                    <div class="profile-label">PROFILMU</div>
                    <div class="avatar-large"><?php echo htmlspecialchars($inisial); ?></div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($namaLengkap); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($emailUser); ?></p>
                    <hr class="profile-divider">
                    <h3 class="bio-heading">Tentang saya</h3>
                    <p class="bio-text">Menemukan cerita di sela hari. Penyuka jalan kaki dan sore yang tenang.</p>
                </div>

                <div class="sidebar-note">
                    <h3 class="sidebar-note-title">Tidak harus luar biasa.</h3>
                    <p class="sidebar-note-text">Kadang, cerita yang paling dekat justru datang dari hal yang kita temui setiap hari.</p>
                </div>
            </aside>

            <!-- Kolom Cerita Kanan -->
            <section class="stories-section">
                <div class="stories-header">
                    <h2 class="stories-title">Cerita di sela hari</h2>
                    <p class="stories-subtitle">Beberapa catatan untuk menemani jedamu.</p>
                </div>

                <!-- Kartu Cerita Utama -->
                <article class="story-card featured">
                    <div>
                        <div class="story-meta">
                            <span class="story-category">CATATAN SORE</span>
                            <span class="story-date">18 Okt 2026</span>
                        </div>
                        <h3 class="story-card-title">Pelan-pelan, sampai juga.</h3>
                        <p class="story-excerpt">Sore ini aku memilih jalan kaki pulang. Melewati toko roti, menyapa kucing di ujung gang, lalu berhenti sebentar melihat langit. Tidak ada yang istimewa. Tapi rasanya cukup.</p>
                    </div>
                    <div class="story-author">
                        <span class="avatar-small"><?php echo htmlspecialchars($inisial); ?></span>
                        <span class="author-name"><?php echo htmlspecialchars($namaLengkap); ?></span>
                    </div>
                </article>

                <!-- 2 Kartu Cerita Bawah -->
                <div class="stories-subgrid">
                    <article class="story-card">
                        <div>
                            <div class="story-meta">
                                <span class="story-category">JEDA PAGI</span>
                                <span class="story-date">17 Okt 2026</span>
                            </div>
                            <h3 class="story-card-title">Sarapan tanpa terburu-buru</h3>
                            <p class="story-excerpt">Biasanya roti habis dalam perjalanan. Hari ini aku duduk di meja makan, membuka jendela, dan menikmati sarapan selagi hangat. Pagi terasa sedikit lebih panjang.</p>
                        </div>
                        <div class="story-author">
                            <span class="avatar-small">RA</span>
                            <span class="author-name">Raka Aditya</span>
                        </div>
                    </article>

                    <article class="story-card">
                        <div>
                            <div class="story-meta">
                                <span class="story-category">HAL-HAL KECIL</span>
                                <span class="story-date">16 Okt 2026</span>
                            </div>
                            <h3 class="story-card-title">Hujan dan halaman terakhir</h3>
                            <p class="story-excerpt">Hujan turun tepat saat aku membaca halaman terakhir. Buku kututup, teh sudah dingin. Aku tetap di sana sebentar, mendengarkan suara air dari teras.</p>
                        </div>
                        <div class="story-author">
                            <span class="avatar-small">AL</span>
                            <span class="author-name">Ayu Lestari</span>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2026 Sela. Ruang berbagi cerita singkat.</span>
            <span class="footer-quote">Ada cerita di antara jeda.</span>
        </div>
    </footer>
</body>
</html>