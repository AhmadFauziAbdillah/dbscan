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

// Fetch data for editing
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM diabetes_data WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    if (!$data) {
        $error_message = "Record not found.";
    }
} else {
    header("Location: delete_data.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $wilayah = $_POST['wilayah'];
    $tahun = $_POST['tahun'];
    $jumlah_penderita = $_POST['jumlah_penderita'];
    $jumlah_kematian = $_POST['jumlah_kematian'];

    try {
        $stmt = $pdo->prepare("UPDATE diabetes_data SET wilayah = ?, tahun = ?, jumlah_penderita = ?, jumlah_kematian = ? WHERE id = ?");
        $stmt->execute([$wilayah, $tahun, $jumlah_penderita, $jumlah_kematian, $id]);
        $success_message = "Record updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating record: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
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
                        <a class="nav-link active" href="delete_data.php">
                            <i class="bi bi-file-earmark-minus me-2"></i>Ubah Data
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dbscan_clustering.php">
                            <i class="bi bi-diagram-3 me-2"></i>DBSCAN Clustering
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-file-earmark-text me-2"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-gear me-2"></i>Settings
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

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Data</h1>
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

            <?php if ($data): ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                    <div class="mb-3">
                        <label for="wilayah" class="form-label">Wilayah</label>
                        <input type="text" class="form-control" id="wilayah" name="wilayah" value="<?php echo htmlspecialchars($data['wilayah']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tahun" class="form-label">Tahun</label>
                        <input type="number" class="form-control" id="tahun" name="tahun" value="<?php echo htmlspecialchars($data['tahun']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="jumlah_penderita" class="form-label">Jumlah Penderita</label>
                        <input type="number" class="form-control" id="jumlah_penderita" name="jumlah_penderita" value="<?php echo htmlspecialchars($data['jumlah_penderita']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="jumlah_kematian" class="form-label">Jumlah Kematian</label>
                        <input type="number" class="form-control" id="jumlah_kematian" name="jumlah_kematian" value="<?php echo htmlspecialchars($data['jumlah_kematian']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="delete_data.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
