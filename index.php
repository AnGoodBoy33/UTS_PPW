<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Awal - Sela.</title>
    <!-- Hubungkan ke file CSS baru khusus halaman utama -->
    <link rel="stylesheet" href="index.css">
    <link rel="icon" type="image/x-icon" href="icon_SELA.ico">
</head>
<body>
    
    <!-- Navigasi -->
    <nav class="navbar">
        <div class="nav-left">
            <h1 class="logo">Sela.</h1>
            <span class="tagline">TEMPAT & KOMUNITAS KAMPUS.</span>
        </div>
        <div class="nav-right">
            <a href="index.php" class="nav-link active">Awal</a>
            <a href="login.php" class="nav-link">Masuk</a>
            <a href="register.php" class="btn btn-primary">Daftar</a>
        </div>
    </nav>

    <!-- Bagian Hero -->
    <header class="hero">
        <div class="hero-left">
            <span class="label">— SEBUAH JEDA, SEBUAH CERITA</span>
            <h2>Cerita kelas,<br>solusi bersama.</h2>
            <p class="hero-desc">Tempat untuk berbagi cerita, bertanya, mencari informasi, dan menemukan teman belajar di kelasmu.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">Daftar sekarang</a>
                <a href="login.php" class="btn btn-outline">Masuk</a>
            </div>
        </div>
        
        <div class="hero-right">
            <div class="card-wrapper">
                <?php
                // ===== SLIDESHOW: Tarik menfess terbaru dari database.php (didekripsi seperti di dashboard) =====[cite: 7]
                date_default_timezone_set('Asia/Jakarta');
                include 'database.php';

                // Siapkan kunci buat buka data dari database[cite: 7]
                $kunci_sistem = "SelaRuangCeritaBersama2026!#$";
                $metode_aes   = "AES-256-CBC";
                $iv_aes       = substr(hash('sha256', $kunci_sistem), 0, 16);

                // Fungsi dekripsi bawaan[cite: 7]
                function sela_dekripsi($teks, $metode, $kunci, $iv) {
                    if ($teks === null || $teks === '') return '';
                    return openssl_decrypt($teks, $metode, $kunci, 0, $iv) ?: $teks;
                }

                // Bersihin teks dari tag berbahaya[cite: 7]
                function sela_aman($teks) {
                    return htmlspecialchars(html_entity_decode((string)$teks, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
                }

                // Bikin inisial 2 huruf dari nama[cite: 7]
                function sela_inisial($nama) {
                    $kata  = preg_split('/\s+/', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
                    $hasil = '';
                    foreach (array_slice($kata, 0, 2) as $k) {
                        $hasil .= strtoupper(substr($k, 0, 1));
                    }
                    return $hasil !== '' ? $hasil : '?';
                }

                // Ubah waktu jadi "X menit lalu"[cite: 7]
                function sela_waktu_lalu($timestamp) {
                    $selisih = max(0, time() - (int)$timestamp);
                    if ($selisih < 60)      return 'Baru saja';
                    if ($selisih < 3600)    return floor($selisih / 60) . ' menit lalu';
                    if ($selisih < 86400)   return floor($selisih / 3600) . ' jam lalu';
                    if ($selisih < 2592000) return floor($selisih / 86400) . ' hari lalu';
                    return date('d M Y', (int)$timestamp);
                }

                // Ubah waktu jadi tanggal standar[cite: 7]
                function sela_tanggal($timestamp) {
                    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    return date('j', $timestamp) . ' ' . $bulan[(int)date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
                }

                // Logo kategori: warna dan ikon disesuaikan sama widget "Kategori Populer" di dashboard[cite: 7]
                $kategori_logo = [
                    'Curhat' => [
                        'kelas' => 'kat-curhat',
                        'tone'  => 'tone-curhat',
                        'ikon'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>',
                    ],
                    'InfoKelas' => [
                        'kelas' => 'kat-infokelas',
                        'tone'  => 'tone-infokelas',
                        'ikon'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                    ],
                    'Teman Belajar' => [
                        'kelas' => 'kat-teman',
                        'tone'  => 'tone-teman',
                        'ikon'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                    ],
                    'Apresiasi' => [
                        'kelas' => 'kat-apresiasi',
                        'tone'  => 'tone-apresiasi',
                        'ikon'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
                    ],
                ];

                // Postingan yang sedang dilaporkan (direport) tidak ditampilkan di halaman depan[cite: 7]
                $id_dilaporkan = array_column($reports_data, 'post_id');

                // Cari tiga menfess terbaru yang aman buat dipamerin[cite: 7]
                $kandidat = [];
                foreach ($menfess_data as $post) {
                    if (!in_array($post['id'], $id_dilaporkan, true)) {
                        $kandidat[] = $post;
                    }
                }
                
                // Urutkan dari yang terbaru[cite: 7]
                usort($kandidat, function ($a, $b) {
                    return $b['waktu'] <=> $a['waktu'];
                });
                $kandidat = array_slice($kandidat, 0, 3);
                $waktu_terakhir = !empty($kandidat) ? $kandidat[0]['waktu'] : null;

                $tampil = [];
                foreach ($kandidat as $post) {
                    $logo   = $kategori_logo[$post['kategori']] ?? $kategori_logo['Curhat'];
                    $anonim = !empty($post['is_anonim']);
                    $tampil[] = [
                        'kategori' => $post['kategori'],
                        'kelas'    => $logo['kelas'],
                        'tone'     => $logo['tone'],
                        'ikon'     => $logo['ikon'],
                        'anonim'   => $anonim,
                        'nama'     => $anonim ? '' : sela_dekripsi($post['nama'] ?? '', $metode_aes, $kunci_sistem, $iv_aes),
                        'pesan'    => sela_dekripsi($post['pesan'] ?? '', $metode_aes, $kunci_sistem, $iv_aes),
                        'tanggal'  => sela_tanggal($post['waktu']),
                        'waktu'    => sela_waktu_lalu($post['waktu']),
                    ];
                }

                // Kalau datanya masih kosong dari 3, isi dengan template kosong[cite: 7]
                while (count($tampil) < 3) {
                    $tampil[] = null;
                }
                
                // Tambahkan slide pertama ke paling belakang buat bikin efek perulangan loop mulus (infinite loop)[cite: 7]
                $tampil[] = $tampil[0];
                ?>

                <!-- Label atas slideshow -->
                <div class="slider-head">
                    <span class="live-dot"></span>
                    <span class="slider-head-title">Menfess terbaru</span>
                    
                    <?php if ($waktu_terakhir !== null): ?>
                        <span class="slider-head-time">Diposting <?= sela_waktu_lalu($waktu_terakhir) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Wadah utama slideshow -->
                <div class="slider-viewport">
                    <div class="slider-track">
                        <?php foreach ($tampil as $i => $item): ?>
                            
                            <div class="story-card slide <?= $item === null ? '' : $item['tone'] ?>"<?= $i === 3 ? ' aria-hidden="true"' : '' ?>>
                                
                                <?php if ($item === null): ?>
                                    <!-- Tampilan default kalau belum ada postingan -->
                                    <div class="card-header">
                                        <span class="card-label">Sela</span>
                                    </div>
                                    <p>Belum ada menfess. Jadilah yang pertama bercerita di sini.</p>
                                <?php else: ?>
                                    
                                    <!-- Tampilan isi postingan aslinya -->
                                    <div class="card-header">
                                        <span class="card-label">
                                            <span class="kat-icon <?= $item['kelas'] ?>"><?= $item['ikon'] ?></span>
                                            <?= sela_aman($item['kategori']) ?>
                                        </span>
                                        <span class="card-date"><?= $item['tanggal'] ?></span>
                                    </div>
                                    <p><?= nl2br(sela_aman($item['pesan'])) ?></p>
                                    <hr>
                                    
                                    <!-- Info orang yang ngepost di paling bawah kartu -->
                                    <div class="author-info">
                                        <div class="avatar<?= $item['anonim'] ? ' avatar-anon' : '' ?>">
                                            <?php if ($item['anonim']): ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                            <?php else: ?>
                                                <?= htmlspecialchars(sela_inisial($item['nama']), ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="author-details">
                                            <strong><?= $item['anonim'] ? 'Anonim' : sela_aman($item['nama']) ?></strong>
                                            <span><?= $item['waktu'] ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Indikator titik tiga di bawah slideshow -->
                <div class="slider-dots">
                    <span class="slider-dot dot-1"></span>
                    <span class="slider-dot dot-2"></span>
                    <span class="slider-dot dot-3"></span>
                </div>
                <p class="card-caption"><i>Berbagi cerita kelas kini semudah ini.</i></p>
                
            </div>
        </div>
    </header>

    <!-- Bagian Fitur -->
    <section class="features">
        <div class="features-header">
            <div class="features-title">
                <h2>Isi jedamu dengan cerita kelas.</h2>
                <p>Ruang santai untuk bertanya, bercerita, dan menemukan teman belajar.</p>
            </div>
            <a href="register.php" class="features-link">Mulai dengan daftar &rarr;</a>
        </div>
        <div class="features-grid">
            <div class="feature-item">
                <span class="feature-label">LANGKAH AWAL</span>
                <h4>Buat akunmu</h4>
                <p>Cukup nama dan email. Pintu masuk yang sederhana menuju obrolan kelasmu.</p>
            </div>
            <div class="feature-item">
                <span class="feature-label">AMBIL WAKTU SEJENAK</span>
                <h4>Isi jeda dengan cerita</h4>
                <p>Tarik napas dari padatnya jadwal. Curhat ringan, sapa teman, atau tanyakan materi yang terlewat.</p>
            </div>
            <div class="feature-item">
                <span class="feature-label">TEMUKAN KONEKSI</span>
                <h4>Teman di setiap cerita</h4>
                <p>Dari paniknya deadline tugas sampai keluhan jadwal pagi. Selalu ada yang relate denganmu di sini.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Sela. Ruang cerita kelas.</p>
        <p class="footer-quote"><i>Saling sapa di sela waktu kuliah.</i></p>
    </footer>
</body>
</html>