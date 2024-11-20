<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Koneksi database
$host = "localhost";
$username = "root";
$password = "";
$database = "inventory";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Buat tabel items jika belum ada
$sql = "CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_item VARCHAR(100) NOT NULL,
    fungsi TEXT,
    stok INT,
    kondisi VARCHAR(50)
)";
mysqli_query($conn, $sql);

// Proses Input Item
if (isset($_POST['submit_item'])) {
    $nama_item = mysqli_real_escape_string($conn, $_POST['nama_item']);
    $fungsi = mysqli_real_escape_string($conn, $_POST['fungsi']);
    $stok = (int)$_POST['stok'];
    $kondisi = mysqli_real_escape_string($conn, $_POST['kondisi']);
    
    $sql = "INSERT INTO items (nama_item, fungsi, stok, kondisi) 
            VALUES ('$nama_item', '$fungsi', $stok, '$kondisi')";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Item berhasil ditambahkan!');</script>";
    } else {
        echo "<script>alert('Gagal menambahkan item!');</script>";
    }
}

// Proses Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sistem Inventory</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 rounded">
            <div class="container-fluid">
                <a class="navbar-brand" href="#"><i class="fas fa-boxes"></i> Sistem Inventory</a>
                <div class="navbar-text text-white">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    <a href="?logout=1" class="btn btn-outline-light btn-sm ms-2">Logout</a>
                </div>
            </div>
        </nav>

        <div class="row">
            <div class="col-md-4">
                <!-- Form Input Item -->
                <div class="form-container">
                    <h2 class="text-center mb-4"><i class="fas fa-plus-circle"></i> Input Item</h2>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Nama Item</label>
                            <input type="text" class="form-control" name="nama_item" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fungsi</label>
                            <textarea class="form-control" name="fungsi" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" class="form-control" name="stok" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kondisi</label>
                            <select class="form-select" name="kondisi" required>
                                <option value="">Pilih Kondisi</option>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                            </select>
                        </div>
                        <button type="submit" name="submit_item" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Submit Item
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Tampilkan Data Item -->
                <div class="form-container">
                    <h2 class="text-center mb-4"><i class="fas fa-list"></i> Data Item</h2>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nama Item</th>
                                    <th>Fungsi</th>
                                    <th>Stok</th>
                                    <th>Kondisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM items ORDER BY id DESC";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['nama_item']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['fungsi']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['stok']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kondisi']) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
