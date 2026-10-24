<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// 1. Proteksi Halaman
if (!isset($_SESSION['nama_user'])) {
    header("Location: login.php");
    exit;
}

$current_user = $_SESSION['nama_user'];
$user_inisial = strtoupper(substr($current_user, 0, 1));

// PENGECEKAN ADMIN
$is_admin = ($current_user === 'Nabil Ramadhan Pradhita' || $current_user === 'Nabil Ramadhan' || strtolower($current_user) === 'admin');

// 2. Hubungkan ke Database File
include 'database.php';

// ==========================================
// --- DISISIPKAN: DEKRIPSI DATA MEMORY ---
// ==========================================
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
// ==========================================

if (!isset($menfess_data)) $menfess_data = [];
if (!isset($reports_data)) $reports_data = [];
if (!isset($poll_data)) {
    $poll_data = [
        0 => ['id' => 1, 'opsi' => 'OOP (C++)', 'jumlah_vote' => 0],
        1 => ['id' => 2, 'opsi' => 'Database', 'jumlah_vote' => 0],
        2 => ['id' => 3, 'opsi' => 'Pemrograman Web', 'jumlah_vote' => 0],
        3 => ['id' => 4, 'opsi' => 'Lainnya', 'jumlah_vote' => 0],
    ];
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
$kata_kunci = trim($_GET['q'] ?? '');
$edit_post_id = $_GET['edit_post'] ?? '';
$edit_kom_id = $_GET['edit_kom'] ?? '';
$show_reply = $_GET['reply_to'] ?? '';
$total_votes = array_sum(array_map('intval', array_column($poll_data, 'jumlah_vote')));
$filtered_menfess = array_filter($menfess_data, function ($post) use ($kategori_aktif, $kata_kunci) {
    $sesuai_kategori = $kategori_aktif === 'Semua' || ($post['kategori'] ?? '') === $kategori_aktif;
    $teks_pencarian = ($post['pesan'] ?? '') . ' ' . ($post['kategori'] ?? '') . ' ' . ($post['nama'] ?? 'Anonim');
    $sesuai_pencarian = $kata_kunci === '' || stripos($teks_pencarian, $kata_kunci) !== false;

    return $sesuai_kategori && $sesuai_pencarian;
});

$data_berubah = false;

// 3. SENTRALISASI LOGIKA POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- A. Kirim Menfess ---
    if ($action === 'kirim_menfess') {
        $pesan = trim($_POST['pesan'] ?? '');
        $kategori = $_POST['kategori'] ?? 'Semua';
        $is_anonim = isset($_POST['anonim']) ? 1 : 0;

        if (!empty($pesan) && $kategori !== 'Semua') {
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
    
    // --- B. Edit Menfess Utama ---
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

    // --- C. Hapus Menfess Utama ---
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
    
    // --- D. Laporkan Menfess Utama ---
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

    // --- E. Like / Unlike ---
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
    
    // --- F. Kirim Komentar ---
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

    // --- G. Edit Komentar ---
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

    // --- H. Hapus Komentar ---
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

    // --- I. Laporkan Komentar ---
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
    
    // --- J. Polling ---
    elseif ($action === 'vote_poll') {
        $poll_id = (int)($_POST['poll_id'] ?? 0);
        foreach ($poll_data as $key => $poll) {
            if ($poll['id'] === $poll_id) {
                $poll_data[$key]['jumlah_vote']++;
                $data_berubah = true;
                break;
            }
        }
    }

        // 4. SIMPAN PERMANEN
    if ($data_berubah) {
        if (!isset($account)) $account = []; 

        // ==========================================
        // --- DISISIPKAN: ENKRIPSI TOTAL SEBELUM TULIS FILE ---
        // ==========================================
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
        // ==========================================

        $isi_file = "<?php\n\n\$account = " . var_export($account, true) . ";\n\n";
        $isi_file .= "\$menfess_data = " . var_export($menfess_tulis, true) . ";\n\n";
        $isi_file .= "\$reports_data = " . var_export($reports_data, true) . ";\n\n";
        $isi_file .= "\$poll_data = " . var_export($poll_data, true) . ";\n";
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
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-left">
            <a href="dashboard.php" class="logo">Sela.</a>
            <a href="dashboard.php" class="nav-link active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                Beranda
            </a>
            <a href="#" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                InfoKelas
            </a>
        </div>
        <div class="nav-center">
            <form action="dashboard.php" method="GET" class="search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="q" class="search-bar" placeholder="Cari menfess, topik, atau kata kunci...">
            </form>
        </div>
        <div class="nav-right">
            <div class="user-profile-nav">
                <div class="avatar-small"><?= $user_inisial ?></div>
                <span><?= explode(' ', htmlspecialchars($current_user))[0] ?></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
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
            <form id="form-menfess" method="POST" action="dashboard.php" class="card create-post">
                <input type="hidden" name="action" value="kirim_menfess">
                <div class="post-input-wrapper">
                    <div class="avatar-small"><?= $user_inisial ?></div>
                    <input type="text" name="pesan" placeholder="Tulis menfess, pertanyaan, atau informasi..." class="post-input" required autocomplete="off">
                </div>
                <div class="post-actions">
                    <div class="post-actions-left">
                        <select name="kategori" class="kategori-select" required>
                            <option value="" disabled selected>Kategori: Semua ▾</option>
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
                        $nama_asli = $row['nama'] ?? null; // Null jika diposting sebagai anonim
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

        <aside class="sidebar-right">
            <div class="card widget-card">
                <div class="widget-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1a4331" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <h4 class="widget-title">Pulse Kelas</h4>
                </div>
                <p class="widget-subtitle">Materi apa yang paling kamu butuhkan untuk dibahas sebelum UTS?</p>
                <?php foreach ($poll_data as $poll): ?>
                    <?php $persen = ($total_votes > 0) ? round(($poll['jumlah_vote'] / $total_votes) * 100) : 0; ?>
                    <form method="POST" action="dashboard.php<?= isset($_GET['kategori']) ? '?kategori='.urlencode($_GET['kategori']) : '' ?>" class="poll-form">
                        <input type="hidden" name="action" value="vote_poll">
                        <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">
                        <button type="submit" class="poll-button">
                            <div class="poll-label"><span><?= htmlspecialchars($poll['opsi']) ?></span><span><?= $persen ?>%</span></div>
                            <div class="progress-bar"><div class="progress" style="width: <?= $persen ?>%;"></div></div>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            
            <div class="card widget-card">
                <div class="widget-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1a4331" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <h4 class="widget-title">InfoKelas</h4>
                </div>
                <ul class="info-list">
                    <li><strong>Deadline tugas PPW</strong><span>24 Oktober 2026 • 23:59</span></li>
                    <li><strong>Jadwal UTS (sementara)</strong><span>28 Oktober 2026</span></li>
                </ul>
            </div>
            
            <div class="logout-container"><a href="logout.php" class="logout-link">Keluar</a></div>
        </aside>
    </div>
</body>
</html>