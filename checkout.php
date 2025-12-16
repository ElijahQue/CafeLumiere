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

// Security Check
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

if(empty($_SESSION['cart'])) { header('Location: index.php'); exit; }

// --- 1. Calculate Totals (Used for both Display and Saving) ---
$total = 0;
foreach($_SESSION['cart'] as $item) {
    $total += ($item['price'] * $item['qty']);
}

$discountType = $_SESSION['discount_type'] ?? ($_GET['discount'] ?? null);
$discountPercentage = 0;
$discountAmount = 0;
$discountLabel = '';

if ($discountType) {
    if ($discountType == 'pwd') {
        $discountPercentage = 0.20; 
        $discountLabel = 'PWD Discount (20%)';
    } elseif ($discountType == 'senior') {
        $discountPercentage = 0.20;
        $discountLabel = 'Senior Discount (20%)';
    }
    $discountAmount = $total * $discountPercentage;
}

$serviceCharge = $total * 0.05;
$finalTotal = $total + $serviceCharge - $discountAmount; // Variable matches your HTML

// --- 2. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Capture Cash Input
    $cashTendered = isset($_POST['cash_tendered']) ? floatval($_POST['cash_tendered']) : 0;
    
    // Calculate Change
    $changeAmount = $cashTendered - $finalTotal;
    if ($changeAmount < 0) { $changeAmount = 0; }

    // Format the Payment Info String
    $paymentInfo = "\n[Payment Info] Cash: ₱" . number_format($cashTendered, 2) . " | Change: ₱" . number_format($changeAmount, 2);

    // Prepare Customer Data
    $customer = [
        'name' => $_POST['name'] ?? 'Guest',
        'contact' => $_POST['contact'] ?? '',
        'notes' => ($_POST['notes'] ?? '') . $paymentInfo // Append payment info to notes
    ];

    // Database Transaction
    if (sqlsrv_begin_transaction($conn) === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $sql = "INSERT INTO TRANSACTIONS (CUSTOMERNAME, TOTALAMOUNT, STATUS, CREATEDAT, NOTES, CONTACT) VALUES (?, ?, 'Pending', GETDATE(), ?, ?); SELECT SCOPE_IDENTITY() AS last_id";
    $params = array($customer['name'], $finalTotal, $customer['notes'], $customer['contact']);
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        sqlsrv_rollback($conn);
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_next_result($stmt);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $transactionId = $row['last_id'];

    $itemsSuccess = true;
    foreach ($_SESSION['cart'] as $item) {
        $itemSql = "INSERT INTO TRANSACTIONITEMS (TRANSACTIONID, PRODUCTNAME, QUANTITY, PRICE) VALUES (?, ?, ?, ?)";
        $itemParams = array($transactionId, $item['name'], $item['qty'], $item['price']);
        
        if (!sqlsrv_query($conn, $itemSql, $itemParams)) {
            $itemsSuccess = false;
            break;
        }
    }

    if ($itemsSuccess) {
        sqlsrv_commit($conn);
        
        // --- NEW: Save Payment Info to Session ---
        $_SESSION['last_receipt'] = [
            'id' => $transactionId,
            'cash' => $cashTendered,
            'change' => $changeAmount
        ];
        // -----------------------------------------

        unset($_SESSION['cart']);
        unset($_SESSION['discount_type']);
        header("Location: receipt.php?id=" . $transactionId);
        exit;
    }
}
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
            --discount-purple: #ce8cdaff;
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

<div class="container my-5">
    <h2 class="text-center mb-4" style="font-family: 'Playfair Display', serif; color: #f4c542;">Complete Your Order</h2>
    
    <form method="POST">
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="checkout-card">
                    <h3 class="summary-title"><i class="fas fa-user-edit me-2"></i>Customer Details</h3>
                    <div class="mb-3">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" name="name" class="form-control" required 
                               placeholder="Enter your name" value="<?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number (Optional)</label>
                        <input type="text" name="contact" class="form-control" placeholder="Enter contact number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Instructions / Notes</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Any special requests..."></textarea>
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
                        <div class="text-center mb-3">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-tag me-1"></i> <?= $discountLabel ?> Applied
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="order-items mb-3" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach($_SESSION['cart'] as $item): ?>
                        <div class="d-flex justify-content-between mb-2 border-bottom border-secondary pb-2">
                            <div>
                                <?= htmlspecialchars($item['name']) ?> 
                                <span class="text-warning small">x<?= $item['qty'] ?></span>
                            </div>
                            <div>₱<?= number_format($item['price'] * $item['qty'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between text-white-50">
                        <span>Subtotal</span>
                        <span>₱<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-white-50">
                        <span>Service Charge (5%)</span>
                        <span>₱<?= number_format($serviceCharge, 2) ?></span>
                    </div>
                    
                    <?php if ($discountAmount > 0): ?>
                    <div class="d-flex justify-content-between text-info">
                        <span>Discount</span>
                        <span>-₱<?= number_format($discountAmount, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <hr class="border-secondary">

                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5">Total Amount:</span>
                        <span class="h5 text-warning" id="finalTotalDisplay">
                            ₱<?= number_format($finalTotal, 2); ?>
                        </span>
                        <input type="hidden" id="rawTotal" value="<?= $finalTotal ?>">
                    </div>

                    <div class="mb-3">
                        <label for="cashInput" class="form-label">Cash Tendered (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-light border-secondary">₱</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="cashInput" 
                                   name="cash_tendered" 
                                   step="0.01" 
                                   min="0" 
                                   placeholder="Enter amount given" 
                                   required>
                        </div>
                        <div id="paymentFeedback" class="form-text text-danger" style="display:none;">
                            <i class="fas fa-exclamation-circle"></i> Insufficient payment amount.
                        </div>
                    </div>

                    <div class="alert alert-dark border-secondary d-flex justify-content-between align-items-center">
                        <span>Change:</span>
                        <span class="h4 mb-0 text-success" id="changeDisplay">₱0.00</span>
                    </div>

                    <button type="submit" class="btn btn-action">
                        <i class="fas fa-check-circle me-2"></i>Place Order
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="cart.php" class="text-white-50 text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Your Discount Logic
function applyDiscount(type) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.action = 'cart.php'; // Assuming cart.php handles setting the discount session

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
    
    if (confirm('Apply discount?')) {
        form.submit();
    }
}

// Payment Calculation Logic
document.addEventListener('DOMContentLoaded', function() {
    const cashInput = document.getElementById('cashInput');
    const changeDisplay = document.getElementById('changeDisplay');
    const rawTotal = parseFloat(document.getElementById('rawTotal').value);
    const feedback = document.getElementById('paymentFeedback');
    const submitBtn = document.querySelector('button[type="submit"]');

    function calculateChange() {
        const cashGiven = parseFloat(cashInput.value);

        if (isNaN(cashGiven)) {
            changeDisplay.textContent = '₱0.00';
            changeDisplay.classList.remove('text-success', 'text-danger');
            return;
        }

        const change = cashGiven - rawTotal;

        if (change >= 0) {
            changeDisplay.textContent = '₱' + change.toFixed(2);
            changeDisplay.classList.remove('text-danger');
            changeDisplay.classList.add('text-success');
            feedback.style.display = 'none';
            cashInput.classList.remove('is-invalid');
            cashInput.classList.add('is-valid');
            submitBtn.disabled = false;
        } else {
            changeDisplay.textContent = 'Insufficient';
            changeDisplay.classList.remove('text-success');
            changeDisplay.classList.add('text-danger');
            feedback.style.display = 'block';
            cashInput.classList.add('is-invalid');
            cashInput.classList.remove('is-valid');
            submitBtn.disabled = true;
        }
    }

    cashInput.addEventListener('input', calculateChange);
});
</script>
</body>
</html>