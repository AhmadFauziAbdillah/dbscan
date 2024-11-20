<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$selected_year = isset($_GET['year']) ? $_GET['year'] : null;

$stmt = $pdo->query("SELECT DISTINCT tahun FROM diabetes_data ORDER BY tahun DESC");
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$selected_year) {
    $selected_year = $years[0];
}

$stmt = $pdo->prepare("SELECT * FROM diabetes_data WHERE tahun = ?");
$stmt->execute([$selected_year]);
$region_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_penderita = array_sum(array_column($region_data, 'jumlah_penderita'));
$total_kematian = array_sum(array_column($region_data, 'jumlah_kematian'));
$avg_penderita = count($region_data) > 0 ? $total_penderita / count($region_data) : 0;
$avg_kematian = count($region_data) > 0 ? $total_kematian / count($region_data) : 0;

$cluster_counts = array('Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0);
foreach ($region_data as $row) {
    if (isset($row['cluster'])) {
        switch ((int)$row['cluster']) {
            case 0: $cluster_counts['Rendah']++; break;
            case 1: $cluster_counts['Sedang']++; break;
            case 2: $cluster_counts['Tinggi']++; break;
        }
    }
}

$chart_data = array_slice($region_data, 0, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Diabetes Clustering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 60px; /* Height of navbar */
        }

        /* Navbar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #0d6efd !important;
            padding: 0.75rem 0;
            height: 60px;
        }

        .navbar-brand {
            color: white;
            font-weight: 500;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 60px; /* Height of navbar */
            left: 0;
            bottom: 0;
            width: 250px;
            background: white;
            padding: 1rem 0;
            overflow-y: auto;
            border-right: 1px solid #e5e7eb;
        }

        .sidebar .nav-link {
            color: #6b7280 !important;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .sidebar .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .sidebar .nav-link:hover {
            background-color: #f3f4f6;
            color: #2563eb !important;
        }

        .sidebar .nav-link.active {
            background-color: #e8f0fe;
            color: #2563eb !important;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px; /* Width of sidebar */
            padding: 2rem;
        }

        /* Card Styles */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stats-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        /* Year Select */
        .year-select {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background-color: white;
            font-size: 0.875rem;
            min-width: 120px;
        }

        /* Status Badge */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .progress {
            height: 8px;
            margin-top: 0.5rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="/">
                <i class="bi bi-grid-3x3-gap-fill me-2"></i>
                Diabetes Clustering DBSCAN
            </a>
            <a href="logout.php" class="nav-link text-white">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link active">
                <i class="bi bi-house-door"></i>
                Dashboard
            </a>
            <a href="data_input.php" class="nav-link">
                <i class="bi bi-file-earmark-plus"></i>
                Input Data
            </a>
            <a href="delete_data.php" class="nav-link">
                <i class="bi bi-file-earmark-minus"></i>
                Ubah Data
            </a>
            <a href="dbscan_clustering.php" class="nav-link">
                <i class="bi bi-diagram-3"></i>
                DBSCAN Clustering
            </a>
            <a href="#" class="nav-link">
                <i class="bi bi-file-text"></i>
                Reports
            </a>
            <a href="#" class="nav-link">
                <i class="bi bi-gear"></i>
                Settings
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Dashboard Statistik Diabetes <?php echo htmlspecialchars($selected_year); ?></h4>
            <select class="year-select" onchange="window.location.href='?year='+this.value">
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary bg-opacity-10">
                        <i class="bi bi-people text-primary"></i>
                    </div>
                    <div class="stats-label">Total Penderita</div>
                    <div class="stats-value text-primary"><?php echo number_format($total_penderita); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-danger bg-opacity-10">
                        <i class="bi bi-heart text-danger"></i>
                    </div>
                    <div class="stats-label">Total Kematian</div>
                    <div class="stats-value text-danger"><?php echo number_format($total_kematian); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success bg-opacity-10">
                        <i class="bi bi-graph-up text-success"></i>
                    </div>
                    <div class="stats-label">Rata-rata Penderita</div>
                    <div class="stats-value text-success"><?php echo number_format($avg_penderita); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning bg-opacity-10">
                        <i class="bi bi-bar-chart text-warning"></i>
                    </div>
                    <div class="stats-label">Rata-rata Kematian</div>
                    <div class="stats-value text-warning"><?php echo number_format($avg_kematian); ?></div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-8">
                <div class="stats-card">
                    <h5 class="mb-4">Distribusi Penderita per Wilayah</h5>
                    <div class="chart-container">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="stats-card">
                    <h5 class="mb-4">Statistik Level</h5>
                    <?php 
                    foreach ($cluster_counts as $level => $count):
                        $total = array_sum($cluster_counts);
                        $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                        
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
                                <span class="status-badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?>">
                                    <?php echo $level; ?>
                                </span>
                                <span class="fw-bold"><?php echo $count; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-<?php echo $color; ?>" 
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('distributionChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($chart_data, 'wilayah')) ?>,
                datasets: [{
                    label: 'Jumlah Penderita',
                    data: <?= json_encode(array_column($chart_data, 'jumlah_penderita')) ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.6)',
                    borderColor: 'rgb(13, 110, 253)',
                    borderWidth: 1,
                    borderRadius: 4,
                    barThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat().format(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>