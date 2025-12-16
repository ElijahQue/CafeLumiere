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

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if($_POST['action'] === 'update') {
        foreach($_POST['qty'] as $i => $q) {
            $q = max(0, (int)$q);
            if($q === 0) { 
                unset($_SESSION['cart'][$i]); 
                $_SESSION['cart_message'] = 'Item removed from cart.';
            }
            else { 
                $_SESSION['cart'][$i]['qty'] = $q;
                $_SESSION['cart_message'] = 'Cart updated successfully!';
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart'] ?? []);
    } elseif($_POST['action'] === 'clear') {
        unset($_SESSION['cart']);
        unset($_SESSION['discount_type']);
        $_SESSION['success_message'] = 'Cart cleared successfully!';
    } elseif($_POST['action'] === 'remove' && isset($_POST['item_index'])) {
        $index = (int)$_POST['item_index'];
        if(isset($_SESSION['cart'][$index])) {
            $itemName = $_SESSION['cart'][$index]['name'];
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $_SESSION['cart_message'] = "$itemName removed from cart.";
        }
    } elseif($_POST['action'] === 'apply_discount') {
        $_SESSION['discount_type'] = $_POST['discount_type'] ?? null;
        $_SESSION['cart_message'] = 'Discount applied successfully!';
    } elseif($_POST['action'] === 'remove_discount') {
        unset($_SESSION['discount_type']);
        $_SESSION['cart_message'] = 'Discount removed!';
    }
    header("Location: cart.php");
    exit;
}

$total = 0;
$itemCount = 0;
$cartItems = $_SESSION['cart'] ?? [];
$discountType = $_SESSION['discount_type'] ?? null;

foreach($cartItems as $item) {
    $itemCount += $item['qty'];
    $total += $item['price'] * $item['qty'];
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

$subtotal = $total;
$grandTotal = ($total + $serviceCharge) - $discountAmount;
$successMessage = '';
$cartMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['cart_message'])) {
    $cartMessage = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Artistic Cart • Café Lumière</title>
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
        --discount-purple: #ce8cdaff;
      }
      
      body {
        background: linear-gradient(135deg, 
                    rgba(11, 30, 63, 0.95) 0%, 
                    rgba(26, 58, 107, 0.95) 100%),
                    url('https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
        background-size: cover;
        background-attachment: fixed;
        font-family: 'Raleway', sans-serif;
        min-height: 100vh;
        color: var(--cafe-cream);
        position: relative;
        overflow-x: hidden;
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
      
      @keyframes twinkle {
        0% { opacity: 0.7; transform: translateY(-50%) scale(1); }
        100% { opacity: 1; transform: translateY(-50%) scale(1.1); }
      }

      .user-greeting {
        font-family: 'Playfair Display', serif;
        color: var(--vangogh-yellow);
        font-weight: 600;
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
      
      .cart-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--vangogh-yellow);
        color: var(--starry-night);
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 0.8rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        border: 2px solid var(--starry-night);
      }
      
      .btn-cart-nav {
        background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        position: relative;
        transition: all 0.3s ease;
      }
      
      .btn-cart-nav:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(90, 125, 30, 0.4);
        color: white;
      }
      
      .cart-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
      }
      
      .cart-header {
        background: linear-gradient(145deg, 
                    rgba(255, 255, 255, 0.08) 0%, 
                    rgba(255, 255, 255, 0.03) 100%);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        border: 2px solid var(--vangogh-yellow);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4),
                    inset 0 0 80px rgba(244, 197, 66, 0.1);
        padding: 2.5rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        text-align: center;
      }
      
      .cart-header::before {
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
        animation: swirl 40s linear infinite;
        pointer-events: none;
      }
      
      @keyframes swirl {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      
      .cart-title {
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
      
      .cart-subtitle {
        color: rgba(242, 228, 183, 0.8);
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
      }
      
      .cart-stats {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
      }
      
      .stat-item {
        text-align: center;
        padding: 1rem 1.5rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        border: 1px solid rgba(244, 197, 66, 0.2);
      }
      
      .stat-value {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--vangogh-yellow);
        margin-bottom: 0.3rem;
      }
      
      .stat-label {
        color: rgba(242, 228, 183, 0.7);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
      }
      
      .message-alert {
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 2px solid;
        backdrop-filter: blur(10px);
        animation: slideIn 0.5s ease;
      }
      
      .message-alert.success {
        background: linear-gradient(135deg, 
                    rgba(40, 167, 69, 0.2) 0%, 
                    transparent);
        border-color: rgba(40, 167, 69, 0.4);
        color: #d4edda;
      }
      
      .message-alert.info {
        background: linear-gradient(135deg, 
                    rgba(23, 162, 184, 0.2) 0%, 
                    transparent);
        border-color: rgba(23, 162, 184, 0.4);
        color: #d1ecf1;
      }
      
      @keyframes slideIn {
        from {
          opacity: 0;
          transform: translateY(-20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      .empty-cart {
        text-align: center;
        padding: 4rem 2rem;
        background: linear-gradient(145deg, 
                    rgba(255, 255, 255, 0.08) 0%, 
                    rgba(255, 255, 255, 0.03) 100%);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        border: 2px dashed rgba(244, 197, 66, 0.3);
      }
      
      .empty-cart-icon {
        font-size: 5rem;
        color: var(--vangogh-yellow);
        margin-bottom: 1.5rem;
        opacity: 0.7;
      }
      
      .empty-cart-title {
        font-family: 'Playfair Display', serif;
        color: var(--vangogh-yellow);
        font-size: 2rem;
        margin-bottom: 1rem;
      }
      
      .btn-browse {
        background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
        color: var(--starry-night);
        border: none;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 1.5rem;
      }
      
      .btn-browse:hover {
        background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
        color: var(--starry-night);
      }
      
      .cart-items-container {
        background: linear-gradient(145deg, 
                    rgba(255, 255, 255, 0.08) 0%, 
                    rgba(255, 255, 255, 0.03) 100%);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        border: 1px solid rgba(244, 197, 66, 0.2);
        padding: 2rem;
        margin-bottom: 2rem;
      }
      
      .cart-item {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(244, 197, 66, 0.1);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1.5rem;
      }
      
      .cart-item:hover {
        border-color: var(--vangogh-yellow);
        transform: translateX(5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      }
      
      .item-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid var(--vangogh-yellow);
      }
      
      .item-details {
        flex: 1;
      }
      
      .item-name {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--vangogh-yellow);
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
      }
      
      .item-price {
        font-weight: 600;
        color: var(--cafe-cream);
        margin-bottom: 0.5rem;
      }
      
      .item-controls {
        display: flex;
        align-items: center;
        gap: 1rem;
      }
      
      .quantity-input {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(244, 197, 66, 0.3);
        border-radius: 8px;
        color: var(--cafe-cream);
        width: 70px;
        text-align: center;
        padding: 0.5rem;
        font-weight: 600;
      }
      
      .quantity-input:focus {
        outline: none;
        border-color: var(--vangogh-yellow);
        box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
      }
      
      .btn-remove {
        background: linear-gradient(135deg, var(--danger-red), #c82333);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s ease;
      }
      
      .btn-remove:hover {
        background: linear-gradient(135deg, #dc3545, var(--danger-red));
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
      }
      
      .item-subtotal {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--vangogh-yellow);
        font-size: 1.2rem;
        min-width: 120px;
        text-align: right;
      }
      
      /* Discount Section */
      .discount-section {
        background: linear-gradient(145deg, 
                    rgba(255, 255, 255, 0.08) 0%, 
                    rgba(255, 255, 255, 0.03) 100%);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        border: 2px solid var(--discount-purple);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(156, 39, 176, 0.1);
      }
      
      .discount-title {
        font-family: 'Playfair Display', serif;
        color: var(--cafe-cream);
        font-size: 1.5rem;
        margin-bottom: 1rem;
        text-align: center;
      }
      
      .discount-badges {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
      }
      
      .discount-badge {
        background: linear-gradient(135deg, var(--discount-purple), #7b1fa2);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      
      .discount-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(156, 39, 176, 0.3);
      }
      
      .discount-badge.active {
        background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
        color: var(--starry-night);
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
      }
      
      .btn-remove-discount {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-top: 0.5rem;
      }
      
      .btn-remove-discount:hover {
        background: linear-gradient(135deg, #5a6268, #6c757d);
        transform: translateY(-2px);
      }
      
      .cart-summary {
        background: linear-gradient(145deg, 
                    rgba(255, 255, 255, 0.08) 0%, 
                    rgba(255, 255, 255, 0.03) 100%);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        border: 2px solid var(--vangogh-yellow);
        padding: 2rem;
        margin-top: 2rem;
      }
      
      .summary-title {
        font-family: 'Playfair Display', serif;
        color: var(--vangogh-yellow);
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        text-align: center;
      }
      
      .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }
      
      .summary-label {
        color: rgba(242, 228, 183, 0.8);
      }
      
      .summary-value {
        font-weight: 600;
        color: var(--cafe-cream);
      }
      
      .discount-row {
        color: var(--discount-purple);
        font-weight: bold;
      }
      
      .discount-value {
        color: var(--discount-purple);
        font-weight: bold;
      }
      
      .summary-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 0;
        border-top: 2px solid var(--vangogh-yellow);
        margin-top: 1rem;
      }
      
      .total-label {
        font-family: 'Playfair Display', serif;
        color: var(--vangogh-yellow);
        font-size: 1.3rem;
        font-weight: 700;
      }
      
      .total-value {
        font-family: 'Playfair Display', serif;
        color: var(--vangogh-yellow);
        font-size: 2rem;
        font-weight: 900;
        text-shadow: 0 0 10px rgba(244, 197, 66, 0.3);
      }
      
      .cart-actions {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 2rem;
        flex-wrap: wrap;
      }
      
      .btn-update {
        background: linear-gradient(135deg, var(--vangogh-blue), #2a6fdb);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(74, 143, 231, 0.3);
      }
      
      .btn-update:hover {
        background: linear-gradient(135deg, #4a8fe7, var(--vangogh-blue));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(74, 143, 231, 0.4);
      }
      
      .btn-clear {
        background: linear-gradient(135deg, var(--danger-red), #c82333);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
      }
      
      .btn-clear:hover {
        background: linear-gradient(135deg, #dc3545, var(--danger-red));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
      }
      
      .btn-checkout {
        background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
        color: var(--starry-night);
        border: none;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
        text-decoration: none;
      }
      
      .btn-checkout:hover {
        background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
        color: var(--starry-night);
      }
      
      .footer {
          background: rgba(11, 30, 63, 0.9);
          border-top: 1px solid rgba(244, 197, 66, 0.3);
          padding: 2rem 0;
          margin-top: 4rem;
          backdrop-filter: blur(10px);
          position: relative;
          z-index: 1;
      }
      
      .btn-outline-light:hover {
          background-color: rgba(255, 255, 255, 0.1);
          border-color: var(--vangogh-yellow);
          color: var(--vangogh-yellow);
      }

      @media (max-width: 768px) {
        .cart-title {
          font-size: 2.2rem;
        }
        
        .cart-item {
          flex-direction: column;
          text-align: center;
          gap: 1rem;
        }
        
        .item-controls {
          justify-content: center;
        }
        
        .item-subtotal {
          text-align: center;
          min-width: auto;
        }
        
        .cart-actions {
          flex-direction: column;
        }
        
        .cart-actions button,
        .cart-actions a {
          width: 100%;
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
        <?php if(isset($_SESSION['user'])): ?>
          <span class="user-greeting me-3">
            <i class="fas fa-palette me-1"></i>
            <?=htmlspecialchars($_SESSION['user']['username'])?>
          </span>
          <a href="logout.php" class="btn btn-outline-light me-2">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
          </a>
          <?php if($_SESSION['user']['role'] === 'admin'): ?>
            <a href="admin_dashboard.php" class="btn btn-cart-nav me-2">
              <i class="fas fa-crown me-1"></i>Admin
            </a>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline-light me-2">
            <i class="fas fa-sign-in-alt me-1"></i>Login
          </a>
          <a href="register.php" class="btn btn-cart-nav me-2">
            <i class="fas fa-user-plus me-1"></i>Register
          </a>
        <?php endif; ?>
        <a href="cart.php" class="btn btn-cart-nav position-relative">
          <i class="fas fa-shopping-cart me-1"></i>Cart
          <?php if ($itemCount > 0): ?>
            <span class="cart-badge"><?php echo $itemCount; ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="cart-container">
  <div class="cart-header">
    <h1 class="cart-title">
      <i class="fas fa-shopping-basket me-2"></i>
      Your Artistic Cart
    </h1>
    <p class="cart-subtitle">Review your order.</p>
    <div class="cart-stats">
      <div class="stat-item">
        <div class="stat-value"><?php echo $itemCount; ?></div>
        <div class="stat-label">Items</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">₱<?php echo number_format($total, 2); ?></div>
        <div class="stat-label">Subtotal</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">₱<?php echo number_format($grandTotal, 2); ?></div>
        <div class="stat-label">Grand Total</div>
      </div>
    </div>
  </div>
  <?php if ($successMessage): ?>
    <div class="message-alert success">
      <div class="d-flex align-items-center">
        <i class="fas fa-check-circle fa-2x me-3" style="color: #28a745;"></i>
        <div>
          <h4 class="mb-1">Success!</h4>
          <p class="mb-0"><?php echo htmlspecialchars($successMessage); ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($cartMessage): ?>
    <div class="message-alert info">
      <div class="d-flex align-items-center">
        <i class="fas fa-info-circle fa-2x me-3" style="color: #17a2b8;"></i>
        <div>
          <h4 class="mb-1">Cart Updated</h4>
          <p class="mb-0"><?php echo htmlspecialchars($cartMessage); ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <?php if(empty($cartItems)): ?>
    <div class="empty-cart">
      <div class="empty-cart-icon">
        <i class="fas fa-paint-brush"></i>
      </div>
      <h2 class="empty-cart-title">Your Cart is empty.</h2>
      <p class="cart-subtitle">Add some products for your order.</p>
      <a href="index.php" class="btn btn-browse">
        <i class="fas fa-palette me-2"></i>Browse Our Menu
      </a>
    </div>
  <?php else: ?>
    <form method="POST" id="cartForm">
      <input type="hidden" name="action" value="update" id="formAction">
      
      <div class="cart-items-container">
        <?php foreach($cartItems as $i => $it): 
          $subtotal = $it['price'] * $it['qty'];
          $imageUrl = $it['img'] ?? 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80';
        ?>
        <div class="cart-item" id="item-<?php echo $i; ?>">
          <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
               alt="<?php echo htmlspecialchars($it['name']); ?>" 
               class="item-image"
               onerror="this.src='https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80'">
          <div class="item-details">
            <h3 class="item-name"><?php echo htmlspecialchars($it['name']); ?></h3>
            <p class="item-price">₱<?php echo number_format($it['price'], 2); ?> each</p>
            <div class="item-controls">
              <input type="number" 
                     name="qty[<?php echo $i; ?>]" 
                     value="<?php echo $it['qty']; ?>" 
                     min="0" 
                     max="99" 
                     class="quantity-input"
                     onchange="updateSubtotal(<?php echo $i; ?>, <?php echo $it['price']; ?>, this.value)">
              <button type="button" 
                      class="btn btn-remove" 
                      onclick="removeItem(<?php echo $i; ?>, '<?php echo addslashes($it['name']); ?>')">
                <i class="fas fa-trash me-1"></i>Remove
              </button>
            </div>
          </div>
          <div class="item-subtotal" id="subtotal-<?php echo $i; ?>">
            ₱<?php echo number_format($subtotal, 2); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="discount-section">
        <h3 class="discount-title">
          <i class="fas fa-percentage me-2"></i>
          Discounts
        </h3>
        <div class="discount-badges">
          <?php if ($discountType === 'pwd'): ?>
            <div class="discount-badge active">
              <i class="fas fa-wheelchair"></i>
              PWD Discount (20%) Applied
            </div>
          <?php else: ?>
            <button type="button" class="discount-badge" onclick="applyDiscount('pwd')">
              <i class="fas fa-wheelchair"></i>
              Apply PWD Discount (20%)
            </button>
          <?php endif; ?>
          <?php if ($discountType === 'senior'): ?>
            <div class="discount-badge active">
              <i class="fas fa-user-check"></i>
              Senior Citizen Discount (20%) Applied
            </div>
          <?php else: ?>
            <button type="button" class="discount-badge" onclick="applyDiscount('senior')">
              <i class="fas fa-user-check"></i>
              Apply Senior Citizen Discount (20%)
            </button>
          <?php endif; ?>
        </div>
        <?php if ($discountType): ?>
          <div class="text-center mt-3">
            <button type="button" class="btn btn-remove-discount" onclick="removeDiscount()">
              <i class="fas fa-times me-1"></i>Remove Discount
            </button>
          </div>
        <?php endif; ?>
      </div>
      <div class="cart-summary">
        <h3 class="summary-title">
          <i class="fas fa-receipt me-2"></i>
          Order Summary
        </h3>
        <div class="summary-row">
          <span class="summary-label">Subtotal</span>
          <span class="summary-value" id="summarySubtotal">₱<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Service Charge (5%)</span>
          <span class="summary-value" id="summaryService">₱<?php echo number_format($serviceCharge, 2); ?></span>
        </div>
        <?php if ($discountAmount > 0): ?>
          <div class="summary-row discount-row">
            <span class="summary-label">
              <i class="fas fa-tag me-1"></i><?php echo $discountLabel; ?>
            </span>
            <span class="discount-value" id="summaryDiscount">-₱<?php echo number_format($discountAmount, 2); ?></span>
          </div>
        <?php endif; ?>
        <div class="summary-total">
          <span class="total-label">Total Amount</span>
          <span class="total-value" id="grandTotal">₱<?php echo number_format($grandTotal, 2); ?></span>
        </div>
      </div>
      <div class="cart-actions">
        <div class="d-flex gap-2 w-100 flex-column flex-md-row justify-content-between">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-clear" onclick="clearCart()">
                <i class="fas fa-trash-alt me-2"></i>Clear
              </button>
            </div>
            <a href="checkout.php?discount=<?php echo $discountType ?? ''; ?>" class="btn btn-checkout">
              <i class="fas fa-palette me-2"></i>Proceed to Checkout
            </a>
        </div>
      </div>
    </form>
  <?php endif; ?>
  
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
    
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach((item, index) => {
        item.style.animationDelay = (index * 0.1) + 's';
        item.style.animation = 'slideIn 0.5s ease forwards';
        item.style.opacity = '0';
    });
    
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.className = 'quantity-wrapper';
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '0.5rem';
        
        const minusBtn = document.createElement('button');
        minusBtn.type = 'button';
        minusBtn.innerHTML = '<i class="fas fa-minus"></i>';
        minusBtn.className = 'btn btn-sm btn-outline-warning';
        minusBtn.style.padding = '0.25rem 0.5rem';
        minusBtn.style.color = '#f4c542';
        minusBtn.style.borderColor = '#f4c542';
        
        minusBtn.onclick = function() {
        const current = parseInt(input.value);
        if (current > 0) {
            input.value = current - 1;
            input.dispatchEvent(new Event('change'));
        }
        };
        
        const plusBtn = document.createElement('button');
        plusBtn.type = 'button';
        plusBtn.innerHTML = '<i class="fas fa-plus"></i>';
        plusBtn.className = 'btn btn-sm btn-outline-warning';
        plusBtn.style.padding = '0.25rem 0.5rem';
        plusBtn.style.color = '#f4c542';
        plusBtn.style.borderColor = '#f4c542';
        
        plusBtn.onclick = function() {
        const current = parseInt(input.value);
        if (current < 99) {
            input.value = current + 1;
            input.dispatchEvent(new Event('change'));
        }
        };
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(minusBtn);
        wrapper.appendChild(input);
        wrapper.appendChild(plusBtn);
    });

    updateGrandTotal();
  });

  function updateSubtotal(itemId, price, quantity) {
    const subtotal = price * quantity;
    const subtotalElement = document.getElementById(`subtotal-${itemId}`);
    if (subtotalElement) {
      subtotalElement.textContent = '₱' + subtotal.toFixed(2);
      updateGrandTotal();
    }
  }

  function updateGrandTotal() {
    let subtotal = 0;
    const subtotalElements = document.querySelectorAll('[id^="subtotal-"]');
    subtotalElements.forEach(element => {
      const value = parseFloat(element.textContent.replace('₱', '').replace(',', ''));
      if (!isNaN(value)) {
        subtotal += value;
      }
    });
    const serviceCharge = subtotal * 0.05;
    const discountType = '<?php echo $discountType ?? ""; ?>';
    const discountPercentage = (discountType === 'pwd' || discountType === 'senior') ? 20 : 0;
    const discountAmount = (subtotal * discountPercentage) / 100;
    const grandTotal = subtotal + serviceCharge - discountAmount;
    
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryService = document.getElementById('summaryService');
    const summaryDiscount = document.getElementById('summaryDiscount');
    const grandTotalElement = document.getElementById('grandTotal');
    
    if (summarySubtotal) summarySubtotal.textContent = '₱' + subtotal.toFixed(2);
    if (summaryService) summaryService.textContent = '₱' + serviceCharge.toFixed(2);
    if (summaryDiscount) summaryDiscount.textContent = '-₱' + discountAmount.toFixed(2);
    if (grandTotalElement) grandTotalElement.textContent = '₱' + grandTotal.toFixed(2);
  }

  function applyDiscount(type) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
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
    form.submit();
  }

  function removeDiscount() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'remove_discount';
    form.appendChild(actionInput);
    document.body.appendChild(form);
    form.submit();
  }

  function removeItem(itemId, itemName) {
    if (confirm(`Remove "${itemName}" from your cart?`)) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'remove';
      const indexInput = document.createElement('input');
      indexInput.type = 'hidden';
      indexInput.name = 'item_index';
      indexInput.value = itemId;
      form.appendChild(actionInput);
      form.appendChild(indexInput);
      document.body.appendChild(form);
      form.submit();
    }
  }

  function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart?\n\nAll items will be removed.')) {
      const form = document.getElementById('cartForm');
      const actionInput = document.getElementById('formAction');
      actionInput.value = 'clear';
      form.submit();
    }
  }
</script>
</body>
</html>