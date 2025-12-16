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

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$dateFilterApplied = !empty($startDate) || !empty($endDate);

$dateWhereClause = "";
$dateParams = [];

if ($dateFilterApplied) {
    if (!empty($startDate) && !empty($endDate)) {
        $dateWhereClause = " WHERE CAST(CREATEDAT AS DATE) BETWEEN ? AND ?";
        $dateParams = array($startDate, $endDate);
    } elseif (!empty($startDate)) {
        $dateWhereClause = " WHERE CAST(CREATEDAT AS DATE) >= ?";
        $dateParams = array($startDate);
    } elseif (!empty($endDate)) {
        $dateWhereClause = " WHERE CAST(CREATEDAT AS DATE) <= ?";
        $dateParams = array($endDate);
    }
}

$statsSql = "SELECT COUNT(*) as total_transactions, SUM(TOTALAMOUNT) as total_revenue, MIN(CREATEDAT) as first_sale, MAX(CREATEDAT) as last_sale FROM TRANSACTIONS" . $dateWhereClause;

$statsResult = sqlsrv_query($conn, $statsSql, $dateParams);
$stats = sqlsrv_fetch_array($statsResult, SQLSRV_FETCH_ASSOC);

$todaySql = "SELECT SUM(TOTALAMOUNT) as today_sales FROM TRANSACTIONS WHERE CAST(CREATEDAT AS DATE) = CAST(GETDATE() AS DATE)";
$todayResult = sqlsrv_query($conn, $todaySql);
$today = sqlsrv_fetch_array($todayResult, SQLSRV_FETCH_ASSOC);

$monthSql = "SELECT SUM(TOTALAMOUNT) as month_sales FROM TRANSACTIONS WHERE MONTH(CREATEDAT) = MONTH(GETDATE()) AND YEAR(CREATEDAT) = YEAR(GETDATE())";
$monthResult = sqlsrv_query($conn, $monthSql);
$month = sqlsrv_fetch_array($monthResult, SQLSRV_FETCH_ASSOC);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) as total FROM TRANSACTIONS" . $dateWhereClause;
$countResult = sqlsrv_query($conn, $countSql, $dateParams);
$countData = sqlsrv_fetch_array($countResult, SQLSRV_FETCH_ASSOC);
$totalTransactions = $countData['total'];
$totalPages = ceil($totalTransactions / $limit);

$baseTxSql = "WITH NumberedTransactions AS (
                SELECT *, 
                       ROW_NUMBER() OVER (ORDER BY CREATEDAT DESC) as row_num
                FROM TRANSACTIONS" . $dateWhereClause . "
              )
              SELECT t.*, 
                     STUFF((SELECT '|' + ti.PRODUCTNAME + ':' + CAST(ti.QUANTITY AS VARCHAR) + ':' + CAST(ti.PRICE AS VARCHAR)
                            FROM TRANSACTIONITEMS ti
                            WHERE ti.TRANSACTIONID = t.TRANSACTIONID
                            FOR XML PATH('')), 1, 1, '') as items
              FROM NumberedTransactions t
              WHERE t.row_num BETWEEN ? AND ?
              ORDER BY t.CREATEDAT DESC";

$txParams = array_merge($dateParams, array($offset + 1, $offset + $limit));
$txStmt = sqlsrv_query($conn, $baseTxSql, $txParams);

$grandTotalSql = "SELECT SUM(TOTALAMOUNT) as grand_total FROM TRANSACTIONS" . $dateWhereClause;
$grandTotalResult = sqlsrv_query($conn, $grandTotalSql, $dateParams);
$grandTotalData = sqlsrv_fetch_array($grandTotalResult, SQLSRV_FETCH_ASSOC);
$grandTotal = $grandTotalData['grand_total'] ?? 0;

$pageTitle = "Sales Analytics • Café Lumière";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?></title>
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
            background: radial-gradient(circle at 20% 30%, rgba(244, 197, 66, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 80% 70%, rgba(74, 143, 231, 0.1) 0%, transparent 40%);
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
            content: '👑';
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
        
        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--vangogh-yellow);
            color: var(--vangogh-yellow);
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
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .sales-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(145deg, 
                        rgba(255, 255, 255, 0.08) 0%, 
                        rgba(255, 255, 255, 0.03) 100%);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(244, 197, 66, 0.2);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--vangogh-yellow);
            box-shadow: 0 10px 25px rgba(244, 197, 66, 0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--vangogh-yellow), var(--vangogh-blue));
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--vangogh-yellow);
        }
        
        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.3rem;
        }
        
        .stat-label {
            color: rgba(242, 228, 183, 0.7);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .filter-section:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--vangogh-yellow);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(244, 197, 66, 0.3);
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            color: white;
            outline: none;
        }
        
        .transactions-container {
            background: linear-gradient(145deg, 
                        rgba(255, 255, 255, 0.08) 0%, 
                        rgba(255, 255, 255, 0.03) 100%);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(244, 197, 66, 0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .transaction-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(244, 197, 66, 0.2);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .transaction-card:hover {
            transform: translateX(5px);
            border-color: var(--vangogh-yellow);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .transaction-header {
            background: rgba(11, 30, 63, 0.5);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(244, 197, 66, 0.1);
        }

        .transaction-id {
            font-family: 'Playfair Display', serif;
            color: var(--vangogh-yellow);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .transaction-amount {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--vangogh-yellow);
        }

        .transaction-body {
            padding: 1.5rem;
        }

        .info-item {
            margin-bottom: 0.5rem;
        }

        .info-label {
            font-size: 0.8rem;
            color: rgba(242, 228, 183, 0.6);
            text-transform: uppercase;
            margin-right: 0.5rem;
        }

        .item-badge {
            display: inline-block;
            background: rgba(74, 143, 231, 0.2);
            border: 1px solid rgba(74, 143, 231, 0.3);
            border-radius: 15px;
            padding: 0.25rem 0.75rem;
            margin: 0.25rem;
            font-size: 0.9rem;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
            color: var(--starry-night);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
        }
        
        .btn-action:hover {
            background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
        }

        .btn-outline-custom {
            border: 2px solid var(--vangogh-blue);
            color: white;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-custom:hover {
            background: var(--vangogh-blue);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 143, 231, 0.3);
        }
        
        .page-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--vangogh-yellow);
            color: var(--cafe-cream);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .page-btn:hover, .page-btn.active {
            background: var(--vangogh-yellow);
            color: var(--starry-night);
            font-weight: bold;
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
        </style>
</head>
<body>
<div id="stars-container"></div>

<nav class="navbar navbar-expand-lg navbar-dark vangogh-navbar">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Café Lumière Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                <span class="user-greeting me-3">
                    <i class="fas fa-user-tie me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
                <a href="index.php" class="btn btn-action">
                    <i class="fas fa-coffee me-1"></i>Back to Café
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="hero-section text-center">
        <h1 class="hero-title">
            <i class="fas fa-chart-line me-2"></i>
            Sales Analytics
        </h1>
        <p class="hero-subtitle">Track revenue and analyze trends. Every transaction tells a story.</p>
    </div>

    <div class="sales-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-value">₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div class="stat-value"><?php echo $stats['total_transactions'] ?? 0; ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value">₱<?php echo number_format($today['today_sales'] ?? 0, 2); ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-value">₱<?php echo number_format($month['month_sales'] ?? 0, 2); ?></div>
            <div class="stat-label">Monthly Revenue</div>
        </div>
    </div>

    <div class="filter-section">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-filter text-warning me-2 fs-4"></i>
            <h4 class="mb-0 text-white">Filter by Date Range</h4>
        </div>
        
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="text-warning mb-2 fw-bold">Start Date</label>
                <input type="date" 
                       name="start_date" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($startDate); ?>"
                       max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4">
                <label class="text-warning mb-2 fw-bold">End Date</label>
                <input type="date" 
                       name="end_date" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($endDate); ?>"
                       max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-action w-100">
                    <i class="fas fa-search me-2"></i>Apply
                </button>
                <?php if ($dateFilterApplied): ?>
                <a href="reports.php" class="btn btn-outline-custom w-100 text-center">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if ($dateFilterApplied): ?>
        <div class="mt-3 text-center">
            <small class="text-white-50">
                <i class="fas fa-info-circle me-1"></i>
                Showing results 
                <?php if (!empty($startDate) && !empty($endDate)): ?>
                    from <?php echo date('F j, Y', strtotime($startDate)); ?> to <?php echo date('F j, Y', strtotime($endDate)); ?>
                <?php elseif (!empty($startDate)): ?>
                    from <?php echo date('F j, Y', strtotime($startDate)); ?> onwards
                <?php elseif (!empty($endDate)): ?>
                    up to <?php echo date('F j, Y', strtotime($endDate)); ?>
                <?php endif; ?>
            </small>
        </div>
        <?php endif; ?>
    </div>

    <div class="transactions-container">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-warning pb-2">
            <h3 class="text-warning mb-0" style="font-family: 'Playfair Display', serif;">
                <i class="fas fa-history me-2"></i>
                Transaction History
            </h3>
            <span class="text-white-50">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        </div>

        <?php
        $hasTransactions = sqlsrv_has_rows($txStmt);
        if (!$hasTransactions) {
            echo '<div class="text-center py-5">
                    <i class="fas fa-wind fa-3x text-warning mb-3 opacity-50"></i>
                    <h4 class="text-white">No transactions found</h4>
                    <p class="text-white-50">Try adjusting your filters or making some sales!</p>
                  </div>';
        } else {
            while ($tx = sqlsrv_fetch_array($txStmt, SQLSRV_FETCH_ASSOC)):
                $tid = $tx['TRANSACTIONID'];
                $cust = htmlspecialchars($tx['CUSTOMERNAME']);
                $contact = htmlspecialchars($tx['CONTACT']);
                $notes = htmlspecialchars($tx['NOTES'] ?: 'No notes');
                $date = $tx['CREATEDAT']->format("F j, Y \\a\\t g:i A");
                $total = number_format($tx['TOTALAMOUNT'], 2);
                $itemsString = $tx['items'] ?? '';
                $items = [];
                if ($itemsString) {
                    $itemParts = explode('|', $itemsString);
                    foreach ($itemParts as $part) {
                        $itemData = explode(':', $part);
                        if (count($itemData) >= 3) {
                            $items[] = [
                                'name' => htmlspecialchars($itemData[0]),
                                'qty' => $itemData[1],
                                'price' => number_format($itemData[2], 2),
                                'subtotal' => number_format($itemData[1] * $itemData[2], 2)
                            ];
                        }
                    }
                }
        ?>
        <div class="transaction-card">
            <div class="transaction-header">
                <div>
                    <span class="transaction-id">#<?= $tid ?></span>
                    <span class="ms-3 text-white-50"><small><?= $date ?></small></span>
                </div>
                <div class="transaction-amount">₱<?= $total ?></div>
            </div>
            <div class="transaction-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="info-item">
                            <i class="fas fa-user me-2 text-warning"></i>
                            <span class="text-white"><?= $cust ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <i class="fas fa-phone me-2 text-warning"></i>
                            <span class="text-white"><?= $contact ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <i class="fas fa-sticky-note me-2 text-warning"></i>
                            <span class="text-white-50 small"><?= $notes ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($items)): ?>
                <div class="border-top border-secondary pt-3 mt-2">
                    <small class="text-warning text-uppercase fw-bold">Items Ordered</small>
                    <div class="mt-2">
                        <?php foreach ($items as $item): ?>
                        <span class="item-badge text-white">
                            <?= $item['name'] ?> 
                            <span class="text-warning ms-1">x<?= $item['qty'] ?></span>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>

        <div class="text-center py-4 mt-4" style="background: rgba(244, 197, 66, 0.1); border-radius: 15px; border: 1px solid var(--vangogh-yellow);">
            <h4 class="text-white-50 mb-2">Grand Total Revenue</h4>
            <h2 class="text-warning display-4 fw-bold" style="font-family: 'Playfair Display', serif;">₱<?= number_format($grandTotal, 2) ?></h2>
            <small class="text-white-50">Based on <?= $totalTransactions ?> transaction<?= $totalTransactions != 1 ? 's' : '' ?></small>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center align-items-center mt-4 pt-3">
            <?php if ($page > 1): ?>
                <?php $prevQuery = $_GET; $prevQuery['page'] = $page - 1; ?>
                <a href="?<?php echo http_build_query($prevQuery); ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $startPage + 4);
            for ($i = $startPage; $i <= $endPage; $i++):
                $pageQuery = $_GET; $pageQuery['page'] = $i;
            ?>
                <a href="?<?php echo http_build_query($pageQuery); ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <?php $nextQuery = $_GET; $nextQuery['page'] = $page + 1; ?>
                <a href="?<?php echo http_build_query($nextQuery); ?>" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php } ?>
    </div>

    <footer class="footer text-center mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start">
                    <h4 class="text-warning mb-3">Café Lumière Admin</h4>
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

        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.max = today;
        });

        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (this.value && endDateInput.value && this.value > endDateInput.value) {
                    endDateInput.value = this.value;
                }
            });
            
            endDateInput.addEventListener('change', function() {
                if (this.value && startDateInput.value && this.value < startDateInput.value) {
                    this.value = startDateInput.value;
                }
            });
        }

        const cards = document.querySelectorAll('.stat-card, .transaction-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
</body>
</html>