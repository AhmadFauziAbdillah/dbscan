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

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM diabetes_data WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Record deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Error deleting record: " . $e->getMessage();
    }
}

// Sorting logic
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column
$allowed_sort_columns = ['id', 'wilayah', 'tahun', 'jumlah_penderita', 'jumlah_kematian', 'cluster'];
if (!in_array($sort, $allowed_sort_columns)) {
    $sort = 'id';
}

// Fetch all data with sorting
try {
    $query = "SELECT * FROM diabetes_data ORDER BY $sort $order";
    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
}

include '../includes/header.php';

// Function to generate sort URL
function sortUrl($column) {
    global $sort, $order;
    $newOrder = ($sort === $column && $order === 'ASC') ? 'DESC' : 'ASC';
    return "?sort=$column&order=$newOrder";
}
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
                <h1 class="h2">Ubah Data</h1>
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

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th><a href="<?php echo sortUrl('id'); ?>">ID <?php echo $sort === 'id' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                            <th><a href="<?php echo sortUrl('wilayah'); ?>">Wilayah <?php echo $sort === 'wilayah' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                            <th><a href="<?php echo sortUrl('tahun'); ?>">Tahun <?php echo $sort === 'tahun' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                            <th><a href="<?php echo sortUrl('jumlah_penderita'); ?>">Jumlah Penderita <?php echo $sort === 'jumlah_penderita' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                            <th><a href="<?php echo sortUrl('jumlah_kematian'); ?>">Jumlah Kematian <?php echo $sort === 'jumlah_kematian' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                            <th><a href="<?php echo sortUrl('cluster'); ?>">Cluster <?php echo $sort === 'cluster' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                            <th>Action</th>
                        </tr>
                    </thead>
<tbody>
    <?php foreach ($data as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['wilayah']); ?></td>
            <td><?php echo htmlspecialchars($row['tahun']); ?></td>
            <td><?php echo htmlspecialchars($row['jumlah_penderita']); ?></td>
            <td><?php echo htmlspecialchars($row['jumlah_kematian']); ?></td>
            <td><?php echo isset($row['cluster']) ? htmlspecialchars($row['cluster']) : 'N/A'; ?></td>
            <td>
                <a href="edit_data.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this record?');" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>