<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'];
    $show_chart_index = isset($_POST['show_chart_index']) ? 1 : 0;
    $show_chart_dashboard = isset($_POST['show_chart_dashboard']) ? 1 : 0;
    $default_sort = $_POST['default_sort'];

    $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, theme, show_chart_index, show_chart_dashboard, default_sort) 
                           VALUES (:user_id, :theme, :show_chart_index, :show_chart_dashboard, :default_sort)
                           ON DUPLICATE KEY UPDATE 
                           theme = VALUES(theme), 
                           show_chart_index = VALUES(show_chart_index), 
                           show_chart_dashboard = VALUES(show_chart_dashboard), 
                           default_sort = VALUES(default_sort)");

    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':theme' => $theme,
        ':show_chart_index' => $show_chart_index,
        ':show_chart_dashboard' => $show_chart_dashboard,
        ':default_sort' => $default_sort
    ]);

    $_SESSION['success_message'] = "Settings updated successfully!";
    header("Location: settings.php");
    exit();
}

// Fetch current settings
$stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no settings found, use defaults
if (!$settings) {
    $settings = [
        'theme' => 'light',
        'show_chart_index' => 1,
        'show_chart_dashboard' => 1,
        'default_sort' => 'jumlah_penderita'
    ];
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Settings</h1>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="theme" class="form-label">Theme</label>
                    <select class="form-select" id="theme" name="theme">
                        <option value="light" <?php echo $settings['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                        <option value="dark" <?php echo $settings['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                    </select>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="show_chart_index" name="show_chart_index" <?php echo $settings['show_chart_index'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_chart_index">Show chart on index page</label>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="show_chart_dashboard" name="show_chart_dashboard" <?php echo $settings['show_chart_dashboard'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_chart_dashboard">Show charts on dashboard</label>
                </div>

                <div class="mb-3">
                    <label for="default_sort" class="form-label">Default sorting</label>
                    <select class="form-select" id="default_sort" name="default_sort">
                        <option value="wilayah" <?php echo $settings['default_sort'] === 'wilayah' ? 'selected' : ''; ?>>Wilayah</option>
                        <option value="tahun" <?php echo $settings['default_sort'] === 'tahun' ? 'selected' : ''; ?>>Tahun</option>
                        <option value="jumlah_penderita" <?php echo $settings['default_sort'] === 'jumlah_penderita' ? 'selected' : ''; ?>>Jumlah Penderita</option>
                        <option value="jumlah_kematian" <?php echo $settings['default_sort'] === 'jumlah_kematian' ? 'selected' : ''; ?>>Jumlah Kematian</option>
                        <option value="cluster" <?php echo $settings['default_sort'] === 'cluster' ? 'selected' : ''; ?>>Cluster</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
