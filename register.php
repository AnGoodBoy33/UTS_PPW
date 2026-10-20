<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = htmlspecialchars($_POST['fullname'] ?? '');
    $email = htmlspecialchars($_POST['mail'] ?? '');
    $password = htmlspecialchars($_POST['pswd'] ?? '');

    if (!empty($nama_lengkap) && !empty($email) && !empty($password)) {
        include 'database.php';

        $account[$email] = [
            "nama" => $nama_lengkap,
            "password" => $password
        ];

        $kontenBaru = "<?php\n\$account = " . var_export($account, true) . ";";
        file_put_contents('database.php', $kontenBaru);

        header("Location: login.php?pesan=registrasi_sukses");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Registrasi Akun</title></head>
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
