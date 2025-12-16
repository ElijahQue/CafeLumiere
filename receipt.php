<?php
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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) { die('Transaction ID missing'); }
$sql = "SELECT * FROM TRANSACTIONS WHERE TRANSACTIONID = '$id'";
$result = sqlsrv_query($conn, $sql);
$txn = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

$itemsSql = "SELECT * FROM TRANSACTIONITEMS WHERE TRANSACTIONID = '$id'";
$itStmt = sqlsrv_query($conn, $itemsSql);

$itemsTotal = 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt • Café Lumière</title>
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
        padding: 2rem 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
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

    .receipt-card {
        background: linear-gradient(145deg, 
                    rgba(255, 255, 255, 0.08) 0%, 
                    rgba(255, 255, 255, 0.03) 100%);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        border: 2px solid var(--vangogh-yellow);
        padding: 0;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        position: relative;
        transform: translateY(20px);
        opacity: 0;
        animation: slideUp 0.8s ease forwards;
    }

    @keyframes slideUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .receipt-header {
        background: linear-gradient(135deg, rgba(11, 30, 63, 0.9), rgba(26, 58, 107, 0.9));
        padding: 2rem;
        text-align: center;
        border-bottom: 2px dashed rgba(244, 197, 66, 0.3);
        position: relative;
    }

    .receipt-header::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 100%;
        height: 20px;
        background: radial-gradient(circle, transparent 70%, rgba(255, 255, 255, 0.03) 71%);
        background-size: 20px 20px;
        background-position: center bottom;
    }

    .brand-logo {
        font-family: 'Playfair Display', serif;
        font-weight: 900;
        font-size: 2.2rem;
        background: linear-gradient(45deg, var(--vangogh-yellow), #ffd700);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .receipt-meta {
        font-size: 0.9rem;
        color: rgba(242, 228, 183, 0.7);
        margin-bottom: 0.2rem;
    }

    .receipt-body {
        padding: 2rem;
    }

    .customer-info {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(244, 197, 66, 0.2);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .info-row:last-child {
        margin-bottom: 0;
    }

    .info-label {
        color: rgba(242, 228, 183, 0.6);
    }

    .info-value {
        font-weight: 600;
        color: var(--vangogh-yellow);
    }

    .items-table {
        width: 100%;
        margin-bottom: 1.5rem;
        border-collapse: separate;
        border-spacing: 0 0.5rem;
    }

    .items-table th {
        text-align: left;
        color: rgba(242, 228, 183, 0.5);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .items-table td {
        padding: 0.75rem 0;
        color: white;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .item-qty {
        color: var(--vangogh-yellow);
        font-weight: bold;
        width: 40px;
        text-align: center;
        background: rgba(244, 197, 66, 0.1);
        border-radius: 4px;
        padding: 2px 0;
    }

    .summary-section {
        margin-top: 1.5rem;
        border-top: 2px dashed rgba(244, 197, 66, 0.3);
        padding-top: 1.5rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        color: rgba(242, 228, 183, 0.8);
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 1.2rem;
        font-weight: bold;
        color: var(--vangogh-yellow);
    }

    .total-amount {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        text-shadow: 0 0 10px rgba(244, 197, 66, 0.3);
    }

    .discount-badge {
        background: linear-gradient(135deg, var(--discount-purple), #7b1fa2);
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
        margin-top: 0.5rem;
        box-shadow: 0 4px 10px rgba(156, 39, 176, 0.3);
    }

    .notes-section {
        background: rgba(11, 30, 63, 0.5);
        border-left: 3px solid var(--vangogh-blue);
        padding: 1rem;
        margin-top: 1.5rem;
        border-radius: 0 8px 8px 0;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .receipt-footer {
        padding: 1.5rem;
        text-align: center;
        background: rgba(0, 0, 0, 0.2);
    }

    .btn-home {
        background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
        color: var(--starry-night);
        border: none;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
    }

    .btn-home:hover {
        background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
        color: var(--starry-night);
    }

    .thank-you {
        font-family: 'Playfair Display', serif;
        font-style: italic;
        color: rgba(242, 228, 183, 0.6);
        margin-bottom: 1rem;
        display: block;
    }

    @media (max-width: 576px) {
        body { padding: 1rem; }
        .receipt-card { width: 100%; }
        .brand-logo { font-size: 1.8rem; }
    }
</style>
</head>
<body>
<div id="stars-container"></div>

<div class="receipt-card">
    <div class="receipt-header">
        <div class="brand-logo">
            <i class="fas fa-coffee me-2"></i>Café Lumière
        </div>
        <div class="receipt-meta">Transaction #<?=htmlspecialchars($txn['TRANSACTIONID'])?></div>
        <div class="receipt-meta"><?= $txn['CREATEDAT']->format('F j, Y • h:i A') ?></div>
        
        <?php 
        $discountType = $txn['DISCOUNT_TYPE'] ?? null;
        $discountAmount = $txn['DISCOUNT_AMOUNT'] ?? 0;
        if ($discountType && $discountType !== 'none'): 
        ?>
            <span class="discount-badge">
                <?php if ($discountType === 'pwd'): ?>
                    <i class="fas fa-wheelchair me-1"></i> PWD Discount
                <?php elseif ($discountType === 'senior'): ?>
                    <i class="fas fa-user-check me-1"></i> Senior Citizen
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="receipt-body">
        <div class="customer-info">
            <div class="info-row">
                <span class="info-label">Customer</span>
                <span class="info-value"><?=htmlspecialchars($txn['CUSTOMERNAME'])?></span>
            </div>
            <?php if($txn['CONTACT']): ?>
            <div class="info-row">
                <span class="info-label">Contact</span>
                <span class="info-value"><?=htmlspecialchars($txn['CONTACT'])?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value" style="color: var(--success-green);">
                    <i class="fas fa-check-circle me-1"></i><?=htmlspecialchars($txn['STATUS'])?>
                </span>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="50%">Item</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="35%" class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($it = sqlsrv_fetch_array($itStmt, SQLSRV_FETCH_ASSOC)):
                    $sub = $it['PRICE'] * $it['QUANTITY']; 
                    $itemsTotal += $sub; ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?=htmlspecialchars($it['PRODUCTNAME'])?></div>
                            <small style="color: rgba(255,255,255,0.5)">@ ₱<?= number_format($it['PRICE'],2) ?></small>
                        </td>
                        <td class="text-center">
                            <div class="item-qty"><?= $it['QUANTITY'] ?></div>
                        </td>
                        <td class="text-end fw-bold">₱<?= number_format($sub,2) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="summary-section">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>₱<?= number_format($itemsTotal,2) ?></span>
            </div>
            
            <?php $serviceCharge = $itemsTotal * 0.05; ?>
            <div class="summary-row">
                <span>Service Charge (5%)</span>
                <span>₱<?= number_format($serviceCharge,2) ?></span>
            </div>
            
            <?php if ($discountAmount > 0): ?>
            <div class="summary-row" style="color: var(--discount-purple); font-weight: 600;">
                <span>Discount Applied</span>
                <span>-₱<?= number_format($discountAmount,2) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="total-row">
                <span>Total Amount</span>
                <span class="total-amount">₱<?= number_format($txn['TOTALAMOUNT'],2) ?></span>
            </div>
        </div>

        <?php if($txn['NOTES']): ?>
        <div class="notes-section">
            <div class="mb-1 text-warning"><i class="fas fa-sticky-note me-2"></i>Special Instructions:</div>
            <?= nl2br(htmlspecialchars($txn['NOTES'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="receipt-footer">
        <span class="thank-you">"Great things are done by a series of small things brought together."</span>
        <a href="index.php" class="btn-home">
            <i class="fas fa-home"></i> Return to Menu
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const starsContainer = document.getElementById('stars-container');
    const starCount = 100;
    
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
});
</script>
</body>
</html>