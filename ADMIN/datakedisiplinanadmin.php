<?php
session_start();

/* ================= SESSION ================= */
if (!isset($_SESSION['user'])) {
    header("Location: /sikes_rpl/login.html");
    exit;
}
 $user = $_SESSION['user'];

/* ================= DATABASE ================= */
 $conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    die("Koneksi database gagal");
}

/* ================= SIMPAN DATA ================= */
if (isset($_POST['simpan'])) {

    $tanggal        = $_POST['tanggal'] ?? '';
    $id_siswa       = $_POST['id_siswa'] ?? '';
    $id_pelanggaran = $_POST['id_pelanggaran'] ?? '';

    // VALIDASI
    if (!$tanggal || !$id_siswa || !$id_pelanggaran) {
        die("Data tidak lengkap");
    }

    // 🔥 PERBAIKAN: Gunakan id_login dari session
    $id_login = $user['id_login']; // Session menyimpan id_login
    
    // Cari id_guru berdasarkan id_login di tabel guru
    $query_guru = mysqli_query($conn, "SELECT id_guru FROM guru WHERE id_login = '$id_login'");
    
    if (!$query_guru) {
        die("Error query guru: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($query_guru) == 0) {
        // Jika admin login (tidak punya id_login di tabel guru), ambil guru pertama
        $query_guru_alt = mysqli_query($conn, "SELECT id_guru FROM guru LIMIT 1");
        if (mysqli_num_rows($query_guru_alt) > 0) {
            $data_guru = mysqli_fetch_assoc($query_guru_alt);
            $id_petugas = $data_guru['id_guru'];
        } else {
            die("Error: Tidak ada data guru di database. Silakan tambahkan data guru terlebih dahulu.");
        }
    } else {
        $data_guru = mysqli_fetch_assoc($query_guru);
        $id_petugas = $data_guru['id_guru'];
    }

    // ambil poin & kategori pelanggaran
    $pel = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT poin, kategori 
        FROM pelanggaran 
        WHERE id_pelanggaran='$id_pelanggaran'
    "));

    if (!$pel) {
        die("Data pelanggaran tidak valid");
    }

    $poin     = $pel['poin'];
    $kategori = $pel['kategori'];

    // upload bukti
    $bukti = null;
    if (!empty($_FILES['bukti']['name'])) {
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            die("Format bukti tidak diizinkan");
        }

        $bukti = "bukti_" . time() . "_" . rand(1000, 9999) . "." . $ext;
        
        // Pastikan folder ada
        if (!is_dir("../uploads/bukti/")) {
            mkdir("../uploads/bukti/", 0777, true);
        }
        
        move_uploaded_file(
            $_FILES['bukti']['tmp_name'],
            "../uploads/bukti/" . $bukti
        );
    }

    $query = "INSERT INTO kedisiplinan
              (tanggal_kejadian, id_siswa, id_pelanggaran, poin, kategori, bukti, id_petugas)
              VALUES 
              ('$tanggal', '$id_siswa', '$id_pelanggaran', '$poin', '$kategori', '$bukti', '$id_petugas')";

    if (mysqli_query($conn, $query)) {
        // update poin siswa
        mysqli_query($conn, "
            UPDATE siswa 
            SET poin_total = poin_total + $poin
            WHERE id_siswa='$id_siswa'
        ");
        
        header("Location: datakedisiplinanadmin.php");
        exit;
    } else {
        // TAMPILKAN ERROR NYATA
        die("Error INSERT: " . mysqli_error($conn) . "<br>Query: " . $query);
    }
}

/* ================= UPDATE DATA ================= */
if (isset($_POST['update'])) {

    $id  = $_POST['id_kedisiplinan'];
    $tgl = $_POST['tanggal'];
    $pel = $_POST['id_pelanggaran'];

    $old = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT poin,id_siswa,bukti 
        FROM kedisiplinan 
        WHERE id_kedisiplinan='$id'
    "));

    $new = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT poin,kategori 
        FROM pelanggaran 
        WHERE id_pelanggaran='$pel'
    "));

    if (!$old || !$new) {
        die("Data tidak valid");
    }

    $buktiSQL = "";
    if (!empty($_FILES['bukti']['name'])) {
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            die("Format bukti tidak diizinkan");
        }

        if ($old['bukti'] && file_exists("../uploads/bukti/" . $old['bukti'])) {
            unlink("../uploads/bukti/" . $old['bukti']);
        }

        $bukti = "bukti_" . time() . "." . $ext;
        move_uploaded_file(
            $_FILES['bukti']['tmp_name'],
            "../uploads/bukti/" . $bukti
        );

        $buktiSQL = ", bukti='$bukti'";
    }

    mysqli_query($conn, "
        UPDATE kedisiplinan SET
        tanggal_kejadian='$tgl',
        id_pelanggaran='$pel',
        poin='{$new['poin']}',
        kategori='{$new['kategori']}'
        $buktiSQL
        WHERE id_kedisiplinan='$id'
    ");

    // update poin siswa
    mysqli_query($conn, "
        UPDATE siswa 
        SET poin_total = poin_total - {$old['poin']} + {$new['poin']}
        WHERE id_siswa='{$old['id_siswa']}'
    ");

    header("Location: datakedisiplinanadmin.php");
    exit;
}

/* ================= HAPUS DATA ================= */
if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    $d = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT poin,id_siswa,bukti 
        FROM kedisiplinan 
        WHERE id_kedisiplinan='$id'
    "));

    if ($d) {

        // Hapus file bukti kalau ada
        if (!empty($d['bukti']) && file_exists("../uploads/bukti/" . $d['bukti'])) {
            unlink("../uploads/bukti/" . $d['bukti']);
        }

        // Kurangi poin tapi jangan sampai minus
        mysqli_query($conn, "
            UPDATE siswa 
            SET poin_total = GREATEST(poin_total - {$d['poin']}, 0)
            WHERE id_siswa='{$d['id_siswa']}'
        ");

        // Hapus data kedisiplinan
        mysqli_query($conn, "
            DELETE FROM kedisiplinan 
            WHERE id_kedisiplinan='$id'
        ");
    }

    header("Location: datakedisiplinanadmin.php");
    exit;
}

/* ================= DATA DROPDOWN ================= */
 $qKelas       = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
 $qPelanggaran = mysqli_query($conn, "SELECT * FROM pelanggaran ORDER BY nama_pelanggaran");

/* ================= DATA TABEL ================= */
 $where = "WHERE s.status = 'aktif'";

if (!empty($_GET['search'])) {
    $cari = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (
        s.nis LIKE '%$cari%' OR
        s.nama_siswa LIKE '%$cari%' OR
        kl.nama_kelas LIKE '%$cari%' OR
        p.nama_pelanggaran LIKE '%$cari%'
    )";
}

 $data = mysqli_query($conn, "
    SELECT k.*, s.nis, s.nama_siswa, kl.nama_kelas, p.nama_pelanggaran
    FROM kedisiplinan k
    JOIN siswa s ON k.id_siswa = s.id_siswa
    JOIN kelas kl ON s.id_kelas = kl.id_kelas
    JOIN pelanggaran p ON k.id_pelanggaran = p.id_pelanggaran
    $where
    ORDER BY k.id_kedisiplinan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kedisiplinan | SI Kedisiplinan</title>
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
            z-index: 9999; /* PENTING: AGAR SELALU DI ATAS */
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

        /* ================= CARD & TOOLBAR ================= */
        .card {
            background: #fff;
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            overflow-x: hidden;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
            flex-wrap: wrap;
        }

        form#formSearch {
            display: flex;
            gap: 10px;
            flex: 2;
            min-width: 200px;
        }

        .search {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ================= BUTTONS ================= */
        .btn, .btn-green {
            background: #1f7a6f;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-green:hover, .btn:hover { opacity: 0.9; }

        .btn-red {
            background: #e74c3c;
            color: #fff;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-red:hover { opacity: 0.9; }

        .btn-grey {
            background: #aaa;
            color: #fff;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-grey:hover { opacity: 0.9; }

        /* ================= TABLE ================= */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th {
            background: #1e7f6d;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            color: #555;
        }

        tr:hover { background: #f2f2f2; }

        .bukti-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
        }

        /* ================= MODALS ================= */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        
        .modal.show { display: flex; }

        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 420px;
            padding: 20px;
            border-radius: 12px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            color: #555;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }

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
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .menu-toggle { display: block; }
            
            .main {
                margin-left: 0;
                padding: 70px 15px 20px 15px;
                width: 100%;
            }

            .top-bar { flex-direction: column; align-items: stretch; }
            form#formSearch { width: 100%; }
            .search { width: 100%; }
            
            .card { width: 100%; }
            .user { z-index: 1101; }
        }

        /* Animasi */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
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
        <a href="datakedisiplinanadmin.php" class="active">
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

<!-- MAIN CONTENT -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <h3>Data Kedisiplinan</h3>
        <div class="user" onclick="toggleDropdown(event)">
            <div class="avatar"><?= strtoupper(substr($user['username'],0,1)) ?></div>
            <div>
                <b style="color:#333; display:block; font-size:14px;"><?= $user['username'] ?></b>
                <small style="color:#666;"><?= $user['role'] ?></small>
            </div>
            <div class="dropdown" id="dropdown">
                <a href="../logout.php"><i class='bx bx-log-out'></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="card">
        <!-- Toolbar -->
        <div class="top-bar">
            <form method="GET" id="formSearch">
                <input
                    type="text"
                    name="search"
                    class="search"
                    placeholder="Cari NIS / Nama / Pelanggaran"
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    oninput="autoSearch()"
                    autocomplete="off"
                >
            </form>

            <div class="btn-group">
                <button class="btn-green" onclick="openExport()">Export PDF</button>
                <button class="btn-green" onclick="openModal()">+ Tambah Data</button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Pelanggaran</th>
                        <th>Poin</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($d=mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $d['tanggal_kejadian'] ?></td>
                        <td><?= $d['nis'] ?></td>
                        <td><?= $d['nama_siswa'] ?></td>
                        <td><?= $d['nama_kelas'] ?></td>
                        <td><?= $d['nama_pelanggaran'] ?></td>
                        <td><?= $d['poin'] ?></td>
                        <td>
                            <?php if($d['bukti']): ?>
                            <img src="../uploads/bukti/<?= $d['bukti'] ?>" class="bukti-img" onclick="window.open(this.src)">
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-green" onclick="openEdit(
                            '<?= $d['id_kedisiplinan'] ?>',
                            '<?= $d['tanggal_kejadian'] ?>',
                            '<?= $d['nama_kelas'] ?>',
                            '<?= $d['nama_siswa'] ?>',
                            '<?= $d['id_pelanggaran'] ?>',
                            '<?= $d['bukti'] ?>'
                            )">Edit</button>

                            <a href="?hapus=<?= $d['id_kedisiplinan'] ?>" onclick="return confirm('Hapus data ini?')">
                                <button class="btn-red" type="button">Hapus</button>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h3>Tambah Data Kedisiplinan</h3>
        <form method="post" enctype="multipart/form-data">
            <label>Tanggal</label>
            <input type="date" name="tanggal" required>

            <label>Kelas</label>
            <select id="kelas" name="id_kelas" onchange="loadSiswa()">
                <option value="">Pilih</option>
                <?php while($k=mysqli_fetch_assoc($qKelas)): ?>
                <option value="<?= $k['id_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Siswa</label>
            <select name="id_siswa" id="siswa" required onchange="setNIS()"></select>

            <label>NIS</label>
            <input type="text" id="nis" readonly>

            <label>Pelanggaran</label>
            <select name="id_pelanggaran" required>
                <?php while($p=mysqli_fetch_assoc($qPelanggaran)): ?>
                <option value="<?= $p['id_pelanggaran'] ?>">
                <?= $p['nama_pelanggaran'] ?> (<?= $p['poin'] ?>)
                </option>
                <?php endwhile; ?>
            </select>
            
            <label>Bukti</label>
            <input type="file" name="bukti">

            <div class="modal-actions">
                <button type="button" class="btn-grey" onclick="closeModal()">Batal</button>
                <button class="btn-green" name="simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="modalEdit">
    <div class="modal-content">
        <h3>Edit Kedisiplinan</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_kedisiplinan" id="e_id">

            <label>Tanggal</label>
            <input type="date" name="tanggal" id="e_tgl" required>

            <label>Kelas</label>
            <input type="text" id="e_kelas" readonly>

            <label>Siswa</label>
            <input type="text" id="e_siswa" readonly>

            <label>Pelanggaran</label>
            <select name="id_pelanggaran" id="e_pelanggaran" required>
                <?php 
                $q = mysqli_query($conn,"SELECT * FROM pelanggaran");
                while($p=mysqli_fetch_assoc($q)):
                ?>
                <option value="<?= $p['id_pelanggaran'] ?>">
                <?= $p['nama_pelanggaran'] ?> (<?= $p['poin'] ?>)
                </option>
                <?php endwhile; ?>
            </select>

            <label>Bukti</label>
            <img id="previewBukti" style="width:80px;margin:6px 0;display:none;border:1px solid #ddd;">
            <input type="file" name="bukti" accept="image/*">

            <div class="modal-actions">
                <button type="button" class="btn-grey" onclick="closemodalEdit()">Batal</button>
                <button class="btn-green" name="update">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL FILTER EXPORT -->
<div class="modal" id="modalExport">
    <div class="modal-content">
        <h3>Filter Laporan</h3>
        <form method="GET" action="export_kedisiplinan_pdf.php">
            <label>Bulan</label>
        <select name="bulan" id="bulan">
            <option value="all">Semua Bulan</option>
            <option value="1">Januari</option>
            <option value="2">Februari</option>
            <option value="3">Maret</option>
            <option value="4">April</option>
            <option value="5">Mei</option>
            <option value="6">Juni</option>
            <option value="7">Juli</option>
            <option value="8">Agustus</option>
            <option value="9">September</option>
            <option value="10">Oktober</option>
            <option value="11">November</option>
            <option value="12">Desember</option>
        </select>   

            <label>Tahun</label>
            <select name="tahun" required>
                <?php
                $thnNow = date('Y');
                for($t=$thnNow;$t>=2020;$t--){
                    echo "<option value='$t'>$t</option>";
                }
                ?>
            </select>

            <div class="modal-actions">
                <button type="button" class="btn-grey" onclick="closeExport()">Batal</button>
                <button class="btn-green">Export PDF</button>
            </div>
        </form>
    </div>
</div>

<script>
    // === LOGIC SIDEBAR & DROPDOWN (SAMA DENGAN YANG LAIN) ===
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("active");
    }

    function toggleDropdown(event) {
        event.stopPropagation();
        const d = document.getElementById("dropdown");
        d.classList.toggle("show");
    }

    // === LOGIC MODALS ===
    const modal = document.getElementById("modal");
    const modalEdit = document.getElementById("modalEdit");
    const modalExport = document.getElementById("modalExport");

    function openModal(){ modal.classList.add("show"); }
    function closeModal(){ modal.classList.remove("show"); }
    function openExport(){ modalExport.classList.add("show"); }
    function closeExport(){ modalExport.classList.remove("show"); }
    function closemodalEdit(){ modalEdit.classList.remove("show"); }

    function openEdit(id,tgl,kelas,siswa,pel,bukti){
        modalEdit.classList.add("show");

        document.getElementById("e_id").value = id;
        document.getElementById("e_tgl").value = tgl;
        document.getElementById("e_kelas").value = kelas;
        document.getElementById("e_siswa").value = siswa;
        document.getElementById("e_pelanggaran").value = pel;

        const previewBukti = document.getElementById("previewBukti");
        if(bukti){
            previewBukti.src = "../uploads/bukti/" + bukti;
            previewBukti.style.display = "block";
        }else{
            previewBukti.style.display = "none";
        }
    }

    // === AUTO SEARCH (SAMA DENGAN ASLI) ===
    let timeout = null;
    let lastSearch = "<?= $_GET['search'] ?? '' ?>";

    function autoSearch(){
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            const input = document.querySelector('.search');
            const rawValue = input.value;
            const cleanValue = rawValue.trim();

            if(cleanValue === lastSearch) return;
            lastSearch = cleanValue;

            if(cleanValue === ''){
                window.location.href = 'datakedisiplinanadmin.php';
            }else{
                input.value = cleanValue; 
                document.getElementById('formSearch').submit();
            }
        }, 500);
    }

    // === AJAX LOAD SISWA (SAMA DENGAN ASLI) ===
    function loadSiswa(){
        const idKelas = document.getElementById("kelas").value;
        const siswa   = document.getElementById("siswa");
        const nis     = document.getElementById("nis");

        siswa.innerHTML = '<option value="">Loading...</option>';
        nis.value = '';

        if(idKelas === ""){
            siswa.innerHTML = '<option value="">Pilih kelas dulu</option>';
            return;
        }

        fetch("get_siswa.php?id_kelas=" + idKelas)
          .then(res => res.json())
          .then(data => {
              siswa.innerHTML = '<option value="">Pilih Siswa</option>';

              data.forEach(s => {
                  siswa.innerHTML += `
                    <option value="${s.id_siswa}" data-nis="${s.nis}">
                        ${s.nama_siswa}
                    </option>`;
              });
          });
    }

    function setNIS(){
        const siswa = document.getElementById("siswa");
        const nis   = document.getElementById("nis");

        const selected = siswa.options[siswa.selectedIndex];
        nis.value = selected.getAttribute("data-nis") || '';
    }

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
        if (e.target === modal) closeModal();
        if (e.target === modalEdit) closemodalEdit();
        if (e.target === modalExport) closeExport();
    }
</script>

</body>
</html>