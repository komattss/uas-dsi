ko<?php
session_start();
require 'auth.php'; // Ensure this handles user authentication

// Database connection configuration
$servername = "localhost"; // Ganti dengan host database Anda (biasanya localhost)
$username = "root"; // Ganti dengan username database Anda
$password = ""; // Ganti dengan password database Anda (kosong jika tidak ada)
$dbname = "warkop"; // Nama database yang digunakan

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to format currency to Rupiah
function format_rupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function to find menu item by name
function find_menu_item($menuItems, $name) {
    foreach ($menuItems as $m) {
        if ($m['name'] === $name) return $m;
    }
    return null;
}

// Retrieve menu items from the database
$menuItems = [];
$sql = "SELECT name, price, description, category FROM menu";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }
}

// Close the connection
$conn->close();

// Filtered arrays for coffee and non-coffee items
$coffeeItems = array_values(array_filter($menuItems, fn($item) => $item['category'] === 'minuman'));
$nonCoffeeItems = array_values(array_filter($menuItems, fn($item) => $item['category'] === 'makanan'));

// Main session management
if (!isset($_SESSION['order'])) $_SESSION['order'] = [];

// Handle actions based on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = $_POST['item'];
        $qty = max(1, intval($_POST['quantity'] ?? 1));
        $_SESSION['order'][$name] = ($_SESSION['order'][$name] ?? 0) + $qty;
    }

    if ($action === 'update') {
        $name = $_POST['item'];
        $qty = max(0, intval($_POST['quantity']));
        if ($qty === 0) unset($_SESSION['order'][$name]);
        else $_SESSION['order'][$name] = $qty;
    }

    if ($action === 'remove') {
        unset($_SESSION['order'][$_POST['item']]);
    }

    if ($action === 'clear') {
        $_SESSION['order'] = [];
    }

    if ($action === 'save') {
        if (empty($_SESSION['order'])) {
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }

        // Reopen connection for order saving
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Create tables if they do not exist
        $conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, created_at DATETIME, total BIGINT)");
        $conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, name VARCHAR(255), price DECIMAL(10,2), quantity INT, line_total DECIMAL(10,2))");

        $total = 0;
        foreach ($_SESSION['order'] as $name => $qty) {
            $item = find_menu_item($menuItems, $name);
            if ($item) {
                $total += $item['price'] * $qty;
            }
        }

        $conn->begin_transaction();
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO orders (created_at, total) VALUES (?, ?)");
            $stmt->bind_param("si", $now, $total);
            $stmt->execute();
            $orderId = $stmt->insert_id;

            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, name, price, quantity, line_total) VALUES (?, ?, ?, ?, ?)");

            foreach ($_SESSION['order'] as $name => $qty) {
                $item = find_menu_item($menuItems, $name);
                if ($item) {
                    $price = $item['price'];
                    $line = $price * $qty;
                    $stmtItem->bind_param("isiii", $orderId, $name, $price, $qty, $line);
                    $stmtItem->execute();
                }
            }

            $conn->commit();
            $_SESSION['order'] = [];
            header("Location: ?action=print&order_id={$orderId}");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
    }

    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect");
    exit;
}

// Print page logic
if (isset($_GET['action']) && $_GET['action'] === 'print') {
    $orderId = intval($_GET['order_id'] ?? 0);
    if (!$orderId) { echo "Order ID tidak ditemukan."; exit; }

    // Fetch order data
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderData = $stmt->get_result()->fetch_assoc();

    if (!$orderData) { echo "Data order tidak ditemukan."; exit; }

    // Fetch order items
    $stmt2 = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt2->bind_param("i", $orderId);
    $stmt2->execute();
    $items = $stmt2->get_result();
    
    // Fetch all items for calculation
    $orderItems = [];
    $subtotal = 0;
    while ($row = $items->fetch_assoc()) {
        $orderItems[] = $row;
        $subtotal += $row['line_total'];
    }
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Struk Pembelian - Warkop Gondrong 77</title>
        <style>
            @media print {
                body {
                    font-family: 'Courier New', monospace;
                    width: 80mm;
                    margin: 0 auto;
                    padding: 10px 5px;
                    font-size: 13px;
                    line-height: 1.3;
                    color: #000;
                }
                .no-print { display: none !important; }
            }
            
            @media screen {
                body {
                    font-family: 'Courier New', monospace;
                    width: 80mm;
                    margin: 20px auto;
                    padding: 10px 5px;
                    font-size: 13px;
                    line-height: 1.3;
                    color: #000;
                    border: 1px solid #ccc;
                    background: white;
                }
            }
            
            .struk-header {
                text-align: center;
                padding-bottom: 8px;
                border-bottom: 1px dashed #000;
                margin-bottom: 8px;
            }
            
            .struk-title {
                font-weight: bold;
                font-size: 16px;
                margin: 5px 0;
            }
            
            .struk-subtitle {
                font-size: 11px;
                margin: 3px 0;
            }
            
            .struk-info {
                margin: 8px 0;
                padding-bottom: 8px;
                border-bottom: 1px dashed #000;
            }
            
            .info-line {
                display: flex;
                justify-content: space-between;
                margin: 2px 0;
            }
            
            .items-table {
                width: 100%;
                margin: 10px 0;
                border-collapse: collapse;
            }
            
            .items-table th {
                text-align: left;
                padding: 4px 0;
                border-bottom: 1px dashed #000;
                font-weight: bold;
            }
            
            .items-table td {
                padding: 4px 0;
                vertical-align: top;
            }
            
            .item-name {
                width: 50%;
            }
            
            .item-qty {
                width: 15%;
                text-align: center;
            }
            
            .item-price {
                width: 35%;
                text-align: right;
            }
            
            .total-section {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 2px solid #000;
            }
            
            .total-line {
                display: flex;
                justify-content: space-between;
                margin: 5px 0;
                font-weight: bold;
            }
            
            .grand-total {
                font-size: 16px;
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed #000;
            }
            
            .struk-footer {
                text-align: center;
                margin-top: 15px;
                padding-top: 10px;
                border-top: 1px dashed #000;
                font-size: 11px;
            }
            
            .thank-you {
                font-weight: bold;
                margin: 10px 0;
                font-size: 12px;
            }
            
            .print-btn {
                display: block;
                width: 100%;
                padding: 12px;
                background: #8B4513;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 14px;
                font-weight: bold;
                margin: 20px 0;
                cursor: pointer;
                text-align: center;
                text-decoration: none;
            }
            
            .print-btn:hover {
                background: #654321;
            }
        </style>
    </head>
    <body>
        <!-- Header Struk -->
        <div class="struk-header">
            <div class="struk-title">WARKOP GONDRONG 77</div>
            <div class="struk-subtitle">Jl. Barokah No. 23, Jakarta</div>
            <div class="struk-subtitle">Telp: 0812-3456-7890</div>
            <div class="struk-subtitle">================================</div>
        </div>
        
        <!-- Info Transaksi -->
        <div class="struk-info">
            <div class="info-line">
                <span>No. Struk:</span>
                <span><strong>#<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></strong></span>
            </div>
            <div class="info-line">
                <span>Tanggal:</span>
                <span><?= date('d/m/Y', strtotime($orderData['created_at'])) ?></span>
            </div>
            <div class="info-line">
                <span>Waktu:</span>
                <span><?= date('H:i:s', strtotime($orderData['created_at'])) ?></span>
            </div>
            <div class="info-line">
                <span>Kasir:</span>
                <span><?= htmlspecialchars($_SESSION['user']['username'] ?? 'System') ?></span>
            </div>
        </div>
        
        <!-- Daftar Item -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-price">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                    <td class="item-qty"><?= $item['quantity'] ?></td>
                    <td class="item-price"><?= format_rupiah($item['line_total']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="font-size: 11px; padding-left: 10px;">
                        @ <?= format_rupiah($item['price']) ?> 
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Total Pembayaran -->
        <div class="total-section">
            <div class="total-line">
                <span>Subtotal:</span>
                <span><?= format_rupiah($subtotal) ?></span>
            </div>
            <div class="total-line">
                <span>PPN (10%):</span>
                <span><?= format_rupiah($subtotal * 0.1) ?></span>
            </div>
            <div class="grand-total total-line">
                <span>TOTAL:</span>
                <span><?= format_rupiah($orderData['total']) ?></span>
            </div>
        </div>
        
        <!-- Footer Struk -->
        <div class="struk-footer">
            <div>================================</div>
            <div class="thank-you">TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
            <div>--------------------------------</div>
            <div>** Barang yang sudah dibeli</div>
            <div>tidak dapat ditukar/dikembalikan **</div>
            <div>--------------------------------</div>
            <div>Silakan datang kembali</div>
            <div>😊 Enjoy Your Coffee! 😊</div>
        </div>
        
        <!-- Tombol Print untuk Browser -->
        <button class="print-btn no-print" onclick="window.print()">
            🖨️ CETAK STRUK
        </button>
        <a href="?" class="print-btn no-print" style="background: #28a745; text-align: center;">
            🔙 KEMBALI KE PEMESANAN
        </a>
        
        <script>
            // Auto print saat halaman struk dibuka
            window.onload = function() {
                window.print();
            }
            
            // Auto redirect setelah 10 detik jika tidak dicetak
            setTimeout(function() {
                if (!document.hidden) {
                    window.location.href = "?";
                }
            }, 10000);
        </script>
    </body>
    </html>

    <?php
    exit;
}

// MAIN PAGE
$currentCategory = $_GET['category'] ?? 'minuman';
$order = &$_SESSION['order'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemesanan Kopi - Warkop Gondrong 77</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8B4513;
            --primary-dark: #654321;
            --secondary: #D2691E;
            --light: #FAF3E0;
            --dark: #3C2A1E;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        /* Navbar */
        .navbar-custom {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 4px 20px rgba(139, 69, 19, 0.3);
            padding: 15px 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white !important;
        }

        .navbar-brand i {
            font-size: 1.8rem;
        }

        .nav-username {
            color: white;
            margin-right: 20px;
            font-size: 1rem;
        }

        .btn-light {
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background-color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
        }

        /* Search Bar */
        .search-container {
            max-width: 600px;
            margin: 0 auto 30px;
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(210, 105, 30, 0.25);
        }

        /* Category Buttons */
        .category-btn {
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 500;
            margin: 0 5px 10px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            background-color: white;
            color: var(--dark);
        }

        .category-btn:hover, .category-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }

        .category-btn i {
            margin-right: 8px;
        }

        /* Menu Items */
        .menu-list-item {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .menu-list-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(139, 69, 19, 0.15);
        }

        .menu-title {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .menu-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border-radius: 8px;
            border: 2px solid #eaeaea;
        }

        .quantity-input:focus {
            border-color: var(--secondary);
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .order-summary h3 {
            color: var(--primary);
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }

        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .order-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        .btn-danger {
            background-color: var(--danger);
            border: none;
        }

        .btn-success {
            background-color: var(--success);
            border: none;
            font-weight: 500;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        /* Modal Konfirmasi */
        .modal-content {
            border-radius: 15px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-bottom: none;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 700;
        }

        .btn-close-white {
            filter: invert(1);
        }

        .modal-body {
            padding: 25px;
        }

        .confirmation-items {
            max-height: 200px;
            overflow-y: auto;
            margin: 15px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .confirmation-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .confirmation-item:last-child {
            border-bottom: none;
        }

        .total-confirmation {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }

        /* QRIS Section */
        .qris-container {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            border: 2px dashed #dee2e6;
        }

        .qris-image {
            max-width: 250px;
            height: auto;
            margin: 15px auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .qris-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .payment-instruction {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid var(--info);
        }

        .payment-instruction ol {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .payment-instruction li {
            margin-bottom: 5px;
        }

        .payment-success {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-radius: 10px;
            margin: 20px 0;
            display: none;
        }

        .payment-success i {
            font-size: 3rem;
            color: var(--success);
            margin-bottom: 15px;
        }

        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes successAnimation {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .menu-list-item, .order-summary {
            animation: fadeIn 0.5s ease;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        .success-animation {
            animation: successAnimation 0.5s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .order-summary {
                position: static;
                margin-top: 30px;
            }
            
            .category-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            
            .menu-list-item {
                margin-bottom: 15px;
            }
            
            .qris-image {
                max-width: 200px;
            }
        }

        /* Badge untuk jumlah pesanan */
        .order-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .cart-icon-container {
            position: relative;
            display: inline-block;
        }

        .done-btn {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 20px auto 0;
            min-width: 200px;
        }

        .done-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .done-btn:active {
            transform: translateY(-1px);
        }

        .timer-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: rgba(139, 69, 19, 0.1);
            border-radius: 8px;
        }

        .modal-footer-custom {
            display: flex;
            justify-content: center;
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        
        /* Base64 QRIS image as fallback */
        .qris-fallback {
            max-width: 250px;
            height: auto;
            margin: 15px auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="bg-light">

<!-- ==================== NAVBAR ==================== -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <i class="fas fa-coffee me-2"></i> Warkop Gondrong 77
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3">
                    <span class="nav-username">
                        <i class="fas fa-user-circle me-2"></i>Halo, <b><?php echo htmlspecialchars($_SESSION['user']['username']); ?></b>
                    </span>
                </li>
                <li class="nav-item cart-icon-container me-3">
                    <span class="text-white">
                        <i class="fas fa-shopping-cart me-1"></i>
                        <?php if (!empty($order)): ?>
                            <span class="order-badge"><?= array_sum($order) ?></span>
                        <?php endif; ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-light">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- ===================================================== -->

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="container mt-3">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container mt-4 pb-5">

    <!-- SEARCH BAR -->
    <div class="row mb-4">
        <div class="col-lg-8 mx-auto">
            <div class="search-container">
                <form method="GET" class="d-flex">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control border-start-0 ps-0" 
                            placeholder="Cari menu favorit Anda..." 
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        >
                        <?php if (isset($_GET['category'])): ?>
                            <input type="hidden" name="category" value="<?= $_GET['category'] ?>">
                        <?php endif; ?>
                        <button class="btn btn-primary px-4">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CATEGORY BUTTONS -->
    <div class="text-center mb-5">
        <h5 class="mb-3 text-muted">Pilih Kategori</h5>
        <div>
            <a href="?category=makanan" class="btn category-btn <?php echo (isset($_GET['category']) && $_GET['category']=='makanan') ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> Makanan
            </a>
            <a href="?category=minuman" class="btn category-btn <?php echo (isset($_GET['category']) && $_GET['category']=='minuman') ? 'active' : ''; ?>">
                <i class="fas fa-coffee"></i> Minuman
            </a>
            <?php if (isset($_GET['category']) || isset($_GET['search'])): ?>
            <a href="?" class="btn category-btn">
                <i class="fas fa-times"></i> Hapus Filter
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- MENU ITEMS -->
        <div class="col-lg-8">
            <h3 class="mb-4" style="color: var(--primary);">
                <i class="fas fa-list-alt me-2"></i>Menu <?php 
                    if (isset($_GET['category'])) {
                        echo ucfirst($_GET['category']);
                    } else {
                        echo 'Semua';
                    }
                ?>
            </h3>
            
            <?php 
            $search = strtolower($_GET['search'] ?? '');
            $hasItems = false;
            ?>
            
            <div class="row">
                <?php foreach ($menuItems as $item): 
                    // Filter items based on category
                    $showItem = true;

                    if (isset($_GET['category']) && $item['category'] !== $_GET['category']) {
                        $showItem = false;
                    }
                    
                    // Filter based on search
                    if ($search !== '' && strpos(strtolower($item['name']), $search) === false) {
                        $showItem = false;
                    }
                    
                    if (!$showItem) continue;
                    
                    $hasItems = true;
                ?>
                
                <div class="col-sm-6 col-md-4 mb-4">
                    <div class="menu-list-item p-3">
                        <div>
                            <h5 class="menu-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="menu-price"><?= format_rupiah($item['price']); ?></p>
                            <?php if (!empty($item['description'])): ?>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($item['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="item" value="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="input-group">
                                <input type="number" name="quantity" class="form-control quantity-input" value="1" min="1" max="99">
                                <button class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Tambah
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php endforeach; ?>
                
                <?php if (!$hasItems): ?>
                <div class="col-12 text-center py-5">
                    <div class="empty-cart">
                        <i class="fas fa-search fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted mb-3">Menu tidak ditemukan</h4>
                        <p class="text-muted">Coba kata kunci lain atau hapus filter</p>
                        <a href="?" class="btn btn-primary mt-3">
                            <i class="fas fa-redo me-1"></i> Lihat Semua Menu
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ORDER SUMMARY -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="order-summary">
                <h3 class="mb-4 text-center">
                    <i class="fas fa-receipt me-2"></i> Pesanan Anda
                </h3>

                <?php if (empty($order)): ?>
                    <div class="empty-cart text-center">
                        <i class="fas fa-shopping-cart fa-4x mb-4"></i>
                        <h5 class="text-muted mb-3">Keranjang Kosong</h5>
                        <p class="text-muted">Tambahkan menu favorit Anda</p>
                    </div>
                <?php else: ?>

                    <div class="order-items mb-4" style="max-height: 400px; overflow-y: auto;">
                        <?php 
                        $total = 0;
                        foreach ($order as $name => $qty): 
                            $item = find_menu_item($menuItems, $name);
                            $price = $item['price'];
                            $line = $price * $qty;
                            $total += $line;
                        ?>
                        <div class="order-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><b><?= htmlspecialchars($name) ?></b></h6>
                                    <small class="text-muted"><?= format_rupiah($price) ?> x <?= $qty ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold" style="color: var(--primary);"><?= format_rupiah($line) ?></span>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <form method="POST" class="d-flex gap-2 flex-grow-1">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item" value="<?= $name ?>">
                                    <input type="number" name="quantity" class="form-control quantity-input flex-grow-1" value="<?= $qty ?>" min="0" max="99">
                                    <button class="btn btn-sm btn-primary">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item" value="<?= $name ?>">
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus item ini?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-top pt-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Total:</h5>
                            <h4 class="mb-0 fw-bold" style="color: var(--primary);"><?= format_rupiah($total) ?></h4>
                        </div>

                        <div class="d-grid gap-2">
                            <!-- Tombol Bayar dengan Modal Konfirmasi -->
                            <button type="button" class="btn btn-success btn-lg py-3" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                <i class="fas fa-credit-card me-2"></i> Bayar & Cetak Struk
                            </button>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="clear">
                                <button class="btn btn-outline-danger py-3" onclick="return confirm('Yakin ingin menghapus semua pesanan?')">
                                    <i class="fas fa-trash-alt me-2"></i> Bersihkan Keranjang
                                </button>
                            </form>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Pembayaran dengan QRIS -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">
                    <i class="fas fa-qrcode me-2"></i>Pembayaran QRIS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Konfirmasi Pesanan -->
                <div id="step1">
                    <p class="text-center mb-4">Apakah Anda yakin ingin melanjutkan pembayaran?</p>
                    
                    <div class="confirmation-items">
                        <h6 class="mb-3">Detail Pesanan:</h6>
                        <?php 
                        $total = 0;
                        foreach ($order as $name => $qty): 
                            $item = find_menu_item($menuItems, $name);
                            $price = $item['price'];
                            $line = $price * $qty;
                            $total += $line;
                        ?>
                        <div class="confirmation-item">
                            <span><?= htmlspecialchars($name) ?> x<?= $qty ?></span>
                            <span><?= format_rupiah($line) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="total-confirmation">
                        Total Pembayaran: <?= format_rupiah($total) ?>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Setelah dikonfirmasi, Anda akan diarahkan ke halaman pembayaran QRIS.
                    </div>
                </div>

                <!-- Step 2: QRIS Payment -->
                <div id="step2" style="display: none;">
                    <div class="text-center mb-4">
                        <h5 class="mb-3" style="color: var(--primary);">Scan QRIS untuk Membayar</h5>
                        <p class="text-muted">Gunakan aplikasi e-wallet atau mobile banking untuk scan QR code</p>
                    </div>
                    
                    <div class="qris-container">
                        <div class="qris-label">
                            <i class="fas fa-qrcode me-2"></i>QR Code Pembayaran
                        </div>
                        <!-- QRIS Image -->
                        <?php
                        // Cek apakah file QRIS ada
                        $qrisPath = 'Tipe-QRIS-statis-small-large.jpg';
                        if (file_exists($qrisPath)): ?>
                            <img src="<?= $qrisPath ?>" alt="QRIS Payment Code" class="qris-image pulse-animation">
                        <?php else: ?>
                            <!-- Fallback jika file tidak ditemukan -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Gambar QRIS tidak ditemukan. Silakan upload file "Tipe-QRIS-statis-small-large.jpg" ke folder aplikasi.
                            </div>
                            <!-- Placeholder untuk QRIS -->
                            <div style="width: 250px; height: 250px; margin: 15px auto; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                               <img src="Tipe-QRIS-statis-small-large.jpg" alt="">
                            </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <div class="timer-display">
                                <i class="fas fa-clock me-2"></i>
                                <span id="paymentTimer">02:00</span>
                            </div>
                            <p class="text-muted small">QR Code berlaku selama 2 menit</p>
                        </div>
                    </div>
                    
                    
                    <div class="payment-success" id="paymentSuccess">
                        <i class="fas fa-check-circle"></i>
                        <h5 class="mt-3 mb-2" style="color: var(--success);">Pembayaran Berhasil!</h5>
                        <p class="text-muted">Struk akan dicetak secara otomatis</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer untuk Step 1 -->
            <div class="modal-footer" id="step1Footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="proceedToQRIS">
                    <i class="fas fa-arrow-right me-1"></i> Lanjut ke QRIS
                </button>
            </div>
            
            <!-- Footer untuk Step 2 -->
            <div class="modal-footer-custom" id="step2Footer" style="display: none;">
                <button type="button" class="btn btn-secondary me-2" id="backToConfirm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </button>
                <button type="button" class="done-btn" id="paymentDoneBtn">
                    <i class="fas fa-check me-1"></i> Saya Sudah Bayar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fungsi untuk update quantity langsung
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            if (this.closest('form')) {
                this.closest('form').submit();
            }
        });
    });

    // Update badge jumlah pesanan
    function updateOrderBadge() {
        const orderCount = <?= !empty($order) ? array_sum($order) : 0 ?>;
        let badge = document.querySelector('.order-badge');
        
        if (orderCount > 0) {
            if (!badge) {
                const container = document.querySelector('.cart-icon-container');
                badge = document.createElement('span');
                badge.className = 'order-badge';
                container.appendChild(badge);
            }
            badge.textContent = orderCount;
        } else if (badge) {
            badge.remove();
        }
    }

    // Animasi untuk menu items
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.menu-list-item');
        menuItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
        });
        
        updateOrderBadge();
        
        // Inisialisasi modal QRIS
        initQRISModal();
    });

    // Variabel untuk timer pembayaran
    let paymentTimer;
    let timeLeft = 120; // 2 menit dalam detik

    // Fungsi untuk inisialisasi modal QRIS
    function initQRISModal() {
        const modal = document.getElementById('confirmModal');
        const proceedBtn = document.getElementById('proceedToQRIS');
        const backBtn = document.getElementById('backToConfirm');
        const doneBtn = document.getElementById('paymentDoneBtn');
        
        if (proceedBtn) {
            proceedBtn.addEventListener('click', function() {
                // Pindah ke step 2 (QRIS)
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                document.getElementById('step1Footer').style.display = 'none';
                document.getElementById('step2Footer').style.display = 'flex';
                
                // Mulai timer pembayaran
                startPaymentTimer();
            });
        }
        
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                // Kembali ke step 1
                document.getElementById('step1').style.display = 'block';
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step1Footer').style.display = 'flex';
                document.getElementById('step2Footer').style.display = 'none';
                
                // Reset timer
                resetPaymentTimer();
            });
        }
        
        if (doneBtn) {
            doneBtn.addEventListener('click', function() {
                // Tampilkan animasi sukses
                showPaymentSuccess();
            });
        }
        
        // Reset modal saat ditutup
        modal.addEventListener('hidden.bs.modal', function() {
            resetQRISModal();
        });
    }
    
    // Fungsi untuk memulai timer pembayaran
    function startPaymentTimer() {
        const timerElement = document.getElementById('paymentTimer');
        timeLeft = 120;
        
        paymentTimer = setInterval(function() {
            timeLeft--;
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Jika waktu habis
            if (timeLeft <= 0) {
                clearInterval(paymentTimer);
                timerElement.textContent = "00:00";
                alert("Waktu pembayaran telah habis. Silakan ulangi proses pembayaran.");
                document.getElementById('backToConfirm').click();
            }
        }, 1000);
    }
    
    // Fungsi untuk reset timer
    function resetPaymentTimer() {
        if (paymentTimer) {
            clearInterval(paymentTimer);
        }
        document.getElementById('paymentTimer').textContent = "02:00";
        timeLeft = 120;
    }
    
    // Fungsi untuk menampilkan animasi pembayaran berhasil
    function showPaymentSuccess() {
        const doneBtn = document.getElementById('paymentDoneBtn');
        const successDiv = document.getElementById('paymentSuccess');
        
        // Nonaktifkan tombol selama proses
        doneBtn.disabled = true;
        doneBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
        
        // Tampilkan animasi sukses
        setTimeout(function() {
            successDiv.style.display = 'block';
            successDiv.classList.add('success-animation');
            
            // Tunggu 2 detik, lalu submit form pembayaran
            setTimeout(function() {
                submitPayment();
            }, 2000);
        }, 1000);
    }
    
    // Fungsi untuk submit pembayaran
    function submitPayment() {
        // Buat form pembayaran tersembunyi
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'save';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Fungsi untuk reset modal QRIS
    function resetQRISModal() {
        document.getElementById('step1').style.display = 'block';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1Footer').style.display = 'flex';
        document.getElementById('step2Footer').style.display = 'none';
        document.getElementById('paymentSuccess').style.display = 'none';
        
        // Reset tombol done
        const doneBtn = document.getElementById('paymentDoneBtn');
        if (doneBtn) {
            doneBtn.disabled = false;
            doneBtn.innerHTML = '<i class="fas fa-check me-1"></i> Saya Sudah Bayar';
        }
        
        resetPaymentTimer();
    }
</script>
</body>
</html>