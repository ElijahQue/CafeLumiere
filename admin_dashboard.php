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


$totalProducts = 0;
$pendingOrders = 0;

try {
    $productsSql = "SELECT COUNT(*) as total_products FROM LUMIEREMENU";
    $productsResult = sqlsrv_query($conn, $productsSql);
    if ($productsResult !== false) {
        $productsRow = sqlsrv_fetch_array($productsResult, SQLSRV_FETCH_ASSOC);
        $totalProducts = $productsRow['total_products'] ?? 0;
    }
    $pendingSql = "SELECT COUNT(*) as pending_orders FROM TRANSACTIONS WHERE STATUS = 'Pending'";
    $pendingResult = sqlsrv_query($conn, $pendingSql);
    if ($pendingResult !== false) {
        $pendingRow = sqlsrv_fetch_array($pendingResult, SQLSRV_FETCH_ASSOC);
        $pendingOrders = $pendingRow['pending_orders'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard Café Lumière</title>
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
      font-size: 3.5rem;
      background: linear-gradient(45deg, var(--vangogh-yellow), var(--cafe-cream));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
      margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.5rem;
      }
    }
    
    .hero-subtitle {
      font-size: 1.2rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto 2rem;
    }
    
    .btn-outline-light:hover {
      background-color: rgba(255, 255, 255, 0.1);
      border-color: var(--vangogh-yellow);
      color: var(--vangogh-yellow);
    }
    
    .btn-admin {
      background: linear-gradient(135deg, var(--vangogh-blue), #2a6fdb);
      color: white;
      border: none;
      border-radius: 50px;
      padding: 0.5rem 1.5rem;
      font-weight: 600;
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
    
    .dashboard-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .dashboard-title {
      font-family: 'Playfair Display', serif;
      font-weight: 900;
      font-size: 3.5rem;
      -webkit-background-clip: text;
      background-clip: text;
      color: var(--vangogh-yellow);
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
      margin-bottom: 1rem;
    }
    
    .admin-greeting {
      font-family: 'Playfair Display', serif;
      color: var(--vangogh-yellow);
      font-weight: 700;
      font-size: 1.3rem;
      margin-bottom: 0.5rem;
    }
    
    .admin-role {
      color: var(--vangogh-yellow);
      font-weight: 600;
      font-size: 1.1rem;
    }
    
    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }
    
    .admin-card {
      background: linear-gradient(145deg, 
                  rgba(255, 255, 255, 0.08) 0%, 
                  rgba(255, 255, 255, 0.03) 100%);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 2rem;
      border: 1px solid rgba(244, 197, 66, 0.2);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      height: 100%;
      position: relative;
      overflow: hidden;
    }
    
    .admin-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--vangogh-yellow), var(--vangogh-blue), var(--olive-green));
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .admin-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4),
                  0 0 0 1px rgba(244, 197, 66, 0.3);
      border-color: rgba(244, 197, 66, 0.4);
    }
    
    .admin-card:hover::before {
      opacity: 1;
    }
    
    .card-icon {
      font-size: 3rem;
      margin-bottom: 1.5rem;
      background: linear-gradient(45deg, var(--vangogh-yellow), var(--vangogh-blue));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    
    .card-title {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--vangogh-yellow);
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }
    
    .card-description {
      color: rgba(242, 228, 183, 0.8);
      font-size: 1rem;
      line-height: 1.6;
      margin-bottom: 1.5rem;
      min-height: 80px;
    }
    
    .btn-admin-action {
      background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
      color: var(--starry-night);
      border: none;
      border-radius: 50px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      width: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
    }
    
    .btn-admin-action:hover {
      background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
    }
    
    .btn-back {
      background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
      color: var(--starry-night);
      border: none;
      border-radius: 50px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
    }
    
    .btn-back:hover {
      background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }
    
    .stat-card {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
      padding: 1.5rem;
      text-align: center;
      border: 1px solid rgba(244, 197, 66, 0.2);
    }
    
    .stat-value {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--vangogh-yellow);
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      color: rgba(242, 228, 183, 0.7);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
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
          <i class="fas fa-crown me-2"></i>
          Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
        </span>
        <a href="logout.php" class="btn btn-outline-light me-2">
          <i class="fas fa-sign-out-alt me-1"></i>Logout
        </a>
        <a href="index.php" class="btn btn-back me-2">
          <i class="fas fa-coffee me-1"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="hero-section text-center">
    <h1 class="hero-title">Admin Dashboard</h1>
    <div class="admin-greeting">
      <i class="fas fa-user-tie me-2"></i>
      <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
    </div>
    <div class="admin-role">
      <i class="fas fa-star me-1"></i>
      Administrator
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value" id="totalProducts"><?php echo $totalProducts; ?></div>
      <div class="stat-label">Menu Items</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" id="pendingOrders"><?php echo $pendingOrders; ?></div>
      <div class="stat-label">Pending Orders</div>
    </div>
  </div>

  <div class="dashboard-cards">
    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-utensils"></i>
      </div>
      <h3 class="card-title">Menu Management</h3>
      <p class="card-description">
        View, add, edit, or delete menu items. Keep your offerings fresh and inspired.
      </p>
      <a href="productlist.php" class="btn btn-admin-action">
        <i class="fas fa-edit me-1"></i>Manage Products
      </a>
    </div>
    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-chart-line"></i>
      </div>
      <h3 class="card-title">Sales Analytics</h3>
      <p class="card-description">
        View detailed sales reports and insights. Track your café's performance.
      </p>
      <a href="reports.php" class="btn btn-admin-action">
        <i class="fas fa-chart-bar me-1"></i>View Reports
      </a>
    </div>
    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-shopping-cart"></i>
      </div>
      <h3 class="card-title">Order Management</h3>
      <p class="card-description">
        Track and manage customer orders, update order status, and ensure smooth service.
      </p>
      <a href="orders.php" class="btn btn-admin-action">
        <i class="fas fa-list-alt me-1"></i>Manage Orders
      </a>
    </div>
    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-users-cog"></i>
      </div>
      <h3 class="card-title">Staff Management</h3>
      <p class="card-description">
        Manage staff accounts, assign roles, and oversee your team's permissions.
      </p>
      <a href="accounts.php" class="btn btn-admin-action">
        <i class="fas fa-user-friends me-1"></i>Manage Staff
      </a>
    </div>
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
              &copy; <?php echo date('Y'); ?> Café Lumière
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
    
    const cards = document.querySelectorAll('.admin-card');
    cards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.zIndex = '10';
      });
      card.addEventListener('mouseleave', function() {
        this.style.zIndex = '';
      });
    });
  });
</script>
</body>
</html>