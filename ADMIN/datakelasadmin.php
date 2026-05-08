<?php
session_start();

/* ======================
   AUTH CHECK
====================== */
if (!isset($_SESSION['user'])) {
    header("Location: /sikes_rpl/login.html");
    exit;
}
 $user = $_SESSION['user'];

/* ======================================================
   KONEKSI DATABASE
====================================================== */
 $conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    die("Koneksi database gagal");
}

/* ======================================================
   TAMBAH KELAS
====================== */
if (isset($_POST['simpan_kelas'])) {
    $nama    = mysqli_real_escape_string($conn, $_POST['nama_kelas']);
    $tingkat = mysqli_real_escape_string($conn, $_POST['tingkat']);
    $wali    = (int) $_POST['id_wali'];

    mysqli_query($conn, "
        INSERT INTO kelas (nama_kelas, tingkat, id_wali)
        VALUES ('$nama', '$tingkat', $wali)
    ");

    header("Location: datakelasadmin.php");
    exit;
}

/* ======================================================
   AMBIL DATA EDIT
====================== */
 $editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $q  = mysqli_query($conn, "SELECT * FROM kelas WHERE id_kelas=$id");
    $editData = mysqli_fetch_assoc($q);
}

/* ======================================================
   UPDATE KELAS
====================== */
if (isset($_POST['update_kelas'])) {
    $id      = (int) $_POST['id_kelas'];
    $nama    = mysqli_real_escape_string($conn, $_POST['nama_kelas']);
    $tingkat = mysqli_real_escape_string($conn, $_POST['tingkat']);
    $wali    = (int) $_POST['id_wali'];

    mysqli_query($conn, "
        UPDATE kelas SET
            nama_kelas = '$nama',
            tingkat    = '$tingkat',
            id_wali    = $wali
        WHERE id_kelas = $id
    ");

    header("Location: datakelasadmin.php");
    exit;
}

/* ======================================================
   DATA GURU (WALI)
====================== */
 $guru = mysqli_query($conn, "
    SELECT id_guru, nama_guru
    FROM guru
    ORDER BY nama_guru ASC
");

/* ======================================================
   DATA KELAS
====================== */
 $kelas = mysqli_query($conn, "
    SELECT 
        k.id_kelas,
        k.nama_kelas,
        k.tingkat,
        g.nama_guru,
        COUNT(s.id_siswa) AS jumlah_siswa
    FROM kelas k
    JOIN guru g ON k.id_wali = g.id_guru
    LEFT JOIN siswa s 
        ON s.id_kelas = k.id_kelas 
        AND s.status = 'aktif'
    GROUP BY k.id_kelas
    ORDER BY k.tingkat, k.nama_kelas
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Kelas | SI Kedisiplinan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel ="icon" href="../assets/image/logo rpl.jpeg">
<style>
    /* ================= RESET & BASIC ================= */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Segoe UI", Tahoma, sans-serif;
    }

    body {
        display: flex;
        min-height: 100vh;
        background-image:
            linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)),
            url("../assets/image/bg1.jpeg");
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        overflow-x: hidden;
    }

    /* ================= SIDEBAR ================= */
    .sidebar {
        width: 250px;
        background: #fff;
        border-right: 1px solid #eee;
        padding: 40px 0;
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        transition: transform 0.3s ease-in-out;
        transform: translateX(0);
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 20px 25px;
        border-bottom: 1px solid #eee;
    }

    .logo img { width: 45px; }

    .menu {
        flex: 1;
        overflow-y: auto;
    }

    .menu a {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        margin: 6px 12px;
        border-radius: 12px;
        color: #444;
        text-decoration: none;
        transition: 0.2s;
    }

    .menu a:hover, .menu a.active {
        background: #e5e7eb;
        color: #333;
    }

    .menu a i { font-size: 20px; color: #3c3d3d; }

    /* ================= MAIN ================= */
    .main {
        flex: 1;
        padding: 20px 30px;
        margin-left: 250px;
        transition: margin-left 0.3s ease-in-out;
    }

    /* ================= TOPBAR ================= */
    .topbar {
        background: #ffffff;
        height: 60px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 25px;
        border-radius: 8px;
        position: relative;
        z-index: 10;
    }

    .user {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        position: relative;
    }

    .avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #3fa58f;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .dropdown {
        position: absolute;
        top: 48px;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        display: none;
        min-width: 140px;
        z-index: 9999; /* PENTING: DI ATAS SEMUA */
    }

    .dropdown.show { display: block; animation: fadeIn 0.2s; }

    .dropdown a {
        display: block;
        padding: 10px 15px;
        font-size: 14px;
        text-decoration: none;
        color: #333;
    }

    .dropdown a:hover { background: #f1f1f1; }

    /* ================= CONTENT CARD ================= */
    .card {
        background: #fff;
        margin-top: 20px;
        padding: 20px;
        border-radius: 10px;
        overflow-x: hidden;
    }

    .btn {
        background: #1e7f6d;
        color: #fff;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
    }
    .btn:hover { opacity: 0.9; }

    /* ================= CARD GRID (KELAS) ================= */
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .kelas-card {
        background: #fff;
        padding: 18px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        transition: 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .kelas-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        border-color: #ccc;
    }

    .kelas-card h4 {
        color: #1e293b;
        margin-bottom: 4px;
        font-size: 18px;
    }

    .kelas-card small {
        color: #64748b;
    }

    .kelas-info {
        margin-top: 10px;
        font-size: 14px;
        color: #555;
    }

    .kelas-info div {
        margin-bottom: 6px;
    }

    .kelas-action {
        margin-top: 12px;
        text-align: right;
    }

    .kelas-action form {
        display: inline-block;
    }

    /* ================= MODAL ================= */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 2000;
    }
    
    .modal.show { display: flex; }

    .modal-content {
        background: #fff;
        width: 90%;
        max-width: 400px;
        padding: 20px;
        border-radius: 10px;
    }

    .modal-content h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .modal-content input,
    .modal-content select {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .modal-actions {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 10px;
    }
    
    .modal-actions button { flex: 1; }

    .btn-cancel {
        background: #aaa;
        color: #fff;
        padding: 8px 14px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-cancel:hover { opacity: 0.9; }

    /* ================= RESPONSIVE MOBILE ================= */
    .menu-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        font-size: 22px;
        background: #1e293b;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        z-index: 1100;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        .sidebar.active {
            transform: translateX(0);
        }
        .menu-toggle {
            display: block;
        }
        
        .main {
            margin-left: 0;
            padding: 70px 15px 20px 15px;
            width: 100%;
        }

        /* Grid 1 kolom di HP */
        .card-grid {
            grid-template-columns: 1fr;
        }
        
        .card { width: 100%; }
        .user { z-index: 1101; }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>

<body>

<!-- Tombol Hamburger (Mobile) -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class='bx bx-menu'></i>
</button>

<!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
        <img src="../assets/image/logo rpl.jpeg">
        <div><b>SIKES RPL</b><br><small>SMKN 1 Cibinong Jurusan RPL</small></div>
    </div>   
        <div class="menu">
        <a href="dashboardadmin.php">
            <i class='bx bx-grid-alt'></i> Dashboard
        </a>
        <a href="datasiswaadmin.php">
            <i class='bx bx-user'></i> Data Siswa
        </a>
        <a href="datapelanggaranadmin.php">
            <i class='bx bx-file'></i> Data Pelanggaran
        </a>
        <a href="datakedisiplinanadmin.php">
            <i class='bx bx-shield-quarter'></i> Data Kedisiplinan
        </a>
        <a href="datakelasadmin.php" class="active">
            <i class='bx bx-building-house'></i> Data Kelas
        </a>
        <a href="dataguruadmin.php">
            <i class='bx bx-user-check'></i> Data Guru
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <h3>Data Kelas</h3>
        <div class="user" onclick="toggleDropdown(event)">
            <div class="avatar"><?= strtoupper(substr($user['username'],0,1)) ?></div>
            <div>
                <b style="color:#333; display:block; font-size:14px;"><?= htmlspecialchars($user['username']) ?></b>
                <small style="color:#666;"><?= htmlspecialchars($user['role']) ?></small>
            </div>
            <div class="dropdown" id="dropdown">
                <a href="../logout.php"><i class='bx bx-log-out'></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="card">
        <button class="btn" onclick="openTambah()">+ Tambah Kelas</button>

        <div class="card-grid">
            <?php while($k=mysqli_fetch_assoc($kelas)): ?>
                <div class="kelas-card">
                    <div>
                        <h4><?= htmlspecialchars($k['nama_kelas']) ?></h4>
                        <small>Tingkat <?= htmlspecialchars($k['tingkat']) ?></small>

                        <div class="kelas-info">
                            <div><i class='bx bx-user'></i> Wali: <b><?= htmlspecialchars($k['nama_guru']) ?></b></div>
                            <div><i class='bx bx-group'></i> Jumlah: <b><?= htmlspecialchars($k['jumlah_siswa']) ?></b> siswa</div>
                        </div>
                    </div>

                    <div class="kelas-action">
                        <form method="GET">
                            <input type="hidden" name="edit" value="<?= $k['id_kelas'] ?>">
                            <button class="btn">Edit</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- MODAL FORM -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h3 id="modalTitle"><?= $editData ? 'Edit' : 'Tambah' ?> Kelas</h3>
        <form method="POST">
            <?php if($editData): ?>
                <input type="hidden" name="id_kelas" value="<?= $editData['id_kelas'] ?>">
            <?php endif; ?>

            <input name="nama_kelas" placeholder="Nama Kelas"
            value="<?= $editData['nama_kelas'] ?? '' ?>" required>

            <select name="tingkat" required>
                <option value="">-- Tingkat --</option>
                <?php foreach(['X','XI','XII'] as $t): ?>
                <option value="<?= $t ?>" <?= ($editData && $editData['tingkat']==$t)?'selected':'' ?>>
                    <?= $t ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select name="id_wali" required>
                <option value="">-- Wali Kelas --</option>
                <?php 
                mysqli_data_seek($guru, 0); 
                while($g=mysqli_fetch_assoc($guru)): ?>
                <option value="<?= $g['id_guru'] ?>"
                    <?= ($editData && $editData['id_wali']==$g['id_guru'])?'selected':'' ?>>
                    <?= $g['nama_guru'] ?>
                </option>
                <?php endwhile; ?>
            </select>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button class="btn" name="<?= $editData?'update_kelas':'simpan_kelas' ?>" style="flex:1;">
                    <?= $editData?'Update':'Simpan' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // === SIDEBAR & DROPDOWN (SAMA DENGAN YANG LAIN) ===
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("active");
    }

    function toggleDropdown(event) {
        event.stopPropagation();
        const d = document.getElementById("dropdown");
        d.classList.toggle("show");
    }

    // === LOGIC MODAL KELAS ===
    const modal = document.getElementById("modal");
    const modalTitle = document.getElementById("modalTitle");

    function openTambah() {
        // Bersihkan URL dari ?edit=
        window.history.replaceState({}, document.title, "datakelasadmin.php");
        
        // Bersihkan form manual agar tidak tercampur data edit
        const form = modal.querySelector("form");
        form.reset();

        // Hapus input hidden id_kelas jika ada
        const hiddenId = form.querySelector("input[name='id_kelas']");
        if(hiddenId) hiddenId.remove();

        // Ubah judul
        modalTitle.innerText = "Tambah Kelas";
        modal.classList.add("show");
    }

    function closeModal() {
        modal.classList.remove("show");
    }

    // Auto open modal jika ada parameter edit (dari PHP)
    document.addEventListener("DOMContentLoaded", function(){
        <?php if($editData): ?>
            modal.classList.add("show");
            modalTitle.innerText = "Edit Kelas";
        <?php endif; ?>
    });

    // === PENTING: MENGGABUNGKAN SEMUA LOGIKA window.onclick DI SINI ===
    window.onclick = function(e) {
        const dropdown = document.getElementById("dropdown");
        const userArea = document.querySelector(".user");
        
        // 1. Tutup Dropdown
        if (!userArea.contains(e.target)) {
            dropdown.classList.remove("show");
        }

        // 2. Tutup Sidebar di Mobile
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById("sidebar");
            const toggleBtn = document.querySelector(".menu-toggle");
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
            }
        }

        // 3. Tutup Modal jika klik area gelap (Backdrop)
        if (e.target === modal) {
            closeModal();
        }
    }
</script>

</body>
</html>