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

$cartItemCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartItemCount += $item['qty'];
    }
}

$catQuery = "SELECT DISTINCT CATEGORY FROM LUMIEREMENU ORDER BY CATEGORY";
$catResult = sqlsrv_query($conn, $catQuery);
$allCategories = [];
while($row = sqlsrv_fetch_array($catResult, SQLSRV_FETCH_ASSOC)) {
    $allCategories[] = $row['CATEGORY'];
}

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'all';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Café Lumière • Van Gogh Inspired Menu</title>
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

    .btn-filter {
      background: rgba(255, 255, 255, 0.1);
      color: var(--vangogh-yellow);
      border: 1px solid var(--vangogh-yellow);
      border-radius: 50px;
      padding: 0.5rem 1.25rem;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      text-decoration: none;
      backdrop-filter: blur(5px);
    }

    .btn-filter:hover {
      background: rgba(244, 197, 66, 0.2);
      color: #ffd700;
      transform: translateY(-2px);
    }

    .btn-filter.active {
      background: var(--vangogh-yellow);
      color: var(--starry-night);
      border-color: var(--vangogh-yellow);
      box-shadow: 0 0 15px rgba(244, 197, 66, 0.4);
    }

    .floating-cart-btn {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 1000;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      animation: float 6s ease-in-out infinite;
      filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
    }
    
    .floating-cart-btn:hover {
      transform: translateY(-5px) scale(1.05);
      animation-play-state: paused;
      filter: drop-shadow(0 15px 30px rgba(0, 0, 0, 0.4));
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    
    .cart-btn-inner {
      position: relative;
      background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
      color: var(--starry-night);
      border: none;
      border-radius: 50px;
      padding: 20px 30px;
      font-weight: 700;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 10px 25px rgba(244, 197, 66, 0.4),
                  inset 0 0 20px rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
      overflow: hidden;
    }
    
    .cart-btn-inner::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      right: -50%;
      bottom: -50%;
      background: linear-gradient(
        to bottom right,
        rgba(255, 255, 255, 0.1) 0%,
        rgba(255, 255, 255, 0.05) 50%,
        transparent 100%
      );
      transform: rotate(30deg);
      transition: all 0.5s ease;
    }
    
    .floating-cart-btn:hover .cart-btn-inner::before {
      transform: rotate(210deg);
    }
    
    .cart-icon {
      font-size: 1.5rem;
      position: relative;
    }
    
    .cart-count-badge {
      position: absolute;
      top: -12px;
      right: -12px;
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      color: white;
      border-radius: 50%;
      width: 28px;
      height: 28px;
      font-size: 0.9rem;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 3px solid var(--starry-night);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      animation: pulse 2s infinite;
      transform-origin: center;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
    
    .cart-text {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
    }
    
    .cart-arrow {
      opacity: 0;
      transform: translateX(-10px);
      transition: all 0.3s ease;
    }
    
    .floating-cart-btn:hover .cart-arrow {
      opacity: 1;
      transform: translateX(0);
    }
    
    @media (max-width: 768px) {
      .floating-cart-btn {
        bottom: 20px;
        right: 20px;
        padding: 15px 20px;
      }
      
      .cart-btn-inner {
        padding: 18px 25px;
        font-size: 1rem;
      }
      
      .cart-text {
        display: none;
      }
      
      .cart-arrow {
        display: none;
      }
      
      .floating-cart-btn:hover .cart-text {
        display: inline;
      }
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

    ::placeholder {
      color: rgba(242, 228, 183, 0.8) !important;
      opacity: 1 !important;
    }

    .search-box input::placeholder {
      color: rgba(255, 255, 255, 0.85) !important; 
      font-weight: 500;
    }

    .search-box {
      max-width: 600px;
      margin: 0 auto;
      position: relative;
    }
    
    .search-box input {
      background: rgba(255, 255, 255, 0.1);
      border: 2px solid var(--vangogh-yellow);
      border-radius: 50px;
      color: white;
      padding: 1rem 1.5rem;
      font-size: 1.1rem;
      backdrop-filter: blur(10px);
    }
    
    .search-box input:focus {
      background: rgba(255, 255, 255, 0.15);
      border-color: #ffd700;
      box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
      color: white;
    }
    
    .search-box button {
      background: var(--vangogh-yellow);
      color: var(--starry-night);
      border: none;
      border-radius: 50px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.3s ease;
      position: absolute;
      right: 5px;
      top: 50%;
      transform: translateY(-50%);
    }
    
    .search-box button:hover {
      background: #ffd700;
      transform: translateY(-50%) scale(1.05);
      box-shadow: 0 5px 15px rgba(244, 197, 66, 0.4);
    }
    
    .category-title {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      font-size: 2.2rem;
      color: var(--vangogh-yellow);
      margin: 3rem 0 1.5rem;
      padding-bottom: 0.5rem;
      border-bottom: 3px solid;
      border-image: linear-gradient(90deg, var(--vangogh-yellow), transparent) 1;
      position: relative;
      display: inline-block;
    }
    
    .category-title::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--vangogh-yellow), transparent);
    }
    
    .menu-card {
      background: linear-gradient(145deg, 
                  rgba(255, 255, 255, 0.08) 0%, 
                  rgba(255, 255, 255, 0.03) 100%);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 1.5rem;
      border: 1px solid rgba(244, 197, 66, 0.2);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      height: 100%;
      position: relative;
      overflow: hidden;
    }
    
    .menu-card::before {
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
    
    .menu-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4),
                  0 0 0 1px rgba(244, 197, 66, 0.3);
      border-color: rgba(244, 197, 66, 0.4);
    }
    
    .menu-card:hover::before {
      opacity: 1;
    }
    
    .menu-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 12px;
      border: 2px solid var(--vangogh-yellow);
      transition: all 0.3s ease;
    }
    
    .menu-card:hover .menu-img {
      transform: scale(1.05);
      border-color: #ffd700;
    }
    
    .menu-card h5 {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--vangogh-yellow);
      margin: 1rem 0 0.5rem;
      font-size: 1.3rem;
    }
    
    .menu-card .description {
      color: var(--vangogh-yellow);
      font-size: 0.95rem;
      line-height: 1.5;
      margin-bottom: 1rem;
      min-height: 60px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .price-tag {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--vangogh-yellow);
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .btn-add-to-cart {
      background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
      color: var(--starry-night);
      border: none;
      border-radius: 50px;
      padding: 0.5rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
    }
    
    .btn-add-to-cart:hover {
      background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
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
    
    .btn-cart {
      display: none;
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
    
    .footer {
      background: rgba(11, 30, 63, 0.9);
      border-top: 1px solid rgba(244, 197, 66, 0.3);
      padding: 2rem 0;
      margin-top: 4rem;
      backdrop-filter: blur(10px);
      position: relative;
      z-index: 1;
    }
    
    .no-results {
      text-align: center;
      padding: 3rem;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
      border: 1px dashed rgba(244, 197, 66, 0.3);
    }
    
    .no-results i {
      font-size: 3rem;
      color: var(--vangogh-yellow);
      margin-bottom: 1rem;
    }
    
    .cart-notification {
      position: fixed;
      bottom: 100px;
      right: 30px;
      background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
      color: white;
      padding: 15px 20px;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
      z-index: 999;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.3s ease;
      max-width: 300px;
      border-left: 4px solid var(--vangogh-yellow);
    }
    
    .cart-notification.show {
      opacity: 1;
      transform: translateY(0);
    }
    
    .cart-notification h6 {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .cart-tooltip {
      position: absolute;
      bottom: 100%;
      right: 0;
      background: rgba(11, 30, 63, 0.95);
      border: 1px solid var(--vangogh-yellow);
      border-radius: 10px;
      padding: 15px;
      min-width: 250px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
      opacity: 0;
      transform: translateY(10px);
      transition: all 0.3s ease;
      pointer-events: none;
      z-index: 1001;
      backdrop-filter: blur(10px);
    }
    
    .floating-cart-btn:hover .cart-tooltip {
      opacity: 1;
      transform: translateY(0);
    }
    
    .tooltip-title {
      font-family: 'Playfair Display', serif;
      color: var(--vangogh-yellow);
      font-weight: 700;
      margin-bottom: 10px;
      font-size: 1.1rem;
    }
    
    .tooltip-item {
      display: flex;
      justify-content: space-between;
      padding: 5px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 0.9rem;
    }
    
    .tooltip-empty {
      text-align: center;
      color: rgba(242, 228, 183, 0.7);
      padding: 10px;
      font-style: italic;
    }
    
    .tooltip-total {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 2px solid var(--vangogh-yellow);
      font-weight: 600;
      color: var(--vangogh-yellow);
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
            Hello, <?=htmlspecialchars($_SESSION['user']['username'])?>
            <span class="badge bg-warning text-dark ms-1"><?php echo ucfirst($_SESSION['user']['role']); ?></span>
          </span>
          <a href="logout.php" class="btn btn-outline-light me-2">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
          </a>
          <?php if($_SESSION['user']['role'] === 'admin'): ?>
            <a href="admin_dashboard.php" class="btn btn-admin me-2">
              <i class="fas fa-crown me-1"></i>Admin
            </a>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline-light me-2">
            <i class="fas fa-sign-in-alt me-1"></i>Login
          </a>
          <a href="register.php" class="btn btn-add-to-cart me-2">
            <i class="fas fa-user-plus me-1"></i>Register
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="hero-section text-center">
    <h1 class="hero-title">Welcome to Café Lumière</h1>
    <p class="hero-subtitle">Where every sip is a brushstroke of flavor, inspired by Van Gogh's vibrant palette. Experience art in every cup.</p>
    <form class="search-box d-flex" method="GET" action="index.php">
      <input name="q" class="form-control" 
             placeholder="Search our menu..."
             value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
      <button class="btn btn-add-to-cart" type="submit">
        <i class="fas fa-search me-1"></i>Search
      </button>
    </form>
    <div class="category-filters mt-4 d-flex justify-content-center flex-wrap gap-2">
        <a href="index.php" class="btn btn-filter <?= ($selectedCategory === 'all') ? 'active' : '' ?>">
            <i class="fas fa-th-large me-1"></i> All
        </a>
        <?php foreach ($allCategories as $catName): ?>
            <a href="?category=<?= urlencode($catName) ?>" 
               class="btn btn-filter <?= ($selectedCategory === $catName) ? 'active' : '' ?>">
               <?= htmlspecialchars(ucfirst($catName)) ?>
            </a>
        <?php endforeach; ?>
    </div>
  </div>

  <?php
  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  $selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'all';
  $categoriesToShow = array();
  
  if ($selectedCategory === 'all') {
      $catSql = "SELECT DISTINCT CATEGORY FROM LUMIEREMENU ORDER BY CATEGORY";
      $catStmt = sqlsrv_query($conn, $catSql);
      while ($catRow = sqlsrv_fetch_array($catStmt, SQLSRV_FETCH_ASSOC)) {
          $categoriesToShow[] = $catRow['CATEGORY'];
      }
  } else {
      $categoriesToShow[] = $selectedCategory;
  }
  
  $hasResults = false;
  
  foreach ($categoriesToShow as $cat):
    if ($q !== '') {
        $like = "%$q%";
        $sql = "SELECT * FROM LUMIEREMENU WHERE CATEGORY = '$cat' AND (PRODUCTNAME LIKE '$like' OR DESCRIPTION LIKE '$like')";
    } else {
        $sql = "SELECT * FROM LUMIEREMENU WHERE CATEGORY = '$cat'";
    }
    
    $stmt = sqlsrv_query($conn, $sql);
    
    $categoryItems = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
      $categoryItems[] = $row;
    }
    
    if (!empty($categoryItems)) {
      $hasResults = true;
      echo "<h2 class='category-title'>" . htmlspecialchars(ucfirst($cat)) . "</h2>";
      echo "<div class='row g-4'>";
      
      foreach ($categoryItems as $row) {
        $id = $row['PRODUCTID'];
        $name = htmlspecialchars($row['PRODUCTNAME']);
        $price = number_format($row['PRICE'], 2);
        $img = htmlspecialchars($row['IMAGEPATH']);
        $desc = htmlspecialchars($row['DESCRIPTION']);
        
        echo "<div class='col-lg-4 col-md-6'>
                <div class='menu-card h-100'>
                  <img src='" . $img . "' class='menu-img' alt='" . $name . "'>
                  <h5>{$name}</h5>
                  <p class='description'>{$desc}</p>
                  <div class='d-flex align-items-center justify-content-between mt-auto'>
                    <span class='price-tag'>₱{$price}</span>
                    <form method='POST' action='add_to_cart.php' class='mb-0'>
                      <input type='hidden' name='id' value='{$id}'>
                      <input type='hidden' name='return' value='index.php'>
                      <button type='submit' class='btn btn-add-to-cart'>
                        <i class='fas fa-palette me-1'></i>Add to Cart
                      </button>
                    </form>
                  </div>
                </div>
              </div>";
      }
      
      echo "</div>";
    }
  endforeach;
  
  if (!$hasResults) {
    if ($q !== '') {
        echo "<div class='no-results my-5'>
                <i class='fas fa-search'></i>
                <h3 class='text-warning mb-3'>No Results Found</h3>
                <p>We couldn't find any menu items matching \"{$q}\" in the selected category.</p>
                <p>Try searching for something else or <a href='index.php' class='text-warning'>browse all items</a>.</p>
              </div>";
    } else {
        echo "<div class='no-results my-5'>
                <i class='fas fa-coffee'></i>
                <h3 class='text-warning mb-3'>Category Empty</h3>
                <p>No items found in this category.</p>
                <a href='index.php' class='btn btn-outline-light mt-3'>View Full Menu</a>
              </div>";
    }
  }
?>

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

<div class="floating-cart-btn">
  <a href="cart.php" class="cart-btn-inner">
    <div class="cart-icon">
      <i class="fas fa-shopping-basket"></i>
      <?php if ($cartItemCount > 0): ?>
        <span class="cart-count-badge"><?php echo $cartItemCount; ?></span>
      <?php endif; ?>
    </div>
    <span class="cart-text">View Cart</span>
    <span class="cart-arrow">
      <i class="fas fa-arrow-right"></i>
    </span>
  </a>

  <div class="cart-tooltip">
    <div class="tooltip-title">
      <i class="fas fa-shopping-basket me-2"></i>
      Cart Preview
    </div>
    
    <?php if (!empty($_SESSION['cart'])): ?>
      <?php 
      $tooltipTotal = 0;
      $displayLimit = 3;
      $itemCount = 0;
      ?>
      
      <?php foreach ($_SESSION['cart'] as $item): ?>
        <?php if ($itemCount < $displayLimit): ?>
          <?php
          $itemTotal = $item['price'] * $item['qty'];
          $tooltipTotal += $itemTotal;
          ?>
          <div class="tooltip-item">
            <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['qty']; ?></span>
            <span>₱<?php echo number_format($itemTotal, 2); ?></span>
          </div>
        <?php endif; ?>
        <?php $itemCount++; ?>
      <?php endforeach; ?>
      
      <?php if (count($_SESSION['cart']) > $displayLimit): ?>
        <div class="tooltip-item">
          <span>+<?php echo count($_SESSION['cart']) - $displayLimit; ?> more items</span>
          <span>...</span>
        </div>
      <?php endif; ?>
      
      <div class="tooltip-total">
        <span>Total:</span>
        <span>₱<?php echo number_format($tooltipTotal, 2); ?></span>
      </div>
      
      <div class="text-center mt-2">
        <small>
          <a href="cart.php" style="color: var(--vangogh-yellow); text-decoration: none;">
            Go to Cart <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </small>
      </div>
    <?php else: ?>
      <div class="tooltip-empty">
        <i class="fas fa-paint-brush mb-2" style="font-size: 2rem;"></i>
        <p>Your cart is empty</p>
        <small>Add some artistic masterpieces!</small>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="cart-notification" id="cartNotification">
  <h6><i class="fas fa-check-circle me-2"></i>Added to Cart!</h6>
  <p class="mb-0" id="notificationMessage">Item successfully added to your cart.</p>
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
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('cart_added')) {
      const itemName = urlParams.get('item') || 'Item';
      showCartNotification(itemName);
    }
    
    document.querySelectorAll('.btn-add-to-cart').forEach(button => {
      button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
          position: absolute;
          border-radius: 50%;
          background: rgba(255, 255, 255, 0.3);
          transform: scale(0);
          animation: ripple 0.6s linear;
          width: ${size}px;
          height: ${size}px;
          top: ${y}px;
          left: ${x}px;
          pointer-events: none;
        `;
        
        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);
        
        setTimeout(() => {
          ripple.remove();
        }, 600);
      });
    });
  });
  
  function showCartNotification(itemName) {
    const notification = document.getElementById('cartNotification');
    const message = document.getElementById('notificationMessage');
    
    message.textContent = `${itemName} successfully added to your cart.`;
    notification.classList.add('show');
    setTimeout(() => {
      notification.classList.remove('show');
    }, 3000);
    const cartBadge = document.querySelector('.cart-count-badge');
    if (cartBadge) {
      const currentCount = parseInt(cartBadge.textContent) || 0;
      cartBadge.textContent = currentCount + 1;
      cartBadge.style.animation = 'none';
      setTimeout(() => {
        cartBadge.style.animation = 'pulse 2s infinite';
      }, 10);
    } else {
      const cartIcon = document.querySelector('.cart-icon');
      if (cartIcon) {
        const badge = document.createElement('span');
        badge.className = 'cart-count-badge';
        badge.textContent = '1';
        cartIcon.appendChild(badge);
      }
    }
  }
  const style = document.createElement('style');
  style.textContent = `
    @keyframes ripple {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);
</script>
</body>
</html>