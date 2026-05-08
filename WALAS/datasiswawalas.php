<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /sikes_rpl/login.html");
    exit;
}
$user = $_SESSION['user'];

/* ================= KONEKSI DATABASE ================= */
$conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

/* ================= LOGIKA AMBIL KELAS WALI ================= */
// 1. Ambil ID Guru dari Login
$id_login = $user['id_login'] ?? 0;
$qGuru = mysqli_query($conn, "SELECT id_guru FROM guru WHERE id_login = '$id_login'");
$dataGuru = mysqli_fetch_assoc($qGuru);
$id_guru = $dataGuru['id_guru'] ?? 0;

// 2. Ambil Kelas yang diwalikan
$qKelas = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas WHERE id_wali = '$id_guru'");
$dataKelas = mysqli_fetch_assoc($qKelas);
$id_kelas = $dataKelas['id_kelas'] ?? 0;
$nama_kelas = $dataKelas['nama_kelas'] ?? "Tidak Diketahui";

// Cek keamanan: Jika bukan wali kelas
if ($id_kelas == 0) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h2>Akses Ditolak</h2>
            <p>Anda belum ditetapkan sebagai Wali Kelas.</p>
            <a href='../logout.php'>Logout</a>
         </div>");
}

/* ================= LOGIKA SEARCH (NIS) ================= */
$search_nis = isset($_GET['search_nis']) ? mysqli_real_escape_string($conn, $_GET['search_nis']) : '';

/* ================= QUERY DATA (FILTER PER KELAS) ================= */

// 1. Hitung Total Siswa (Di kelas walas)
$qSiswa = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM siswa 
    WHERE status = 'aktif' 
    AND id_kelas = '$id_kelas'
");
$totalSiswa = mysqli_fetch_assoc($qSiswa)['total'] ?? 0;

// 2. Hitung Total Pelanggaran (Di kelas walas)
$qPelanggaran = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM kedisiplinan kd
    JOIN siswa s ON kd.id_siswa = s.id_siswa
    WHERE s.status = 'aktif'
    AND s.id_kelas = '$id_kelas'
");
$totalPelanggaran = mysqli_fetch_assoc($qPelanggaran)['total'] ?? 0;

// 3. Pelanggaran 7 Hari Terakhir (Di kelas walas)
$q7Hari = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM kedisiplinan kd
    JOIN siswa s ON kd.id_siswa = s.id_siswa
    WHERE kd.tanggal_kejadian >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND s.status = 'aktif'
    AND s.id_kelas = '$id_kelas'
");
$pelanggaran7Hari = mysqli_fetch_assoc($q7Hari)['total'] ?? 0;

// 4. Query Tabel Utama: Siswa Wali Kelas dengan Poin & Search NIS
$qDataSiswa = mysqli_query($conn, "
    SELECT 
        s.id_siswa,
        s.nis,
        s.nama_siswa,
        k.nama_kelas,
        COALESCE(SUM(kd.poin), 0) as total_poin
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN kedisiplinan kd ON s.id_siswa = kd.id_siswa
    WHERE s.status = 'aktif' 
    AND s.id_kelas = '$id_kelas'
    " . (!empty($search_nis) ? " AND s.nis LIKE '%$search_nis%'" : "") . "
    GROUP BY s.id_siswa
    ORDER BY s.nama_siswa ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa Walas | SI Kedisiplinan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/siswa.css">
    <link rel ="icon" href="../assets/image/logo rpl.jpeg">

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
        <a href="dashboardwalas.php">
            <i class='bx bx-grid-alt'></i> Dashboard
        </a>
        <a href="datasiswawalas.php" class="active">
            <i class='bx bx-user'></i> Data Siswa
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <h3>Dashboard</h3>
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

    <!-- Content Dashboard -->
    <div class="content">
        <h2 style="margin-top: 20px;">
            Selamat Datang, <?= htmlspecialchars($user['username']) ?>
        </h2>
        <p style="margin-bottom: 25px; opacity: 0.9;">
            Monitoring poin kedisiplinan siswa kelas <?= htmlspecialchars($nama_kelas) ?>
        </p>
    </div>

    
    <!-- TABEL SISWA -->
    <div class="table-box">
        <div class="table-header">
            <div>
                <h3>Rekap Poin Siswa</h3>
                <div class="sub-header-class">Kelas: <b><?= htmlspecialchars($nama_kelas) ?></b></div>
            </div>
            
            <!-- AREA TOOLBAR SEARCH YANG SUDAH DIPERBAIKI -->
            <div class="toolbar-container">
                <form method="GET" class="toolbar-form" id="searchForm">
                    <div class="search-wrapper">
                        <i class='bx bx-search search-icon'></i>
                        <input type="text" 
                               name="search_nis" 
                               id="search_nis_input"
                               placeholder="Cari NIS..." 
                               value="<?= htmlspecialchars($search_nis) ?>"
                               onkeyup="this.value = this.value.toUpperCase();">
                        <?php if(!empty($search_nis)): ?>
                            <a href="datasiswawalas.php" class="btn-reset-search" title="Reset Pencarian">
                                <i class='bx bx-x'></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn-search-submit">Cari</button>
                </form>
            </div>
        </div>
        
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Total Poin</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($qDataSiswa && mysqli_num_rows($qDataSiswa) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($qDataSiswa)): 
                            // Logika warna poin
                            $poinClass = 'poin-0';
                            if($row['total_poin'] > 0 && $row['total_poin'] < 20) $poinClass = 'poin-mid';
                            if($row['total_poin'] >= 20) $poinClass = 'poin-high';
                        ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($row['nama_siswa']) ?>
                                    <br><small style="color:#888; font-size:11px;"><?= $row['nis'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                <td>
                                    <span class="poin-badge <?= $poinClass ?>">
                                        <?= $row['total_poin'] ?> Poin
                                    </span>
                                </td>
                                <td>
                                    <a href="arsippelanggaran.php?nis=<?= $row['nis'] ?>" class="btn-arsip">
                                        <i class='bx bx-folder'></i> Arsip
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 20px;">
                                <?php if(!empty($search_nis)): ?>
                                    Data siswa dengan NIS "<?= htmlspecialchars($search_nis) ?>" tidak ditemukan di kelas <?= htmlspecialchars($nama_kelas) ?>.
                                <?php else: ?>
                                    Belum ada data siswa di kelas <?= htmlspecialchars($nama_kelas) ?>.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
        
        // Tutup Dropdown jika klik di luar area user
        if (userArea && !userArea.contains(e.target)) {
            dropdown.classList.remove("show");
        }

        // Tutup Sidebar di Mobile jika klik di luar sidebar & tombol toggle
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById("sidebar");
            const toggleBtn = document.querySelector(".menu-toggle");
            if (sidebar && toggleBtn && !sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
            }
        }
    }
</script>

</body>
</html>