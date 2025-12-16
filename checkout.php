<?php
session_start();
$serverName = "Elijah\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "LUMIERE",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { 
    die(print_r(sqlsrv_errors(), true));
}

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

if(empty($_SESSION['cart'])) { header('Location: index.php'); exit; }

$discountType = $_SESSION['discount_type'] ?? ($_GET['discount'] ?? null);

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer = [
        'name' => $_POST['name'] ?? 'Guest',
        'contact' => $_POST['contact'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];

    $total = 0;
    foreach($_SESSION['cart'] as $item) {
        $total += ($item['price'] * $item['qty']);
    }

    $serviceCharge = $total * 0.05;
    $discountPercentage = 0;
    $discountAmount = 0;
    
    if ($discountType) {
        if ($discountType === 'pwd') {
            $discountPercentage = 20;
        } elseif ($discountType === 'senior') {
            $discountPercentage = 20;
        }
        $discountAmount = ($total * $discountPercentage) / 100;
    }

    $finalTotal = ($total + $serviceCharge) - $discountAmount;

    $customerName = $customer['name'];
    $contact = $customer['contact'];
    $notes = $customer['notes'];
    $totalAmount = $finalTotal;
    $discountTypeDB = $discountType ?? 'none';
    $discountAmountDB = $discountAmount;
    
    $insertSql = "INSERT INTO TRANSACTIONS (CUSTOMERNAME, CONTACT, TOTALAMOUNT, NOTES, CREATEDAT, STATUS, DISCOUNT_TYPE, DISCOUNT_AMOUNT) OUTPUT INSERTED.TRANSACTIONID VALUES ('$customerName', '$contact', '$totalAmount', '$notes', GETDATE(), 'Pending', '$discountTypeDB', '$discountAmountDB')";
    $insertResult = sqlsrv_query($conn, $insertSql);

    if ($insertResult === false) {
        die("Insert failed: " . print_r(sqlsrv_errors(), true));
    }

    $transactionId = null;
    if (sqlsrv_has_rows($insertResult)) {
        $row = sqlsrv_fetch_array($insertResult, SQLSRV_FETCH_ASSOC);
        if ($row !== false && isset($row['TRANSACTIONID'])) {
            $transactionId = $row['TRANSACTIONID'];
        }
    }
    
    if (!$transactionId) {
        $getIdSql = "SELECT SCOPE_IDENTITY() AS TRANSACTIONID";
        $getIdResult = sqlsrv_query($conn, $getIdSql);
        if ($getIdResult !== false) {
            $idRow = sqlsrv_fetch_array($getIdResult, SQLSRV_FETCH_ASSOC);
            if ($idRow !== false && isset($idRow['TRANSACTIONID'])) {
                $transactionId = $idRow['TRANSACTIONID'];
            }
        }
    }

    if (!$transactionId) {
        die("Failed to get transaction ID. Please check if TRANSACTIONS table has an IDENTITY column.");
    }

    foreach($_SESSION['cart'] as $it) {
        $pid = $it['id'];
        $pname = $it['name'];
        $price = $it['price'];
        $qty = $it['qty'];

        $itemSql = "INSERT INTO TRANSACTIONITEMS (TRANSACTIONID, PRODUCTID, PRODUCTNAME, PRICE, QUANTITY) VALUES ('$transactionId', '$pid', '$pname', '$price', '$qty')";

        $itemResult = sqlsrv_query($conn, $itemSql);

        if ($itemResult === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }

    $_SESSION['last_txn'] = $transactionId;
    unset($_SESSION['cart']);
    unset($_SESSION['discount_type']);

    header("Location: receipt.php?id={$transactionId}");
    exit;
}

$total = 0;
foreach($_SESSION['cart'] as $item) {
    $total += ($item['price'] * $item['qty']);
}

$serviceCharge = $total * 0.05;
$discountPercentage = 0;
$discountAmount = 0;
$discountLabel = '';

if ($discountType) {
    if ($discountType === 'pwd') {
        $discountPercentage = 20;
        $discountLabel = 'PWD Discount (20%)';
    } elseif ($discountType === 'senior') {
        $discountPercentage = 20;
        $discountLabel = 'Senior Citizen Discount (20%)';
    }
    $discountAmount = ($total * $discountPercentage) / 100;
}

$finalTotal = ($total + $serviceCharge) - $discountAmount;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout • Café Lumière</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Raleway:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
        :root {
            --starry-night: #0b1e3f;
            --vangogh-yellow: #f4c542;
            --vangogh-blue: #4a8fe7;
            --cafe-cream: #f2e4b7;
            --artistic-brown: #8B4513;
            --olive-green: #6B8E23;
            --swirl-orange: #d2691e;
            --success-green: #28a745;
            --danger-red: #dc3545;
            --discount-purple: #9c27b0;
        }
        
        body { 
            background: linear-gradient(135deg, 
                        rgba(11, 30, 63, 0.95) 0%, 
                        rgba(26, 58, 107, 0.95) 100%),
                        url('https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-repeat: no-repeat;
            color: var(--cafe-cream); 
            font-family: 'Raleway', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            opacity: 100vh;
            padding-bottom: 100px;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(244, 197, 66, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(74, 143, 231, 0.1) 0%, transparent 40%);
            pointer-events: none;
            z-index: -1;
        }
        
        .vangogh-navbar {
            background: linear-gradient(135deg, rgba(26, 58, 107, 0.95) 0%, rgba(11, 30, 63, 0.95) 100%) !important;
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--vangogh-yellow);
            padding: 0.75rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 1.8rem;
            background: linear-gradient(45deg, var(--vangogh-yellow), #ffd700);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            padding-left: 2.5rem;
        }
        
        .navbar-brand::before {
            content: '✦';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--vangogh-yellow);
            font-size: 1.5rem;
            animation: twinkle 3s infinite alternate;
        }

        .nav-items-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-greeting {
            font-family: 'Playfair Display', serif;
            color: var(--vangogh-yellow);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-logout-nav {
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            background: transparent;
        }

        .btn-logout-nav:hover {
            border-color: var(--vangogh-yellow);
            color: var(--vangogh-yellow);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-cart-nav {
            background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
            color: var(--starry-night);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 8px rgba(244, 197, 66, 0.3);
        }

        .btn-cart-nav:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
            color: var(--starry-night);
            box-shadow: 0 4px 12px rgba(244, 197, 66, 0.4);
        }
        
        @keyframes twinkle {
            0% { opacity: 0.7; transform: translateY(-50%) scale(1); }
            100% { opacity: 1; transform: translateY(-50%) scale(1.1); }
        }

        .star-decoration {
            position: absolute;
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            animation: star-twinkle 3s infinite;
        }

        @keyframes star-twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
        
        .hero-section {
            position: relative;
            border-radius: 20px;
            margin: 2rem auto;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, 
                        rgba(26, 58, 107, 0.7) 0%, 
                        rgba(11, 30, 63, 0.85) 100%),
                        url('https://images.unsplash.com/photo-1543005471-0994b6c2be57?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            border: 3px solid var(--vangogh-yellow);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4),
                        inset 0 0 60px rgba(244, 197, 66, 0.1);
            overflow: hidden;
            text-align: center;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            right: -50%;
            bottom: -50%;
            background: repeating-linear-gradient(
            0deg,
            transparent,
            transparent 2px,
            rgba(244, 197, 66, 0.03) 2px,
            rgba(244, 197, 66, 0.03) 4px
            );
            animation: swirl 60s linear infinite;
            pointer-events: none;
        }
        
        @keyframes swirl {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 3rem;
            background: linear-gradient(45deg, var(--vangogh-yellow), var(--cafe-cream));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-label {
            color: var(--vangogh-yellow);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(244, 197, 66, 0.3);
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: rgba(242, 228, 183, 0.5);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            color: white;
            outline: none;
        }

        .checkout-card {
            background: linear-gradient(145deg, 
                        rgba(255, 255, 255, 0.08) 0%, 
                        rgba(255, 255, 255, 0.03) 100%);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            border: 1px solid rgba(244, 197, 66, 0.2);
            padding: 2rem;
            height: 100%;
        }

        .summary-title {
            font-family: 'Playfair Display', serif;
            color: var(--vangogh-yellow);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid rgba(244, 197, 66, 0.3);
            padding-bottom: 0.5rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .item-name {
            color: var(--cafe-cream);
        }

        .item-price {
            color: white;
            font-weight: 500;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(244, 197, 66, 0.3);
            font-size: 1.1rem;
        }

        .grand-total {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid var(--vangogh-yellow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grand-total-label {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--vangogh-yellow);
        }

        .grand-total-amount {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--vangogh-yellow);
            text-shadow: 0 0 10px rgba(244, 197, 66, 0.3);
        }

        .discount-badge {
            background: linear-gradient(135deg, var(--discount-purple), #7b1fa2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
            box-shadow: 0 0 15px rgba(156, 39, 176, 0.4);
        }

        .btn-action {
            background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
            color: var(--starry-night);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
            font-size: 1.1rem;
        }
        
        .btn-action:hover {
            background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
            color: var(--starry-night);
        }

        .btn-back {
            color: rgba(242, 228, 183, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            color: var(--vangogh-yellow);
            text-decoration: underline;
        }

        .btn-outline-custom {
            border: 2px solid var(--vangogh-blue);
            color: white;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            background: transparent;
            width: 100%;
        }

        .btn-outline-custom:hover {
            background: var(--vangogh-blue);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 143, 231, 0.3);
        }

        .footer {
            background: rgba(11, 30, 63, 0.9);
            border-top: 1px solid rgba(244, 197, 66, 0.3);
            padding: 2rem 0;
            margin-bottom: auto;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .nav-items-container {
                flex-direction: column;
                width: 100%;
                margin-top: 1rem;
            }
            .btn-logout-nav, .btn-cart-nav {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
</head>
<body>
<div id="stars-container"></div>

<nav class="navbar navbar-expand-lg navbar-dark vangogh-navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">Café Lumière</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto align-items-center">
        <div class="nav-items-container">
            <?php if(isset($_SESSION['user'])): ?>
              <span class="user-greeting">
                <i class="fas fa-user-circle fs-5"></i>
                <?=htmlspecialchars($_SESSION['user']['username'])?>
              </span>
              
              <a href="logout.php" class="btn-logout-nav">
                <i class="fas fa-sign-out-alt"></i> Logout
              </a>
            <?php else: ?>
              <a href="login.php" class="btn-logout-nav">
                <i class="fas fa-sign-in-alt"></i> Login
              </a>
            <?php endif; ?>
            
            <a href="cart.php" class="btn-cart-nav">
              <i class="fas fa-shopping-cart"></i> Cart
            </a>
        </div>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">
    <div class="hero-section">
        <h1 class="hero-title">
            <i class="fas fa-cash-register me-2"></i>
            Checkout
        </h1>
        <p class="hero-subtitle">Finalize your masterpiece order</p>
    </div>

    <form method="POST">
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="checkout-card">
                    <h3 class="summary-title"><i class="fas fa-user-edit me-2"></i>Customer Details</h3>
                    <div class="mb-3">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" name="name" class="form-control" required 
                               placeholder="Enter your name" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number (Optional)</label>
                        <input type="text" name="contact" class="form-control" 
                               placeholder="Enter contact number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Instructions / Notes</label>
                        <textarea name="notes" class="form-control" rows="4" 
                                  placeholder="Any special requests or notes for this order..."></textarea>
                    </div>

                    <?php if (!$discountType): ?>
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <label class="form-label mb-3">Apply Special Discount (Optional)</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-custom" onclick="applyDiscount('pwd')">
                                    <i class="fas fa-wheelchair me-2"></i>PWD (20%)
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-custom" onclick="applyDiscount('senior')">
                                    <i class="fas fa-user-check me-2"></i>Senior Citizen (20%)
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="discount_type" id="discountType" value="">
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5 mb-4">
                <div class="checkout-card">
                    <h3 class="summary-title"><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                    
                    <?php if ($discountType): ?>
                        <div class="text-center">
                            <span class="discount-badge">
                                <?php if ($discountType === 'pwd'): ?>
                                    <i class="fas fa-wheelchair me-1"></i> PWD Discount Applied
                                <?php elseif ($discountType === 'senior'): ?>
                                    <i class="fas fa-user-check me-1"></i> Senior Discount Applied
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="order-items mb-3" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach($_SESSION['cart'] as $item): ?>
                        <div class="summary-item">
                            <div class="item-name">
                                <?= htmlspecialchars($item['name']) ?> 
                                <span class="text-warning small">x<?= $item['qty'] ?></span>
                            </div>
                            <div class="item-price">₱<?= number_format($item['price'] * $item['qty'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="total-row">
                        <span class="text-white-50">Subtotal</span>
                        <span class="text-white">₱<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="total-row border-0 mt-0 pt-1">
                        <span class="text-white-50">Service Charge (5%)</span>
                        <span class="text-white">₱<?= number_format($serviceCharge, 2) ?></span>
                    </div>
                    
                    <?php if ($discountAmount > 0): ?>
                    <div class="total-row border-0 mt-0 pt-1">
                        <span style="color: var(--discount-purple);">
                            <i class="fas fa-tag me-1"></i><?= $discountLabel ?>
                        </span>
                        <span style="color: var(--discount-purple);">-₱<?= number_format($discountAmount, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="grand-total">
                        <span class="grand-total-label">Total Amount</span>
                        <span class="grand-total-amount">₱<?= number_format($finalTotal, 2) ?></span>
                    </div>

                    <button type="submit" class="btn btn-action">
                        <i class="fas fa-check-circle me-2"></i>Place Order
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="cart.php" class="btn-back">
                            <i class="fas fa-arrow-left me-1"></i>Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <footer class="footer text-center mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start">
                    <h4 class="text-warning mb-3">Café Lumière</h4>
                    <p class="mb-0">Inspired by Vincent van Gogh's passion for color and life.</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <p class="mb-0">
                        <i class="fas fa-map-marker-alt text-warning me-2"></i>
                        JP Laurel St. Nasugbu, Batangas
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-clock text-warning me-2"></i>
                        Open Daily: 7AM - 10PM
                    </p>
                </div>
            </div>
            <hr class="my-3" style="border-color: rgba(244, 197, 66, 0.2);">
            <div class="row">
                <div class="col-12">
                    <p class="mb-0">
                        <small>
                            &copy; <?php echo date('Y'); ?> Café Lumière.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const starsContainer = document.getElementById('stars-container');
    const starCount = 150;
    
    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.classList.add('star-decoration');
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 100}%`;
        const size = Math.random() * 3 + 1;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.animationDelay = `${Math.random() * 3}s`;
        star.style.animationDuration = `${Math.random() * 2 + 2}s`;
        starsContainer.appendChild(star);
    }

    const cards = document.querySelectorAll('.checkout-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
});

document.querySelector('form').addEventListener('submit', function(e) {
    const nameInput = document.querySelector('input[name="name"]');
    if (!nameInput.value.trim()) {
        e.preventDefault();
        nameInput.focus();
        nameInput.style.borderColor = '#ff6b6b';
        nameInput.style.boxShadow = '0 0 0 0.25rem rgba(255, 107, 107, 0.25)';
        alert('Please enter customer name.');
        return false;
    }
});

function applyDiscount(type) {
    const discountTypeInput = document.getElementById('discountType');
    if (discountTypeInput) {
        discountTypeInput.value = type;
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.action = 'cart.php';
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'apply_discount';
        const discountInput = document.createElement('input');
        discountInput.type = 'hidden';
        discountInput.name = 'discount_type';
        discountInput.value = type;
        form.appendChild(actionInput);
        form.appendChild(discountInput);
        document.body.appendChild(form);
        const discountName = type === 'pwd' ? 'PWD' : 'Senior Citizen';
        if (confirm(`Apply ${discountName} discount (20%) to your order?`)) {
            form.submit();
        }
    }
}

<?php if ($discountType && !isset($_POST['discount_type'])): ?>
    const discountTypeInput = document.getElementById('discountType');
    if (discountTypeInput) {
        discountTypeInput.value = '<?php echo $discountType; ?>';
    }
<?php endif; ?>
</script>
</body>
</html>