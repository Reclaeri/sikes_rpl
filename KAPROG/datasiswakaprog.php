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

/* ================= LOGIKA SEARCH & SORTING ================= */
// 1. Ambil input pencarian (NIS) dan sanitasi untuk keamanan
 $search_nis = isset($_GET['search_nis']) ? mysqli_real_escape_string($conn, $_GET['search_nis']) : '';

// 2. Cek parameter URL untuk urutan kelas (asc = X ke XII, desc = XII ke X)
 $sort_order = isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'DESC' : 'ASC';

// 3. Bangun kondisi WHERE dinamis
// Kita mulai dengan filter status aktif
 $where_clause = "WHERE s.status = 'aktif'";

// Jika ada input pencarian, tambahkan filter NIS
if (!empty($search_nis)) {
    $where_clause .= " AND s.nis LIKE '%$search_nis%'";
}

/* ================= QUERY DATA DASHBOARD ================= */

// 1. Hitung Total Siswa Aktif
 $qSiswa = mysqli_query($conn, "SELECT COUNT(*) AS total FROM siswa WHERE status = 'aktif'");
 $totalSiswa = mysqli_fetch_assoc($qSiswa)['total'] ?? 0;

// 2. Hitung Total Pelanggaran
 $qPelanggaran = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM kedisiplinan kd
    JOIN siswa s ON kd.id_siswa = s.id_siswa
    WHERE s.status = 'aktif'
");
 $totalPelanggaran = mysqli_fetch_assoc($qPelanggaran)['total'] ?? 0;

// 3. Pelanggaran 7 Hari Terakhir (Untuk Statistik)
 $q7Hari = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM kedisiplinan kd
    JOIN siswa s ON kd.id_siswa = s.id_siswa
    WHERE kd.tanggal_kejadian >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND s.status = 'aktif'
");
 $pelanggaran7Hari = mysqli_fetch_assoc($q7Hari)['total'] ?? 0;

// 4. Query Tabel Utama: Siswa dengan Total Poin & Custom Sort Kelas + Filter Search
// Menggunakan variabel $where_clause yang sudah dibuat di atas
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
    $where_clause
    GROUP BY s.id_siswa
    ORDER BY
        CASE k.nama_kelas
            WHEN 'X RPL 1' THEN 1
            WHEN 'X RPL 2' THEN 2
            WHEN 'XI RPL 1' THEN 3
            WHEN 'XI RPL 2' THEN 4
            WHEN 'XII RPL 1' THEN 5
            WHEN 'XII RPL 2' THEN 6
            ELSE 7
        END $sort_order, s.nama_siswa ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa Kaprog | SI Kedisiplinan</title>
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
<!-- ===== SIDEBAR ===== -->
        <div class="sidebar" id="sidebar">
        <div class="logo">
        <img src="../assets/image/logo rpl.jpeg">
        <div><b>SIKES RPL</b><br><small>SMKN 1 Cibinong Jurusan RPL</small></div>
    </div>   
        <div class="menu">
            <a href="dashboardkaprog.php">
                <i class='bx bx-grid-alt'></i> Dashboard
            </a>
            <a href="datasiswakaprog.php"class="active">
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
            Monitoring poin kedisiplinan siswa jurusan RPL
        </p>

        <div class="table-box">
            <div class="table-header">
                <h3>Rekapitulasi Poin Siswa</h3>
                
                <!-- AREA TOOLBAR (SEARCH & SORT) -->
                <div class="toolbar-container">
                    <!-- Menggabungkan Search dan Sort dalam satu form agar parameter tidak hilang saat submit -->
                    <form method="GET" class="toolbar-form">
                        
                        <!-- Input Search NIS -->
                        <input type="text" 
                               name="search_nis" 
                               placeholder="Cari NIS..." 
                               value="<?= htmlspecialchars($search_nis) ?>"
                               onkeyup="this.value = this.value.toUpperCase();">
                        
                        <!-- Button Reset (Hanya muncul jika sedang mencari) -->
                        <?php if(!empty($search_nis)): ?>
                        <a href="datasiswakaprog.php" class="btn-reset" title="Reset Pencarian">
                            <i class='bx bx-x'></i>
                        </a>
                        <?php endif; ?>

                        <!-- Dropdown Sort Kelas -->
                        <select name="sort" onchange="this.form.submit()">
                            <option value="asc" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>
                                Urutkan: X ke XII
                            </option>
                            <option value="desc" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>
                                Urutkan: XII ke X
                            </option>
                        </select>
                        
                        <!-- Icon Search sebagai submit button alternatif jika user ingin klik -->
                        <button type="submit" style="border:none; background:none; cursor:pointer; color:#555;">
                            <i class='bx bx-search' style="font-size: 20px;"></i>
                        </button>

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
                                        <!-- Link Arsip Pelanggaran -->
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
                                        Data siswa dengan NIS "<?= htmlspecialchars($search_nis) ?>" tidak ditemukan.
                                    <?php else: ?>
                                        Tidak ada data siswa.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
        if (!userArea.contains(e.target)) {
            dropdown.classList.remove("show");
        }

        // Tutup Sidebar di Mobile jika klik di luar sidebar & tombol toggle
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById("sidebar");
            const toggleBtn = document.querySelector(".menu-toggle");
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
            }
        }
    }
</script>

</body>
</html>