<?php

$host = "localhost"; // Ganti dengan host database Anda
$username = "root";  // Ganti dengan username database Anda
$password = "";  // Ganti dengan password database Anda
$database = "nama_database"; // Ganti dengan nama database Anda

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

?>
<?php

require_once 'db_config.php';
require_once 'spreadsheet_helper.php';

// Function to sanitize input using filter_var
function sanitize_input($data, $filter, $options = null) {
    $data = trim($data);
    $data = stripslashes($data);
    return filter_var($data, $filter, $options);
}

function addContent($tanggal_posting, $judul, $script, $caption, $link_referensi, $format, $spreadsheet_id, $range) {
    global $conn;

    // Sanitize input (gunakan fungsi sanitize_input)
    $tanggal_posting = sanitize_input($tanggal_posting, FILTER_SANITIZE_STRING);
    $judul = sanitize_input($judul, FILTER_SANITIZE_STRING);
    $script = sanitize_input($script, FILTER_SANITIZE_STRING);
    $caption = sanitize_input($caption, FILTER_SANITIZE_STRING);
    $link_referensi = sanitize_input($link_referensi, FILTER_VALIDATE_URL);
    $format = sanitize_input($format, FILTER_SANITIZE_STRING);

    $status = 'aktif'; // Default status

    $sql = "INSERT INTO konten (tanggal_posting, judul, script, caption, link_referensi, format, status)
            VALUES ('$tanggal_posting', '$judul', '$script', '$caption', '$link_referensi', '$format', '$status')";

    if ($conn->query($sql) === TRUE) {
        $last_id = $conn->insert_id; // Dapatkan ID konten yang baru ditambahkan

        // Tambahkan ke spreadsheet
        $data = array(
            $tanggal_posting,
            $judul,
            $script,
            $caption,
            $link_referensi,
            $format
        );

        try {
            addDataToSpreadsheet($spreadsheet_id, $range, $data);
        } catch (Exception $e) {
            error_log("Error adding to spreadsheet: " . $e->getMessage());
            // Mungkin perlu rollback jika spreadsheet gagal
        }

        return $last_id; // Kembalikan ID konten
    } else {
        error_log("Error adding content to database: " . $conn->error);
        return false;
    }
}

function getContentList($status = 'aktif') {
    global $conn;

    $status = $conn->real_escape_string($status); // Penting untuk keamanan

    $sql = "SELECT id, tanggal_posting, judul, script, caption, link_referensi, format FROM konten WHERE status = '$status'";
    $result = $conn->query($sql);

    $content = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $content[] = $row;
        }
    }
    return $content;
}

function moveToTrash($id) {
    global $conn;

    $id = intval($id); // Pastikan ID adalah integer

    $sql = "UPDATE konten SET status = 'sampah', tanggal_dihapus = NOW() WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        error_log("Error moving to trash: " . $conn->error);
        return false;
    }
}

function restoreFromTrash($id) {
    global $conn;

    $id = intval($id);

    $sql = "UPDATE konten SET status = 'aktif', tanggal_dihapus = NULL WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        error_log("Error restoring from trash: " . $conn->error);
        return false;
    }
}

function deletePermanent($id) {
    global $conn;

    $id = intval($id);

    $sql = "DELETE FROM konten WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        error_log("Error deleting permanently: " . $conn->error);
        return false;
    }
}

function clearTrash() {
    global $conn;

    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

    $sql = "DELETE FROM konten WHERE status = 'sampah' AND tanggal_dihapus <= '$thirty_days_ago'";

    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        error_log("Error clearing trash: " . $conn->error);
        return false;
    }
}

// Fungsi untuk mendapatkan detail konten berdasarkan ID
function getContentById($id) {
    global $conn;

    $id = intval($id); // Pastikan ID adalah integer

    $sql = "SELECT id, tanggal_posting, judul, script, caption, link_referensi, format, status FROM konten WHERE id = $id";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

?>
<?php
require_once 'functions.php';

// Konfigurasi Spreadsheet
$spreadsheet_id = 'YOUR_SPREADSHEET_ID';
$range = 'Sheet1';

// Proses Form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'add') {
            $tanggal_posting = $_POST["tanggal_posting"];
            $judul = $_POST["judul"];
            $script = $_POST["script"];
            $caption = $_POST["caption"];
            $link_referensi = $_POST["link_referensi"];
            $format = $_POST["format"];

            $result = addContent(
                $tanggal_posting,
                $judul,
                $script,
                $caption,
                $link_referensi,
                $format,
                $spreadsheet_id,
                $range
            );

            if ($result) {
                echo "<p>Konten berhasil ditambahkan dengan ID: " . $result . "</p>";
            } else {
                echo "<p>Gagal menambahkan konten.</p>";
            }
        } elseif ($action == 'trash') {
            $id = $_POST['id'];
            if (moveToTrash($id)) {
                echo "<p>Konten berhasil dipindahkan ke sampah.</p>";
            } else {
                echo "<p>Gagal memindahkan konten ke sampah.</p>";
            }
        } elseif ($action == 'restore') {
            $id = $_POST['id'];
            if (restoreFromTrash($id)) {
                echo "<p>Konten berhasil dipulihkan.</p>";
            } else {
                echo "<p>Gagal memulihkan konten.</p>";
            }
        } elseif ($action == 'delete') {
            $id = $_POST['id'];
            if (deletePermanent($id)) {
                echo "<p>Konten berhasil dihapus permanen.</p>";
            } else {
                echo "<p>Gagal menghapus konten permanen.</p>";
            }
        } elseif ($action == 'clear_trash') {
            if (clearTrash()) {
                echo "<p>Sampah berhasil dibersihkan.</p>";
            } else {
                echo "<p>Gagal membersihkan sampah.</p>";
            }
        }
    }
}

// Tampilkan Daftar Konten Aktif
echo "<h2>Daftar Konten Aktif</h2>";
$activeContent = getContentList('aktif');
if (count($activeContent) > 0) {
    echo "<ul>";
    foreach ($activeContent as $item) {
        echo "<li>" . htmlspecialchars($item['judul']) . " - ";
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='action' value='trash'>";
        echo "<input type='hidden' name='id' value='" . $item['id'] . "'>";
        echo "<button type='submit'>Pindahkan ke Sampah</button>";
        echo "</form>";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Tidak ada konten aktif.</p>";
}

// Tampilkan Daftar Konten di Sampah
echo "<h2>Daftar Konten di Sampah</h2>";
$trashedContent = getContentList('sampah');
if (count($trashedContent) > 0) {
    echo "<ul>";
    foreach ($trashedContent as $item) {
        echo "<li>" . htmlspecialchars($item['judul']) . " - ";
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='action' value='restore'>";
        echo "<input type='hidden' name='id' value='" . $item['id'] . "'>";
        echo "<button type='submit'>Pulihkan</button>";
        echo "</form>";
        echo " | ";
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='action' value='delete'>";
        echo "<input type='hidden' name='id' value='" . $item['id'] . "'>";
        echo "<button type='submit'>Hapus Permanen</button>";
        echo "</form>";
        echo "</li>";
    }
    echo "</ul>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='clear_trash'>";
    echo "<button type='submit'>Bersihkan Sampah</button>";
    echo "</form>";
} else {
    echo "<p>Tidak ada konten di sampah.</p>";
}

// Form Tambah Konten
?>
<h2>Tambah Konten Baru</h2>
<form method="post">
    <input type="hidden" name="action" value="add">
    Tanggal Posting: <input type="date" name="tanggal_posting"><br>
    Judul: <input type="text" name="judul"><br>
    Script: <textarea name="script"></textarea><br>
    Caption: <textarea name="caption"></textarea><br>
    Link Referensi: <input type="url" name="link_referensi"><br>
    Format: <input type="text" name="format"><br>
    <button type="submit">Simpan Konten</button>
</form>
CREATE TABLE konten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_posting DATE,
    judul VARCHAR(255) NOT NULL,
    script TEXT,
    caption TEXT,
    link_referensi VARCHAR(255),
    format VARCHAR(50),
    status ENUM('aktif', 'sampah') DEFAULT 'aktif',
    tanggal_dihapus DATETIME NULL
);
<form id="addContentForm">
    <!-- ... input fields ... -->
    <button type="button" onclick="submitForm()">Simpan Konten</button>
</form>
async function submitForm() {
    const formData = new FormData(document.getElementById('addContentForm'));

    try {
        const response = await fetch('index.php', { // Atau nama file PHP Anda
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            Swal.fire({ // Gunakan SweetAlert atau sejenisnya
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
            });
            // Muat ulang daftar konten atau tambahkan item baru ke daftar
            loadContentList();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message,
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Terjadi kesalahan saat mengirim data.',
        });
    }
}
<?php
// ... (kode koneksi database, require functions.php) ...

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    // ... (ambil data dari $_POST) ...

    $result = addContent(...); // Panggil fungsi addContent

    if ($result) {
        echo json_encode(array("status" => "success", "message" => "Konten berhasil ditambahkan."));
    } else {
        echo json_encode(array("status" => "error", "message" => "Gagal menambahkan konten."));
    }
    exit; // Penting untuk menghentikan eksekusi skrip setelah mengirim respons JSON
}

// ... (kode tampilan daftar konten, form, dll.) ...
?>
