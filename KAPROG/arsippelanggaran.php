<?php
// ================= KONEKSI DATABASE =================
$conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ================= AMBIL NIS =================
$nis = $_GET['nis'] ?? '';
if ($nis == '') {
    die("NIS tidak ditemukan di URL");
}

// ================= DATA SISWA =================
$qSiswa = mysqli_query($conn, "
    SELECT 
        s.id_siswa,
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.poin_total,
        s.status,
        k.nama_kelas
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.nis = '$nis'
");

if (!$qSiswa) {
    die("QUERY SISWA ERROR: " . mysqli_error($conn));
}

$dataSiswa = mysqli_fetch_assoc($qSiswa);
if (!$dataSiswa) {
    die("Data siswa dengan NIS $nis tidak ditemukan");
}

// ================= TOTAL POIN =================
$qPoin = mysqli_query($conn, "
    SELECT COALESCE(SUM(kd.poin),0) AS total_poin
    FROM kedisiplinan kd
    JOIN siswa s ON kd.id_siswa = s.id_siswa
    WHERE s.nis = '$nis'
");

if (!$qPoin) {
    die("QUERY POIN ERROR: " . mysqli_error($conn));
}

$dataPoin = mysqli_fetch_assoc($qPoin);

// ================= RIWAYAT PELANGGARAN =================
$qRiwayat = mysqli_query($conn, "
    SELECT 
        kd.tanggal_kejadian,
        p.nama_pelanggaran,
        p.kategori,
        p.poin,
        kd.bukti
    FROM kedisiplinan kd
    JOIN pelanggaran p ON kd.id_pelanggaran = p.id_pelanggaran
    JOIN siswa s ON kd.id_siswa = s.id_siswa
    WHERE s.nis = '$nis'
    ORDER BY kd.tanggal_kejadian DESC
");

if (!$qRiwayat) {
    die("QUERY RIWAYAT ERROR: " . mysqli_error($conn));
}

$totalRiwayat = mysqli_num_rows($qRiwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Arsip Pelanggaran Siswa</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/css/arsip.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel ="icon" href="../assets/image/logo rpl.jpeg">
</head>

<body>
<button class="menu-toggle" onclick="toggleSidebar()">☰</button>
<!-- MAIN -->
<div class="main">

<div class="topbar">
    <h3>Arsip pelanggaran siswa</h3>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin:15px 25px;">
    <a href="datasiswakaprog.php" class="back">← Kembali Ke Data Siswa</a>

    <a href="export_arsip_kaprog.php?nis=<?= $dataSiswa['nis']; ?>" 
       class="btn-export">
       Export PDF
    </a>
</div>

<div class="card">
    <h2><?= $dataSiswa['nama_siswa']; ?></h2>
    <small>
        NIS: <?= $dataSiswa['nis']; ?> |
        Kelas: <?= $dataSiswa['nama_kelas']; ?>
    </small>

    <div class="poin"><?= $dataPoin['total_poin'] ?? 0; ?></div>
    <small>Total Poin Pelanggaran</small>
</div>

<div class="card">
    <h3>Riwayat Pelanggaran</h3>
    <p>Total <?= $totalRiwayat; ?> pelanggaran tercatat</p>

    <?php if ($totalRiwayat > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($qRiwayat)): ?>
<?php
    $pathBukti = "../uploads/bukti/" . $row['bukti'];
    $ext = strtolower(pathinfo($row['bukti'], PATHINFO_EXTENSION));
?>
<div class="riwayat">

    <?php if (!empty($row['bukti']) && file_exists($pathBukti)): ?>

        <?php if (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
        <img src="<?= $pathBukti; ?>" alt="Bukti" class="img-bukti" onclick="lihatGambar(this.src)">

        <?php elseif (in_array($ext, ['mp4','webm'])): ?>
            <video width="70" height="70" controls>
                <source src="<?= $pathBukti; ?>" type="video/<?= $ext; ?>">
            </video>
        <?php endif; ?>

    <?php else: ?>
        <img src="../assets/no-image.png" alt="Tidak ada bukti">
    <?php endif; ?>

    <div>
        <strong><?= $row['nama_pelanggaran']; ?></strong><br>
        <small><?= date('d F Y', strtotime($row['tanggal_kejadian'])); ?></small>
    </div>

    <div style="margin-left:auto">
        <span class="tag <?= strtolower($row['kategori']); ?>">
            <?= $row['kategori']; ?>
        </span>
        <span class="tag berat">
            <?= $row['poin']; ?> Poin
        </span>
    </div>
</div>
<?php endwhile; ?>

    <?php else: ?>
        <p>Tidak ada riwayat pelanggaran</p>
    <?php endif; ?>
</div>


</div>
<!-- MODAL GAMBAR -->
<div id="modalGambar" class="modal">
    <span class="close" onclick="tutupGambar()">&times;</span>
    <img class="modal-content" id="gambarBesar">
</div>
<script src="../assets/js/arsip.js"></script>
</body>
</html>
