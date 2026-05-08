<?php
$conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    echo json_encode([]);
    exit;
}

$id_kelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;
if ($id_kelas === 0) {
    echo json_encode([]);
    exit;
}

$q = mysqli_query($conn, "
    SELECT id_siswa, nis, nama_siswa
    FROM siswa
    WHERE id_kelas = $id_kelas
    AND status = 'aktif'
    ORDER BY nama_siswa ASC
");

$data = [];
while ($s = mysqli_fetch_assoc($q)) {
    $data[] = $s;
}

header('Content-Type: application/json');
echo json_encode($data);