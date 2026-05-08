<?php
session_start();
require('../fpdf/fpdf.php');

/* ================= SESSION ================= */
if (!isset($_SESSION['user'])) {
    header("Location: /sikes_rpl/login.html");
    exit;
}

$conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    die("Koneksi database gagal");
}

$nis = $_GET['nis'] ?? '';

if ($nis == '') {
    die("NIS tidak ditemukan");
}

/* ================= DATA SISWA ================= */
$qSiswa = mysqli_query($conn, "
    SELECT s.nama_siswa, s.nis, k.nama_kelas, s.poin_total
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.nis = '$nis' AND s.status = 'aktif'
");

$dataSiswa = mysqli_fetch_assoc($qSiswa);

if (!$dataSiswa) {
    die("Data siswa tidak ditemukan");
}

/* ================= RIWAYAT PELANGGARAN ================= */
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

/* ================= HITUNG STATISTIK ================= */
$totalPelanggaran = mysqli_num_rows($qRiwayat);

// Hitung berdasarkan kategori
$kategoriRingan = 0;
$kategoriSedang = 0;
$kategoriBerat = 0;

mysqli_data_seek($qRiwayat, 0);
while ($row = mysqli_fetch_assoc($qRiwayat)) {
    switch ($row['kategori']) {
        case 'Ringan':
            $kategoriRingan++;
            break;
        case 'Sedang':
            $kategoriSedang++;
            break;
        case 'Berat':
            $kategoriBerat++;
            break;
    }
}
mysqli_data_seek($qRiwayat, 0);

/* ================= KELAS PDF PROFESIONAL ================= */
class PDF extends FPDF
{
    // Header profesional
    function Header()
    {
        // Logo kiri (Sekolah)
        if (file_exists('../logo sekolah.png')) {
            $this->Image('../logo sekolah.png', 10, 5, 20);
        } elseif (file_exists('../assets/image/logo kamvak.png')) {
            $this->Image('../assets/image/logo kamvak.png', 10, 5, 20);
        }
        
        // Logo kanan (Jurusan)
        if (file_exists('../logo jurusan.png')) {
            $this->Image('../logo jurusan.png', 180, 5, 15);
        } elseif (file_exists('../assets/image/logo rpl.jpeg')) {
            $this->Image('../assets/image/logo rpl.jpeg', 180, 5, 15);
        }
        
        // Judul Header
        $this->SetY(12);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'SMK NEGERI 1 CIBINONG', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, 'Program Keahlian Rekayasa Perangkat Lunak', 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, 'Jl. Raya Karadenan No. 7, Kelurahan Karadenan, Kecamatan Cibinong, Kabupaten Bogor', 0, 1, 'C');
        
        // Garis pembatas
        $this->SetY(30);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY() + 1.5, 200, $this->GetY() + 1.5);
        
        $this->Ln(8);
    }
    
    // Footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, 'Dicetak: ' . date('d-m-Y H:i:s'), 0, 0, 'L');
        $this->Cell(0, 4, 'Halaman ' . $this->PageNo() . ' dari {nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }
    
    // Kartu Info Siswa (FULL LEBAR)
    function InfoSiswaCard($dataSiswa, $totalPoin, $totalPelanggaran)
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(30, 127, 109);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, 'DATA SISWA', 0, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 10);
        
        // Box informasi FULL LEBAR
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(200, 200, 200);
        
        // Lebar kolom label dan value
        $labelWidth = 35;
        $valueWidth = 155; // 190 - 35 (dengan margin 10 kiri-kanan)
        
        // Nama
        $this->SetFillColor(245, 245, 245);
        $this->Cell($labelWidth, 7, 'Nama Siswa', 1, 0, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->Cell($valueWidth, 7, ': ' . $dataSiswa['nama_siswa'], 1, 1, 'L', true);
        
        // NIS
        $this->SetFillColor(245, 245, 245);
        $this->Cell($labelWidth, 7, 'NIS', 1, 0, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->Cell($valueWidth, 7, ': ' . $dataSiswa['nis'], 1, 1, 'L', true);
        
        // Kelas
        $this->SetFillColor(245, 245, 245);
        $this->Cell($labelWidth, 7, 'Kelas', 1, 0, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->Cell($valueWidth, 7, ': ' . $dataSiswa['nama_kelas'], 1, 1, 'L', true);
        
        // Total Poin
        $this->SetFillColor(245, 245, 245);
        $this->Cell($labelWidth, 7, 'Total Poin', 1, 0, 'L', true);
        $this->SetFillColor(255, 255, 255);
        
        // Warna berbeda untuk poin tinggi
        if ($totalPoin >= 50) {
            $this->SetTextColor(220, 20, 60);
        } elseif ($totalPoin >= 25) {
            $this->SetTextColor(255, 140, 0);
        } else {
            $this->SetTextColor(34, 139, 34);
        }
        $this->Cell($valueWidth, 7, ': ' . $totalPoin . ' Poin', 1, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        
        // Total Pelanggaran
        $this->SetFillColor(245, 245, 245);
        $this->Cell($labelWidth, 7, 'Total Pelanggaran', 1, 0, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->Cell($valueWidth, 7, ': ' . $totalPelanggaran . ' Kali', 1, 1, 'L', true);
        
        $this->Ln(6);
    }
    
    // Statistik Kategori (RAPI - 3 kolom sejajar)
    function StatistikKategori($ringan, $sedang, $berat)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(240, 248, 245);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 7, 'STATISTIK BERDASARKAN KATEGORI', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 9);
        
        // Lebar masing-masing kolom (sepertiga dari lebar available)
        $colWidth = 63;
        
        // Header kategori
        $this->SetFillColor(46, 204, 113);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($colWidth, 7, 'RINGAN', 1, 0, 'C', true);
        
        $this->SetFillColor(241, 196, 15);
        $this->Cell($colWidth, 7, 'SEDANG', 1, 0, 'C', true);
        
        $this->SetFillColor(231, 76, 60);
        $this->Cell($colWidth, 7, 'BERAT', 1, 1, 'C', true);
        
        // Jumlah pelanggaran
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 11);
        
        $this->Cell($colWidth, 8, $ringan . ' Kali', 1, 0, 'C', true);
        $this->Cell($colWidth, 8, $sedang . ' Kali', 1, 0, 'C', true);
        $this->Cell($colWidth, 8, $berat . ' Kali', 1, 1, 'C', true);
        
        $this->SetFont('Arial', '', 9);
        $this->Ln(5);
    }
    
    // Header Tabel FULL LEBAR
    function TableHeader()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(30, 127, 109);
        $this->SetTextColor(255, 255, 255);
        $this->SetLineWidth(0.2);
        
        // Lebar total: 190mm (dengan margin 10 kiri-kanan)
        $this->Cell(25, 8, 'Tanggal', 1, 0, 'C', true);
        $this->Cell(70, 8, 'Pelanggaran', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Kategori', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Poin', 1, 0, 'C', true);
        $this->Cell(55, 8, 'Bukti', 1, 0, 'C', true);
        $this->Ln();
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
    }
    
    // Baris Tabel FULL LEBAR dengan Zebra
    function TableRow($data, $isEven, $rowHeight)
    {
        // Warna kategori untuk kolom kategori
        $kategoriColor = [
            'Ringan' => [46, 204, 113],
            'Sedang' => [241, 196, 15],
            'Berat' => [231, 76, 60]
        ];
        
        $this->SetFillColor($isEven ? 245 : 255, $isEven ? 245 : 255, $isEven ? 245 : 255);
        
        $this->Cell(25, $rowHeight, $data['tanggal'], 1, 0, 'C', true);
        $this->Cell(70, $rowHeight, $data['pelanggaran'], 1, 0, 'L', true);
        
        // Kolom Kategori dengan warna
        $xCat = $this->GetX();
        $yCat = $this->GetY();
        $this->Cell(25, $rowHeight, '', 1, 0, 'C', true);
        
        if (isset($kategoriColor[$data['kategori']])) {
            $this->SetTextColor(255, 255, 255);
            $this->SetFillColor($kategoriColor[$data['kategori']][0], $kategoriColor[$data['kategori']][1], $kategoriColor[$data['kategori']][2]);
            $this->SetXY($xCat, $yCat);
            $this->Cell(25, $rowHeight, $data['kategori'], 1, 0, 'C', true);
            $this->SetTextColor(0, 0, 0);
            $this->SetFillColor($isEven ? 245 : 255, $isEven ? 245 : 255, $isEven ? 245 : 255);
            $this->SetXY($xCat + 25, $yCat);
        }
        
        $this->Cell(15, $rowHeight, $data['poin'], 1, 0, 'C', true);
        
        // Kolom Bukti FULL
        $x = $this->GetX();
        $y = $this->GetY();
        $this->Cell(55, $rowHeight, '', 1, 0, 'C', true);
        
        if (!empty($data['bukti']) && file_exists("../uploads/bukti/" . $data['bukti'])) {
            $this->Image(
                "../uploads/bukti/" . $data['bukti'],
                $x + 15,
                $y + 1.5,
                25,
                $rowHeight - 3
            );
        }
        
        $this->Ln();
    }
}

/* ================= BUAT PDF ================= */
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);
$pdf->SetLeftMargin(10);
$pdf->SetRightMargin(10);

/* ================= JUDUL UTAMA ================= */
$pdf->SetY(45);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 7, 'LAPORAN ARSIP PELANGGARAN SISWA', 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'Periode Seluruh Data Pelanggaran', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(5);

/* ================= INFO SISWA ================= */
$pdf->InfoSiswaCard($dataSiswa, $dataSiswa['poin_total'], $totalPelanggaran);

/* ================= STATISTIK ================= */
if ($totalPelanggaran > 0) {
    $pdf->StatistikKategori($kategoriRingan, $kategoriSedang, $kategoriBerat);
}

/* ================= TABEL RIWAYAT ================= */
if ($totalPelanggaran > 0) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(30, 127, 109);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'RIWAYAT PELANGGARAN', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    $pdf->TableHeader();
    
    $rowCount = 0;
    $rowHeight = 16;
    
    while ($row = mysqli_fetch_assoc($qRiwayat)) {
        $data = [
            'tanggal' => date('d-m-Y', strtotime($row['tanggal_kejadian'])),
            'pelanggaran' => $row['nama_pelanggaran'],
            'kategori' => $row['kategori'],
            'poin' => $row['poin'],
            'bukti' => $row['bukti']
        ];
        
        $pdf->TableRow($data, $rowCount % 2 == 0, $rowHeight);
        $rowCount++;
    }
} else {
    // Jika tidak ada pelanggaran
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(34, 139, 34);
    $pdf->Cell(0, 10, 'SISWA INI BELUM PERNAH MELAKUKAN PELANGGARAN', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 20);
    $pdf->Cell(0, 10, '★', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Siswa Teladan', 0, 1, 'C');
}

/* ================= CATATAN KAKI ================= */
$pdf->Ln(8);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 4, 'Dokumen ini adalah bukti resmi pelanggaran yang tercatat di sistem SIKES RPL', 0, 1, 'C');
$pdf->Cell(0, 4, 'Terhitung sejak pertama kali siswa terdaftar hingga saat ini.', 0, 1, 'C');

/* ================= TANDA TANGAN ================= */
$pdf->Ln(8);
$pdf->SetX(130);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(60, 6, 'Mengetahui,', 0, 1, 'C');
$pdf->SetX(130);
$pdf->Cell(60, 6, 'Petugas Kedisiplinan', 0, 1, 'C');
$pdf->Ln(12);
$pdf->SetX(140);
$pdf->Cell(40, 5, '(____________________)', 0, 1, 'C');

/* ================= OUTPUT ================= */
$namaBersih = preg_replace('/[^A-Za-z0-9\- ]/', '', $dataSiswa['nama_siswa']);
$namaFile = "Laporan_Pelanggaran_" . $namaBersih . "_" . date('Ymd') . ".pdf";

$pdf->Output('I', $namaFile);
exit;
?>