<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload & Tampilkan Media</title>
    <style>
        /* CSS Sederhana untuk mempercantik Layout */
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 20px; text-align: center; }
        .upload-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: inline-block; margin-bottom: 30px; }
        .gallery { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
        .media-card { background: white; padding: 10px; border-radius: 8px; box-shadow: 0 0 5px rgba(0,0,0,0.1); max-width: 320px; }
        .media-card img, .media-card video { width: 100%; max-width: 300px; height: auto; border-radius: 4px; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>

    <h1>Upload Gambar, Video, atau GIF</h1>
    
    <!-- Notifikasi status upload -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'success'): ?>
            <p class="success">File berhasil diunggah!</p>
        <?php else: ?>
            <p class="error">Gagal mengunggah file. Pastikan format benar (JPG, PNG, GIF, MP4) dan ukuran tidak terlalu besar.</p>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Form Upload (Wajib gunakan enctype="multipart/form-data") -->
    <div class="upload-box">
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="media" required>
            <button type="submit" name="submit">Unggah Media</button>
        </form>
    </div>

    <h2>Galeri Media User</h2>
    <div class="gallery">
        <?php
        $dir = "uploads/";
        // Membaca semua file di dalam folder uploads jika foldernya ada
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                // Lewati file sistem . dan ..
                if ($file == '.' || $file == '..') continue;

                $filePath = $dir . $file;
                $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                echo '<div class="media-card">';
                // Membedakan tag HTML berdasarkan jenis file
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    // Tag untuk Gambar dan GIF sama-sama menggunakan <img>
                    echo '<img src="' . $filePath . '" alt="User Media">';
                } elseif (in_array($fileExtension, ['mp4', 'webm', 'ogg'])) {
                    // Tag khusus untuk Video
                    echo '<video controls><source src="' . $filePath . '" type="video/' . $fileExtension . '">Browser Anda tidak mendukung video.</video>';
                }
                echo '<p>' . htmlspecialchars($file) . '</p>';
                echo '</div>';
            }
        }
        ?>
    </div>

</body>
</html>
