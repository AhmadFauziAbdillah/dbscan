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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (empty($_POST['eps']) || empty($_POST['min_samples']) || empty($_POST['selected_year'])) {
        $error_message = "Semua field harus diisi";
    } else {
        $eps = floatval($_POST['eps']);
        $min_samples = intval($_POST['min_samples']);
        $selected_year = intval($_POST['selected_year']);

        try {
            // Fetch data for clustering
            $stmt = $pdo->prepare("SELECT wilayah, jumlah_penderita, jumlah_kematian FROM diabetes_data WHERE tahun = ?");
            $stmt->execute([$selected_year]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                throw new Exception("Tidak ada data untuk tahun yang dipilih.");
            }

            // Normalize data
            $max_penderita = max(array_column($data, 'jumlah_penderita'));
            $max_kematian = max(array_column($data, 'jumlah_kematian'));
            
            foreach ($data as &$point) {
                $point['normalized_penderita'] = $point['jumlah_penderita'] / $max_penderita;
                $point['normalized_kematian'] = $point['jumlah_kematian'] / $max_kematian;
            }

            // Perform DBSCAN clustering
            $clusters = performDBSCAN($data, $eps, $min_samples);

            // Update database with cluster results
            $pdo->beginTransaction();
            
            // First, reset clusters for the selected year
            $stmt = $pdo->prepare("UPDATE diabetes_data SET cluster = NULL WHERE tahun = ?");
            $stmt->execute([$selected_year]);
            
            // Then, update with new cluster assignments
            $stmt = $pdo->prepare("UPDATE diabetes_data SET cluster = ? WHERE wilayah = ? AND tahun = ?");
            foreach ($clusters as $wilayah => $cluster) {
                $stmt->execute([$cluster, $wilayah, $selected_year]);
            }

            // Calculate cluster statistics
            $cluster_count = count(array_unique($clusters));
            if (in_array(-1, $clusters)) {
                $cluster_count--; // Don't count noise points as a cluster
            }

            // Store clustering results
            $stmt = $pdo->prepare("INSERT INTO clustering_results (tahun, epsilon, min_samples, cluster_count, date_generated) 
                                 VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$selected_year, $eps, $min_samples, $cluster_count]);

            $pdo->commit();
            
            // Assign categories based on clusters
            $stmt = $pdo->prepare("UPDATE diabetes_data 
                                 SET kategori = CASE 
                                    WHEN cluster = 1 THEN 'Rendah'
                                    WHEN cluster = 2 THEN 'Sedang'
                                    WHEN cluster = 3 THEN 'Tinggi'
                                    ELSE 'Tidak Terdefinisi'
                                 END
                                 WHERE tahun = ?");
            $stmt->execute([$selected_year]);

            $success_message = "DBSCAN clustering berhasil! Jumlah cluster: " . $cluster_count;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

function performDBSCAN($data, $eps, $min_samples) {
    $clusters = array_fill(0, count($data), -1); // Initialize all points as noise
    $current_cluster = 0;
    
    // Index data by wilayah for easy lookup
    $data_by_wilayah = array_column($data, null, 'wilayah');
    $visited = array();
    
    foreach ($data as $i => $point) {
        if (isset($visited[$point['wilayah']])) continue;
        
        $visited[$point['wilayah']] = true;
        $neighbors = getNeighbors($data, $point, $eps);
        
        if (count($neighbors) < $min_samples) {
            continue; // Point remains noise
        }
        
        // Start a new cluster
        $current_cluster++;
        $clusters[$i] = $current_cluster;
        
        // Process neighbors
        $neighbor_queue = $neighbors;
        while (!empty($neighbor_queue)) {
            $neighbor = array_shift($neighbor_queue);
            if (!isset($visited[$neighbor['wilayah']])) {
                $visited[$neighbor['wilayah']] = true;
                $new_neighbors = getNeighbors($data, $neighbor, $eps);
                
                if (count($new_neighbors) >= $min_samples) {
                    $neighbor_queue = array_merge($neighbor_queue, $new_neighbors);
                }
            }
            
            // Add point to cluster if not yet assigned
            $neighbor_index = array_search($neighbor['wilayah'], array_column($data, 'wilayah'));
            if ($clusters[$neighbor_index] == -1) {
                $clusters[$neighbor_index] = $current_cluster;
            }
        }
    }
    
    // Convert array index-based clusters to wilayah-based
    $wilayah_clusters = array();
    foreach ($data as $i => $point) {
        $wilayah_clusters[$point['wilayah']] = $clusters[$i];
    }
    
    return $wilayah_clusters;
}

function getNeighbors($data, $point, $eps) {
    $neighbors = array();
    foreach ($data as $other_point) {
        if ($point['wilayah'] === $other_point['wilayah']) continue;
        
        $distance = sqrt(
            pow($point['normalized_penderita'] - $other_point['normalized_penderita'], 2) +
            pow($point['normalized_kematian'] - $other_point['normalized_kematian'], 2)
        );
        
        if ($distance <= $eps) {
            $neighbors[] = $other_point;
        }
    }
    return $neighbors;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DBSCAN Clustering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
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
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="data_input.php">
                                <i class="bi bi-file-plus"></i> Input Data
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="dbscan_clustering.php">
                                <i class="bi bi-diagram-3"></i> DBSCAN Clustering
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
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
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">DBSCAN Parameters</h5>
                                <form method="POST" action="">
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
                                        <input type="number" step="0.1" class="form-control" id="eps" name="eps" 
                                               required min="0.1" placeholder="e.g., 0.5">
                                        <div class="form-text">Recommended value: 0.5</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="min_samples" class="form-label">Minimum Samples</label>
                                        <input type="number" class="form-control" id="min_samples" name="min_samples" 
                                               required min="2" placeholder="e.g., 5">
                                        <div class="form-text">Recommended value: 5</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Run DBSCAN Clustering</button>
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
