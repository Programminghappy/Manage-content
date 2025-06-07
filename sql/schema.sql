<?php

session_start(); // Pastikan sesi dimulai di setiap halaman yang membutuhkannya

$host = "localhost";
$username = "user";
$password = "password";
$database = "nama_database";

$koneksi = new mysqli($host, $username, $password, $database);

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Fungsi untuk membersihkan input
function bersihkanInput($data) {
    global $koneksi;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $koneksi->real_escape_string($data);
}

// Fungsi untuk menyimpan konten
function simpanKonten($tanggal_posting, $judul, $script, $caption, $link_referensi, $format, $user_id) {
    global $koneksi;

    // Validasi data
    if (empty($judul) || empty($script) || empty($caption) || empty($link_referensi) || empty($format)) {
        return "Error: Semua field harus diisi.";
    }

    // Bersihkan data
    $tanggal_posting = bersihkanInput($tanggal_posting);
    $judul = bersihkanInput($judul);
    $script = bersihkanInput($script);
    $caption = bersihkanInput($caption);
    $link_referensi = bersihkanInput($link_referensi);
    $format = bersihkanInput($format);

    // Mulai transaksi
    $koneksi->begin_transaction();

    try {
        $sql = "INSERT INTO konten (tanggal_posting, judul, script, caption, link_referensi, format, user_id, tanggal_submit)
                VALUES ('$tanggal_posting', '$judul', '$script', '$caption', '$link_referensi', '$format', $user_id, NOW())";

        if ($koneksi->query($sql) === TRUE) {
            $konten_id = $koneksi->insert_id;

            // Log aktivitas
            $sql_log = "INSERT INTO log_aktivitas (user_id, aksi, konten_id, detail)
                        VALUES ($user_id, 'Buat Konten', $konten_id, 'Konten baru dibuat')";
            $koneksi->query($sql_log);

            // Commit transaksi
            $koneksi->commit();
            return "Konten berhasil disimpan.";
        } else {
            throw new Exception("Error: " . $sql . "<br>" . $koneksi->error);
        }

    } catch (Exception $e) {
        // Rollback transaksi jika ada kesalahan
        $koneksi->rollback();
        return "Error: " . $e->getMessage();
    }
}

// Pastikan form disubmit dengan benar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $tanggal_posting = $_POST['tanggal_posting'];
    $judul = $_POST['judul'];
    $script = $_POST['script'];
    $caption = $_POST['caption'];
    $link_referensi = $_POST['link_referensi'];
    $format = $_POST['format'];

    // Pastikan user sudah login dan ID sesi tersedia
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Simpan konten
        $pesan = simpanKonten($tanggal_posting, $judul, $script, $caption, $link_referensi, $format, $user_id);
        echo $pesan; // Tampilkan pesan ke pengguna
    } else {
        echo "Error: User belum login.";
    }
} else {
    echo "Error: Form tidak disubmit dengan benar.";
}

$koneksi->close();

?>
CREATE TABLE konten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_posting DATETIME NOT NULL,
    judul VARCHAR(255) NOT NULL,
    script TEXT NOT NULL,
    caption TEXT NOT NULL,
    link_referensi VARCHAR(255) NOT NULL,
    format VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    tanggal_submit TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aksi VARCHAR(255) NOT NULL,
    konten_id INT,
    detail TEXT,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
