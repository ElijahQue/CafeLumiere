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

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','staff'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];

    if ($id > 0) {
        $updateSql = "UPDATE TRANSACTIONS SET STATUS = 'Completed' WHERE TRANSACTIONID = ?";
        sqlsrv_query($conn, $updateSql, [$id]);

        $_SESSION['success_message'] = "Order #{$id} marked as completed!";
    }
    $cleanQuery = $_GET;
    unset($cleanQuery['complete']);

    $redirectUrl = 'orders.php';
    if (!empty($cleanQuery)) {
        $redirectUrl .= '?' . http_build_query($cleanQuery);
    }

    header("Location: $redirectUrl");
    exit;
}

$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$dateFilterApplied = !empty($startDate) || !empty($endDate);

$dateWhere = " WHERE STATUS = 'Pending'";
$params = [];

if ($startDate && $endDate) {
    $dateWhere .= " AND CAST(CREATEDAT AS DATE) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];
} elseif ($startDate) {
    $dateWhere .= " AND CAST(CREATEDAT AS DATE) >= ?";
    $params = [$startDate];
} elseif ($endDate) {
    $dateWhere .= " AND CAST(CREATEDAT AS DATE) <= ?";
    $params = [$endDate];
} else {
    $dateWhere .= " AND CAST(CREATEDAT AS DATE) = CAST(GETDATE() AS DATE)";
}

$statsSql = "SELECT COUNT(*) AS pending_count, SUM(TOTALAMOUNT) AS pending_total FROM TRANSACTIONS $dateWhere";
$statsStmt = sqlsrv_query($conn, $statsSql, $params);
$stats = sqlsrv_fetch_array($statsStmt, SQLSRV_FETCH_ASSOC);

if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    if ($id > 0) {
        $sql = "UPDATE TRANSACTIONS SET STATUS='Completed' WHERE TRANSACTIONID=?";
        $params = array($id);
        sqlsrv_query($conn, $sql, $params);
        
        $_SESSION['success_message'] = "Order #{$id} marked as completed!";
        header("Location: orders.php" . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
        exit;
    }
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) AS total FROM TRANSACTIONS $dateWhere";
$countStmt = sqlsrv_query($conn, $countSql, $params);
$totalOrders = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)['total'];
$totalPages = max(1, ceil($totalOrders / $limit));
$orderSql = " WITH Ordered AS (SELECT *, ROW_NUMBER() OVER (ORDER BY CREATEDAT DESC) AS rn FROM TRANSACTIONS $dateWhere) SELECT o.*, STUFF((SELECT '|' + ti.PRODUCTNAME + ':' + CAST(ti.QUANTITY AS VARCHAR) + ':' + CAST(ti.PRICE AS VARCHAR) FROM TRANSACTIONITEMS ti WHERE ti.TRANSACTIONID = o.TRANSACTIONID FOR XML PATH('')),1,1,'') AS items FROM Ordered o WHERE rn BETWEEN ? AND ? ORDER BY CREATEDAT DESC";
$orderParams = array_merge($params, [$offset + 1, $offset + $limit]);
$stmt = sqlsrv_query($conn, $orderSql, $orderParams);

$pageTitle = "Starry Orders • Café Lumière";
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

        .filter-section {
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(244, 197, 66, 0.3);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
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

        .orders-list-container {
            background: linear-gradient(145deg, 
                        rgba(255, 255, 255, 0.08) 0%, 
                        rgba(255, 255, 255, 0.03) 100%);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(244, 197, 66, 0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .order-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(244, 197, 66, 0.2);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateX(5px);
            border-color: var(--vangogh-yellow);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .order-header {
            background: rgba(11, 30, 63, 0.5);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(244, 197, 66, 0.1);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .order-id {
            font-family: 'Playfair Display', serif;
            color: var(--vangogh-yellow);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .order-amount {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--vangogh-yellow);
        }

        .order-body {
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

        .items-table-container {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .items-table {
            width: 100%;
            color: var(--cafe-cream);
            margin-bottom: 0;
        }

        .items-table th {
            background: rgba(244, 197, 66, 0.1);
            color: var(--vangogh-yellow);
            font-weight: 600;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(244, 197, 66, 0.2);
        }

        .items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }

        .item-qty-badge {
            background: rgba(74, 143, 231, 0.2);
            border: 1px solid rgba(74, 143, 231, 0.3);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
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

        .btn-complete {
            background: linear-gradient(135deg, var(--success-green), #1e7e34);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-complete:hover {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-view {
            background: rgba(255, 255, 255, 0.1);
            color: var(--vangogh-yellow);
            border: 1px solid var(--vangogh-yellow);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: rgba(244, 197, 66, 0.2);
            color: white;
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

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border-color: var(--success-green);
            color: #d4edda;
            backdrop-filter: blur(10px);
            border-radius: 12px;
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
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success animate__animated animate__fadeIn">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['success_message']; ?>
        <?php unset($_SESSION['success_message']); ?>
    </div>
    <?php endif; ?>

    <div class="hero-section text-center">
        <h1 class="hero-title">
            <i class="fas fa-clipboard-list me-2"></i>
            Order Management
        </h1>
        <p class="hero-subtitle">Review pending orders.</p>
    </div>

    <div class="sales-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?php echo $stats['pending_count'] ?? 0; ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-value">₱<?php echo number_format($stats['pending_total'] ?? 0, 0); ?></div>
            <div class="stat-label">Pending Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value"><?php echo date('M j'); ?></div>
            <div class="stat-label">Today's Date</div>
        </div>
    </div>

    <div class="filter-section">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-filter text-warning me-2 fs-4"></i>
            <h4 class="mb-0 text-white">Filter Orders by Date</h4>
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
                <a href="orders.php" class="btn btn-outline-custom w-100 text-center">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if ($dateFilterApplied): ?>
        <div class="mt-3 text-center">
            <small class="text-white-50">
                <i class="fas fa-info-circle me-1"></i>
                Showing pending orders 
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

    <div class="orders-list-container">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-warning pb-2">
            <h3 class="text-warning mb-0" style="font-family: 'Playfair Display', serif;">
                <i class="fas fa-list-alt me-2"></i>
                <?php echo $dateFilterApplied ? 'Filtered Pending Orders' : "Today's Pending Orders"; ?>
            </h3>
            <span class="text-white-50">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        </div>
        
        <?php
        $hasOrders = sqlsrv_has_rows($stmt);
        if (!$hasOrders) {
            echo '<div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3 opacity-75"></i>
                    <h3 class="text-white mb-3" style="font-family: \'Playfair Display\', serif;">All caught up!</h3>
                    <p class="text-white-50 mb-4">No pending orders found.</p>
                    <a href="admin_dashboard.php" class="btn btn-outline-custom">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                  </div>';
        } else {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                $tid = $row['TRANSACTIONID'];
                $name = htmlspecialchars($row['CUSTOMERNAME']);
                $contact = htmlspecialchars($row['CONTACT']);
                $total = number_format($row['TOTALAMOUNT'], 2);
                $notes = htmlspecialchars($row['NOTES'] ?: 'No special notes');
                $date = $row['CREATEDAT']->format("F j, Y \\a\\t g:i A");
                $itemsString = $row['items'] ?? '';
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
        <div class="order-card" id="order-<?php echo $tid; ?>">
            <div class="order-header">
                <div>
                    <span class="order-id">
                        <i class="fas fa-receipt me-2"></i>Order #<?php echo $tid; ?>
                    </span>
                    <span class="ms-3 text-white-50"><small><?php echo $date; ?></small></span>
                </div>
                <div class="order-amount">₱<?php echo $total; ?></div>
            </div>
            <div class="order-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-user me-1 text-warning"></i> Customer</span>
                            <div class="text-white"><?php echo $name; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-phone me-1 text-warning"></i> Contact</span>
                            <div class="text-white"><?php echo $contact; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-sticky-note me-1 text-warning"></i> Notes</span>
                            <div class="text-white-50 small"><em><?php echo $notes; ?></em></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($items)): ?>
                <div class="mb-4">
                    <button class="btn btn-view w-100 mb-2" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#items-<?php echo $tid; ?>"
                            aria-expanded="false"
                            aria-controls="items-<?php echo $tid; ?>">
                        <i class="fas fa-utensils me-2"></i>
                        View Order Details (<?php echo count($items); ?> items)
                        <i class="fas fa-chevron-down ms-2"></i>
                    </button>
                    <div class="collapse" id="items-<?php echo $tid; ?>">
                        <div class="items-table-container">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><strong><?php echo $item['name']; ?></strong></td>
                                        <td class="text-center">
                                            <span class="item-qty-badge"><?php echo $item['qty']; ?></span>
                                        </td>
                                        <td class="text-end text-white-50">₱<?php echo $item['price']; ?></td>
                                        <td class="text-end text-warning fw-bold">₱<?php echo $item['subtotal']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr style="background: rgba(244, 197, 66, 0.05);">
                                        <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                        <td class="text-end fw-bold fs-5 text-warning">₱<?php echo $total; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end gap-2">
                    <a href="orders.php?complete=<?php echo $tid; ?>&<?php echo http_build_query($_GET); ?>"
                       onclick="return confirm('Mark Order #<?php echo $tid; ?> as completed?')"
                       class="btn btn-complete">
                        <i class="fas fa-check me-2"></i>Mark Completed
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center align-items-center mt-4 pt-3">
            <?php if ($page > 1): ?>
                <?php $prevQuery = $_GET; $prevQuery['page'] = $page - 1; ?>
                <a href="?<?php echo http_build_query($prevQuery); ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <span class="mx-2 text-white-50">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

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
        
        const cards = document.querySelectorAll('.stat-card, .order-card');
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