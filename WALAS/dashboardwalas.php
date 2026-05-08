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

/* ================= AMBIL KELAS PERWALIAN ================= */

// ambil id_login dari session
$id_login = $user['id_login'] ?? 0;

// ambil id_guru
$qGuru = mysqli_query($conn, "SELECT id_guru FROM guru WHERE id_login = '$id_login'");
$dataGuru = mysqli_fetch_assoc($qGuru);
$id_guru = $dataGuru['id_guru'] ?? 0;

// ambil id_kelas yang dia wali
$qKelasWali = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE id_wali = '$id_guru'");
$dataKelas = mysqli_fetch_assoc($qKelasWali);
$id_kelas = $dataKelas['id_kelas'] ?? 0;

// kalau bukan wali kelas
if ($id_kelas == 0) {
    die("Anda bukan wali kelas!");
}

/* ================= DATA DASHBOARD ================= */

// total siswa (per kelas wali)
$qSiswa = mysqli_query($conn, "
SELECT COUNT(*) AS total 
FROM siswa 
WHERE status = 'aktif' 
AND id_kelas = '$id_kelas'
");
$totalSiswa = mysqli_fetch_assoc($qSiswa)['total'] ?? 0;


// total pelanggaran (kelas wali)
$qPelanggaran = mysqli_query($conn, "
SELECT COUNT(*) AS total 
FROM pelanggaran
");
$totalPelanggaran = mysqli_fetch_assoc($qPelanggaran)['total'] ?? 0;

// pelanggaran 7 hari terakhir (kelas wali)
$q7Hari = mysqli_query($conn, "
SELECT COUNT(*) AS total 
FROM kedisiplinan kd
JOIN siswa s ON kd.id_siswa = s.id_siswa
WHERE kd.tanggal_kejadian >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
AND s.id_kelas = '$id_kelas'
");
$pelanggaran7Hari = mysqli_fetch_assoc($q7Hari)['total'] ?? 0;

// tabel pelanggaran terbaru (kelas wali)
$sqlTerbaru = "
SELECT s.nama_siswa, k.nama_kelas, p.nama_pelanggaran, kd.poin, kd.tanggal_kejadian 
FROM kedisiplinan kd
JOIN siswa s ON kd.id_siswa = s.id_siswa
JOIN kelas k ON s.id_kelas = k.id_kelas
JOIN pelanggaran p ON kd.id_pelanggaran = p.id_pelanggaran
WHERE s.status = 'aktif'
AND s.id_kelas = '$id_kelas'
ORDER BY kd.tanggal_kejadian DESC 
LIMIT 5
";
$qTerbaru = mysqli_query($conn, $sqlTerbaru);
?> 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin | SI Kedisiplinan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

    <!-- Tombol Hamburger (Muncul di HP) -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class='bx bx-menu'></i>
    </button>

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar" id="sidebar">
         <div class="logo">
        <img src="../assets/image/logo rpl.jpeg">
        <div><b>SIKES RPL</b><br><small>SMKN 1 Cibinong Jurusan RPL</small></div>
    </div>
        <div class="menu">
            <a href="dashboardadmin.php" class="active">
                <i class='bx bx-grid-alt'></i> Dashboard
            </a>
            <a href="datasiswawalas.php">
                <i class='bx bx-user'></i> Data Siswa
            </a>
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main">
        
        <!-- Topbar -->
        <div class="topbar">
            <h3 style="color: #333;">Dashboard</h3>
            
            <!-- User Dropdown (TRIGGERED BY CLICK) -->
            <div class="user" onclick="toggleUserDropdown(event)">
                <div class="avatar">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <b style="color: #333; display: block; font-size: 14px;"><?= htmlspecialchars($user['username']) ?></b>
                    <small style="color: #666;"><?= htmlspecialchars($user['role']) ?></small>
                </div>

                <!-- Dropdown Menu -->
                <div class="dropdown" id="userDropdown">
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
                Ringkasan sistem kedisiplinan siswa
            </p>

            <!-- Cards -->
            <div class="cards">
                <div class="card">
                    <div class="card-info">
                        <h4>Total Siswa</h4>
                        <h1><?= $totalSiswa ?></h1>
                    </div>
                    <div class="icon blue"><i class='bx bx-user'></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h4>Total Pelanggaran</h4>
                        <h1><?= $totalPelanggaran ?></h1>
                    </div>
                    <div class="icon orange"><i class='bx bx-error'></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h4>7 Hari Terakhir</h4>
                        <h1><?= $pelanggaran7Hari ?></h1>
                    </div>
                    <div class="icon red"><i class='bx bx-time'></i></div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-box">
                <h3>Pelanggaran Terbaru</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Pelanggaran</th>
                                <th>Poin</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($qTerbaru && mysqli_num_rows($qTerbaru) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($qTerbaru)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                        <td><span style="font-weight:bold; color: #d9534f;"><?= $row['poin'] ?> Poin</span></td>
                                        <td><?= date('d M Y', strtotime($row['tanggal_kejadian'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 20px;">Belum ada data pelanggaran.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        // 1. Fungsi Toggle Sidebar (Mobile)
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("active");
        }

        // 2. Fungsi Toggle User Dropdown (KLIK)
        function toggleUserDropdown(event) {
            event.stopPropagation(); 
            
            const dropdown = document.getElementById("userDropdown");
            dropdown.classList.toggle("show");
        }

        // 3. Menutup Dropdown jika klik di luar area user
        // DAN menutup Sidebar di mobile jika klik di luar sidebar
        window.onclick = function(event) {
            const dropdown = document.getElementById("userDropdown");
            const userArea = document.querySelector(".user");
            
            // Tutup Dropdown
            if (!userArea.contains(event.target)) {
                dropdown.classList.remove("show");
            }

            // Tutup Sidebar Mobile (Hanya jika di lebar mobile)
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById("sidebar");
                const toggleBtn = document.querySelector(".menu-toggle");
                
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove("active");
                }
            }
        }
    </script>

</body>
</html>