<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Fetch available years
$stmt = $pdo->query("SELECT DISTINCT tahun FROM diabetes_data ORDER BY tahun DESC");
$available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ... (previous code remains the same until the clustering section)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eps = $_POST['eps'];
    $min_samples = $_POST['min_samples'];
    $selected_year = $_POST['selected_year'];

    try {
        // Start timing the execution
        $start_time = microtime(true);

        $stmt = $pdo->prepare("SELECT wilayah, jumlah_penderita, jumlah_kematian FROM diabetes_data WHERE tahun = ?");
        $stmt->execute([$selected_year]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            throw new Exception("No data available for the selected year.");
        }

        // Count total data points before normalization
        $data_points_count = count($data);

        // Normalize data
        $max_penderita = max(array_column($data, 'jumlah_penderita'));
        $max_kematian = max(array_column($data, 'jumlah_kematian'));
        
        foreach ($data as &$point) {
            $point['jumlah_penderita'] = $point['jumlah_penderita'] / $max_penderita;
            $point['jumlah_kematian'] = $point['jumlah_kematian'] / $max_kematian;
        }

        $clusters = performDBSCAN($data, $eps, $min_samples);

        // Update cluster assignments in database
        $stmt = $pdo->prepare("UPDATE diabetes_data SET cluster = ? WHERE wilayah = ? AND tahun = ?");
        foreach ($clusters as $wilayah => $cluster) {
            $stmt->execute([$cluster, $wilayah, $selected_year]);
        }

        // Calculate number of actual clusters (excluding noise points marked as 0)
        $cluster_count = count(array_unique($clusters)) - (in_array(0, $clusters) ? 1 : 0);
        
        // Count outliers (points with cluster = 0)
        $outliers_count = array_count_values($clusters)[0] ?? 0;

        // Calculate execution time
        $execution_time = round(microtime(true) - $start_time, 4);

        // Insert clustering results with all required fields including execution_time
        $stmt = $pdo->prepare("INSERT INTO clustering_results 
            (cluster_count, epsilon, min_samples, min_points, data_points, outliers, execution_time, date_generated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $cluster_count,
            $eps,
            $min_samples,
            $min_samples,  // min_points same as min_samples for DBSCAN
            $data_points_count,
            $outliers_count,
            $execution_time
        ]);
        
        $success_message = "DBSCAN clustering completed successfully for year $selected_year! ".
                          "Number of clusters: $cluster_count, Outliers: $outliers_count, ".
                          "Execution time: {$execution_time}s";
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

function performDBSCAN($data, $eps, $min_samples) {
    $clusters = [];
    $cluster_id = 0;

    foreach ($data as $point) {
        if (isset($clusters[$point['wilayah']])) continue;

        $neighbors = getNeighbors($data, $point, $eps);
        if (count($neighbors) < $min_samples) {
            $clusters[$point['wilayah']] = 0; // Noise point
        } else {
            $cluster_id++;
            expandCluster($data, $point, $neighbors, $cluster_id, $eps, $min_samples, $clusters);
        }
    }

    // If all points are noise, assign them to a single cluster
    if (count(array_unique($clusters)) === 1 && reset($clusters) === 0) {
        foreach ($clusters as &$cluster) {
            $cluster = 1;
        }
    }

    return $clusters;
}

function getNeighbors($data, $point, $eps) {
    $neighbors = [];
    foreach ($data as $other_point) {
        if ($point['wilayah'] === $other_point['wilayah']) continue;
        
        $distance = sqrt(
            pow($point['jumlah_penderita'] - $other_point['jumlah_penderita'], 2) +
            pow($point['jumlah_kematian'] - $other_point['jumlah_kematian'], 2)
        );
        
        if ($distance <= $eps) {
            $neighbors[] = $other_point;
        }
    }
    return $neighbors;
}

function expandCluster(&$data, $point, $neighbors, $cluster_id, $eps, $min_samples, &$clusters) {
    $clusters[$point['wilayah']] = $cluster_id;
    
    for ($i = 0; $i < count($neighbors); $i++) {
        $neighbor = $neighbors[$i];
        
        if (!isset($clusters[$neighbor['wilayah']]) || $clusters[$neighbor['wilayah']] === 0) {
            $clusters[$neighbor['wilayah']] = $cluster_id;
            
            $new_neighbors = getNeighbors($data, $neighbor, $eps);
            if (count($new_neighbors) >= $min_samples) {
                $neighbors = array_merge($neighbors, $new_neighbors);
            }
        }
    }
}

?>

<!-- HTML Structure -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DBSCAN Clustering - Diabetes Data Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house-door me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="data_input.php">
                            <i class="bi bi-file-earmark-plus me-2"></i>Input Data
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="delete_data.php">
                            <i class="bi bi-file-earmark-minus me-2"></i>Ubah Data
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dbscan_clustering.php">
                            <i class="bi bi-diagram-3 me-2"></i>DBSCAN Clustering
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">DBSCAN Clustering</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">DBSCAN Parameters</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="selected_year" class="form-label">Select Year</label>
                                    <select class="form-select" id="selected_year" name="selected_year" required>
                                        <?php foreach ($available_years as $year): ?>
                                            <option value="<?php echo htmlspecialchars($year); ?>">
                                                <?php echo htmlspecialchars($year); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="eps" class="form-label">Epsilon (eps)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="eps" name="eps" required>
                                    <div class="form-text">Distance threshold for neighbors (0-1)</div>
                                </div>
                                <div class="mb-3">
                                    <label for="min_samples" class="form-label">Minimum Samples</label>
                                    <input type="number" min="1" class="form-control" id="min_samples" name="min_samples" required>
                                    <div class="form-text">Minimum number of points to form a cluster</div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-play-fill me-2"></i>Run DBSCAN Clustering
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
