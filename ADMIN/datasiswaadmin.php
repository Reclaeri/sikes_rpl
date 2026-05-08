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
   PROSES TAMBAH / EDIT SISWA
====================================================== */
if (isset($_POST['simpan'])) {

    $mode   = $_POST['mode'];
    $nis    = $_POST['nis'];
    $nama   = $_POST['nama'];
    $jk     = $_POST['jk'];
    $kelas  = $_POST['kelas'];
    $status = $_POST['status'];

    if ($mode === "tambah") {
        mysqli_query($conn, "
            INSERT INTO siswa 
            (nis, nama_siswa, jenis_kelamin, id_kelas, status)
            VALUES 
            ('$nis', '$nama', '$jk', '$kelas', '$status')
        ");
    } else {
        mysqli_query($conn, "
            UPDATE siswa SET
                nama_siswa     = '$nama',
                jenis_kelamin  = '$jk',
                id_kelas       = '$kelas',
                status         = '$status'
            WHERE nis = '$nis'
        ");
    }

    header("Location: datasiswaadmin.php");
    exit;
}

/* ======================================================
   PROSES HAPUS SISWA
====================================================== */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM siswa WHERE id_siswa = '$id'");
    header("Location: datasiswaadmin.php");
    exit;
}

/* ======================================================
   AMBIL DATA KELAS & SISWA
====================================================== */
 $dataKelas = mysqli_query($conn, "
    SELECT * FROM kelas ORDER BY nama_kelas ASC
");

 $where = "WHERE siswa.status = 'aktif'";

if (!empty($_GET['search'])) {
    $cari = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (
        siswa.nis LIKE '%$cari%' OR
        siswa.nama_siswa LIKE '%$cari%' OR
        kelas.nama_kelas LIKE '%$cari%'
    )";
}

if (!empty($_GET['kelas'])) {
    $idKelas = (int)$_GET['kelas'];
    $where .= " AND siswa.id_kelas = $idKelas";
}

// Perbaikan: Menambahkan perhitungan poin total otomatis via subquery
 $dataSiswa = mysqli_query($conn, "
    SELECT siswa.*, kelas.nama_kelas, 
           (SELECT COALESCE(SUM(kd.poin), 0) FROM kedisiplinan kd WHERE kd.id_siswa = siswa.id_siswa) as poin_total
    FROM siswa
    JOIN kelas ON siswa.id_kelas = kelas.id_kelas
    $where
    ORDER BY kelas.nama_kelas ASC, siswa.nama_siswa ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Siswa | SI Kedisiplinan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/siswa.css">
<link rel ="icon" href="../assets/image/logo rpl.jpeg">
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
        <div><b>SIKES RPL</b><br><small>SMKN 1 Cibinong Jurusan RPL</small></div>
    </div>
    <div class="menu">
        <a href="dashboardadmin.php">
            <i class='bx bx-grid-alt'></i> Dashboard
        </a>
        <a href="datasiswaadmin.php" class="active">
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
        <a href="dataguruadmin.php">
            <i class='bx bx-user-check'></i> Data Guru
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <h3>Data Siswa</h3>
        <div class="user" onclick="toggleDropdown(event)">
            <div class="avatar"><?= strtoupper(substr($user['username'],0,1)) ?></div>
            <div class="user-info">
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
        <!-- Toolbar -->
        <div class="top-bar">
            <form method="GET">
                <input 
                    type="text"
                    name="search"
                    class="search"
                    placeholder="Cari NIS / Nama / Kelas..."
                    value="<?= $_GET['search'] ?? '' ?>"
                >

                <select name="kelas" class="filter-kelas">
                    <option value="">Semua Kelas</option>
                    <?php 
                    mysqli_data_seek($dataKelas, 0);
                    while ($k = mysqli_fetch_assoc($dataKelas)): 
                    ?>
                        <option value="<?= $k['id_kelas'] ?>"
                            <?= (isset($_GET['kelas']) && $_GET['kelas']==$k['id_kelas'])?'selected':'' ?>>
                            <?= $k['nama_kelas'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button class="btn">Filter</button>
            </form>
                    </br>
            <button class="btn" onclick="openModal()" style="flex-shrink: 0;">+ Tambah Siswa</button>
        </div>
                    </br>
        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>JK</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Poin</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($s = mysqli_fetch_assoc($dataSiswa)) :
                    // Ambil poin_total dari query SQL
                    $p = $s['poin_total']; 
                    $badge = ($p>=25?'p25':($p>=20?'p20':($p>=15?'p15':($p>=10?'p10':'p0'))));
                    ?>
                    <tr>
                        <td><?= $s['nis'] ?></td>
                        <td><?= $s['nama_siswa'] ?></td>
                        <td><?= $s['jenis_kelamin'] ?></td>
                        <td><?= $s['nama_kelas'] ?></td>
                        <td><?= $s['status'] ?></td>
                        <td><span class="badge <?= $badge ?>"><?= $p ?> Poin</span></td>
                        <td class="action">
                            <!-- Edit -->
                            <!-- PERBAIKAN: Tambahkan ENT_QUOTES agar nama dengan kutip tidak error JS -->
                            <span onclick="editSiswa(
                                '<?= htmlspecialchars($s['nis'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['nama_siswa'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['jenis_kelamin'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['id_kelas'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($s['status'], ENT_QUOTES) ?>'
                            )" title="Edit" style="margin-right:8px; cursor:pointer; color: #1e7f6d;">
                                <i class='bx bx-edit'></i>
                            </span>

                            <!-- Arsip Pelanggaran -->
                            <a href="arsip-pelanggaran.php?nis=<?= $s['nis'] ?>" title="Arsip Pelanggaran" style="margin-right:8px; color: #555; font-size:18px;">
                                <i class='bx bx-folder'></i>
                            </a>

                            <!-- Hapus -->
                            <a href="?hapus=<?= $s['id_siswa'] ?>" onclick="return confirm('Hapus data ini?')" title="Hapus" style="color: #dc2626; font-size:18px;">
                                <i class='bx bx-trash'></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL FORM -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h3 style="margin-bottom:15px; color:#333;">Tambah / Edit Siswa</h3>
        <form method="POST">
            <input type="hidden" name="mode" id="mode" value="tambah">

            <input name="nis" id="nis" placeholder="NIS" required>
            <input name="nama" id="nama" placeholder="Nama Lengkap" required>

            <select name="jk" id="jk" required>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>

            <select name="kelas" id="kelas" required>
                <option value="">-- Pilih Kelas --</option>
                <?php 
                mysqli_data_seek($dataKelas, 0); 
                while ($k = mysqli_fetch_assoc($dataKelas)) : 
                ?>
                    <option value="<?= $k['id_kelas'] ?>">
                        <?= $k['nama_kelas'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="status" id="status" required>
                <option value="aktif">Aktif</option>
                <option value="lulus">Lulus</option>
                <option value="keluar">Keluar</option>
            </select>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button class="btn" name="simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // === LOGIC SIDEBAR & DROPDOWN ===
    
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
        
        // Tutup Dropdown
        if (!userArea.contains(e.target)) {
            dropdown.classList.remove("show");
        }

        // Tutup Sidebar di Mobile
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById("sidebar");
            const toggleBtn = document.querySelector(".menu-toggle");
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
            }
        }
        
        // Tutup Modal jika klik di luar (Backdrop)
        const modal = document.getElementById("modal");
        if (e.target === modal) {
            closeModal();
        }
    }

    // === LOGIC MODAL SISWA (YANG SUDAH DIPERBAIKI) ===
    
    // Fungsi ini khusus untuk tombol TAMBAH (Mereset form)
    function openModal(){
        document.getElementById("mode").value = "tambah";
        document.getElementById("nis").readOnly = false;
        document.getElementById("nis").value = "";
        document.getElementById("nama").value = "";
        document.getElementById("kelas").value = "";
        document.getElementById("status").value = "aktif";
        
        document.getElementById("modal").classList.add("show");
    }
    
    function closeModal(){
        document.getElementById("modal").classList.remove("show");
    }
    
    // Fungsi ini khusus untuk tombol EDIT (Mengisi data)
    function editSiswa(nis, nama, jk, kelas, status){
        // Isi data form
        document.getElementById("mode").value = "edit";
        document.getElementById("nis").value = nis;
        document.getElementById("nis").readOnly = true; // NIS dikunci saat edit
        document.getElementById("nama").value = nama;
        document.getElementById("jk").value = jk;
        document.getElementById("kelas").value = kelas;
        document.getElementById("status").value = status;
        
        // PERBAIKAN: Langsung show modal, jangan panggil openModal() agar tidak ter-reset
        document.getElementById("modal").classList.add("show");
    }
</script>

</body>
</html>