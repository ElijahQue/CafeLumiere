<?php
session_start();
$serverName = "Elijah\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "LUMIERE",
    "Uid" => "",
    "PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Product Management • Café Lumière</title>
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
  
  .btn-admin {
    background: linear-gradient(135deg, var(--vangogh-blue), #2a6fdb);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
  }
  
  .management-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
  }
  
  .page-header {
    background: linear-gradient(145deg, 
                rgba(255, 255, 255, 0.08) 0%, 
                rgba(255, 255, 255, 0.03) 100%);
    backdrop-filter: blur(15px);
    border-radius: 25px;
    border: 2px solid var(--vangogh-yellow);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4),
                inset 0 0 80px rgba(244, 197, 66, 0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
  }
  
  .page-header::before {
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
  
  .page-title {
    font-family: 'Playfair Display', serif;
    font-weight: 900;
    font-size: 2.8rem;
    -webkit-background-clip: text;
    background-clip: text;
    color: var(--vangogh-yellow);
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    margin-bottom: 1rem;
  }
  
  .page-subtitle {
    color: rgba(242, 228, 183, 0.8);
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
  }
  
  .admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 1.2rem;
    text-align: center;
    border: 1px solid rgba(244, 197, 66, 0.2);
    transition: all 0.3s ease;
  }
  
  .stat-card:hover {
    transform: translateY(-3px);
    border-color: var(--vangogh-yellow);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }
  
  .stat-value {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--vangogh-yellow);
    margin-bottom: 0.3rem;
  }
  
  .stat-label {
    color: rgba(242, 228, 183, 0.7);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  
  .btn-add-product {
    background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
    color: var(--starry-night);
    border: none;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
  }
  
  .btn-add-product:hover {
    background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
  }
  
  .btn-back {
    background: linear-gradient(135deg, var(--vangogh-blue), #2a6fdb);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(74, 143, 231, 0.3);
  }
  
  .btn-back:hover {
    background: linear-gradient(135deg, #4a8fe7, var(--vangogh-blue));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 143, 231, 0.4);
  }
  
  .products-table-container {
    background: linear-gradient(145deg, 
                rgba(255, 255, 255, 0.08) 0%, 
                rgba(255, 255, 255, 0.03) 100%);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    border: 1px solid rgba(244, 197, 66, 0.2);
    padding: 2rem;
    margin-top: 2rem;
    overflow: hidden;
  }
  
  .table {
    color: var(--cafe-cream) !important;
    margin-bottom: 0;
    --bs-table-bg: transparent !important;
    --bs-table-color: var(--cafe-cream) !important;
    --bs-table-border-color: rgba(244, 197, 66, 0.2) !important;
    --bs-table-striped-bg: rgba(255, 255, 255, 0.03) !important;
    --bs-table-striped-color: var(--cafe-cream) !important;
    --bs-table-hover-bg: rgba(244, 197, 66, 0.1) !important;
    --bs-table-hover-color: var(--cafe-cream) !important;
  }
  
  .table th,
  .table td {
    color: var(--cafe-cream) !important;
    background-color: transparent !important;
  }
  
  .table thead th {
    border-bottom: 2px solid var(--vangogh-yellow);
    color: var(--vangogh-yellow) !important;
    font-weight: 700;
    padding: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9rem;
    background-color: rgba(0, 0, 0, 0.2) !important;
  }
  
  .table tbody td {
    border-color: rgba(244, 197, 66, 0.1);
    padding: 1rem;
    vertical-align: middle;
    color: var(--cafe-cream) !important;
  }
  
  .table tbody tr {
    transition: all 0.3s ease;
    background-color: transparent !important;
  }
  
  .table tbody tr:nth-child(even) {
    background-color: rgba(255, 255, 255, 0.02) !important;
  }
  
  .table tbody tr:hover {
    background: rgba(244, 197, 66, 0.1) !important;
    transform: translateX(5px);
  }
  
  .product-image {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid var(--vangogh-yellow);
    transition: all 0.3s ease;
  }
  
  .product-image:hover {
    transform: scale(1.1);
    border-color: #ffd700;
    box-shadow: 0 5px 15px rgba(244, 197, 66, 0.3);
  }
  
  .price-tag {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--vangogh-yellow) !important;
    font-size: 1.1rem;
  }
  
  .category-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .category-coffee { 
    background: rgba(210, 180, 140, 0.3); 
    color: #f5deb3 !important; 
    border: 1px solid #d2b48c; 
  }
  .category-pastry { 
    background: rgba(244, 197, 66, 0.3); 
    color: var(--vangogh-yellow) !important; 
    border: 1px solid var(--vangogh-yellow); 
  }
  .category-dessert { 
    background: rgba(218, 112, 214, 0.3); 
    color: #dda0dd !important; 
    border: 1px solid #da70d6; 
  }
  .category-milktea { 
    background: rgba(144, 238, 144, 0.3); 
    color: #90ee90 !important; 
    border: 1px solid #90ee90; 
  }
  .category-other { 
    background: rgba(135, 206, 235, 0.3); 
    color: #87ceeb !important; 
    border: 1px solid #87ceeb; 
  }
  
  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }
  
  .btn-edit {
    background: linear-gradient(135deg, var(--vangogh-blue), #2a6fdb);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-edit:hover {
    background: linear-gradient(135deg, #4a8fe7, var(--vangogh-blue));
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(74, 143, 231, 0.3);
  }
  
  .btn-delete {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-delete:hover {
    background: linear-gradient(135deg, #dc3545, #c82333);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
  }
  
  .empty-state {
    text-align: center;
    padding: 3rem;
    color: rgba(242, 228, 183, 0.6);
  }
  
  .empty-state i {
    font-size: 4rem;
    color: var(--vangogh-yellow);
    margin-bottom: 1rem;
    opacity: 0.5;
  }
  
  .search-container {
    position: relative;
    max-width: 400px;
    margin-bottom: 1.5rem;
  }
  
  .search-input {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(244, 197, 66, 0.3);
    border-radius: 50px;
    color: white;
    padding: 0.75rem 1.5rem 0.75rem 3rem;
    width: 100%;
    font-size: 1rem;
    transition: all 0.3s ease;
  }
  
  .search-input:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: var(--vangogh-yellow);
    box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
    outline: none;
  }
  
  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--vangogh-yellow);
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
  
  @media (max-width: 768px) {
    .page-title {
      font-size: 2.2rem;
    }
    
    .table thead {
      display: none;
    }
    
    .table tbody tr {
      display: block;
      margin-bottom: 1rem;
      background: rgba(255, 255, 255, 0.05) !important;
      border-radius: 10px;
      padding: 1rem;
      border: 1px solid rgba(244, 197, 66, 0.2);
    }
    
    .table tbody td {
      display: block;
      text-align: right;
      border: none;
      padding: 0.5rem;
      position: relative;
      color: var(--cafe-cream) !important;
    }
    
    .table tbody td::before {
      content: attr(data-label);
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-weight: 700;
      color: var(--vangogh-yellow) !important;
    }
    
    .action-buttons {
      justify-content: flex-end;
    }
    
    .product-image {
      margin: 0 auto;
      display: block;
    }
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
        <a href="index.php" class="btn btn-add-to-cart me-2">
          <i class="fas fa-coffee me-1"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="hero-section text-center">
    <h1 class="hero-title">
      <i class="fas fa-utensils me-2"></i>
      Product Management
    </h1>
    <p class="hero-subtitle">Where every menu item is a masterpiece. Manage your culinary creations with artistic precision.</p>
    
    <?php
    $statsSql = "SELECT COUNT(*) as total_products FROM LUMIEREMENU";
    $statsResult = sqlsrv_query($conn, $statsSql);
    $stats = sqlsrv_fetch_array($statsResult, SQLSRV_FETCH_ASSOC);
    ?>
    
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
      <div class="stat-card">
        <div class="stat-value" id="totalProducts"><?php echo $stats['total_products'] ?? 0; ?></div>
        <div class="stat-label">Total Products</div>
      </div>
    </div>
  </div>

  <div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="productSearch" class="search-input" placeholder="Search products...">
      </div>
      <div class="d-flex gap-2">
        <a href="add_product.php" class="btn btn-add-product">
          <i class="fas fa-plus-circle me-2"></i>Add New Product
        </a>
        <a href="admin_dashboard.php" class="btn btn-back">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="products-table-container">
    <?php
    $sql = "SELECT * FROM LUMIEREMENU ORDER BY PRODUCTNAME";
    $stmt = sqlsrv_query($conn, $sql);
    $hasProducts = sqlsrv_has_rows($stmt);
    
    if (!$hasProducts) {
      echo '<div class="empty-state">
              <i class="fas fa-utensils"></i>
              <h3 class="text-warning mb-3">No Products Found</h3>
              <p>Your menu is empty. Start by adding your first masterpiece!</p>
              <a href="add_product.php" class="btn btn-add-product mt-3">
                <i class="fas fa-plus-circle me-2"></i>Add Your First Product
              </a>
            </div>';
    } else {
      echo '<div class="table-responsive">
              <table class="table table-hover" id="productsTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th width="180">Actions</th>
                  </tr>
                </thead>
                <tbody>';
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $categoryClass = 'category-other';
        switch(strtolower($row['CATEGORY'])) {
          case 'coffee': $categoryClass = 'category-coffee'; break;
          case 'pastry': $categoryClass = 'category-pastry'; break;
          case 'dessert': $categoryClass = 'category-dessert'; break;
          case 'milktea': $categoryClass = 'category-milktea'; break;
        }
        echo '<tr>
                <td data-label="ID"><strong>' . $row['PRODUCTID'] . '</strong></td>
                <td data-label="Product">
                  <strong>' . htmlspecialchars($row['PRODUCTNAME']) . '</strong>
                </td>
                <td data-label="Category">
                  <span class="category-badge ' . $categoryClass . '">
                    ' . htmlspecialchars($row['CATEGORY']) . '
                  </span>
                </td>
                <td data-label="Description">
                  <small>
                    ' . htmlspecialchars($row['DESCRIPTION'] ?? 'No description') . '
                  </small>
                </td>
                <td data-label="Price">
                  <span class="price-tag">₱' . number_format($row['PRICE'], 2) . '</span>
                </td>
                <td data-label="Image">
                  <img src="' . htmlspecialchars($row['IMAGEPATH'] ?: 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') . '" 
                       class="product-image" 
                       alt="' . htmlspecialchars($row['PRODUCTNAME']) . '">
                </td>
                <td data-label="Actions">
                  <div class="action-buttons">
                    <a href="edit_product.php?id=' . $row['PRODUCTID'] . '" class="btn btn-edit">
                      <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <a href="delete_product.php?id=' . $row['PRODUCTID'] . '" 
                       class="btn btn-delete" 
                       onclick="return confirmDelete(\'' . htmlspecialchars(addslashes($row['PRODUCTNAME'])) . '\')">
                      <i class="fas fa-trash me-1"></i>Delete
                    </a>
                  </div>
                </td>
              </tr>';
      }
      echo '</tbody>
            </table>
          </div>';
    }
    ?>
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
    
    const searchInput = document.getElementById('productSearch');
    const productsTable = document.getElementById('productsTable');
    if (searchInput && productsTable) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = productsTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
      });
    }
    
    window.confirmDelete = function(productName) {
      return confirm(`Are you sure you want to delete "${productName}"?\n\nThis action cannot be undone and will remove the product from your menu permanently.`);
    };
    
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
      row.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(5px)';
      });
      row.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
      });
    });
    
    const productImages = document.querySelectorAll('.product-image');
    productImages.forEach(img => {
      img.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1) rotate(1deg)';
      });
      
      img.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1) rotate(0deg)';
      });
    });
  });
</script>
</body>
</html>