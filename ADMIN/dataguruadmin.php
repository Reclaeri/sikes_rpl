<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: /sikes_rpl/login.html");
    exit;
}
$user = $_SESSION['user'];

$conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

/* ======================
   PROSES TAMBAH GURU
====================== */
if (isset($_POST['simpan_guru'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $nip      = mysqli_real_escape_string($conn, $_POST['nip']);
    $kontak   = mysqli_real_escape_string($conn, $_POST['kontak']);
    $jabatan  = mysqli_real_escape_string($conn, $_POST['jabatan']);
    
    // 🔥 PERBAIKAN: Gunakan id_login, bukan id_users
    // Buat user di tabel users terlebih dahulu
    $username = strtolower(str_replace(' ', '_', $nama)); // username dari nama
    $password = 'teacher123'; // default password
    
    // Cek apakah username sudah ada
    $cek_user = mysqli_query($conn, "SELECT id_login FROM login WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) == 0) {
        // Buat user baru
        $insert_user = mysqli_query($conn, "
            INSERT INTO login (username, password, role, status) 
            VALUES ('$username', '$password', 'guru', 'aktif')
        ");
        $id_login = mysqli_insert_id($conn);
    } else {
        $data_user = mysqli_fetch_assoc($cek_user);
        $id_login = $data_user['id_login'];
    }
    
    // Insert ke tabel guru dengan id_login
    $insert = mysqli_query($conn, "
        INSERT INTO guru (nama_guru, nip, no_telp, jabatan, id_login)
        VALUES ('$nama', '$nip', '$kontak', '$jabatan', '$id_login')
    ");

    if ($insert) {
        header("Location: dataguruadmin.php");
        exit;
    } else {
        $error = "Gagal menambahkan data guru: " . mysqli_error($conn);
        echo "<script>alert('$error');</script>";
    }
}

/* ======================
   PROSES UPDATE GURU
====================== */
if (isset($_POST['update_guru'])) {
    $id       = (int) $_POST['id_guru'];
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $nip      = mysqli_real_escape_string($conn, $_POST['nip']);
    $kontak   = mysqli_real_escape_string($conn, $_POST['kontak']);
    $jabatan  = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $id_login = (int) $_POST['id_login']; // 🔥 Ganti dari id_users ke id_login

    $update = mysqli_query($conn, "
        UPDATE guru SET
            nama_guru = '$nama',
            nip = '$nip',
            no_telp = '$kontak',
            jabatan = '$jabatan',
            id_login = $id_login
        WHERE id_guru = $id
    ");

    if ($update) {
        header("Location: dataguruadmin.php");
        exit;
    } else {
        die("Gagal update data guru: " . mysqli_error($conn));
    }
}

/* ======================
   AMBIL DATA GURU DENGAN JOIN
====================== */
$result = mysqli_query($conn, "
    SELECT g.*, l.username, l.role 
    FROM guru g 
    LEFT JOIN login l ON g.id_login = l.id_login 
    ORDER BY g.id_guru DESC
");

/* ======================
   PROSES HAPUS GURU
====================== */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    
    // Ambil id_login sebelum hapus guru
    $q = mysqli_query($conn, "SELECT id_login FROM guru WHERE id_guru = $id");
    $data = mysqli_fetch_assoc($q);
    $id_login = $data['id_login'];
    
    // Hapus guru
    mysqli_query($conn, "DELETE FROM guru WHERE id_guru = $id");
    
    // Opsional: Hapus user terkait (jika mau)
    // mysqli_query($conn, "DELETE FROM login WHERE id_login = $id_login");
    
    header("Location: dataguruadmin.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Guru | SI Kedisiplinan</title>
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
        z-index: 9999;
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

    /* ================= CONTENT & CARD ================= */
    .card {
        background: #fff;
        margin-top: 20px;
        padding: 20px;
        border-radius: 10px;
        overflow-x: hidden;
    }

    /* ================= BUTTONS ================= */
    .btn {
        background: #1e7f6d;
        color: #fff;
        padding: 8px 14px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        white-space: nowrap;
        text-decoration: none;
        display: inline-block;
    }
    .btn:hover { opacity: 0.9; }

    .btn-cancel {
        background: #aaa;
        color: #fff;
        padding: 8px 14px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-cancel:hover { opacity: 0.9; }

    .btn-edit {
        background: #1e7f6d;
        color: #fff;
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
    }
    .btn-edit:hover { background: #166455; }

    .btn-delete {
        background: #e74c3c;
        color: #fff;
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
    }
    .btn-delete:hover { background: #c0392b; }

    /* ================= TABLE ================= */
    .table-container {
        overflow-x: auto;
        margin-top: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }

    th {
        background: #1e7f6d;
        color: white;
        padding: 12px 10px;
        text-align: left;
        font-weight: 600;
    }

    td {
        padding: 12px 10px;
        border-bottom: 1px solid #ddd;
        vertical-align: middle;
    }

    tr:hover { background: #f2f2f2; }

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
        gap: 10px;
        margin-top: 10px;
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
        .sidebar { transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0); }
        .menu-toggle { display: block; }
        
        .main {
            margin-left: 0;
            padding: 70px 15px 20px 15px;
            width: 100%;
        }

        .card { width: 100%; }
        .user { z-index: 1101; }
        
        .modal-content { width: 95%; }
    }
    
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
        <a href="datakedisiplinanadmin.php">
            <i class='bx bx-shield-quarter'></i> Data Kedisiplinan
        </a>
        <a href="datakelasadmin.php">
            <i class='bx bx-building-house'></i> Data Kelas
        </a>
        <a href="dataguruadmin.php" class="active">
            <i class='bx bx-user-check'></i> Data Guru
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <h3>Data Guru</h3>
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
        <button class="btn" onclick="openAdd()">+ Tambah Guru</button>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Kontak</th>
                        <th>Jabatan</th>
                        <th>Username</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($g = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($g['nama_guru']) ?></td>
                    <td><?= htmlspecialchars($g['nip']) ?></td>
                    <td><?= htmlspecialchars($g['no_telp']) ?></td>
                    <td><?= htmlspecialchars($g['jabatan']) ?></td>
                    <td><?= htmlspecialchars($g['username'] ?? '-') ?></td>
                    <td>
                        <button class="btn-edit"
                            onclick="openEdit(
                                '<?= $g['id_guru'] ?>',
                                '<?= htmlspecialchars($g['nama_guru'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($g['nip'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($g['no_telp'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($g['jabatan'], ENT_QUOTES) ?>',
                                '<?= $g['id_login'] ?>'
                            )">Edit</button>

                        <a href="?hapus=<?= $g['id_guru'] ?>" onclick="return confirm('Hapus data ini?')">
                            <button class="btn-delete" type="button">Hapus</button>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL ADD -->  
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Tambah Guru</h3>
        <form method="POST">
            <input name="nama" placeholder="Nama Lengkap" required>
            <input name="nip" placeholder="NIP">
            <input name="kontak" placeholder="No. Kontak">
            <select name="jabatan" required>
                <option value="">Pilih Jabatan</option>
                <option>Wali Kelas</option>
                <option>Kaprog</option>
                <option>Admin</option>
            </select>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button class="btn" name="simpan_guru">Simpan</button>
            </div>
        </form>
        <small style="color:#666;">* Username akan dibuat otomatis dari nama, password default: teacher123</small>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Guru</h3>
        <form method="POST">
            <input type="hidden" name="id_guru" id="e_id">
            <input name="nama" id="e_nama" placeholder="Nama Lengkap" required>
            <input name="nip" id="e_nip" placeholder="NIP">
            <input name="kontak" id="e_kontak" placeholder="No. Kontak">
            <select name="jabatan" id="e_jabatan" required>
                <option value="">Pilih Jabatan</option>
                <option>Wali Kelas</option>
                <option>Kaprog</option>
                <option>Admin</option>
            </select>
            <input type="hidden" name="id_login" id="e_login">
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button class="btn" name="update_guru">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    // === SIDEBAR & DROPDOWN ===
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("active");
    }

    function toggleDropdown(event) {
        event.stopPropagation();
        const d = document.getElementById("dropdown");
        d.classList.toggle("show");
    }

    window.onclick = function(e) {
        const dropdown = document.getElementById("dropdown");
        const userArea = document.querySelector(".user");
        
        if (!userArea.contains(e.target)) {
            dropdown.classList.remove("show");
        }

        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById("sidebar");
            const toggleBtn = document.querySelector(".menu-toggle");
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
            }
        }
        
        if (e.target.classList.contains("modal")) {
            closeModal();
        }
    }

    // === LOGIC MODAL GURU ===
    const addModal = document.getElementById("addModal");
    const editModal = document.getElementById("editModal");

    function openAdd() {
        addModal.classList.add("show");
    }

    function openEdit(id, nama, nip, kontak, jabatan, id_login) {
        editModal.classList.add("show");
        
        document.getElementById("e_id").value = id;
        document.getElementById("e_nama").value = nama;
        document.getElementById("e_nip").value = nip || '';
        document.getElementById("e_kontak").value = kontak || '';
        document.getElementById("e_jabatan").value = jabatan;
        document.getElementById("e_login").value = id_login;
    }

    function closeModal() {
        addModal.classList.remove("show");
        editModal.classList.remove("show");
    }
</script>

</body>
</html>