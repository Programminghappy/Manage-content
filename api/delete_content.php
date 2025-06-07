<?php
// add_content.php (Contoh sederhana)
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "nama_database";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$tanggal_posting = $_POST["tanggal_posting"];
$judul = $_POST["judul"];
$script = $_POST["script"];
$caption = $_POST["caption"];
$link_referensi = $_POST["link_referensi"];
$format = $_POST["format"];

$sql = "INSERT INTO konten (tanggal_posting, judul, script, caption, link_referensi, format)
VALUES ('$tanggal_posting', '$judul', '$script', '$caption', '$link_referensi', '$format')";

if ($conn->query($sql) === TRUE) {
    echo "Konten berhasil disimpan";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
// server.js
const express = require('express');
const app = express();
const port = 3000;

app.use(express.json()); // Middleware untuk parsing JSON

// Endpoint untuk menambahkan konten
app.post('/api/content', (req, res) => {
    // Logika untuk menyimpan data ke database
    const newContent = req.body;
    // ... simpan ke database ...
    res.json({ message: 'Konten berhasil disimpan', data: newContent });
});

app.listen(port, () => {
    console.log(`Server berjalan di http://localhost:${port}`);
});
form.addEventListener('submit', async function (event) {
    event.preventDefault();

    const formData = new FormData(form);
    const formDataObject = Object.fromEntries(formData.entries()); // Convert FormData to object

    try {
        const response = await fetch('/api/content', { // Ganti dengan URL API Anda
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formDataObject)
        });

        if (response.ok) {
            const data = await response.json();
            console.log(data); // Tampilkan respons dari server
            // ... perbarui tampilan halaman ...
            showPopup();
            form.reset();
        } else {
            console.error('Terjadi kesalahan:', response.status);
        }
    } catch (error) {
        console.error('Terjadi kesalahan:', error);
    }
});
mycms/
├── index.html          (Halaman utama dengan formulir dan daftar konten)
├── style.css           (File CSS)
├── script.js           (File JavaScript)
├── api/
│   ├── add_content.php   (API untuk menambahkan konten)
│   ├── get_content.php   (API untuk mendapatkan daftar konten)
│   ├── delete_content.php (API untuk menghapus konten)
│   ├── restore_content.php (API untuk memulihkan konten)
│   └── ...
└── database/
    └── schema.sql        (File SQL untuk membuat tabel database)
