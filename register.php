
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

    <title>Daftar Akun - Zaki.</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Formulir Registrasi</h2>
    <form action="register.php" method="POST">
        <label>Nama Lengkap:</label><br>
        <input type="text" name="fullname" required oninvalid="(this.setCustomValidity('Nama tidak boleh kosong, mohon diisi!'))" 
            oninput="this.setCustomValidity('')"><br><br>

        <label>Email Baru:</label><br>
        <input type="email" name="mail" required oninvalid="if(this.validity.valueMissing){this.setCustomValidity('Email tidak boleh kosong, mohon diisi!')} else if(this.validity.typeMismatch){this.setCustomValidity('Format email salah, mohon sertakan tanda @ dan format yang valid (example@email.com) !')} "
            oninput="
            if (this.value.trim() === '') {
              this.setCustomValidity('Email tidak boleh kosong, mohon diisi!');
            } else if (this.validity.typeMismatch) {
              this.setCustomValidity('Format email salah, mohon sertakan tanda @ dan format yang valid (example@email.com) !');
            } else {
              this.setCustomValidity('');
            }
          "><br><br>

        <label>Password Baru:</label><br>
        <input type="password" name="pswd" required  pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}" oninvalid="this.setCustomValidity('Password tidak boleh kosong, mohon diisi!')" 
            oninput="
            if (this.value.trim() === '') {
              this.setCustomValidity('Password tidak boleh kosong, mohon diisi!');
            } else if (this.validity.patternMismatch) {
              this.setCustomValidity('Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan karakter spesial (misal: @, #, $, !)!');
            } else {
              this.setCustomValidity('');
            }
          "><br><br>

        <input type="submit" value="Daftar Sekarang">
    </form>
    <br>
    <a href="login.php">Sudah punya akun? Login</a>
</body>
</html>