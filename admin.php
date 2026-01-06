<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'auth.php'; // Ensure this file handles user authentication
require 'koneksi.php'; // Ensure this connects to your database properly

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle form submission to add new menu items, edit, or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_menu') {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $description = $_POST['description'];

        $stmt = $conn->prepare("INSERT INTO menu (name, category, price, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $name, $category, $price, $description);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Menu item added successfully!";
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
        }

        $stmt->close();
    } elseif ($_POST['action'] === 'edit_menu') {
        // Handle editing menu item
        $id = $_POST['id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $description = $_POST['description'];

        $stmt = $conn->prepare("UPDATE menu SET name=?, category=?, price=?, description=? WHERE id=?");
        $stmt->bind_param("ssdsi", $name, $category, $price, $description, $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Menu item updated successfully!";
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
        }

        $stmt->close();
    } elseif ($_POST['action'] === 'delete_menu') {
        // Handle deletion of menu item
        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM menu WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Menu item deleted successfully!";
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Function to get orders
function get_orders() {
    global $conn; // Use the global $conn variable
    $sql = "SELECT id, created_at, total FROM orders ORDER BY created_at DESC";
    return $conn->query($sql);
}

// Function to get menu items
function get_menu_items() {
    global $conn;
    $sql = "SELECT * FROM menu ORDER BY category, name";
    return $conn->query($sql);
}

$orders_result = get_orders();
$menu_items_result = get_menu_items();

function format_rupiah($amount) {
    return "Rp " . number_format($amount, 0, ',', '.');
}

// Calculate total sales
$total_sales = 0;
if ($orders_result->num_rows > 0) {
    $orders_result->data_seek(0);
    while ($order = $orders_result->fetch_assoc()) {
        $total_sales += $order['total'];
    }
    $orders_result->data_seek(0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Warkop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8B4513;      /* Cokelat tua (kopi) */
            --primary-dark: #654321;  /* Cokelat lebih gelap */
            --secondary: #D2691E;     /* Cokelat oranye */
            --accent: #A0522D;        /* Cokelat merah */
            --light: #FAF3E0;         /* Krem muda */
            --dark: #3C2A1E;          /* Cokelat pekat */
            --success: #8FBC8F;       /* Hijau sage */
            --warning: #DAA520;       /* Emas */
            --danger: #CD5C5C;        /* Merah anggur */
            --sidebar-width: 280px;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e6e9f0 100%);
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 5px 0 25px rgba(101, 67, 33, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 250, 0.2);
            text-align: center;
            background: rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            color: white;
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.4rem;
        }

        .sidebar-header p {
            color: rgba(255, 255, 250, 0.8);
            font-size: 0.9rem;
            margin: 0;
        }

        .nav-link {
            color: rgba(255, 255, 250, 0.85);
            padding: 14px 25px;
            margin: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
            font-size: 1.2rem;
        }

        .nav-link:hover {
            color: white;
            background: linear-gradient(90deg, var(--secondary) 0%, var(--accent) 100%);
            transform: translateX(8px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .nav-link.active {
            color: white;
            background: linear-gradient(90deg, var(--secondary) 0%, var(--accent) 100%);
            transform: translateX(8px);
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(60, 42, 30, 0.1);
        }

        .header h1 {
            color: var(--primary);
            font-weight: 700;
            margin: 0;
            font-size: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(139, 69, 19, 0.3);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            border: none;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(101, 67, 33, 0.15);
        }

        .stat-card.total::before { background: var(--success); }
        .stat-card.orders::before { background: var(--warning); }
        .stat-card.menu::before { background: var(--secondary); }

        .stat-card i {
            font-size: 2.8rem;
            margin-bottom: 20px;
            color: var(--primary);
            background: linear-gradient(135deg, var(--light) 0%, #fff8e1 100%);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
        }

        .stat-card.total i { 
            color: var(--success);
            background: linear-gradient(135deg, rgba(143, 188, 143, 0.1) 0%, rgba(143, 188, 143, 0.2) 100%);
        }
        .stat-card.orders i { 
            color: var(--warning);
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1) 0%, rgba(218, 165, 32, 0.2) 100%);
        }
        .stat-card.menu i { 
            color: var(--secondary);
            background: linear-gradient(135deg, rgba(210, 105, 30, 0.1) 0%, rgba(210, 105, 30, 0.2) 100%);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 15px 0;
            color: var(--dark);
        }

        .stat-label {
            color: #7E6B5A;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(101, 67, 33, 0.08);
            margin-bottom: 35px;
            overflow: hidden;
            background: white;
        }

        .card-header {
            background: linear-gradient(90deg, white 0%, var(--light) 100%);
            border-bottom: 2px solid rgba(139, 69, 19, 0.1);
            padding: 25px 30px;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 28px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
        }

        .btn-custom:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--accent) 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--warning) 0%, #FFD700 100%);
            color: var(--dark);
            border: none;
            font-weight: 600;
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger) 0%, #FF6B6B 100%);
            color: white;
            border: none;
            font-weight: 600;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--success) 0%, #98FB98 100%);
            color: var(--dark);
            border: none;
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            padding: 10px;
        }

        .table {
            margin-bottom: 0;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background: linear-gradient(90deg, var(--light) 0%, #f5e6d3 100%);
            border: none;
            color: var(--primary);
            font-weight: 700;
            padding: 18px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 18px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(139, 69, 19, 0.1);
            font-size: 0.95rem;
        }

        .table tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(250, 243, 224, 0.4);
            transform: translateX(5px);
        }

        .badge-category {
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        .badge-makanan {
            background: linear-gradient(135deg, var(--success) 0%, rgba(143, 188, 143, 0.9) 100%);
            color: var(--dark);
        }

        .badge-minuman {
            background: linear-gradient(135deg, var(--secondary) 0%, rgba(210, 105, 30, 0.9) 100%);
            color: white;
        }

        .modal-content {
            border-radius: 18px;
            border: none;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(101, 67, 33, 0.2);
        }

        .modal-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px 30px;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.4rem;
        }

        .btn-close-white {
            filter: invert(1) brightness(2);
            opacity: 0.8;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 18px;
            border: 2px solid #E0D6C9;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(210, 105, 30, 0.25);
        }

        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 18px 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            background: linear-gradient(90deg, #e3f2fd 0%, #bbdefb 100%);
            color: var(--dark);
            font-weight: 500;
        }

        .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            padding: 0 20px;
        }

        .btn-outline-light {
            border: 2px solid rgba(255, 255, 250, 0.3);
            color: rgba(255, 255, 250, 0.9);
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 250, 0.1);
            border-color: rgba(255, 255, 250, 0.8);
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h3, .sidebar-header p, .nav-link span, .btn-outline-light span {
                display: none;
            }
            
            .sidebar-header {
                padding: 15px 10px;
            }
            
            .nav-link {
                justify-content: center;
                margin: 10px;
                padding: 15px;
            }
            
            .nav-link i {
                margin-right: 0;
                font-size: 1.4rem;
            }
            
            .main-content {
                margin-left: 80px;
                padding: 20px;
            }
            
            .btn-outline-light i {
                margin-right: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
        }

        .order-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .order-link:hover {
            color: var(--secondary);
            text-decoration: none;
            transform: translateX(3px);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons .btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card, .card {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--accent) 100%);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-coffee me-2"></i>WARKOP 77</h3>
            <p>Admin Dashboard</p>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="#" id="menu-tab-link">
                <i class="fas fa-utensils"></i>
                <span>Kelola Menu</span>
            </a>
            <a class="nav-link" href="#" id="sales-tab-link">
                <i class="fas fa-chart-line"></i>
                <span>Data Penjualan</span>
            </a>
            <a class="nav-link" href="#" id="add-menu-tab-link" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                <i class="fas fa-plus-circle"></i>
                <span>Tambah Menu Baru</span>
            </a>
        </nav>
        
        <div class="logout-btn">
            <a href="logout.php" class="btn btn-outline-light w-100">
                <i class="fas fa-sign-out-alt me-2"></i>
                <span>Keluar</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>Dashboard Admin</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                        $initial = isset($_SESSION['user']['name']) ? strtoupper(substr($_SESSION['user']['name'], 0, 1)) : 'A';
                        echo $initial;
                    ?>
                </div>
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></div>
                    <small class="text-muted" style="color: var(--secondary) !important;">Administrator</small>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card total">
                <i class="fas fa-money-bill-wave"></i>
                <div class="stat-value"><?= format_rupiah($total_sales) ?></div>
                <div class="stat-label">Total Penjualan</div>
            </div>
            <div class="stat-card orders">
                <i class="fas fa-shopping-cart"></i>
                <div class="stat-value"><?= $orders_result->num_rows ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card menu">
                <i class="fas fa-utensils"></i>
                <div class="stat-value"><?= $menu_items_result->num_rows ?></div>
                <div class="stat-label">Item Menu</div>
            </div>
        </div>

        <!-- Menu Management Section -->
        <div id="menu-section" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bars me-2"></i>Kelola Menu</h5>
                    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                        <i class="fas fa-plus me-2"></i>Tambah Menu Baru
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Menu</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Deskripsi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($menu_item = $menu_items_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-dark">#<?= $menu_item['id'] ?></span></td>
                                        <td class="fw-bold"><?= htmlspecialchars($menu_item['name']) ?></td>
                                        <td>
                                            <span class="badge-category badge-<?= $menu_item['category'] ?>">
                                                <?= ($menu_item['category'] === 'makanan') ? '🍔 Makanan' : '☕ Minuman' ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold" style="color: var(--primary);"><?= format_rupiah($menu_item['price']) ?></td>
                                        <td><?= htmlspecialchars($menu_item['description']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#editMenuModal" 
                                                    data-id="<?= $menu_item['id'] ?>"
                                                    data-name="<?= htmlspecialchars($menu_item['name']) ?>"
                                                    data-category="<?= $menu_item['category'] ?>"
                                                    data-price="<?= $menu_item['price'] ?>"
                                                    data-description="<?= htmlspecialchars($menu_item['description']) ?>">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </button>
                                                <form action="admin.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus menu ini?');">
                                                    <input type="hidden" name="action" value="delete_menu">
                                                    <input type="hidden" name="id" value="<?= $menu_item['id'] ?>">
                                                    <button type="submit" class="btn btn-delete btn-sm">
                                                        <i class="fas fa-trash me-1"></i>Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Data Section -->
        <div id="sales-section" class="content-section" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Data Penjualan</h5>
                    <div class="text-muted">Total Pendapatan: <span class="fw-bold text-success"><?= format_rupiah($total_sales) ?></span></div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-dark">#<?= $order['id'] ?></span></td>
                                        <td><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
                                        <td class="fw-bold" style="color: var(--success);"><?= format_rupiah($order['total']) ?></td>
                                        <td>
                                            <a href="view_order.php?order_id=<?= $order['id'] ?>" class="btn btn-view btn-sm">
                                                <i class="fas fa-eye me-1"></i>Lihat
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-custom alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); endif; ?>
    </div>

    <!-- Add Menu Modal -->
    <div class="modal fade" id="addMenuModal" tabindex="-1" aria-labelledby="addMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMenuModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Menu Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_menu">
                        <div class="mb-4">
                            <label for="addName" class="form-label fw-bold">Nama Menu</label>
                            <input type="text" class="form-control" id="addName" name="name" placeholder="Masukkan nama menu" required>
                        </div>
                        <div class="mb-4">
                            <label for="addCategory" class="form-label fw-bold">Kategori</label>
                            <select name="category" id="addCategory" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                <option value="makanan">🍔 Makanan</option>
                                <option value="minuman">☕ Minuman</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="addPrice" class="form-label fw-bold">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="100" class="form-control" id="addPrice" name="price" placeholder="Masukkan harga" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="addDescription" class="form-label fw-bold">Deskripsi</label>
                            <textarea class="form-control" id="addDescription" name="description" rows="4" placeholder="Masukkan deskripsi menu" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-custom">
                            <i class="fas fa-save me-2"></i>Simpan Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Menu Modal -->
    <div class="modal fade" id="editMenuModal" tabindex="-1" aria-labelledby="editMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMenuModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Menu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_menu">
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-4">
                            <label for="editName" class="form-label fw-bold">Nama Menu</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-4">
                            <label for="editCategory" class="form-label fw-bold">Kategori</label>
                            <select name="category" id="editCategory" class="form-select" required>
                                <option value="makanan">🍔 Makanan</option>
                                <option value="minuman">☕ Minuman</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="editPrice" class="form-label fw-bold">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="100" class="form-control" id="editPrice" name="price" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="editDescription" class="form-label fw-bold">Deskripsi</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-custom">
                            <i class="fas fa-save me-2"></i>Update Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab Navigation
        document.getElementById('menu-tab-link').addEventListener('click', function(e) {
            e.preventDefault();
            showSection('menu-section');
            setActiveTab(this);
        });

        document.getElementById('sales-tab-link').addEventListener('click', function(e) {
            e.preventDefault();
            showSection('sales-section');
            setActiveTab(this);
        });

        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId).style.display = 'block';
        }

        function setActiveTab(activeTab) {
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.classList.remove('active');
            });
            activeTab.classList.add('active');
        }

        // Edit Modal Handler
        document.addEventListener('DOMContentLoaded', function() {
            var editMenuModal = document.getElementById('editMenuModal');
            if (editMenuModal) {
                editMenuModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    
                    document.getElementById('editId').value = button.getAttribute('data-id');
                    document.getElementById('editName').value = button.getAttribute('data-name');
                    document.getElementById('editCategory').value = button.getAttribute('data-category');
                    document.getElementById('editPrice').value = button.getAttribute('data-price');
                    document.getElementById('editDescription').value = button.getAttribute('data-description');
                });
            }
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Add click handler for add menu tab link
            document.getElementById('add-menu-tab-link').addEventListener('click', function(e) {
                e.preventDefault();
                showSection('menu-section');
                setActiveTab(this);
            });
        });
    </script>
</body>
</html>

<?php
$conn->close(); // Close the connection at the end of the script
?>