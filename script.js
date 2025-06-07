document.addEventListener('DOMContentLoaded', function () {
    const contentList = document.querySelector('.content-list');
    const form = document.getElementById('content-form');
    const popup = document.getElementById('success-popup');
    const belumSelesaiSection = document.querySelector('.belum-selesai');
    const selesaiSection = document.querySelector('.selesai');
    const trashList = document.getElementById('trash-list');

    const CONTENT_DATA_KEY = 'contentData';
    const TRASH_DATA_KEY = 'trash';

    // Fungsi untuk mendapatkan data konten dari localStorage
    function getContentData() {
        try {
            const storedData = localStorage.getItem(CONTENT_DATA_KEY);
            return storedData ? JSON.parse(storedData) : [];
        } catch (error) {
            console.error("Error getting content data from localStorage:", error);
            return [];
        }
    }

    // Fungsi untuk menyimpan data konten ke localStorage
    function saveContentData(data) {
        try {
            localStorage.setItem(CONTENT_DATA_KEY, JSON.stringify(data));
        } catch (error) {
            console.error("Error saving content data to localStorage:", error);
        }
    }

    // Fungsi untuk mendapatkan data sampah dari localStorage
    function getTrashData() {
        try {
            const trashData = localStorage.getItem(TRASH_DATA_KEY);
            return trashData ? JSON.parse(trashData) : [];
        } catch (error) {
            console.error("Error getting trash data:", error);
            return [];
        }
    }

    // Fungsi untuk menyimpan data sampah ke localStorage
    function saveTrashData(trashData) {
        try {
            localStorage.setItem(TRASH_DATA_KEY, JSON.stringify(trashData));
        } catch (error) {
            console.error("Error saving trash data:", error);
        }
    }

    // Fungsi untuk mengubah teks menjadi tautan
    function linkify(inputText) {
        var replacedText, replacePattern1, replacePattern2, replacePattern3;

        //URLs starting with http(s)://
        replacePattern1 = /(\b(https?):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
        replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');

        //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
        replacePattern2 = /(\b(www)\.[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
        replacedText = replacedText.replace(replacePattern2, '<a href="http://$1" target="_blank">$1</a>');

        //Change email addresses to mailto:: links.
        replacePattern3 = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/ig;
        replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1">$1</a>');

        return replacedText;
    }

    // Fungsi untuk membuat card konten baru
    function createContentCard(data) {
        const article = document.createElement('article');
        article.classList.add('content-card');
        article.dataset.id = data.id;

        const linkedScript = linkify(data.script);
        const linkedCaption = linkify(data.caption);
        const linkedReferensi = linkify(data.link_referensi);

        const postedStatus = data.posted ? 'Posted' : 'Pending';
        const postedClass = data.posted ? 'posted' : 'pending';
        const checkedAttribute = data.posted ? 'checked' : '';

        article.innerHTML = `
            <h3>${data.judul}</h3>
            <p><strong>Tanggal & Waktu:</strong> ${formatDate(data.tanggal_posting)}</p>
            <p><strong>Script:</strong> ${linkedScript}</p>
            <p><strong>Caption:</strong> ${linkedCaption}</p>
            <p><strong>Link Referensi:</strong> <a href="${data.link_referensi}" target="_blank">Lihat Link</a></p>
            <p><strong>Format:</strong> ${data.format}</p>
            <div class="post-checkbox">
                <input type="checkbox" id="posted${data.id}" name="posted${data.id}" ${checkedAttribute} data-content-id="${data.id}">
                <label for="posted${data.id}">Telah Diposting</label>
            </div>
            <p class="status ${postedClass}">Status: ${postedStatus}</p>
            <button class="delete-button" data-id="${data.id}">Hapus</button>
        `;

        // Event listener untuk checkbox status posted
        const checkbox = article.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', function () {
            const contentId = parseInt(this.dataset.contentId);
            const content = contentData.find(item => item.id === contentId);
            if (content) {
                content.posted = this.checked;
                saveContentData(contentData);
                updateContentDisplay();
            }
        });

        // Event listener untuk tombol hapus
        const deleteButton = article.querySelector('.delete-button');
        deleteButton.addEventListener('click', () => deleteContent(data.id));

        return article;
    }

    // Fungsi untuk memformat tanggal
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Fungsi untuk mengelompokkan konten berdasarkan status posted, excluding trashed items
    function groupContentByStatus(data) {
        const trashData = getTrashData();
        const trashIds = trashData.map(item => item.id);

        const belumSelesai = [];
        const selesai = [];

        data.forEach(item => {
            if (trashIds.includes(item.id)) {
                // Skip items in trash
                return;
            }
            if (item.posted) {
                selesai.push(item);
            } else {
                belumSelesai.push(item);
            }
        });

        return { belumSelesai, selesai };
    }

    // Fungsi untuk menampilkan konten berdasarkan status
    function displayContentByStatus(groupedContent) {
        belumSelesaiSection.innerHTML = '<h2>Belum Selesai</h2>';
        selesaiSection.innerHTML = '<h2>Selesai</h2>';

        groupedContent.belumSelesai.forEach(item => {
            const card = createContentCard(item);
            belumSelesaiSection.appendChild(card);
        });

        groupedContent.selesai.forEach(item => {
            const card = createContentCard(item);
            selesaiSection.appendChild(card);
        });
    }

    // Fungsi untuk memperbarui tampilan konten
    function updateContentDisplay() {
        const groupedContent = groupContentByStatus(contentData);
        displayContentByStatus(groupedContent);
        displayTrash();
    }

    // Fungsi untuk menampilkan konten di daftar sampah
    function displayTrash() {
        trashList.innerHTML = '<h2>Sampah</h2>';
        const trashData = getTrashData();

        trashData.forEach(item => {
            const content = contentData.find(c => c.id === item.id);
            if (content) {
                const card = createContentCard(content);
                card.classList.add('trashed', 'in-trash');

                // Tambahkan tombol Pulihkan jika belum ada
                if (!card.querySelector('.restore-button')) {
                    const restoreButton = document.createElement('button');
                    restoreButton.textContent = 'Pulihkan';
                    restoreButton.classList.add('restore-button');
                    restoreButton.addEventListener('click', () => restoreContent(item.id));
                    card.appendChild(restoreButton);
                }

                // Tambahkan tombol Hapus Permanen jika belum ada
                if (!card.querySelector('.delete-permanent-button')) {
                    const deletePermanentButton = document.createElement('button');
                    deletePermanentButton.textContent = 'Hapus Permanen';
                    deletePermanentButton.classList.add('delete-permanent-button');
                    deletePermanentButton.addEventListener('click', () => deleteContentPermanently(item.id));
                    card.appendChild(deletePermanentButton);
                }

                trashList.appendChild(card);
            }
        });
    }

    // Fungsi untuk memindahkan konten ke sampah
    function deleteContent(id) {
        Swal.fire({
            title: 'Pindahkan ke Sampah?',
            text: "Konten akan dipindahkan ke sampah dan dapat dipulihkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, pindahkan!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                // Tambahkan ke trashData
                const trashData = getTrashData();
                if (!trashData.some(item => item.id === id)) {
                    trashData.push({ id: id, deletionDate: new Date().toISOString() });
                    saveTrashData(trashData);
                }
                updateContentDisplay();
                Swal.fire('Dipindahkan!', `Konten dengan ID ${id} dipindahkan ke sampah.`, 'success');
            }
        });
    }

    // Fungsi untuk menghapus konten secara permanen
    function deleteContentPermanently(id) {
        Swal.fire({
            title: 'Hapus Permanen?',
            text: "Anda yakin ingin menghapus konten ini secara permanen? Tindakan ini tidak dapat dibatalkan!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus permanen!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                // Hapus dari trashData
                let trashData = getTrashData();
                trashData = trashData.filter(item => item.id !== id);
                saveTrashData(trashData);

                // Hapus dari contentData
                contentData = contentData.filter(item => item.id !== id);
                saveContentData(contentData);

                updateContentDisplay();
                Swal.fire('Dihapus!', `Konten dengan ID ${id} telah dihapus secara permanen.`, 'success');
            }
        });
    }

    // Fungsi untuk memulihkan konten dari sampah
    function restoreContent(id) {
        let trashData = getTrashData();
        trashData = trashData.filter(item => item.id !== id);
        saveTrashData(trashData);
        updateContentDisplay();
        Swal.fire('Dipulihkan!', `Konten dengan ID ${id} telah dipulihkan.`, 'success');
    }

    // Fungsi untuk mengirim data ke Google Sheets
   function submitToGoogleSheets(data) {
    const SCRIPT_URL = "https://script.google.com/macros/s/AKfycbxX4iIqUDN4sLExIQjim6vHggKQjishuptMpnjr6J6edcysNEc1KWGqBKT933aM6bRu/exec";
    fetch(SCRIPT_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        console.log('Success:', result);
        showPopup();
    })
    .catch(error => {
        console.error('Gagal:', error.message);
        Swal.fire('Gagal', 'Tidak dapat mengirim ke Google Sheets', 'error');
    });
}



    // Fungsi untuk menampilkan popup sukses
    function showPopup() {
        popup.style.display = 'block';
    }

    // Fungsi untuk menutup popup sukses
    window.closePopup = function () {
        popup.style.display = 'none';
    }

    // Fungsi untuk membersihkan sampah secara otomatis (hapus konten yang sudah 30 hari di trash)
    function cleanTrash() {
        let trashData = getTrashData();
        const now = new Date();

        trashData = trashData.filter(item => {
            const deletionDate = new Date(item.deletionDate);
            const timeDiff = now.getTime() - deletionDate.getTime();
            const daysDiff = timeDiff / (1000 * 3600 * 24);
            if (daysDiff >= 30) {
                // Hapus konten secara permanen jika sudah lebih dari 30 hari
                contentData = contentData.filter(content => content.id !== item.id);
            }
            return daysDiff < 30;
        });

        saveTrashData(trashData);
        saveContentData(contentData);
        updateContentDisplay();
    }

    // Panggil cleanTrash setiap 24 jam
    setInterval(cleanTrash, 24 * 60 * 60 * 1000);

    // Inisialisasi data konten
    let contentData = getContentData();

    // Jika data kosong, isi dengan data contoh
    if (contentData.length === 0) {
        contentData = [
            {
                id: 1,
                judul: 'Judul Konten 1',
                tanggal_posting: '2023-11-15T10:00',
                script: 'Ini adalah script konten 1.',
                caption: 'Ini adalah caption konten 1.',
                format: 'video',
                posted: true,
                link_referensi: 'https://www.example.com/konten1'
            },
            {
                id: 2,
                judul: 'Judul Konten 2',
                tanggal_posting: '2023-11-16T14:30',
                script: 'Ini adalah script konten 2.',
                caption: 'Ini adalah caption konten 2.',
                format: 'foto',
                posted: false,
                link_referensi: 'https://www.example.com/konten2'
            },
            {
                id: 3,
                judul: 'Judul Konten 3',
                tanggal_posting: '2023-12-20T09:00',
                script: 'Ini adalah script konten 3.',
                caption: 'Ini adalah caption konten 3.',
                format: 'video',
                posted: true,
                link_referensi: 'https://www.example.com/konten3'
            },
            {
                id: 4,
                judul: 'Judul Konten 4',
                tanggal_posting: '2024-01-05T16:00',
                script: 'Ini adalah script konten 4.',
                caption: 'Ini adalah caption konten 4.',
                format: 'foto',
                posted: false,
                link_referensi: 'https://www.example.com/konten4'
            },
            {
                id: 5,
                judul: 'Judul Konten 5',
                tanggal_posting: '2024-01-18T11:00',
                script: 'Ini adalah script konten 5.',
                caption: 'Ini adalah caption konten 5.',
                format: 'video',
                posted: true,
                link_referensi: 'https://www.example.com/konten5'
            },
            {
                id: 6,
                judul: 'Judul Konten 6',
                tanggal_posting: '2024-02-28T13:00',
                script: 'Ini adalah script konten 6.',
                caption: 'Ini adalah caption konten 6.',
                format: 'foto',
                posted: false,
                link_referensi: 'https://www.example.com/konten6'
            }
        ];
        saveContentData(contentData);
    }

    // Event listener untuk form submission
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(form);
        const tanggalPosting = formData.get('tanggal_posting');
        const judul = formData.get('judul');
        const script = formData.get('script');
        const caption = formData.get('caption');
        const link_referensi = formData.get('link_referensi');
        const format = formData.get('format');

        // Buat objek data baru dengan ID unik
        const newContent = {
            id: contentData.length > 0 ? Math.max(...contentData.map(c => c.id)) + 1 : 1,
            tanggal_posting: tanggalPosting,
            judul: judul,
            script: script,
            caption: caption,
            link_referensi: link_referensi,
            format: format,
            posted: false
        };

        contentData.push(newContent);
        saveContentData(contentData);

        // Kirim data ke Google Sheets
        submitToGoogleSheets(newContent);

        updateContentDisplay();
        form.reset();
    });

    // Inisialisasi tampilan konten dan sampah saat halaman dimuat
    updateContentDisplay();
    displayTrash();
});
