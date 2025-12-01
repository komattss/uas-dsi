<?php
session_start();
require 'auth.php';
require 'koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Format Rupiah
function format_rupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Cari item menu
function find_menu_item($menuItems, $name) {
    foreach ($menuItems as $m) {
        if ($m['name'] === $name) return $m;
    }
    return null;
}

// Handle image path or placeholder
function get_image_src($path, $alt = '') {
    $full = __DIR__ . '/' . ltrim($path, '/');
    if (file_exists($full)) return $path;

    $text = htmlspecialchars($alt ?: 'Image');
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='600' height='400'>
            <rect width='100%' height='100%' fill='%23f4f4f4'/>
            <text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle'
            font-family='Arial' font-size='24' fill='%23654321'>{$text}</text>
            </svg>";

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

// === DEKLARASI MENU (PASTIKAN HANYA SATU $menuItems = [...]) ===
$menuItems = [
    ["name" => "Cappuccino", "price" => 10000, "description" => "Kombinasi kopi, susu, dan gula jawa membuat minuman ini nikmat.", "image" => "images/cappuccino.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Coffee Latte", "price" => 10000, "description" => "Coffee latte, salah satu jenis kopi susu populer.", "image" => "images/coffee_latte.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Aren Latte", "price" => 10000, "description" => "Aren Latte adalah kopi susu yang lembut dan segar.", "image" => "images/aren_latte.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Americano", "price" => 8000, "description" => "Americano adalah espresso dicampur air panas.", "image" => "images/americano.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Caramel Macchiato", "price" => 15000, "description" => "Coffee latte dengan rasa karamel.", "image" => "images/caramel_macchiato.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Flat White", "price" => 12000, "description" => "Coffee latte dengan cita rasa lembut.", "image" => "images/flat_white.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Espresso", "price" => 10000, "description" => "Espresso adalah minuman kopi yang paling sederhana.", "image" => "images/espresso.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Mocha", "price" => 12000, "description" => "Mocha adalah perpaduan kopi dan cokelat.", "image" => "images/mocha.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Macchiato", "price" => 12000, "description" => "Macchiato adalah espresso dengan rasa karamel.", "image" => "images/macchiato.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Latte", "price" => 12000, "description" => "Latte adalah espresso dengan susu.", "image" => "images/latte.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Latte Caramel", "price" => 15000, "description" => "Latte Caramel adalah espresso dengan rasa karamel.", "image" => "images/latte_caramel.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Latte Mocha", "price" => 15000, "description" => "Latte Mocha adalah espresso dengan perpaduan kopi dan cokelat.", "image" => "images/latte_mocha.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Latte Frappe", "price" => 15000, "description" => "Latte Frappe adalah espresso dengan perpaduan kopi dan cokelat.", "image" => "images/latte_frappe.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Latte Macchiato", "price" => 15000, "description" => "Latte Macchiato adalah espresso dengan rasa karamel.", "image" => "images/latte_macchiato.svg", "category" => "minuman", "type" => "coffee"],
    ["name" => "Affogato", "price" => 15000, "description" => "Affogato adalah espresso dengan es krim vanila.", "image" => "images/affogato.svg", "category" => "minuman", "type" => "coffee"],

    // Non-coffee
    ["name" => "Chocolate", "price" => 12000, "description" => "Cokelat panas manis.", "image" => "images/chocolate.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Matcha Latte", "price" => 13000, "description" => "Green tea latte.", "image" => "images/matcha.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Taro Latte", "price" => 13000, "description" => "Taro latte ungu lembut.", "image" => "images/taro.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Red Velvet Latte", "price" => 14000, "description" => "Red velvet latte manis.", "image" => "images/redvelvet.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Vanilla Milk", "price" => 12000, "description" => "Susu vanilla segar.", "image" => "images/vanilla_milk.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Lemon Tea", "price" => 10000, "description" => "Teh lemon segar.", "image" => "images/lemon_tea.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Lychee Tea", "price" => 12000, "description" => "Teh leci dingin.", "image" => "images/lychee_tea.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Strawberry Milk", "price" => 13000, "description" => "Susu stroberi segar.", "image" => "images/strawberry_milk.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Mango Juice", "price" => 15000, "description" => "Jus mangga segar.", "image" => "images/mango_juice.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Orange Juice", "price" => 15000, "description" => "Jus jeruk segar.", "image" => "images/orange_juice.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Coconut Water", "price" => 10000, "description" => "Air kelapa segar.", "image" => "images/coconut_water.svg", "category" => "minuman", "type" => "non-coffee"],
    ["name" => "Mineral Water", "price" => 5000, "description" => "Air mineral botol.", "image" => "images/mineral_water.svg", "category" => "minuman", "type" => "non-coffee"],

    // makanan
    ["name" => "Sandwich", "price" => 15000, "description" => "Sandwich lezat dengan bahan segar.", "image" => "images/sandwich.svg", "category" => "makanan"],
    ["name" => "Pasta", "price" => 20000, "description" => "Pasta Italia klasik dengan saus gurih.", "image" => "images/pasta.svg", "category" => "makanan"],
    ["name" => "Salad", "price" => 12000, "description" => "Salad segar dengan dressing pilihan.", "image" => "images/salad.svg", "category" => "makanan"],
    ["name" => "Burger", "price" => 25000, "description" => "Burger daging sapi dengan topping lengkap.", "image" => "images/burger.svg", "category" => "makanan"],
    ["name" => "Fries", "price" => 10000, "description" => "Kentang goreng renyah dengan saus.", "image" => "images/fries.svg", "category" => "makanan"],
    ["name" => "Pizza", "price" => 30000, "description" => "Pizza segar dengan topping pilihan.", "image" => "images/pizza.svg", "category" => "makanan"],
    ["name" => "Steak", "price" => 40000, "description" => "Steak daging sapi kualitas tinggi.", "image" => "images/steak.svg", "category" => "makanan"],
    ["name" => "Sushi", "price" => 30000, "description" => "Sushi segar dengan saus pilihan.", "image" => "images/sushi.svg", "category" => "makanan"],
    ["name" => "Cake", "price" => 15000, "description" => "Kue lezat untuk pencuci mulut.", "image" => "images/cake.svg", "category" => "makanan"],
    ["name" => "Rice Bowl", "price" => 20000, "description" => "Nasi dengan lauk pauk pilihan.", "image" => "images/rice_bowl.svg", "category" => "makanan"],
    ["name" => "Taco", "price" => 15000, "description" => "Taco segar dengan saus pilihan.", "image" => "images/taco.svg", "category" => "makanan"],
    ["name" => "Soup", "price" => 12000, "description" => "Soup segar dengan bahan segar.", "image" => "images/soup.svg", "category" => "makanan"],
];

// === FILTERED ARRAYS (PASTIKAN INI DISETEL SETELAH $menuItems) ===
$coffeeItems = array_values(array_filter($menuItems, fn($item) => isset($item['type']) && $item['type'] === 'coffee'));
$nonCoffeeItems = array_values(array_filter($menuItems, fn($item) => isset($item['type']) && $item['type'] === 'non-coffee'));

// === MAIN PAGE LOGIC
if (!isset($_SESSION['order'])) $_SESSION['order'] = [];

// Handle actions
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

        require_once __DIR__ . "/koneksi.php";

        $conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, created_at DATETIME, total BIGINT)");
        $conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, name VARCHAR(255), price BIGINT, quantity INT, line_total BIGINT)");

        $total = 0;
        foreach ($_SESSION['order'] as $name => $qty) {
            $item = find_menu_item($menuItems, $name);
            $total += $item['price'] * $qty;
        }

        $conn->begin_transaction();
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO orders (created_at, total) VALUES (?, ?)");
            $stmt->bind_param("si", $now, $total);
            $stmt->execute();
            $orderId = $stmt->insert_id;

            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, name, price, quantity, line_total) VALUES (?,?,?,?,?)");

            foreach ($_SESSION['order'] as $name => $qty) {
                $item = find_menu_item($menuItems, $name);
                $price = $item['price'];
                $line = $price * $qty;

                $stmtItem->bind_param("isiii", $orderId, $name, $price, $qty, $line);
                $stmtItem->execute();
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
    $params = [];
    if (isset($_GET['category'])) $params['category'] = $_GET['category'];
    if (isset($_GET['type'])) $params['type'] = $_GET['type'];
    if (!empty($params)) $redirect .= "?" . http_build_query($params);
    header("Location: $redirect");
    exit;
}
// PRINT PAGE
if (isset($_GET['action']) && $_GET['action'] === 'print') {
    $orderId = intval($_GET['order_id'] ?? 0);
    if (!$orderId) { echo "Order ID tidak ditemukan."; exit; }

    // Ambil data order
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderData = $stmt->get_result()->fetch_assoc();

    if (!$orderData) { echo "Data order tidak ditemukan."; exit; }

    // Ambil item order
    $stmt2 = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt2->bind_param("i", $orderId);
    $stmt2->execute();
    $items = $stmt2->get_result();
    ?>
    
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Struk Pembelian</title>
        <style>
            body {
                font-family: monospace;
                width: 260px;
                margin: 0;
                padding: 10px;
                font-size: 12px;
            }
            .center { text-align: center; }
            .line { display: flex; justify-content: space-between; }
            hr { border: 0; border-top: 1px dashed #000; margin: 8px 0; }
        </style>
    </head>
    <body onload="window.print()">

        <div class="center">
            <strong>WARKOP GONDRONG 77</strong><br>
            <small>Jl. Barokah No. 23</small><br>
            <small>Telp: 0812-3456-7890</small>
        </div>

        <hr>

        <div>
            Tanggal: <?= $orderData['created_at'] ?><br>
            Order: <?= $orderId ?><br>
        </div>

        <hr>

        <?php while ($row = $items->fetch_assoc()): ?>
            <div class="line">
                <span><?= $row['name'] ?> x<?= $row['quantity'] ?></span>
                <span><?= format_rupiah($row['line_total']) ?></span>
            </div>
        <?php endwhile; ?>

        <hr>

        <div class="line">
            <strong>Total</strong>
            <strong><?= format_rupiah($orderData['total']) ?></strong>
        </div>

        <hr>

        <div class="center">
            <small>Terima Kasih!</small><br>
            <small>🙏</small>
        </div>

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
    <title>Pemesanan Kopi - Warkop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-light">

<!-- ==================== NAVBAR BARU ==================== -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container">
        
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <i class="fas fa-coffee me-2"></i> Warkop Gondrong 77
        </a>

        <!-- Toggle mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center">

                <!-- Username -->
                <span class="nav-username">
                    Halo, <b><?php echo $_SESSION['user']['username']; ?></b>
                </span>

                <!-- Logout -->
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>
<!-- ======================================================= -->

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="container mt-3">
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container mt-4">

    <!-- SEARCH BAR -->
    <div class="row mb-4">
        <div class="col-lg-6 mx-auto">
            <form method="GET" class="d-flex">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Cari menu..." 
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                >
                <?php if (isset($_GET['category'])): ?>
                    <input type="hidden" name="category" value="<?= $_GET['category'] ?>">
                <?php endif; ?>
                <button class="btn btn-primary ms-2">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- CATEGORY BUTTONS -->
    <div class="text-center mb-4">
        <a href="?type=coffee" class="btn category-btn <?php echo (isset($_GET['type']) && $_GET['type']=='coffee') ? 'active' : ''; ?>">
            <i class="fas fa-mug-hot"></i> Coffee
        </a>
        <a href="?type=non-coffee" class="btn category-btn <?php echo (isset($_GET['type']) && $_GET['type']=='non-coffee') ? 'active' : ''; ?>">
            <i class="fas fa-leaf"></i> Non-Coffee
        </a>
        <a href="?category=makanan" class="btn category-btn <?php echo ($currentCategory=='makanan') ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i> Makanan
        </a>
    </div>

<div class="row">
    <!-- MENU ITEMS -->
    <div class="col-lg-8">
        <div class="row">
            <?php 
            $search = strtolower($_GET['search'] ?? '');
            foreach ($menuItems as $item): 
                if (isset($_GET['category']) && $_GET['category'] == 'makanan') {
                    if ($item['category'] !== 'makanan') continue;
                } else if (isset($_GET['type'])) {
                    if (!isset($item['type']) || $item['type'] !== $_GET['type']) continue;
                } else {
                    continue; // tidak tampilkan apapun jika tidak ada filter
                }
                if ($search !== '' && strpos(strtolower($item['name']), $search) === false) continue;
            ?>
            
            <div class="col-sm-6 col-md-4 mb-3">
                <div class="menu-list-item p-3 rounded shadow-sm bg-white h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="menu-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <p class="menu-price"><?php echo format_rupiah($item['price']); ?></p>
                    </div>
                    <form method="POST" class="mt-2 d-flex align-items-center gap-2">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="item" value="<?php echo htmlspecialchars($item['name']); ?>">
                        <input type="number" name="quantity" class="form-control quantity-input" value="1" min="1">
                        <button class="btn btn-primary w-100">Tambah</button>
                    </form>
                </div>
            </div>

            <?php endforeach; ?>
        </div>
    </div>
        <!-- ORDER SUMMARY -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="order-summary">
                <h3 class="h4 mb-4 text-center">
                    <i class="fas fa-receipt me-2"></i> Pesanan
                </h3>

                <?php if (empty($order)): ?>
                    <div class="empty-cart text-center">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Tidak ada pesanan</p>
                    </div>
                <?php else: ?>

                    <div class="order-items mb-4">
                        <?php 
                        $total = 0;
                        foreach ($order as $name => $qty): 
                            $item = find_menu_item($menuItems, $name);
                            $price = $item['price'];
                            $line = $price * $qty;
                            $total += $line;
                        ?>
                        <div class="order-item d-flex justify-content-between align-items-center">
                            <div>
                                <b><?= htmlspecialchars($name) ?></b><br>
                                <small><?= format_rupiah($price) ?> x <?= $qty ?></small>
                            </div>

                            <div class="d-flex align-items-center">
                                <form method="POST" class="me-2 d-flex">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item" value="<?= $name ?>">
                                    <input type="number" name="quantity" class="quantity-input me-2" value="<?= $qty ?>" min="0">
                                    <button class="btn btn-sm btn-primary">Update</button>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item" value="<?= $name ?>">
                                    <button class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>

                            <span class="fw-bold ms-3"><?= format_rupiah($line) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total:</span>
                            <span class="fs-5"><?= format_rupiah($total) ?></span>
                        </div>

                        <div class="d-grid gap-2">
                            <form method="POST" target="_blank">
                                <input type="hidden" name="action" value="save">
                                <button class="btn btn-success"><i class="fas fa-save"></i> Simpan & Cetak</button>
                            </form>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="clear">
                                <button class="btn btn-outline-danger">Bersihkan Keranjang</button>
                            </form>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>