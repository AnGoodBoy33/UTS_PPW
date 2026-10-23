<?php
if (isset($_POST['submit'])) {
    $file = $_FILES['media'];

    // Ambil informasi file
    $fileName = basename($file['name']);
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Ekstrak ekstensi file
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Atur ekstensi yang diizinkan (Gambar, GIF, Video)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];

    if (in_array($fileExt, $allowedExtensions)) {
        if ($fileError === 0) {
            // Batasi ukuran file (contoh: maks 50MB untuk mengakomodasi video)
            if ($fileSize < 50000000) {
                
                // Buat folder uploads jika belum ada
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                // Berikan nama unik agar file dengan nama yang sama tidak saling menimpa
                $newFileName = uniqid('media_', true) . "." . $fileExt;
                $fileDestination = 'uploads/' . $newFileName;

                // Pindahkan file dari folder sementara ke folder tujuan
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    header("Location: index.php?status=success");
                    exit();
                } else {
                    header("Location: index.php?status=uploaderror");
                    exit();
                }
            } else {
                header("Location: index.php?status=toobig");
                exit();
            }
        } else {
            header("Location: index.php?status=error");
            exit();
        }
    } else {
        header("Location: index.php?status=invalidtype");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
