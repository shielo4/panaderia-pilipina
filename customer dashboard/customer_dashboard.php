<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login%20form/customer_login.php?error=Please+login+as+customer');
    exit();
}

require_once __DIR__ . '/../order_store.php';
require_once __DIR__ . '/../account_store.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login%20form/index.php');
    exit();
}

$products = [
    'Pandesal' => 8,
    'Ensaymada' => 35,
    'Ube Bread' => 45,
    'Spanish Bread' => 12,
    'Cheese Roll' => 15,
    'Hopia' => 20,
    'Monay' => 18,
    'Pan de Coco' => 25,
    'Putok (Star Bread)' => 15,
    'Pan de Monggo' => 20,
    'Bicho-Bicho' => 18,
    'Pinagong' => 22,
    'Pan de Leche' => 25,
    'Pianono' => 30,
];
$productImages = [
    'Pandesal' => '../image/images.jpeg',
    'Ensaymada' => '../image/823728781_1319838950089602_6463677968597898602_n.jpg',
    'Ube Bread' => '../image/images%20%281%29.jpeg',
    'Spanish Bread' => '../image/images%20%282%29.jpeg',
    'Cheese Roll' => '../image/images%20%283%29.jpeg',
    'Hopia' => '../image/817176278_1125760289882458_6785903130317121759_n.jpg',
    'Monay' => '../image/823728781_1319838950089602_6463677968597898602_n%20copy.jpg',
    'Pan de Coco' => '../image/slices-of-pan-de-coco-filipino-bread-roll.jpg',
    'Putok (Star Bread)' => '../image/featurred-pinagong-turtle-bread.jpg',
    'Pan de Monggo' => '../image/pan-de-leche-3.webp',
    'Bicho-Bicho' => '../image/sddefault.jpg',
    'Pinagong' => '../image/817176278_1125760289882458_6785903130317121759_n.jpg',
    'Pan de Leche' => '../image/images.jpeg',
    'Pianono' => '../image/images%20%281%29.jpeg',
];
$productCategories = [
    'Pandesal' => 'classic',
    'Ensaymada' => 'classic',
    'Ube Bread' => 'special',
    'Spanish Bread' => 'classic',
    'Cheese Roll' => 'savory',
    'Hopia' => 'classic',
    'Monay' => 'fresh',
    'Pan de Coco' => 'classic',
    'Putok (Star Bread)' => 'classic',
    'Pan de Monggo' => 'fresh',
    'Bicho-Bicho' => 'fresh',
    'Pinagong' => 'classic',
    'Pan de Leche' => 'fresh',
    'Pianono' => 'special',
];
$allOrders = loadAllCustomerOrders();
$salesByProduct = array_fill_keys(array_keys($products), 0);
foreach ($allOrders as $order) {
    foreach (($order['items'] ?? []) as $product => $quantity) {
        if (array_key_exists($product, $salesByProduct)) {
            $salesByProduct[$product] += max(0, (int)$quantity);
        }
    }
}
$rankedSales = array_filter($salesByProduct, static fn($quantity) => $quantity > 0);
arsort($rankedSales);
$rankedQuantities = array_values($rankedSales);
$bestSellerCutoff = $rankedQuantities[min(2, count($rankedQuantities) - 1)] ?? 0;
$bestSellerProducts = $bestSellerCutoff > 0
    ? array_keys(array_filter($rankedSales, static fn($quantity) => $quantity >= $bestSellerCutoff))
    : [];
$bestSellerSet = array_fill_keys($bestSellerProducts, true);
$categories = [
    'all' => 'All Menu',
    'best_seller' => 'Best Seller',
    'classic' => 'Classic',
    'fresh' => 'Fresh Picks',
    'savory' => 'Savory',
    'special' => 'Specialty',
];
$searchQuery = trim($_GET['search'] ?? '');
$selectedCategory = $_GET['category'] ?? 'all';
if (!isset($categories[$selectedCategory])) {
    $selectedCategory = 'all';
}
$filteredProducts = [];
foreach ($products as $product => $price) {
    $isBestSeller = isset($bestSellerSet[$product]);
    $matchesCategory = $selectedCategory === 'all' || ($selectedCategory === 'best_seller' ? $isBestSeller : ($productCategories[$product] ?? 'classic') === $selectedCategory);
    $searchText = strtolower($product . ' bread ' . str_replace('_', ' ', $productCategories[$product] ?? '') . ($isBestSeller ? ' best seller' : ''));
    $matchesSearch = $searchQuery === '' || strpos($searchText, strtolower($searchQuery)) !== false;
    if ($matchesCategory && $matchesSearch) {
        $filteredProducts[$product] = $price;
    }
}
$featuredProductOrder = array_merge($bestSellerProducts, array_values(array_diff(array_keys($filteredProducts), $bestSellerProducts)));
$cart = $_SESSION['cart'] ?? [];
$customerName = trim((string)($_SESSION['username'] ?? 'Customer'));
$customerEmail = strtolower(trim((string)($_SESSION['customer_email'] ?? '')));
if ($customerEmail === '') {
    foreach (loadCustomerAccounts() as $account) {
        $storedName = trim((string)($account['full_name'] ?? ''));
        $storedUsername = trim((string)($account['username'] ?? ''));
        if (strtolower($storedName) === strtolower($customerName) || strtolower($storedUsername) === strtolower($customerName)) {
            $customerEmail = strtolower(trim((string)($account['email'] ?? '')));
            break;
        }
    }
}
if ($customerEmail === '') {
    $customerEmail = strtolower($customerName);
}
$_SESSION['customer_email'] = $customerEmail;
$paymentMessage = '';
$placedOrder = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product = trim($_POST['product'] ?? '');
    if (isset($products[$product])) {
        $cart[$product] = ($cart[$product] ?? 0) + 1;
        $_SESSION['cart'] = $cart;
    }
    header('Location: customer_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $product = trim($_POST['product'] ?? '');
    $change = (int) ($_POST['change'] ?? 0);
    if (isset($products[$product]) && in_array($change, [-1, 1], true)) {
        $cart[$product] = ($cart[$product] ?? 0) + $change;
        if ($cart[$product] <= 0) {
            unset($cart[$product]);
        }
        $_SESSION['cart'] = $cart;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $gcashNumber = trim($_POST['gcash_number'] ?? '');
    $customerAddress = trim($_POST['customer_address'] ?? '');

    if ($cart !== [] && $customerAddress !== '' && in_array($paymentMethod, ['Cash on Delivery', 'GCash', 'Maya'], true) && (preg_match('/^[0-9]{11}$/', $gcashNumber) || $paymentMethod === 'Cash on Delivery')) {
        $total = 0;
        foreach ($cart as $product => $quantity) {
            $total += $products[$product] * $quantity;
        }
        $placedOrder = [
            'id' => '#PP-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'date' => date('M j, Y'),
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'status' => 'Success',
            'items' => $cart,
            'total' => $total,
            'payment' => $paymentMethod,
            'address' => $customerAddress,
            'contactNumber' => $gcashNumber,
        ];
        if (saveCustomerOrder($placedOrder)) {
            $paymentMessage = 'Order placed for ' . htmlspecialchars($customerName) . ' via ' . htmlspecialchars($paymentMethod) . ' for PHP ' . number_format($total, 2) . '. Delivery address: ' . htmlspecialchars($customerAddress) . '.';
            $_SESSION['cart'] = [];
            $cart = [];
        } else {
            $paymentMessage = 'Could not save your order record. Your cart is still available; please try again.';
            $placedOrder = null;
        }
    } else {
        $paymentMessage = 'Please add bread to your cart, enter your delivery address, choose a payment method, and enter an 11-digit contact number or payment number when required.';
    }
}

$cartTotal = 0;
foreach ($cart as $product => $quantity) {
    $cartTotal += $products[$product] * $quantity;
}
$customerOrders = array_values(array_filter($allOrders, static function ($order) use ($customerEmail): bool {
    return strtolower((string)($order['customerEmail'] ?? '')) === $customerEmail;
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panaderia Pilipina | Customer Dashboard</title>
    <style>
        :root { --brown: #7c2d12; --deep-brown: #431407; --gold: #d97706; --cream: #fffaf0; --ink: #292524; --muted: #78716c; --line: #f1d9b5; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; color: var(--ink); background: #fff7ed fixed; }
        .topbar { background: var(--deep-brown); color: #fff7ed; padding: 18px 5vw; display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .brand { font-size: 1.35rem; font-weight: 800; letter-spacing: .04em; }
        .brand span { display: block; color: #fbbf24; font-size: .75rem; font-weight: 500; letter-spacing: .14em; margin-top: 4px; }
        .user-actions { display: flex; align-items: center; gap: 12px; }
        .logged-user { color: #fef3c7; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.03em; }
        .logout-btn { color: #fff; border: 1px solid #fbbf24; border-radius: 7px; padding: 9px 14px; text-decoration: none; font-size: .9rem; font-weight: 700; }
        main { width: min(1800px, 98%); margin: auto; padding: 42px 0 56px; }
        .welcome { display: flex; justify-content: space-between; align-items: end; gap: 24px; margin-bottom: 30px; }
        .eyebrow { color: var(--gold); font-size: .78rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; margin: 0 0 9px; }
        h1 { margin: 0 0 8px; color: var(--brown); font-size: clamp(2rem, 4vw, 3rem); }
        .welcome p { color: var(--muted); margin: 0; }
        .pay-btn { background: var(--gold); color: #fff; padding: 13px 18px; border: 0; border-radius: 8px; text-decoration: none; font-weight: 800; cursor: pointer; }
        .content-grid { position: relative; display: block; }
        #featured { position: relative; width: 100%; min-height: 580px; }
        #orders { position: absolute; z-index: 5; top: 68px; right: 24px; width: min(390px, calc(100% - 48px)); height: min(68vh, 520px); max-height: min(68vh, 520px); min-height: 0; overflow-x: auto; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; box-shadow: 0 16px 40px rgba(41, 37, 36, .24); }
        #payment { width: 100%; margin-top: 22px; }
        .section, .product-card { background: #fff; border: 1px solid var(--line); border-radius: 10px; }
        .section { padding: 24px; }
        .section-heading { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        h2 { color: var(--brown); font-size: 1.25rem; margin: 0; }
        .section-link { color: var(--gold); text-decoration: none; font-size: .85rem; font-weight: 700; }
        .dashboard-footer { margin-top: 26px; padding: 32px 0; background: #111; color: #f5f5f5; }
        .footer-inner { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(240px, 1fr) minmax(220px, .8fr); gap: 30px; width: min(1800px, 98%); margin: 0 auto; text-align: center; }
        .footer-title { margin: 0 0 12px; color: #fff; font-size: 1rem; font-weight: 800; }
        .footer-description { max-width: 620px; margin: 0 auto 18px; color: #d4d4d4; line-height: 1.6; }
        .footer-categories { margin: 0; }
        .footer-categories ul { display: grid; grid-template-rows: repeat(4, auto); grid-auto-flow: column; grid-auto-columns: minmax(100px, 1fr); justify-items: center; gap: 0 10px; overflow-x: auto; margin: 0; padding: 0; list-style: none; }
        .footer-categories a { display: inline-block; padding: 1px 0; color: #e5e5e5; font-size: .9rem; text-decoration: none; }
        .footer-categories a:hover, .footer-contact a:hover { color: #fbbf24; }
        .footer-contact { display: flex; flex-direction: column; justify-content: flex-start; align-items: center; gap: 6px; color: #d4d4d4; font-size: .9rem; line-height: 1.5; }
        .footer-contact .footer-title { margin-bottom: 2px; }
        .footer-contact span { display: block; }
        .footer-contact a { color: #fbbf24; font-weight: 700; text-decoration: none; }
        .search-box { margin-bottom: 18px; }
        .search-input { width: 100%; padding: 14px 16px; border: 1px solid var(--line); border-radius: 10px; background: #fff; font-size: 1rem; color: var(--ink); }
        .category-strip { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; margin-bottom: 18px; scrollbar-width: thin; }
        .category-pill { white-space: nowrap; display: inline-block; padding: 9px 14px; border: 1px solid var(--line); border-radius: 999px; background: #fffaf0; color: var(--brown); text-decoration: none; font-size: .84rem; font-weight: 700; }
        .category-pill.active { background: var(--brown); color: #fff; border-color: var(--brown); }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); align-items: stretch; gap: 12px; }
        .search-empty { display: none; margin: 16px 0 0; color: var(--muted); }
        .product-card { display: flex; flex-direction: column; min-height: 200px; overflow: hidden; background: var(--cream); position: relative; }
        .product-card[hidden] { display: none; }
        .product-art { height: 92px; overflow: hidden; background: #f7d9a6; }
        .product-art img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
        .product-badge { position: absolute; top: 10px; right: 10px; background: rgba(255, 191, 36, 0.95); color: var(--deep-brown); font-size: .65rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; padding: 5px 8px; border-radius: 999px; }
        .product-rating { margin: 6px 0 0; color: var(--gold); font-size: .8rem; }
        .product-info { display: flex; flex: 1; flex-direction: column; padding: 12px; }
        .product-info strong { display: -webkit-box; height: 2.4em; overflow: hidden; color: var(--brown); margin-bottom: 5px; line-height: 1.2em; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .price { display: block; min-height: 1.2em; color: var(--muted); font-size: .86rem; }
        .product-rating { min-height: 1em; margin: 6px 0 0; color: var(--gold); font-size: .8rem; }
        .product-stock {
            margin-top: auto;
            color: var(--brown);
            font-size: .8rem;
            font-weight: 800;
            text-align: right;
        }
        .product-stock.out-of-stock {
            color: #b91c1c;
        }
        .cart-form { margin-top: auto; }
        .buy-btn { width: 100%; margin-top: 6px; padding: 7px 10px; border: 0; border-radius: 6px; background: var(--gold); color: #fff; cursor: pointer; font-size: .78rem; font-weight: 700; }
        .buy-btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .cart-table, .orders { width: 100%; border-collapse: collapse; font-size: .9rem; }
        .orders { min-width: 620px; }
        .cart-table th, .cart-table td, .orders th, .orders td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #f5e8d3; }
        .cart-table th, .orders th { color: var(--muted); font-size: .75rem; text-transform: uppercase; }
        .quantity-controls { display: inline-flex; align-items: center; gap: 8px; }
        .quantity-btn { width: 28px; height: 28px; padding: 0; border: 1px solid var(--line); border-radius: 6px; background: #fffaf0; color: var(--brown); font-size: 1rem; font-weight: 800; cursor: pointer; }
        .quantity-btn:disabled { opacity: .55; cursor: wait; }
        .cart-total { color: var(--brown); font-size: 1.1rem; font-weight: 800; text-align: right; }
        .empty-cart { color: var(--muted); }
        .payment-form { display: block; gap: 14px; }
        .payment-field label, .gcash-number-label { display: block; margin-bottom: 6px; color: var(--brown); font-size: .82rem; font-weight: 700; }
        .payment-field select, .gcash-number-input, #customer_address { width: 100%; padding: 11px; border: 1px solid var(--line); border-radius: 7px; background: #fff; color: var(--ink); }
        .gcash-number-input { max-width: 360px; display: block; margin: 0 auto; text-align: center; letter-spacing: .04em; }
        #customer_address { min-height: 110px; resize: vertical; }
        .payment-methods-row { width: 100%; }
        .payment-options { display: flex; flex-direction: row; gap: 10px; flex-wrap: nowrap; width: 100%; }
        .payment-option { flex: 1 1 0; min-width: 140px; padding: 12px 14px; border: 1px solid var(--line); border-radius: 10px; background: #fffaf0; color: var(--brown); font-weight: 700; cursor: pointer; }
        .payment-option.active { background: var(--brown); color: #fff; border-color: var(--brown); }
        .gcash-details { display: none; grid-column: 1 / -1; padding: 16px; border: 1px solid #93c5fd; border-radius: 9px; background: #eff6ff; }
        .place-order-btn { width: 100%; min-width: 180px; padding: 14px 18px; margin-top: 8px; }
        .gcash-details.visible { display: flex; align-items: center; gap: 18px; }
        .gcash-details img { width: 132px; height: 132px; border: 6px solid #fff; border-radius: 6px; }
        .gcash-details strong { display: block; color: #1d4ed8; margin-bottom: 7px; }
        .gcash-number-label { color: #1e3a8a; margin-top: 14px; }
        .gcash-note { color: #475569; font-size: .82rem; margin: 7px 0 0; }
        .payment-message { margin: 0 0 18px; padding: 12px 14px; border-radius: 7px; background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; font-weight: 600; }
        .status { color: #15803d; font-weight: 700; }
        @media (max-width: 760px) { main { width: 94%; } #featured { min-height: 0; } #orders { position: static; width: 100%; height: 70vh; max-height: 70vh; min-height: 0; margin-top: 14px; } .footer-inner { width: 94%; grid-template-columns: 1fr; gap: 22px; } .welcome, .content-grid { display: grid; grid-template-columns: 1fr; align-items: start; } .products { grid-template-columns: 1fr; } .product-card { display: grid; grid-template-columns: 90px 1fr; } .product-art { height: 100%; } .payment-form, .gcash-details.visible { grid-template-columns: 1fr; display: grid; } }
    </style>
</head>
<body>
<div id="new-order-data" hidden data-order="<?php echo htmlspecialchars(json_encode($placedOrder), ENT_QUOTES, 'UTF-8'); ?>"></div>
<header class="topbar">
    <div class="brand">Panaderia Pilipina<span>Customer Portal</span></div>
    <div class="user-actions">
        <span class="logged-user"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a class="logout-btn" href="customer_dashboard.php?logout=1">Log Out</a>
    </div>
</header>
<main>
    <section class="welcome"><div><h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>.</h1><p>Fresh favorites and your order activity, all in one place.</p></div></section>
    <div class="content-grid">
        <section class="section" id="featured">
            <div class="section-heading"><h2>Featured today</h2><a class="section-link" id="show_recent_orders" href="#orders" aria-expanded="false">Recent orders</a></div>
            <div class="search-box">
                <form method="GET">
                    <input class="search-input" id="bread_search" type="search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search bread, favorites, or treats..." autocomplete="off">
                </form>
            </div>
            <div class="category-strip">
                <?php foreach ($categories as $slug => $label): ?>
                    <a class="category-pill <?php echo $selectedCategory === $slug ? 'active' : ''; ?>" href="customer_dashboard.php?category=<?php echo urlencode($slug); ?>&search=<?php echo urlencode($slug === 'all' ? '' : $searchQuery); ?>"><?php echo htmlspecialchars($label); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="products" id="product_list">
                <?php foreach ($featuredProductOrder as $product): ?>
                    <?php $price = $filteredProducts[$product]; ?>
                    <article class="product-card" data-search="<?php echo htmlspecialchars(strtolower($product . ' bread ' . str_replace('_', ' ', $productCategories[$product] ?? '') . (isset($bestSellerSet[$product]) ? ' best seller' : ''))); ?>">
                        <?php if (isset($bestSellerSet[$product])): ?>
                            <div class="product-badge">Best Seller</div>
                        <?php endif; ?>
                        <div class="product-art">
                            <img src="<?php echo htmlspecialchars($productImages[$product] ?? 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80'); ?>" alt="<?php echo htmlspecialchars($product); ?>">
                        </div>
                        <div class="product-info">
                            <strong><?php echo htmlspecialchars($product); ?></strong>
                            <div class="product-rating" <?php echo isset($bestSellerSet[$product]) ? '' : 'aria-hidden="true"'; ?>><?php echo isset($bestSellerSet[$product]) ? '★★★★★' : '&nbsp;'; ?></div>
                            <span class="price">From PHP <?php echo number_format($price, 2); ?></span>
                            <div class="product-stock" data-product="<?php echo htmlspecialchars($product); ?>">0</div>
                            <form class="cart-form" method="POST"><input type="hidden" name="product" value="<?php echo htmlspecialchars($product); ?>"><button class="buy-btn" type="submit" name="add_to_cart">Add to cart</button></form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="search-empty" id="search_empty">No bread found for your search.</p>
        </section>

        <section class="section" id="orders" hidden><div class="section-heading"><h2>Recent orders</h2><a class="section-link" id="see_all_orders" href="#all-orders">View all</a></div><table class="orders"><thead><tr><th>Order</th><th>Bread purchased</th><th>Amount paid</th><th>Date</th><th>Status</th></tr></thead><tbody id="recent_orders_body"></tbody></table><div id="all-orders" hidden><table class="orders"><thead><tr><th>Order</th><th>Bread purchased</th><th>Amount paid</th><th>Date</th><th>Status</th></tr></thead><tbody id="all_orders_body"></tbody></table></div></section>

        <?php include __DIR__ . '/../payment.php'; ?>
    </div>
</main>
<footer class="dashboard-footer">
    <div class="footer-inner">
        <section class="footer-menu">
            <p class="footer-title">Menu Categories</p>
            <nav class="footer-categories" aria-label="Bread menu">
                <ul>
                    <?php foreach ($products as $breadName => $price): ?>
                        <li><a href="customer_dashboard.php?search=<?php echo urlencode($breadName); ?>"><?php echo htmlspecialchars($breadName); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </section>
        <section class="footer-about" aria-label="About Panaderia Pilipina">
            <p class="footer-title">Panaderia Pilipina</p>
            <p class="footer-description">Panaderia Pilipina serves freshly baked Filipino breads and bakery favorites, made for everyday merienda and sharing.</p>
        </section>
        <aside class="footer-contact" aria-label="Admin contact">
            <p class="footer-title">Admin Contact</p>
            <span>Facebook: <a href="https://www.facebook.com/share/1HePFLeTMX/" target="_blank" rel="noopener noreferrer">Shielo M. Toyco</a></span>
            <span>Phone: <a href="tel:09515773435">09515773435</a></span>
            <span>Location: Brgy Granada, Sitio. Piagao 2</span>
        </aside>
    </div>
</footer>
<script>
    const recentOrdersBody = document.getElementById('recent_orders_body');
    const inventoryStorageKey = 'panaderia_inventory';
    const serverOrders = <?php echo json_encode($customerOrders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const newlyPlacedOrder = <?php echo json_encode($placedOrder, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    for (let index = localStorage.length - 1; index >= 0; index -= 1) {
        const key = localStorage.key(index);
        if (key && key.startsWith('panaderia_recent_orders_')) {
            localStorage.removeItem(key);
        }
    }

    function readInventory() {
        const defaults = [
            { name: 'Pandesal', stock: 120 },
            { name: 'Ensaymada', stock: 42 },
            { name: 'Spanish Bread', stock: 84 },
            { name: 'Hopia', stock: 28 },
            { name: 'Ube Bread', stock: 67 },
            { name: 'Cheese Roll', stock: 55 },
            { name: 'Monay', stock: 19 },
            { name: 'Pan de Coco', stock: 30 },
            { name: 'Putok (Star Bread)', stock: 30 },
            { name: 'Pan de Monggo', stock: 30 },
            { name: 'Bicho-Bicho', stock: 30 },
            { name: 'Pinagong', stock: 30 },
            { name: 'Pan de Leche', stock: 30 },
            { name: 'Pianono', stock: 30 }
        ];

        try {
            const stored = JSON.parse(localStorage.getItem(inventoryStorageKey) || '[]');
            if (Array.isArray(stored) && stored.length > 0) {
                const existingNames = new Set(stored.map((item) => item.name));
                const mergedInventory = [...stored, ...defaults.filter((item) => !existingNames.has(item.name))];
                if (mergedInventory.length !== stored.length) {
                    localStorage.setItem(inventoryStorageKey, JSON.stringify(mergedInventory));
                }
                return mergedInventory;
            }
        } catch (error) {
            // fall through to defaults
        }

        localStorage.setItem(inventoryStorageKey, JSON.stringify(defaults));
        return defaults;
    }

    function renderProductStocks() {
        const inventory = readInventory();
        const stockByName = {};
        inventory.forEach((item) => {
            stockByName[item.name] = Number(item.stock || 0);
        });

        document.querySelectorAll('.product-stock').forEach((stockElement) => {
            const productName = stockElement.dataset.product;
            const stockValue = stockByName[productName] ?? 0;
            stockElement.textContent = String(stockValue);
            stockElement.classList.toggle('out-of-stock', stockValue <= 0);

            const button = stockElement.parentElement?.querySelector('.buy-btn');
            if (button) {
                button.disabled = stockValue <= 0;
            }
        });
    }

    function formatOrderItems(order) {
        const items = Object.entries(order.items || {});
        return items.length > 0 ? items.map(([product, quantity]) => product + ' x' + quantity).join(', ') : 'Not recorded';
    }

    function formatOrderTotal(order) {
        return order.total !== undefined && order.total !== null ? 'PHP ' + Number(order.total).toFixed(2) : 'Not recorded';
    }

    function renderSavedOrders(orders) {
        const normalizedOrders = orders.map((order) => ({
            ...order,
            status: order.status === 'Pending' || !order.status ? 'Success' : order.status
        }));

        recentOrdersBody.replaceChildren();
        const recentOrders = normalizedOrders.slice(0, 7);
        if (recentOrders.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="5" class="empty-cart">No recent order records yet.</td>';
            recentOrdersBody.appendChild(emptyRow);
        } else {
            recentOrders.forEach((order) => {
                const row = document.createElement('tr');
                row.className = 'saved-order-row';
                row.innerHTML = '<td>' + order.id + '</td><td>' + formatOrderItems(order) + '</td><td>' + formatOrderTotal(order) + '</td><td>' + order.date + '</td><td class="status">' + order.status + '</td>';
                recentOrdersBody.appendChild(row);
            });
        }
        const allOrdersBody = document.getElementById('all_orders_body');
        const seeAllOrders = document.getElementById('see_all_orders');
        if (allOrdersBody && seeAllOrders) {
            allOrdersBody.innerHTML = '';
            normalizedOrders.slice(7).forEach((order) => {
                const row = document.createElement('tr');
                row.innerHTML = '<td>' + order.id + '</td><td>' + formatOrderItems(order) + '</td><td>' + formatOrderTotal(order) + '</td><td>' + order.date + '</td><td class="status">' + order.status + '</td>';
                allOrdersBody.appendChild(row);
            });
            seeAllOrders.textContent = normalizedOrders.length > 7 ? 'View all (' + (normalizedOrders.length - 7) + ')' : 'View all';
        }
    }

    function syncQuantitySummary(responseDocument) {
        const currentPayment = document.getElementById('payment');
        const updatedPayment = responseDocument.getElementById('payment');
        if (!currentPayment || !updatedPayment) {
            return;
        }
        const currentTable = currentPayment.querySelector('.cart-table');
        const updatedTable = updatedPayment.querySelector('.cart-table');
        const currentTotal = currentPayment.querySelector('.cart-total');
        const updatedTotal = updatedPayment.querySelector('.cart-total');
        const emptyCart = currentPayment.querySelector('.empty-cart');
        const updatedEmptyCart = updatedPayment.querySelector('.empty-cart');

        if (currentTable && updatedTable) {
            currentTable.replaceWith(updatedTable);
        } else if (currentTable && updatedEmptyCart) {
            currentTable.replaceWith(updatedEmptyCart);
        } else if (!currentTable && updatedTable) {
            if (emptyCart) {
                emptyCart.replaceWith(updatedTable);
            } else {
                currentPayment.insertBefore(updatedTable, currentPayment.querySelector('.payment-form'));
            }
        }
        if (currentTotal && updatedTotal) {
            currentTotal.replaceWith(updatedTotal);
        } else if (currentTotal && !updatedTotal) {
            currentTotal.remove();
        } else if (!currentTotal && updatedTotal) {
            currentPayment.insertBefore(updatedTotal, currentPayment.querySelector('.payment-form'));
        }
        const placeOrderButton = currentPayment.querySelector('.place-order-btn');
        if (placeOrderButton) {
            placeOrderButton.disabled = !updatedTable;
        }
    }

    const savedOrders = [...serverOrders];
    if (newlyPlacedOrder !== null && !savedOrders.some((order) => order.id === newlyPlacedOrder.id)) {
        savedOrders.unshift(newlyPlacedOrder);
    }

    renderSavedOrders(savedOrders);
    renderProductStocks();
    window.addEventListener('storage', (event) => {
        if (event.key === inventoryStorageKey) {
            renderProductStocks();
        }
    });
    const breadSearch = document.getElementById('bread_search');
    const productCards = document.querySelectorAll('#product_list .product-card');
    const searchEmpty = document.getElementById('search_empty');
    if (breadSearch) {
        breadSearch.addEventListener('input', () => {
            const searchText = breadSearch.value.trim().toLowerCase();
            let visibleProducts = 0;
            productCards.forEach((card) => {
                const matches = searchText === '' || card.dataset.search.includes(searchText);
                card.hidden = !matches;
                if (matches) {
                    visibleProducts += 1;
                }
            });
            if (searchEmpty) {
                searchEmpty.style.display = visibleProducts === 0 ? 'block' : 'none';
            }
        });
        breadSearch.dispatchEvent(new Event('input'));
    }
    const seeAllOrders = document.getElementById('see_all_orders');
    const allOrdersPanel = document.getElementById('all-orders');
    const showRecentOrders = document.getElementById('show_recent_orders');
    const ordersSection = document.getElementById('orders');
    if (showRecentOrders && ordersSection) {
        showRecentOrders.addEventListener('click', (event) => {
            event.preventDefault();
            ordersSection.hidden = !ordersSection.hidden;
            showRecentOrders.setAttribute('aria-expanded', String(!ordersSection.hidden));
            showRecentOrders.textContent = ordersSection.hidden ? 'Recent orders' : 'Close recent orders';
            if (window.matchMedia('(max-width: 900px)').matches) {
                if (!ordersSection.hidden) {
                    ordersSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        });
    }
    if (seeAllOrders && allOrdersPanel) {
        seeAllOrders.addEventListener('click', (event) => {
            event.preventDefault();
            allOrdersPanel.hidden = !allOrdersPanel.hidden;
            seeAllOrders.textContent = allOrdersPanel.hidden ? 'View all' : 'Hide older orders';
        });
    }

    document.querySelectorAll('.cart-form, .payment-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.reportValidity()) {
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                if (!form.classList.contains('cart-form')) {
                    submitButton.textContent = 'Placing...';
                }
            }

            try {
                const formData = new FormData(form);
                if (submitButton && submitButton.name) {
                    formData.append(submitButton.name, submitButton.value || '1');
                }
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');

                if (form.classList.contains('cart-form')) {
                    const currentPayment = document.getElementById('payment');
                    const updatedPayment = responseDocument.getElementById('payment');
                    const currentTable = currentPayment ? currentPayment.querySelector('.cart-table') : null;
                    const updatedTable = updatedPayment ? updatedPayment.querySelector('.cart-table') : null;
                    const currentTotal = currentPayment ? currentPayment.querySelector('.cart-total') : null;
                    const updatedTotal = updatedPayment ? updatedPayment.querySelector('.cart-total') : null;
                    const emptyCart = currentPayment ? currentPayment.querySelector('.empty-cart') : null;

                    if (emptyCart) {
                        emptyCart.remove();
                    }
                    if (currentTable && updatedTable) {
                        currentTable.replaceWith(updatedTable);
                    } else if (!currentTable && updatedTable && currentPayment) {
                        currentPayment.insertBefore(updatedTable, currentPayment.querySelector('.payment-form'));
                    }
                    if (currentTotal && updatedTotal) {
                        currentTotal.replaceWith(updatedTotal);
                    } else if (!currentTotal && updatedTotal && currentPayment) {
                        currentPayment.insertBefore(updatedTotal, currentPayment.querySelector('.payment-form'));
                    }
                    const placeOrderButton = currentPayment ? currentPayment.querySelector('.place-order-btn') : null;
                    if (placeOrderButton) {
                        placeOrderButton.disabled = false;
                    }
                    if (currentPayment) {
                        currentPayment.hidden = false;
                    }
                    if (submitButton) {
                        submitButton.textContent = 'Added';
                    }
                    setTimeout(() => {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = originalText;
                        }
                    }, 900);
                } else {
                    const newOrderData = responseDocument.getElementById('new-order-data');
                    const orderJson = newOrderData ? newOrderData.dataset.order : '';
                    if (orderJson && orderJson !== 'null') {
                        const confirmedOrder = JSON.parse(orderJson);
                        const currentOrders = [...serverOrders];
                        if (!currentOrders.some((order) => order.id === confirmedOrder.id)) {
                            currentOrders.unshift(confirmedOrder);
                            renderSavedOrders(currentOrders);
                        }
                    }
                    const responseMessage = responseDocument.querySelector('.payment-message');
                    const currentPayment = document.getElementById('payment');
                    const currentMessage = currentPayment ? currentPayment.querySelector('.payment-message') : null;
                    if (responseMessage && currentPayment) {
                        if (currentMessage) {
                            currentMessage.replaceWith(responseMessage);
                        } else {
                            currentPayment.insertBefore(responseMessage, currentPayment.querySelector('.cart-table') || currentPayment.firstChild);
                        }
                    }
                    const updatedPayment = responseDocument.getElementById('payment');
                    const currentTable = currentPayment ? currentPayment.querySelector('.cart-table') : null;
                    const currentTotal = currentPayment ? currentPayment.querySelector('.cart-total') : null;
                    const updatedEmptyCart = updatedPayment ? updatedPayment.querySelector('.empty-cart') : null;
                    if (currentTable && updatedEmptyCart) {
                        currentTable.replaceWith(updatedEmptyCart);
                    }
                    if (currentTotal) {
                        currentTotal.remove();
                    }
                    form.reset();
                    if (typeof updatePaymentDetails === 'function') {
                        updatePaymentDetails();
                    }
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = originalText;
                    }
                }
            } catch (error) {
                form.submit();
            }
        });
    });

    document.addEventListener('click', async (event) => {
        const quantityButton = event.target.closest('.quantity-btn');
        if (!quantityButton || quantityButton.disabled) {
            return;
        }
        quantityButton.disabled = true;
        const formData = new FormData();
        formData.append('update_cart', '1');
        formData.append('product', quantityButton.dataset.product || '');
        formData.append('change', quantityButton.dataset.change || '0');
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error('Request failed');
            }
            syncQuantitySummary(new DOMParser().parseFromString(await response.text(), 'text/html'));
        } catch (error) {
            quantityButton.disabled = false;
        }
    });

    const scrollStorageKey = 'panaderia_scroll_position';
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    window.addEventListener('beforeunload', () => {
        sessionStorage.setItem(scrollStorageKey, String(window.scrollY));
    });
    const savedScrollPosition = sessionStorage.getItem(scrollStorageKey);
    if (savedScrollPosition !== null) {
        requestAnimationFrame(() => {
            window.scrollTo(0, Number(savedScrollPosition));
            sessionStorage.removeItem(scrollStorageKey);
        });
    }
</script>
</body>
</html>
