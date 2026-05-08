<?php
session_start();
require('../fpdf/fpdf.php');

/* ================= SESSION ================= */
if (!isset($_SESSION['user'])) {
    header("Location: /sikes_rpl/login.html");
    exit;
}

/* ================= DATABASE ================= */
$conn = mysqli_connect("localhost", "root", "", "sikes_rpl");
if (!$conn) {
    die("Koneksi database gagal");
}

/* ================= FUNGSI KONVERSI BULAN ================= */
function namaBulan($angkaBulan) {
    $daftarBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $daftarBulan[(int)$angkaBulan];
}

/* ================= FILTER BULAN & TAHUN ================= */
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';

$where = "WHERE s.status NOT IN ('lulus','keluar')";

if ($tahun != "") {

    if ($bulan == "all") {

        $where .= " AND YEAR(dk.tanggal_kejadian) = " . (int)$tahun;

    } elseif ($bulan != "") {

        $bulanAngka = (int)$bulan;
        $tahun = (int)$tahun;

        $where .= "
        AND dk.tanggal_kejadian 
        BETWEEN '$tahun-" . str_pad($bulanAngka,2,'0',STR_PAD_LEFT) . "-01'
        AND LAST_DAY('$tahun-" . str_pad($bulanAngka,2,'0',STR_PAD_LEFT) . "-01')
        ";
    }
}

/* ================= QUERY DATA ================= */
$query = mysqli_query($conn, "
SELECT 
dk.tanggal_kejadian,
s.nis,
s.nama_siswa,
k.nama_kelas,
p.nama_pelanggaran,
dk.poin,
dk.bukti
FROM kedisiplinan dk
JOIN siswa s ON dk.id_siswa = s.id_siswa
JOIN kelas k ON s.id_kelas = k.id_kelas
JOIN pelanggaran p ON dk.id_pelanggaran = p.id_pelanggaran
$where
ORDER BY dk.tanggal_kejadian ASC
");

if (!$query) {
    die(mysqli_error($conn));
}

/* ================= CEK DATA ================= */
if (mysqli_num_rows($query) == 0) {
    echo "
    <script>
    alert('Maaf, data pada bulan dan tahun yang dipilih tidak tersedia');
    window.history.back();
    </script>
    ";
    exit;
}

/* ================= BUAT PDF ================= */
class PDF extends FPDF
{
    // Header profesional A4 Landscape
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
            $this->Image('../logo jurusan.png', 272, 5, 15);
        } elseif (file_exists('..//assets/image/logo rpl.jpeg')) {
            $this->Image('../assets/image/logo rpl.jpeg', 272, 5, 15);
        }
        
        // Judul laporan
        $this->SetY(20);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 6, 'LAPORAN DATA KEDISIPLINAN SISWA', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'SMK NEGERI 1 CIBINONG', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, 'Program Keahlian Rekayasa Perangkat Lunak', 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, 'Jl. Raya Karadenan No. 7, Kelurahan Karadenan, Kecamatan Cibinong, Kabupaten Bogor, Jawa Barat 16111', 0, 1, 'C');
        
        // Garis ganda
        $this->SetY(50);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY() + 1.5, 287, $this->GetY() + 1.5);
        
        $this->Ln(10);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, 'Dicetak: ' . date('d-m-Y H:i:s'), 0, 0, 'L');
        $this->Cell(0, 4, 'Halaman ' . $this->PageNo() . ' dari {nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }
    
    // Header Tabel FULL LEBAR untuk A4 Landscape
    function FullWidthTableHeader()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(30, 127, 109);
        $this->SetTextColor(255, 255, 255);
        $this->SetLineWidth(0.2);
        
        // Lebar total: 277mm (A4 Landscape 297mm - margin kiri 10 - margin kanan 10)
        $this->Cell(27, 9, 'Tanggal', 1, 0, 'C', true);
        $this->Cell(30, 9, 'NIS', 1, 0, 'C', true);
        $this->Cell(50, 9, 'Nama Siswa', 1, 0, 'C', true);
        $this->Cell(30, 9, 'Kelas', 1, 0, 'C', true);
        $this->Cell(90, 9, 'Pelanggaran', 1, 0, 'C', true);
        $this->Cell(20, 9, 'Poin', 1, 0, 'C', true);
        $this->Cell(30, 9, 'Bukti', 1, 0, 'C', true);
        $this->Ln();
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
    }
    
    // Baris tabel dengan zebra stripes
    function FullWidthTableRow($data, $isEven, $rowHeight)
    {
        if ($isEven) {
            $this->SetFillColor(245, 245, 245);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->Cell(27, $rowHeight, $data['tanggal'], 1, 0, 'C', true);
        $this->Cell(30, $rowHeight, $data['nis'], 1, 0, 'L', true);
        $this->Cell(50, $rowHeight, $data['nama_siswa'], 1, 0, 'L', true);
        $this->Cell(30, $rowHeight, $data['kelas'], 1, 0, 'L', true);
        $this->Cell(90, $rowHeight, $data['pelanggaran'], 1, 0, 'L', true);
        $this->Cell(20, $rowHeight, $data['poin'], 1, 0, 'C', true);
        
        // Kolom bukti
        $x = $this->GetX();
        $y = $this->GetY();
        $this->Cell(30, $rowHeight, '', 1, 0, 'C', true);
        
        if (!empty($data['bukti']) && file_exists("../uploads/bukti/".$data['bukti'])) {
            $this->Image(
                "../uploads/bukti/".$data['bukti'],
                $x + 3,
                $y + 1.5,
                24,
                $rowHeight - 3
            );
        }
        
        $this->Ln();
    }
}

// Buat objek PDF dengan A4 Landscape
$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetLeftMargin(10);
$pdf->SetRightMargin(10);

/* ================= INFORMASI FILTER ================= */
$pdf->SetY(68);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 248, 245);
$pdf->Cell(0, 8, 'PERIODE LAPORAN', 0, 1, 'C');

$infoFilter = "";
if ($tahun) {
    if ($bulan == "all") {
        $infoFilter = "Tahun: " . $tahun;
    } else {
        $namaBulan = namaBulan($bulan);
        $infoFilter = "Bulan: " . $namaBulan . " " . $tahun;
    }
}
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 248, 245);
$pdf->Cell(0, 7, $infoFilter, 0, 1, 'C', true);
$pdf->Ln(8);

/* ================= TABEL DATA ================= */
$pdf->FullWidthTableHeader();

/* ================= ISI DATA ================= */
$rowCount = 0;
$rowHeight = 14;

while ($row = mysqli_fetch_assoc($query)) {
    $data = [
        'tanggal' => date('d-m-Y', strtotime($row['tanggal_kejadian'])),
        'nis' => $row['nis'],
        'nama_siswa' => $row['nama_siswa'],
        'kelas' => $row['nama_kelas'],
        'pelanggaran' => $row['nama_pelanggaran'],
        'poin' => $row['poin'],
        'bukti' => $row['bukti']
    ];
    
    $pdf->FullWidthTableRow($data, $rowCount % 2 == 0, $rowHeight);
    $rowCount++;
}

/* ================= RINGKASAN ================= */
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(30, 127, 109);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, 'RINGKASAN LAPORAN', 0, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 9);

// Hitung total poin
$totalPoin = 0;
$totalPelanggaran = $rowCount;
mysqli_data_seek($query, 0);
while ($row = mysqli_fetch_assoc($query)) {
    $totalPoin += $row['poin'];
}

$pdf->Ln(5);
$pdf->Cell(140, 7, 'Total Pelanggaran: ' . $totalPelanggaran . ' kasus', 0, 0, 'L');
$pdf->Cell(0, 7, 'Total Poin: ' . $totalPoin, 0, 1, 'L');
$pdf->Cell(140, 7, 'Dicetak oleh: Petugas Kedisiplinan', 0, 1, 'L');

// Tanda tangan
$pdf->Ln(10);
$pdf->SetX(190);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(80, 6, 'Mengetahui,', 0, 1, 'C');
$pdf->SetX(190);
$pdf->Cell(80, 6, 'Perwalian Siswa Jurusan RPL', 0, 1, 'C');
$pdf->Ln(15);
$pdf->SetX(210);
$pdf->Cell(40, 6, '(____________________)', 0, 1, 'C');
$pdf->SetX(210);
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(40, 4, 'NIP. _________________', 0, 1, 'C');

/* ================= OUTPUT ================= */
$pdf->Output('D', 'Laporan_Kedisiplinan_' . date('Ymd_His') . '.pdf');
?>