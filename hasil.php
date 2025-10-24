<?php
include 'header.php';
require 'criteria.php';
require 'alternatives.php';

// Mendapatkan daftar kriteria
$criterias = getCriterias();

// Mendapatkan daftar alternatif
$alternatives = getAlternatives();

// Validasi data
if (empty($criterias) || empty($alternatives)) {
    // Tampilkan pesan error dan berhenti jika data kosong
    echo "<div class='alert alert-danger'>Data Kriteria atau Alternatif belum lengkap. Harap periksa database.</div>";
    include 'footer.php';
    exit;
}

// Langkah pertama: Menjumlahkan nilai bobot
$totalWeight = 0;
foreach ($criterias as $criteria) {
    $totalWeight += $criteria['weight'];
}

// Langkah kedua: Menghitung bobot kepentingan (Wj)
$criteriaWeights = array();
foreach ($criterias as $criteria) {
    // Pengecekan totalWeight > 0 untuk menghindari Division by Zero
    $criteriaWeights[$criteria['id']] = ($totalWeight > 0) ? ($criteria['weight'] / $totalWeight) : 0;
}

// Langkah ketiga: Menghitung nilai Vektor S
$sVector = array();
foreach ($alternatives as $alternative) {
    $s = 1;
    $valueIndex = 1; // Mulai dari value1, value2, ...
    
    foreach ($criterias as $criteria) {
        $valueKey = 'value' . $valueIndex;
        
        // KOREKSI UTAMA: Menggunakan isset() untuk menghindari error dan mengambil nilai berdasarkan index (value1, value2, dst.)
        $value = isset($alternative[$valueKey]) ? $alternative[$valueKey] : 1; 
        
        $weight = $criteriaWeights[$criteria['id']];
        
        if ($criteria['type'] === 'benefit') {
            // Benefit: Pangkat Positif
            $s *= pow($value, $weight);
        } else {
            // Cost: Pangkat Negatif
            $s *= pow($value, -$weight);
        }
        
        $valueIndex++; // Lanjut ke kolom nilai berikutnya
    }
    $sVector[$alternative['id']] = $s;
}

// Langkah keempat: Menjumlahkan nilai vektor S
$totalS = array_sum($sVector);

// Langkah kelima: Menghitung nilai vektor V
$vVector = array();
foreach ($alternatives as $alternative) {
    // Pengecekan totalS > 0 untuk menghindari Division by Zero
    if ($totalS > 0) {
        $vVector[$alternative['id']] = $sVector[$alternative['id']] / $totalS;
    } else {
        $vVector[$alternative['id']] = 0;
    }
}

// Langkah keenam: Meranking alternatif berdasarkan nilai vektor V
arsort($vVector);

?>

<div class="panel panel-container" style="padding: 20px; box-shadow: 2px 2px 5px #888888;">
    <h4>Tabel Ranking</h4>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ranking</th>
                    <th>Nama Alternatif</th>
                    <th>Nilai S</th>
                    <th>Nilai Vektor V</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $rank = 1;
                foreach ($vVector as $alternativeId => $value) {
                    // Pastikan fungsi getAlternative() tersedia dan mengembalikan array
                    $alternative = getAlternative($alternativeId); 
                    if ($alternative) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($alternative['id']) . "</td>";
                        echo "<td>" . $rank . "</td>";
                        echo "<td>" . htmlspecialchars($alternative['name']) . "</td>";
                        // Peningkatan: Pembulatan untuk tampilan agar mudah dibaca
                        echo "<td>" . number_format($sVector[$alternative['id']], 8) . "</td>"; 
                        echo "<td>" . number_format($value, 8) . "</td>";
                        echo "</tr>";
                        $rank++;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'footer.php';
?>