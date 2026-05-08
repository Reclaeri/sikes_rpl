<?php
/* ======================================================
   SESSION & AUTH
====================================================== */
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /sikes_rpl/login.html");
    exit;
}
 $user = $_SESSION['user'];

/* ======================================================
   DATABASE
====================================================== */
 $conn = new mysqli("localhost", "root", "", "sikes_rpl");
if ($conn->connect_error) {
    die("Koneksi database gagal");
}

/* ======================================================
   HAPUS DATA (POST)
====================================================== */
if (isset($_POST['hapus'])) {
    $id = (int) $_POST['hapus'];

    $stmt = $conn->prepare(
        "DELETE FROM pelanggaran WHERE id_pelanggaran = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: datapelanggaranadmin.php");
    exit;
}

/* ======================================================
   SIMPAN / UPDATE DATA
====================================================== */
if (isset($_POST['simpan'])) {
    $id       = $_POST['id'];
    $nama     = trim($_POST['nama']);
    $kategori = $_POST['kategori'];
    $poin     = (int) $_POST['poin'];

    if ($id === "") {
        $stmt = $conn->prepare(
            "INSERT INTO pelanggaran (nama_pelanggaran, kategori, poin)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("ssi", $nama, $kategori, $poin);
    } else {
        $stmt = $conn->prepare(
            "UPDATE pelanggaran
             SET nama_pelanggaran = ?, kategori = ?, poin = ?
             WHERE id_pelanggaran = ?"
        );
        $stmt->bind_param("ssii", $nama, $kategori, $poin, $id);
    }

    $stmt->execute();
    header("Location: datapelanggaranadmin.php");
    exit;
}

/* ======================================================
   AMBIL DATA
====================================================== */
 $dataPelanggaran = $conn->query(
    "SELECT * FROM pelanggaran ORDER BY kategori, poin DESC"
);
 $kategoriList = ["Ringan", "Sedang", "Berat"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SI Kedisiplinan | Data Pelanggaran</title>
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
        z-index: 9999; /* Di atas semuanya */
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

    /* ================= CONTENT WRAPPER ================= */
    .content-wrapper {
        background: #ffffff;
        border-radius: 16px;
        padding: 25px;
        margin: 20px 0;
        overflow-x: hidden;
    }

    /* ================= CARDS & GRID (Khusus Pelanggaran) ================= */
    .section {
        margin-bottom: 25px;
    }

    .badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        margin-bottom: 15px;
        display: inline-block;
        font-weight: bold;
    }

    .ringan { background: #d8f5ec; color: #1e7f6d; }
    .sedang { background: #e3f0ff; color: #1d5fa7; }
    .berat { background: #fff1db; color: #c26a00; }

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
    }

    .card-item {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        background: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.2s;
    }

    .card-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-color: #ccc;
    }

    .point {
        font-size: 20px;
        font-weight: bold;
        color: #d9534f;
        margin-bottom: 15px;
    }

    /* ================= BUTTONS ================= */
    .btn {
        background: #1e7f6d;
        color: #fff;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
    }
    .btn:hover { opacity: 0.9; }

    .btn-hapus {
        background: #d9534f;
        color: #fff;
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
    }
    .btn-hapus:hover { opacity: 0.9; }

    .action {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    /* ================= MODAL ================= */
    .modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000; /* Di atas semua */
    }
    
    .modal.show { display: flex; }

    .modal-box {
        background: #fff;
        width: 90%;
        max-width: 400px;
        padding: 25px;
        border-radius: 12px;
    }

    .modal-box label {
        display: block;
        margin-bottom: 5px;
        font-size: 13px;
        color: #555;
        font-weight: bold;
    }

    .modal-box input,
    .modal-box select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .modal-actions {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .modal-actions button { flex: 1; }

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

        .cards {
            grid-template-columns: 1fr;
        }

        .card-item {
            width: 100%;
            padding: 15px;
        }
        
        .content-wrapper {
            padding: 15px;
        }
        
        .user { z-index: 1101; }
    }
</style>
</head>

<body>

<!-- Tombol Hamburger -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class='bx bx-menu'></i>
</button>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="../assets/image/logo rpl.jpeg" alt="Logo" onerror="this.style.display='none'">
        <div>
            <div><b>SIKES RPL</b><br>
            <small>SMKN 1 Cibinong Jurusan RPL</small></div>
        </div>
    </div>
    <div class="menu">
        <a href="dashboardadmin.php">
            <i class='bx bx-grid-alt'></i> Dashboard
        </a>
        <a href="datasiswaadmin.php">
            <i class='bx bx-user'></i> Data Siswa
        </a>
        <a href="datapelanggaranadmin.php" class="active">
            <i class='bx bx-file'></i> Data Pelanggaran
        </a>
        <a href="datakedisiplinanadmin.php">
            <i class='bx bx-shield-quarter'></i> Data Kedisiplinan
        </a>
        <a href="datakelasadmin.php">
            <i class='bx bx-building-house'></i> Data Kelas
        </a>
        <a href="dataguruadmin.php">
            <i class='bx bx-user-check'></i> Data Guru
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <h3>Data Pelanggaran</h3>
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

    <!-- CONTENT WRAPPER -->
    <div class="content-wrapper">
        
        <!-- Toolbar -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap: wrap; gap: 10px;">
            <h3 style="color:#333;">Daftar Pelanggaran</h3>
            <button type="button" class="btn" onclick="openModal()">
                <i class='bx bx-plus'></i> Tambah Pelanggaran
            </button>
        </div>

        <!-- List Pelanggaran per Kategori -->
        <?php foreach ($kategoriList as $kat): ?>
        <div class="section">
            <span class="badge <?= strtolower($kat) ?>"><?= $kat ?></span>

            <div class="cards">
            <?php
            mysqli_data_seek($dataPelanggaran, 0);
            while ($row = $dataPelanggaran->fetch_assoc()):
                if ($row['kategori'] === $kat):
            ?>
                <div class="card-item">
                    <div>
                        <h4 style="color:#333; margin-bottom:5px;"><?= htmlspecialchars($row['nama_pelanggaran']) ?></h4>
                        <div class="point"><?= $row['poin'] ?> Poin</div>
                    </div>

                    <div class="action">
                        <button class="btn" onclick='editData(<?= json_encode($row) ?>)'>
                            <i class='bx bx-edit'></i> Ubah
                        </button>

                        <form method="POST" onsubmit="return confirm('Hapus data ini?');" style="display:inline;">
                            <input type="hidden" name="hapus" value="<?= $row['id_pelanggaran'] ?>">
                            <button type="submit" class="btn-hapus" title="Hapus">
                                <i class='bx bx-trash'></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; endwhile; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div> <!-- END content-wrapper -->
</div> <!-- END main -->

<!-- MODAL FORM -->
<div class="modal" id="modal">
    <form method="POST" class="modal-box">
        <h3 id="modalTitle" style="margin-bottom:15px; color:#333;">Tambah Pelanggaran</h3>

        <input type="hidden" name="id" id="id">

        <label>Nama Pelanggaran</label>
        <input type="text" name="nama" id="nama" required placeholder="Contoh: Datang Terlambat">

        <label>Kategori</label>
        <select name="kategori" id="kategori">
            <option value="Ringan">Ringan</option>
            <option value="Sedang">Sedang</option>
            <option value="Berat">Berat</option>
        </select>

        <label>Poin</label>
        <input type="number" name="poin" id="poin" required placeholder="0">

        <div class="modal-actions">
            <button type="submit" name="simpan" class="btn">Simpan</button>
            <button type="button" class="btn btn-hapus" onclick="closeModal()">Batal</button>
        </div>
    </form>
</div>

<script>
    // === LOGIC TOGGLE SIDEBAR & DROPDOWN (SAMA DENGAN YANG LAIN) ===
    
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("active");
    }

    function toggleDropdown(event) {
        event.stopPropagation();
        const d = document.getElementById("dropdown");
        d.classList.toggle("show");
    }

    // === LOGIC MODAL PELANGGARAN ===
    const modal = document.getElementById("modal");
    const id = document.getElementById("id");
    const nama = document.getElementById("nama");
    const kategori = document.getElementById("kategori");
    const poin = document.getElementById("poin");
    const modalTitle = document.getElementById("modalTitle");

    function openModal() {
        resetForm();
        modalTitle.innerText = "Tambah Pelanggaran";
        modal.classList.add("show");
    }

    function closeModal() {
        modal.classList.remove("show");
    }

    function resetForm() {
        id.value = "";
        nama.value = "";
        kategori.value = "Ringan";
        poin.value = "";
    }

    function editData(data) {
        modalTitle.innerText = "Edit Pelanggaran";
        modal.classList.add("show");

        id.value = data.id_pelanggaran;
        nama.value = data.nama_pelanggaran;
        kategori.value = data.kategori;
        poin.value = data.poin;
    }

    // === PENTING: MENGGABUNGKAN SEMUA LOGIKA window.onclick DI SINI ===
    window.onclick = function(e) {
        const dropdown = document.getElementById("dropdown");
        const userArea = document.querySelector(".user");
        const sidebar = document.getElementById("sidebar");
        const toggleBtn = document.querySelector(".menu-toggle");

        // 1. Tutup Dropdown
        if (!userArea.contains(e.target)) {
            dropdown.classList.remove("show");
        }

        // 2. Tutup Sidebar di Mobile
        if (window.innerWidth <= 768) {
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