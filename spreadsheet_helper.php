{
  "tanggal_posting": [_THISROW].[tanggal_posting],
  "judul": [_THISROW].[judul],
  "script": [_THISROW].[script],
  "caption": [_THISROW].[caption],
  "link_referensi": [_THISROW].[link_referensi],
  "format": [_THISROW].[format]
}
<?php

function sendDataToAppSheet($data) {
    // Konfigurasi AppSheet API (Ganti dengan URL Webhook Anda)
    $appSheetWebhookUrl = 'YOUR_APPSHEET_WEBHOOK_URL'; // Ganti dengan URL Webhook AppSheet Anda

    // Konfigurasi cURL
    $ch = curl_init($appSheetWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    // Eksekusi cURL
    $response = curl_exec($ch);

    // Periksa error
    if (curl_errno($ch)) {
        return 'cURL error: ' . curl_error($ch);
    }

    // Tutup koneksi cURL
    curl_close($ch);

    // Periksa response dari AppSheet
    return $response;
}
?>
<?php
require 'spreadsheet_helper.php'; // Include helper functions

// Inisialisasi array untuk menyimpan data (sementara)
session_start();
if (!isset($_SESSION['content_list'])) {
    $_SESSION['content_list'] = [];
}

// Fungsi untuk membersihkan data input
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validasi data
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $tanggal_posting = sanitizeInput($_POST["tanggal_posting"]);
    $judul = sanitizeInput($_POST["judul"]);
    $script = sanitizeInput($_POST["script"]);
    $caption = sanitizeInput($_POST["caption"]);
    $link_referensi = sanitizeInput($_POST["link_referensi"]);
    $format = sanitizeInput($_POST["format"]);

    // Validasi tanggal
    if (empty($tanggal_posting)) {
        $errors[] = "Tanggal Posting harus diisi.";
    }

    // Validasi judul
    if (empty($judul)) {
        $errors[] = "Judul harus diisi.";
    }

    // Validasi script
    if (empty($script)) {
        $errors[] = "Script harus diisi.";
    }

    // Validasi caption
    if (empty($caption)) {
        $errors[] = "Caption harus diisi.";
    }

    // Validasi link referensi
    if (empty($link_referensi)) {
        $errors[] = "Link Referensi harus diisi.";
    }

    // Validasi format
    if (empty($format)) {
        $errors[] = "Format harus dipilih.";
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        $content = [
            'id' => uniqid(), // Generate ID unik
            'tanggal_posting' => $tanggal_posting,
            'judul' => $judul,
            'script' => $script,
            'caption' => $caption,
            'link_referensi' => $link_referensi,
            'format' => $format,
        ];

        // Simpan ke list (session)
        $_SESSION['content_list'][] = $content;

        // Kirim data ke AppSheet
        $result = sendDataToAppSheet($content);

        if ($result) {
            // Berhasil dikirim ke AppSheet
            $response = ['status' => 'success', 'message' => 'Konten berhasil disimpan dan dikirim ke AppSheet. Response: ' . $result];
        } else {
            // Gagal mengirim ke AppSheet
            $response = ['status' => 'error', 'message' => 'Gagal mengirim ke AppSheet: ' . $result];
        }
    } else {
        // Ada error validasi
        $response = ['status' => 'error', 'message' => 'Error validasi: ' . implode(', ', $errors)];
    }
} else {
    // Bukan method POST
    $response = ['status' => 'error', 'message' => 'Invalid request method.'];
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
const { google } = require('googleapis');

exports.addDataToSheet = async (req, res) => {
  if (req.method === 'POST') {
    const data = req.body;

    // Konfigurasi Google Sheets API
    const auth = new google.auth.GoogleAuth({
      keyFile: 'path/to/your/credentials.json', // Ganti dengan path ke file credentials Anda
      scopes: ['https://www.googleapis.com/auth/spreadsheets'],
    });

    const sheets = google.sheets({ version: 'v4', auth });
    const spreadsheetId = 'YOUR_SPREADSHEET_ID'; // Ganti dengan ID Spreadsheet Anda
    const range = 'Sheet1'; // Ganti dengan nama sheet Anda

    // Siapkan data untuk ditambahkan ke sheet
    const values = [
      [
        data.tanggal_posting,
        data.judul,
        data.script,
        data.caption,
        data.link_referensi,
        data.format,
      ],
    ];

    // Tambahkan data ke sheet
    try {
      const result = await sheets.spreadsheets.values.append({
        spreadsheetId,
        range,
        valueInputOption: 'USER_ENTERED',
        insertDataOption: 'INSERT_ROWS',
        resource: {
          values,
        },
      });

      console.log('%d cells appended.', result.data.updates.updatedCells);
      res.status(200).send('Data added to sheet successfully.');
    } catch (err) {
      console.error('The API returned an error: ' + err);
      res.status(500).send('Error adding data to sheet: ' + err);
    }
  } else {
    res.status(405).send('Method Not Allowed');
  }
};
