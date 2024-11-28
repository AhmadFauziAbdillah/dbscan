<?php
require_once 'config/database.php';

// Get the selected year (default to the latest year if not set)
$selected_year = isset($_GET['year']) ? $_GET['year'] : null;

// Fetch available years
$stmt = $pdo->query("SELECT DISTINCT tahun FROM diabetes_data ORDER BY tahun DESC");
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If no year is selected, use the latest year
if (!$selected_year) {
    $selected_year = $years[0];
}

// Fetch region data for chart and table
$stmt = $pdo->prepare("SELECT * FROM diabetes_data WHERE tahun = ? ORDER BY jumlah_penderita DESC");
$stmt->execute([$selected_year]);
$region_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top 10 regions for the chart
$chart_data = array_slice($region_data, 0, 10);

// Calculate statistics
$total_penderita = array_sum(array_column($region_data, 'jumlah_penderita'));
$total_kematian = array_sum(array_column($region_data, 'jumlah_kematian'));
$avg_penderita = $total_penderita / count($region_data);
$avg_kematian = $total_kematian / count($region_data);

// Calculate level statistics with validation
$level_counts = ['Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0, 'Tidak Terdefinisi' => 0];
foreach ($region_data as $row) {
    if (isset($row['cluster'])) {
        $category = getCategory($row['cluster'])['category'];
        $level_counts[$category] = ($level_counts[$category] ?? 0) + 1;
    }
}

function getCategory($cluster) {
    $cluster = (int)$cluster;
    switch ($cluster) {
        case 0:
            return ['category' => 'Tinggi', 'color' => 'bg-danger'];
        case 1:
            return ['category' => 'Sedang', 'color' => 'bg-warning'];
        case 2:
            return ['category' => 'rendah', 'color' => 'bg-success'];
        default:
            return ['category' => 'Tidak Terdefinisi', 'color' => 'bg-secondary'];
    }
}

include 'includes/header.php';
?>

<style>
    .dashboard-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        transition: transform 0.3s ease;
        padding: 20px;
        margin-bottom: 20px;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
    }

    .custom-table th, .custom-table td {
        padding: 15px;
    }

    .custom-table tbody tr {
        transition: background-color 0.3s;
    }

    .custom-table tbody tr:hover {
        background-color: rgba(0,0,0,0.02);
    }

    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
    }

    .year-select {
        padding: 10px 20px;
        border-radius: 10px;
        border: 2px solid #dee2e6;
        font-size: 16px;
        transition: all 0.3s;
    }

    .year-select:focus {
        border-color: #0d6efd;
        outline: none;
        box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
    }

    .progress {
        background-color: #f8f9fa;
        overflow: hidden;
        height: 8px;
        margin-top: 0.5rem;
        border-radius: 4px;
    }

    .progress-bar {
        transition: width 1s ease-in-out;
    }

    .chart-container {
        position: relative;
        height: 400px !important;
        width: 100% !important;
        margin: 0 auto;
    }

    .chart-wrapper {
        position: relative;
        height: 400px;
    }

    canvas#regionChart {
        width: 100% !important;
        height: 100% !important;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <h1 class="h2">Dashboard Statistik Diabetes <?= $selected_year ?></h1>
        <form method="GET" class="d-flex align-items-center">
            <select name="year" class="year-select" onchange="this.form.submit()">
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="row fade-in">
        <?php
        $stats = [
            ['icon' => 'bi-people-fill', 'title' => 'Total Penderita', 'value' => number_format($total_penderita), 'color' => 'primary'],
            ['icon' => 'bi-heart-fill', 'title' => 'Total Kematian', 'value' => number_format($total_kematian), 'color' => 'danger'],
            ['icon' => 'bi-graph-up', 'title' => 'Rata-rata Penderita', 'value' => number_format($avg_penderita, 0), 'color' => 'success'],
            ['icon' => 'bi-bar-chart-fill', 'title' => 'Rata-rata Kematian', 'value' => number_format($avg_kematian, 0), 'color' => 'warning']
        ];

        foreach ($stats as $index => $stat):
        ?>
            <div class="col-md-3" style="animation-delay: <?= $index * 0.1 ?>s">
                <div class="dashboard-card">
                    <div class="stat-icon bg-<?= $stat['color'] ?> bg-opacity-10">
                        <i class="bi <?= $stat['icon'] ?> fs-4 text-<?= $stat['color'] ?>"></i>
                    </div>
                    <div class="text-muted mb-2"><?= $stat['title'] ?></div>
                    <div class="stat-value text-<?= $stat['color'] ?>"><?= $stat['value'] ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts Section -->
    <div class="row fade-in">
        <div class="col-lg-8">
            <div class="dashboard-card">
                <h5 class="mb-4">Distribusi Penderita per Wilayah</h5>
                <div class="chart-wrapper">
                    <canvas id="regionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="dashboard-card">
                <h5 class="mb-4">Statistik Level</h5>
                <?php foreach ($level_counts as $level => $count): 
                    $percentage = ($count / array_sum($level_counts)) * 100;
                    $color = '';
                    switch($level) {
                        case 'Rendah':
                            $color = 'success';
                            break;
                        case 'Sedang':
                            $color = 'warning';
                            break;
                        case 'Tinggi':
                            $color = 'danger';
                            break;
                        default:
                            $color = 'secondary';
                    }   
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="status-badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                <?= $level ?>
                            </span>
                            <span class="fw-bold"><?= $count ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-<?= $color ?>" 
                                 role="progressbar" 
                                 style="width: <?= $percentage ?>%" 
                                 aria-valuenow="<?= $percentage ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="dashboard-card fade-in mt-4">
        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th>Wilayah</th>
                        <th>Jumlah Penderita</th>
                        <th>Jumlah Kematian</th>
                        <th>Cluster</th>
                        <th>Kategori</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($region_data as $row): 
                        $cat_info = getCategory($row['cluster']);
                    ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($row['wilayah']) ?></td>
                        <td><?= number_format($row['jumlah_penderita']) ?></td>
                        <td><?= number_format($row['jumlah_kematian']) ?></td>
                        <td><?= htmlspecialchars($row['cluster']) ?></td>
                        <td>
                            <span class="status-badge <?= $cat_info['color'] ?>">
                                <?= $cat_info['category'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('regionChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($chart_data, 'wilayah')) ?>,
            datasets: [{
                label: 'Jumlah Penderita',
                data: <?= json_encode(array_column($chart_data, 'jumlah_penderita')) ?>,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.6)',  // primary
                    'rgba(220, 53, 69, 0.6)',   // danger
                    'rgba(25, 135, 84, 0.6)',   // success
                    'rgba(255, 193, 7, 0.6)',   // warning
                    'rgba(13, 202, 240, 0.6)',  // info
                    'rgba(111, 66, 193, 0.6)',  // purple
                    'rgba(102, 16, 242, 0.6)',  // indigo
                    'rgba(253, 126, 20, 0.6)',  // orange
                    'rgba(32, 201, 151, 0.6)',  // teal
                    'rgba(214, 51, 132, 0.6)'   // pink
                ],
                borderColor: [
                    'rgb(13, 110, 253)',
                    'rgb(220, 53, 69)',
                    'rgb(25, 135, 84)',
                    'rgb(255, 193, 7)',
                    'rgb(13, 202, 240)',
                    'rgb(111, 66, 193)',
                    'rgb(102, 16, 242)',
                    'rgb(253, 126, 20)',
                    'rgb(32, 201, 151)',
                    'rgb(214, 51, 132)'
                ],
                borderWidth: 1,
                borderRadius: 5,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 10
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value);
                        },
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#000',
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyColor: '#000',
                    bodyFont: {
                        size: 13
                    },
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            let label = 'Jumlah Penderita: ';
                            label += new Intl.NumberFormat().format(context.raw);
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
