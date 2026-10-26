<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Proteksi Halaman (Pastikan user udah login & cek status admin)
if (!isset($_SESSION['nama_user'])) {
    header("Location: login.php");
    exit;
}

$current_user = $_SESSION['nama_user'];
$user_inisial = strtoupper(substr($current_user, 0, 1));
$is_admin = ($current_user === 'Nabil Ramadhan Pradhita' || $current_user === 'Nabil Ramadhan' || strtolower($current_user) === 'admin');

include 'database.php';

$kunci_sistem = "SelaRuangCeritaBersama2026!#$";
$metode_aes   = "AES-256-CBC";
$iv_aes       = substr(hash('sha256', $kunci_sistem), 0, 16);

if (isset($menfess_data) && is_array($menfess_data)) {
    foreach ($menfess_data as $key => $post) {
        if (isset($post['pesan'])) {
            $menfess_data[$key]['pesan'] = openssl_decrypt($post['pesan'], $metode_aes, $kunci_sistem, 0, $iv_aes) ?: $post['pesan'];
        }
        if (isset($post['nama'])) {
            $menfess_data[$key]['nama'] = openssl_decrypt($post['nama'], $metode_aes, $kunci_sistem, 0, $iv_aes) ?: $post['nama'];
        }
        if (isset($post['komentar']) && is_array($post['komentar'])) {
            foreach ($post['komentar'] as $k_key => $kom) {
                if (isset($kom['pesan'])) {
                    $menfess_data[$key]['komentar'][$k_key]['pesan'] = openssl_decrypt($kom['pesan'], $metode_aes, $kunci_sistem, 0, $iv_aes) ?: $kom['pesan'];
                }
                if (isset($kom['nama'])) {
                    $menfess_data[$key]['komentar'][$k_key]['nama'] = openssl_decrypt($kom['nama'], $metode_aes, $kunci_sistem, 0, $iv_aes) ?: $kom['nama'];
                }
            }
        }
    }
}

function time_ago($timestamp) {
    $selisih = max(0, time() - (int)$timestamp);

    if ($selisih < 60) return 'Baru saja';
    if ($selisih < 3600) return floor($selisih / 60) . ' menit lalu';
    if ($selisih < 86400) return floor($selisih / 3600) . ' jam lalu';
    if ($selisih < 2592000) return floor($selisih / 86400) . ' hari lalu';

    return date('d M Y', (int)$timestamp);
}

$kategori_aktif = $_GET['kategori'] ?? 'Semua';
$edit_post_id = $_GET['edit_post'] ?? '';
$edit_kom_id = $_GET['edit_kom'] ?? '';
$show_reply = $_GET['reply_to'] ?? '';

$filtered_menfess = array_filter($menfess_data, function ($post) use ($kategori_aktif) {
    $sesuai_kategori = $kategori_aktif === 'Semua' || ($post['kategori'] ?? '') === $kategori_aktif;

    return $sesuai_kategori;
});

$data_berubah = false;

// Perhitungan persentase tiap kategori buat widget statistik
$total_menfess = count($menfess_data);
$kategori_counts = [
    'Curhat' => 0,
    'InfoKelas' => 0,
    'Teman Belajar' => 0,
    'Apresiasi' => 0
];

foreach ($menfess_data as $post) {
    $kat = $post['kategori'] ?? '';
    if (isset($kategori_counts[$kat])) {
        $kategori_counts[$kat]++;
    }
}

$kategori_persen = [];
foreach ($kategori_counts as $kat => $count) {
    $kategori_persen[$kat] = ($total_menfess > 0) ? round(($count / $total_menfess) * 100) : 0;
}
arsort($kategori_persen);

// Sentralisasi Logika Post (Proses semua form dari user)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Kirim Menfess 
    if ($action === 'kirim_menfess') {
        $pesan = trim($_POST['pesan'] ?? '');
        $kategori = $_POST['kategori'] ?? 'Semua';
        $is_anonim = isset($_POST['anonim']) ? 1 : 0;
        $error_menfess = '';
        
        if (empty($pesan)) {
            $error_menfess = 'Pesan tidak boleh kosong!';
        } elseif (!in_array($kategori, ['Curhat', 'InfoKelas', 'Teman Belajar', 'Apresiasi'], true)) {
            $error_menfess = 'Silakan pilih kategori terlebih dahulu!';
        }

        if ($error_menfess === '' && !empty($pesan) && $kategori !== 'Semua') {
            $post_baru = [
                'id' => uniqid('post_'),
                'pesan' => htmlspecialchars($pesan),
                'kategori' => htmlspecialchars($kategori),
                'is_anonim' => $is_anonim,
                'waktu' => time(), 
                'likes' => 0,
                'liked_by' => [],
                'komentar' => []
            ];
            
            if (!$is_anonim) {
                $post_baru['nama'] = htmlspecialchars($current_user);
            }
            
            array_unshift($menfess_data, $post_baru);
            $data_berubah = true;
        }
    }
    
    // Edit Menfess Utama 
    elseif ($action === 'simpan_edit_post') {
        $post_id = $_POST['post_id'] ?? '';
        $pesan_baru = trim($_POST['pesan_edit'] ?? '');

        if (!empty($pesan_baru)) {
            foreach ($menfess_data as $key => $post) {
                if ((string)$post['id'] === (string)$post_id) {
                    $is_owner = isset($post['nama']) && $post['nama'] === $current_user;
                    if ($is_owner) {
                        $menfess_data[$key]['pesan'] = htmlspecialchars($pesan_baru);
                        $data_berubah = true;
                    }
                    break;
                }
            }
        }
    }

    // Hapus Menfess Utama 
    elseif ($action === 'hapus_post') {
        $post_id = $_POST['post_id'] ?? '';
        foreach ($menfess_data as $key => $post) {
            if ((string)$post['id'] === (string)$post_id) {
                $is_owner = isset($post['nama']) && $post['nama'] === $current_user;
                if ($is_owner || $is_admin) {
                    unset($menfess_data[$key]);
                    $menfess_data = array_values($menfess_data);
                    $data_berubah = true;
                }
                break;
            }
        }
    }
    
    // Laporkan Menfess Utama 
    elseif ($action === 'laporkan_post') {
        $post_id = $_POST['post_id'] ?? '';
        $reports_data[] = [
            'pelapor' => $current_user,
            'post_id' => $post_id,
            'tipe' => 'post_utama',
            'waktu_lapor' => time()
        ];
        $data_berubah = true;
    }

    // Like / Unlike 
    elseif ($action === 'like_post') {
        $post_id = $_POST['post_id'] ?? '';
        foreach ($menfess_data as $key => $post) {
            if (isset($post['id']) && (string)$post['id'] === (string)$post_id) {
                if (!isset($menfess_data[$key]['liked_by'])) $menfess_data[$key]['liked_by'] = [];
                $user_index = array_search($current_user, $menfess_data[$key]['liked_by']);
                
                if ($user_index !== false) {
                    unset($menfess_data[$key]['liked_by'][$user_index]);
                    $menfess_data[$key]['liked_by'] = array_values($menfess_data[$key]['liked_by']); 
                } else {
                    $menfess_data[$key]['liked_by'][] = $current_user;
                }
                
                $menfess_data[$key]['likes'] = count($menfess_data[$key]['liked_by']);
                $data_berubah = true;
                break;
            }
        }
    }
    
    // Kirim Komentar 
    elseif ($action === 'kirim_komentar') {
        $post_id = $_POST['post_id'] ?? '';
        $pesan_komentar = trim($_POST['pesan_komentar'] ?? '');

        if (!empty($pesan_komentar)) {
            foreach ($menfess_data as $key => $post) {
                if (isset($post['id']) && (string)$post['id'] === (string)$post_id) {
                    if (!isset($menfess_data[$key]['komentar'])) $menfess_data[$key]['komentar'] = [];
                    $menfess_data[$key]['komentar'][] = [
                        'id' => uniqid('kom_'),
                        'nama' => htmlspecialchars($current_user),
                        'pesan' => htmlspecialchars($pesan_komentar),
                        'waktu' => time()
                    ];
                    $data_berubah = true;
                    break;
                }
            }
        }
    }

    // Edit Komentar 
    elseif ($action === 'simpan_edit_komentar') {
        $post_id = $_POST['post_id'] ?? '';
        $kom_id = $_POST['kom_id'] ?? '';
        $pesan_baru = trim($_POST['pesan_edit'] ?? '');

        if (!empty($pesan_baru)) {
            foreach ($menfess_data as $key => $post) {
                if ($post['id'] == $post_id && isset($post['komentar'])) {
                    foreach ($post['komentar'] as $k_key => $kom) {
                        if (isset($kom['id']) && $kom['id'] == $kom_id) {
                            if ($kom['nama'] === $current_user) {
                                $menfess_data[$key]['komentar'][$k_key]['pesan'] = htmlspecialchars($pesan_baru);
                                $data_berubah = true;
                            }
                            break 2;
                        }
                    }
                }
            }
        }
    }

    // Hapus Komentar 
    elseif ($action === 'hapus_komentar') {
        $post_id = $_POST['post_id'] ?? '';
        $kom_id = $_POST['kom_id'] ?? '';

        foreach ($menfess_data as $key => $post) {
            if ($post['id'] == $post_id && isset($post['komentar'])) {
                foreach ($post['komentar'] as $k_key => $kom) {
                    if (isset($kom['id']) && $kom['id'] == $kom_id) {
                        if ($kom['nama'] === $current_user || $is_admin) {
                            unset($menfess_data[$key]['komentar'][$k_key]);
                            $menfess_data[$key]['komentar'] = array_values($menfess_data[$key]['komentar']);
                            $data_berubah = true;
                        }
                        break 2;
                    }
                }
            }
        }
    }

    // Laporkan Komentar 
    elseif ($action === 'laporkan_komentar') {
        $post_id = $_POST['post_id'] ?? '';
        $kom_id = $_POST['kom_id'] ?? '';
        
        $reports_data[] = [
            'pelapor' => $current_user,
            'post_id' => $post_id,
            'kom_id' => $kom_id,
            'tipe' => 'komentar',
            'waktu_lapor' => time()
        ];
        $data_berubah = true;
    }
    
    

    // Simpan Permanen (Enkripsi ulang kalau ada yang diubah sebelum ditulis ke file)
    if ($data_berubah) {
        if (!isset($account)) $account = []; 

        $menfess_tulis = $menfess_data;
        if (is_array($menfess_tulis)) {
            foreach ($menfess_tulis as $m_key => $post) {
                if (isset($post['pesan'])) {
                    $menfess_tulis[$m_key]['pesan'] = openssl_encrypt($post['pesan'], $metode_aes, $kunci_sistem, 0, $iv_aes);
                }
                if (isset($post['nama'])) {
                    $menfess_tulis[$m_key]['nama'] = openssl_encrypt($post['nama'], $metode_aes, $kunci_sistem, 0, $iv_aes);
                }
                if (isset($post['komentar']) && is_array($post['komentar'])) {
                    foreach ($post['komentar'] as $k_key => $kom) {
                        if (isset($kom['pesan'])) {
                            $menfess_tulis[$m_key]['komentar'][$k_key]['pesan'] = openssl_encrypt($kom['pesan'], $metode_aes, $kunci_sistem, 0, $iv_aes);
                        }
                        if (isset($kom['nama'])) {
                            $menfess_tulis[$m_key]['komentar'][$k_key]['nama'] = openssl_encrypt($kom['nama'], $metode_aes, $kunci_sistem, 0, $iv_aes);
                        }
                    }
                }
            }
        }

        $isi_file = "<?php\n\n\$account = " . var_export($account, true) . ";\n\n";
        $isi_file .= "\$menfess_data = " . var_export($menfess_tulis, true) . ";\n\n";
        $isi_file .= "\$reports_data = " . var_export($reports_data, true) . ";\n\n";
        file_put_contents('database.php', $isi_file);
        
        $curr_kategori = isset($_GET['kategori']) ? "?kategori=" . urlencode($_GET['kategori']) : "";
        header("Location: dashboard.php" . $curr_kategori);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sela.</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="icon" type="image/x-icon" href="icon_SELA.ico">
</head>
<body>

    <!-- Munculin notifikasi sukses login sekali, lalu hapus dari session -->
    <?php if (!empty($_SESSION['notif_login'])): unset($_SESSION['notif_login']); ?>
    <div class="toast-success" role="status">
        <span class="toast-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
        Berhasil login
    </div>
    <?php endif; ?>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-left">
            <a href="dashboard.php" class="logo">Sela.</a>
            <a href="dashboard.php" class="nav-link active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                Beranda
            </a>
        </div>
        <div class="nav-center">
            <div class="quote-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2" style="flex-shrink: 0;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <div class="quote-slider-viewport">
                    <div class="quote-slider-track">
                        <div class="quote-slide"><span>Tetap positif, bareng Sela!</span></div>
                        <div class="quote-slide"><span>Kampus tempat belajar & bertumbuh.</span></div>
                        <div class="quote-slide"><span>Jangan lupa istirahat hari ini.</span></div>
                        <div class="quote-slide"><span>Tetap positif, bareng Sela!</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="dropdown-item text-danger">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
            </a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        
        <!-- SIDEBAR KIRI -->
        <aside class="sidebar-left">
            <div class="profile-header">
                <div class="avatar-large"><?= $user_inisial ?></div>
                <div class="profile-info">
                    <h4><?= htmlspecialchars($current_user) ?> <?= $is_admin ? '<span style="color:#e63946; font-size:10px;">(Admin)</span>' : '' ?></h4>
                    <p>Semester 3 • Ilmu Komputer</p>
                </div>
            </div>
            
            <a href="#form-menfess" class="btn-primary btn-tulis-menfess">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Tulis Menfess
            </a>
            
            <ul class="sidebar-menu">
                <h4 class="sidebar-title">Cari Kategori</h4>
                <li><a href="dashboard.php?kategori=Semua" class="<?= $kategori_aktif === 'Semua' ? 'active' : '' ?>">Semua</a></li>
                <li><a href="dashboard.php?kategori=Curhat" class="<?= $kategori_aktif === 'Curhat' ? 'active' : '' ?>">Curhat</a></li>
                <li><a href="dashboard.php?kategori=InfoKelas" class="<?= $kategori_aktif === 'InfoKelas' ? 'active' : '' ?>">InfoKelas</a></li>
                <li><a href="dashboard.php?kategori=Teman Belajar" class="<?= $kategori_aktif === 'Teman Belajar' ? 'active' : '' ?>">Teman Belajar</a></li>
                <li><a href="dashboard.php?kategori=Apresiasi" class="<?= $kategori_aktif === 'Apresiasi' ? 'active' : '' ?>">Apresiasi</a></li>
            </ul>
        </aside>

        <!-- FEED UTAMA -->
        <main class="feed-content">
            
            <!-- FORM KIRIM MENFESS -->
            <form id="form-menfess" method="POST" action="dashboard.php" class="card create-post" novalidate>
                <input type="hidden" name="action" value="kirim_menfess">
                <div class="post-input-wrapper">
                    <div class="avatar-small"><?= $user_inisial ?></div>
                    <input type="text" name="pesan" placeholder="Tulis menfess, pertanyaan, atau informasi..." class="post-input" required autocomplete="off"
                        value="<?= htmlspecialchars($_POST['pesan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <?php if (!empty($error_menfess)): ?>
                    <span class="field-error"><?= htmlspecialchars($error_menfess, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>

                <div class="post-actions">
                    <div class="post-actions-left">
                        <select name="kategori" class="kategori-select" required>
                            <option value="" disabled selected>Kategori</option>
                            <option value="Curhat">Curhat</option>
                            <option value="InfoKelas">InfoKelas</option>
                            <option value="Teman Belajar">Teman Belajar</option>
                            <option value="Apresiasi">Apresiasi</option>
                        </select>
                        <label class="checkbox-label"><input type="checkbox" name="anonim" value="1"> Anonim</label>
                    </div>
                    <button type="submit" class="btn-primary btn-kirim">Kirim</button>
                </div>
            </form>

            <!-- FEED ITEM MENFESS -->
            <?php if (!empty($filtered_menfess)): ?>
                <?php foreach ($filtered_menfess as $row): ?>
                    <?php 
                        $post_id = $row['id'] ?? '';
                        $nama_asli = $row['nama'] ?? null; 
                        $is_post_owner = ($nama_asli !== null && $nama_asli === $current_user);
                        
                        $nama_tampil = $nama_asli ? htmlspecialchars($nama_asli) : 'Anonim';
                        $inisial_feed = $nama_asli ? strtoupper(substr($nama_tampil, 0, 1)) : '';
                        
                        $kategori_class = 'cat-infokelas';
                        if ($row['kategori'] === 'Curhat') { $kategori_class = 'cat-curhat'; }
                        elseif ($row['kategori'] === 'Teman Belajar') { $kategori_class = 'cat-teman'; }
                        elseif ($row['kategori'] === 'Apresiasi') { $kategori_class = 'cat-apresiasi'; }

                        $has_liked = isset($row['liked_by']) && in_array($current_user, $row['liked_by']);
                        $likes_count = isset($row['liked_by']) ? count($row['liked_by']) : ($row['likes'] ?? 0);
                        
                        $komentar_list = $row['komentar'] ?? [];
                        $komentar_count = count($komentar_list);
                        $is_reply_open = ($show_reply == $post_id);
                    ?>
                    <div class="card post-card" id="post-<?= $post_id ?>">
                        <div class="post-header">
                            <div class="post-user-info">
                                <div class="avatar-small <?= $nama_asli === null ? 'bg-anonim' : '' ?>">
                                    <?php if ($nama_asli === null): ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    <?php else: ?>
                                        <?= $inisial_feed ?>
                                    <?php endif; ?>
                                </div>
                                <div class="post-meta-container">
                                    <div class="post-category <?= $kategori_class ?>">
                                        <span class="cat-dot"></span><?= htmlspecialchars($row['kategori']) ?>
                                    </div>
                                    <div class="post-author-time">
                                        <?= $nama_tampil ?> • <?= time_ago($row['waktu']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- TITIK TIGA (...) UNTUK POSTINGAN UTAMA -->
                            <details class="comment-options" style="margin-left: auto;">
                                <summary class="btn-more-kom">•••</summary>
                                <div class="options-dropdown">
                                    <?php if ($is_post_owner): ?>
                                        <a href="dashboard.php?edit_post=<?= $post_id ?>#post-<?= $post_id ?>" class="dropdown-item">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($is_post_owner || $is_admin): ?>
                                        <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>" style="margin:0;">
                                            <input type="hidden" name="action" value="hapus_post">
                                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                            <button type="submit" class="dropdown-item text-danger">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Hapus
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!$is_post_owner): ?>
                                        <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>" style="margin:0;">
                                            <input type="hidden" name="action" value="laporkan_post">
                                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                            <button type="submit" class="dropdown-item text-warning" onclick="alert('Postingan telah dilaporkan ke Admin.')">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg> Laporkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </div>
                        
                        <!-- JIKA MODE EDIT MENFESS UTAMA -->
                        <?php if ($edit_post_id === $post_id && $is_post_owner): ?>
                            <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>#post-<?= $post_id ?>" style="margin-bottom:15px;">
                                <input type="hidden" name="action" value="simpan_edit_post">
                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                <textarea name="pesan_edit" class="post-input" required style="width:100%; border:1px solid #d8e2dc; padding:12px; border-radius:12px; resize:vertical; min-height:80px; margin-bottom:10px; font-size:14px;"><?= htmlspecialchars($row['pesan']) ?></textarea>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <button type="submit" class="btn-primary" style="padding: 6px 20px;">Simpan</button>
                                    <a href="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>#post-<?= $post_id ?>" style="font-size:13px; color:#73827a; text-decoration:none;">Batal</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="post-text"><?= nl2br($row['pesan']) ?></p>
                        <?php endif; ?>
                        
                        <!-- TOMBOL AKSI INTERAKTIF LIKES & TANGGAPI -->
                        <div class="post-footer">
                            <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>#post-<?= $post_id ?>" class="form-action-inline">
                                <input type="hidden" name="action" value="like_post">
                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                <button type="submit" class="action-btn <?= $has_liked ? 'liked' : '' ?>">
                                    <?php if ($has_liked): ?>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                    <?php else: ?>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                    <?php endif; ?>
                                    <span><?= $likes_count ?></span>
                                </button>
                            </form>

                            <a href="dashboard.php?<?= isset($_GET['kategori']) ? 'kategori='.urlencode($_GET['kategori']).'&' : '' ?>reply_to=<?= $is_reply_open ? '' : $post_id ?>#post-<?= $post_id ?>" class="action-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                <span><?= $komentar_count ?></span>
                            </a>
                            
                            <a href="dashboard.php?<?= isset($_GET['kategori']) ? 'kategori='.urlencode($_GET['kategori']).'&' : '' ?>reply_to=<?= $is_reply_open ? '' : $post_id ?>#post-<?= $post_id ?>" class="action-btn action-tanggapi">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg> Tanggapi
                            </a>
                        </div>

                        <!-- LIST KOMENTAR -->
                        <?php if (!empty($komentar_list)): ?>
                            <div class="comments-list">
                                <?php foreach ($komentar_list as $k_index => $kom): ?>
                                    <?php 
                                        $kom_id = $kom['id'] ?? 'kom_old_' . $k_index;
                                        $is_kom_owner = ($kom['nama'] === $current_user);
                                    ?>
                                    
                                    <?php if ($edit_kom_id === $kom_id && $is_kom_owner): ?>
                                        <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>#post-<?= $post_id ?>" class="edit-comment-form">
                                            <input type="hidden" name="action" value="simpan_edit_komentar">
                                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                            <input type="hidden" name="kom_id" value="<?= $kom_id ?>">
                                            <input type="text" name="pesan_edit" value="<?= htmlspecialchars($kom['pesan']) ?>" class="reply-input" required autofocus autocomplete="off">
                                            <button type="submit" class="btn-primary" style="padding: 6px 12px; font-size:11px;">Simpan</button>
                                            <a href="dashboard.php?reply_to=<?= $post_id ?>#post-<?= $post_id ?>" style="font-size: 11px; color: #888; text-decoration:none;">Batal</a>
                                        </form>
                                    <?php else: ?>
                                        <div class="comment-item">
                                            <div class="comment-content">
                                                <strong><?= htmlspecialchars($kom['nama']) ?>:</strong> <?= htmlspecialchars($kom['pesan']) ?>
                                                <span class="comment-time"><?= time_ago($kom['waktu']) ?></span>
                                            </div>
                                            
                                            <!-- MENU TITIK TIGA DI KOMENTAR -->
                                            <details class="comment-options">
                                                <summary class="btn-more-kom">•••</summary>
                                                <div class="options-dropdown">
                                                    <?php if ($is_kom_owner): ?>
                                                        <a href="dashboard.php?edit_kom=<?= $kom_id ?>&reply_to=<?= $post_id ?>#post-<?= $post_id ?>" class="dropdown-item">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($is_kom_owner || $is_admin): ?>
                                                        <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>#post-<?= $post_id ?>" style="margin:0;">
                                                            <input type="hidden" name="action" value="hapus_komentar">
                                                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                                            <input type="hidden" name="kom_id" value="<?= $kom_id ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Hapus
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if (!$is_kom_owner): ?>
                                                        <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>#post-<?= $post_id ?>" style="margin:0;">
                                                            <input type="hidden" name="action" value="laporkan_komentar">
                                                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                                            <input type="hidden" name="kom_id" value="<?= $kom_id ?>">
                                                            <button type="submit" class="dropdown-item text-warning" onclick="alert('Komentar telah dilaporkan ke Admin.')">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg> Laporkan
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- FORM INPUT TANGGAPI -->
                        <?php if ($is_reply_open): ?>
                            <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>#post-<?= $post_id ?>" class="reply-form">
                                <input type="hidden" name="action" value="kirim_komentar">
                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                <input type="text" name="pesan_komentar" placeholder="Tulis tanggapan..." class="reply-input" required autofocus autocomplete="off">
                                <button type="submit" class="btn-primary btn-reply-send">Kirim</button>
                            </form>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card empty-card"><p>Belum ada menfess di kategori <strong><?= htmlspecialchars($kategori_aktif) ?></strong>.</p></div>
            <?php endif; ?>
            
        </main>
        <div>
            <div class="card widget-card">
                <div class="widget-header" style="margin-bottom: 20px;">
                    <h4 class="widget-title">Kategori Populer</h4>
                </div>
                <ul class="populer-list">
                    <?php foreach ($kategori_persen as $nama_kategori => $persen): ?>
                        <?php 
                            if ($nama_kategori == 'Curhat') { 
                                $icon_color = '#009688'; $bg_color = '#e0f2f1'; 
                            } elseif ($nama_kategori == 'InfoKelas') { 
                                $icon_color = '#ff9800'; $bg_color = '#fff3e0'; 
                            } elseif ($nama_kategori == 'Teman Belajar') { 
                                $icon_color = '#2196f3'; $bg_color = '#e3f2fd'; 
                            } else { 
                                $icon_color = '#e91e63'; $bg_color = '#fce4ec'; 
                            }
                        ?>
                        <li class="populer-item">
                            <div class="populer-kiri">
                                <div class="populer-icon" style="background-color: <?= $bg_color ?>; color: <?= $icon_color ?>;">
                                    <?php if ($nama_kategori == 'Curhat'): ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                    <?php elseif ($nama_kategori == 'InfoKelas'): ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <?php elseif ($nama_kategori == 'Teman Belajar'): ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <?php else: ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                    <?php endif; ?>
                                </div>
                                <span class="populer-nama"><?= htmlspecialchars($nama_kategori) ?></span>
                            </div>
                            <span class="populer-persen"><?= $persen ?>%</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        </aside>
    </div>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2026 Sela. Ruang cerita  kelas.</span>
            <span class="footer-quote">Saling sapa di sela waktu kuliah.</span>
        </div>
    </footer>
</body>
</html>