<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'auth.php'; 
require 'koneksi.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

function get_order($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND status = 'successful'"); 
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $order = get_order($order_id);

    if (!$order) {
        $_SESSION['message'] = "Order not found or not successful.";
        header("Location: view_orders.php");
        exit;
    }
} else {
    $_SESSION['message'] = "Invalid order ID.";
    header("Location: view_orders.php");
    exit;
}

// Ensure you have a function to format currency
function format_rupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>View Order - Warkop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h2>Order Details (ID: <?= htmlspecialchars($order['id']) ?>)</h2>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-info"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); endif; ?>

        <div class="mb-4">
            <h4>Order Summary</h4>
            <p><strong>Total Amount:</strong> <?= format_rupiah($order['total']) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
        </div>

        <h4>Ordered Items</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Menu Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $item_stmt = $conn->prepare("SELECT mi.name AS menu_name, oi.quantity, oi.price FROM order_items oi JOIN menu mi ON oi.menu_id = mi.id WHERE oi.order_id = ?");
                $item_stmt->bind_param("i", $order['id']);
                $item_stmt->execute();
                $items_result = $item_stmt->get_result();

                while ($item = $items_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['menu_name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td><?= format_rupiah($item['price']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="view_orders.php" class="btn btn-secondary">Back to Orders</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>