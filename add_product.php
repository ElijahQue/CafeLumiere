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

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);
    $destination = "Uploads/"; 
    
    if (empty($name) || empty($category) || $price <= 0) {
        $message = "Please fill in all required fields with valid values.";
        $messageType = "error";
    } else {
        $filename = $_FILES["product_image"]["name"];
        $targetfilepath = $destination . time() . "_" . $filename;

        $allowtypes = ['jpg','jpeg','png','gif','webp'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($filetype, $allowtypes)) {
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }
                
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetfilepath)) {
                $sql = "INSERT INTO LUMIEREMENU (PRODUCTNAME, CATEGORY, PRICE, DESCRIPTION, IMAGEPATH) VALUES ('$name', '$category', '$price', '$description', '$targetfilepath')";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt) {
                    $message = "Product added successfully!";
                    $messageType = "success";
                    $_POST = array();
                } else {
                    $message = "Error adding product: " . print_r(sqlsrv_errors(), true);
                    $messageType = "error";
                }
            } else {
                $message = "Image upload failed. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF, WebP";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Product • Café Lumière</title>
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
    
    .edit-container {
        max-width: 900px;
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
        font-size: 2.5rem;
        color: var(--vangogh-yellow);
        text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        margin-bottom: 0.5rem;
    }
    
    .page-subtitle {
        color: rgba(242, 228, 183, 0.8);
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }
    
    .product-info-card {
        background: linear-gradient(145deg, 
                    rgba(255, 255, 255, 0.08) 0%, 
                    rgba(255, 255, 255, 0.03) 100%);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        border: 1px solid rgba(244, 197, 66, 0.2);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .product-preview {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .product-image-main {
        width: 100%;
        max-width: 400px;
        height: 250px;
        object-fit: cover;
        border-radius: 15px;
        border: 3px solid var(--vangogh-yellow);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
    }
    
    .product-image-main:hover {
        transform: scale(1.02);
        box-shadow: 0 15px 35px rgba(244, 197, 66, 0.2);
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
        color: rgba(255, 255, 255, 0.7);
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: var(--vangogh-yellow);
        box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
        color: white;
        outline: none;
    }

    .form-control:focus::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .form-control[type="textarea"]::placeholder,
    textarea.form-control::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }
    
    .form-select {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(244, 197, 66, 0.3);
        border-radius: 12px;
        color: white;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-select:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: var(--vangogh-yellow);
        box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
        color: white;
        outline: none;
    }
    
    .form-select option {
        background: var(--starry-night);
        color: white;
    }
    
    .category-badge {
        display: inline-block;
        padding: 0.35rem 1rem;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: rgba(0, 0, 0, 0.3);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        color: #ffffff !important;
        border: 1px solid;
        text-align: center;
    }
    
    .category-coffee { 
        background: linear-gradient(135deg, #8B4513, #A0522D) !important;
        border-color: #8B4513 !important;
    }
    
    .category-pastry { 
        background: linear-gradient(135deg, var(--vangogh-yellow), #ff9a00) !important;
        border-color: var(--vangogh-yellow) !important;
        color: #000000 !important;
    }
    
    .category-dessert { 
        background: linear-gradient(135deg, #9370db, #8a2be2) !important;
        border-color: #8a2be2 !important;
    }
    
    .category-milktea { 
        background: linear-gradient(135deg, #6B8E23, #9acd32) !important;
        border-color: #6B8E23 !important;
    }
    
    .category-other { 
        background: linear-gradient(135deg, var(--vangogh-blue), #36d1dc) !important;
        border-color: var(--vangogh-blue) !important;
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
    
    .alert {
        border-radius: 15px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
        backdrop-filter: blur(10px);
    }
    
    .alert-success {
        background: rgba(40, 167, 69, 0.2);
        border-color: #28a745;
        color: #d4edda;
    }
    
    .alert-danger {
        background: rgba(220, 53, 69, 0.2);
        border-color: #dc3545;
        color: #f8d7da;
    }
    
    .alert-warning {
        background: rgba(255, 193, 7, 0.2);
        border-color: #ffc107;
        color: #fff3cd;
    }
    
    .image-upload-box {
        background: rgba(255, 255, 255, 0.05);
        border: 2px dashed rgba(244, 197, 66, 0.3);
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }
    
    .image-upload-box:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--vangogh-yellow);
    }
    
    .image-upload-box i {
        font-size: 3rem;
        color: var(--vangogh-yellow);
        margin-bottom: 1rem;
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
            font-size: 2rem;
        }
        
        .edit-container {
            padding: 1rem;
        }
        
        .page-header,
        .product-info-card {
            padding: 1.5rem;
        }
        
        .product-image-main {
            max-width: 100%;
            height: 200px;
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
                <a href="index.php" class="btn btn-add-to-cart">
                    <i class="fas fa-coffee me-1"></i>Back to Café
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="hero-section text-center">
        <h1 class="hero-title">
            <i class="fas fa-plus-circle me-2"></i>
            Add New Product
        </h1>
        <p class="hero-subtitle">Create a new masterpiece for your café menu. Every product is a brushstroke of flavor.</p>
    </div>

    <div class="page-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="category-badge category-other">
                    <i class="fas fa-plus me-1"></i>New Product
                </span>
            </div>
            
            <div class="d-flex gap-2">
                <a href="productlist.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
                <a href="admin_dashboard.php" class="btn btn-add-product">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <?php if (isset($message) && $message): ?>
    <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : ($messageType === 'error' ? 'alert-danger' : 'alert-warning'); ?>">
        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : ($messageType === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'); ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <div class="product-info-card">
        <div class="row">
            <div class="col-md-5">
                <div class="product-preview">
                    <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" 
                         class="product-image-main" 
                         alt="New Product Preview"
                         id="imagePreview">
                    
                    <div class="mt-3">
                        <small style="color: rgba(242, 228, 183, 0.6) !important;">
                            <i class="fas fa-info-circle me-1"></i>
                            New product will be assigned an ID automatically
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <form method="POST" enctype="multipart/form-data" id="addForm">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                               required
                               placeholder="Enter product name">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select category</option>
                                <option value="Coffee" <?php echo ($_POST['category'] ?? '') == 'Coffee' ? 'selected' : ''; ?>>Coffee</option>
                                <option value="Pastry" <?php echo ($_POST['category'] ?? '') == 'Pastry' ? 'selected' : ''; ?>>Pastry</option>
                                <option value="Dessert" <?php echo ($_POST['category'] ?? '') == 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
                                <option value="Milktea" <?php echo ($_POST['category'] ?? '') == 'Milktea' ? 'selected' : ''; ?>>Milktea</option>
                                <option value="Other" <?php echo ($_POST['category'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₱) *</label>
                            <input type="number" step="0.01" min="0.01" name="price" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                   required
                                   placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" 
                                  class="form-control" 
                                  rows="4"
                                  placeholder="Describe your product..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="image-upload-box" onclick="document.getElementById('product_image').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5>Upload Product Image</h5>
                        <p>Click to upload image</p>
                        <small>JPG, PNG, GIF, WebP up to 25MB</small>

                        <input type="file"
                            name="product_image"
                            id="product_image"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            required
                            class="d-none"
                            onchange="previewImage(this)">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="submit" name="save" class="btn btn-add-product">
                            <i class="fas fa-plus-circle me-2"></i>Add Product
                        </button>
                        <div class="d-flex gap-2">
                            <a href="productlist.php" class="btn btn-back">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
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
                            &copy; <?php echo date('Y'); ?> Café Lumière. Add Product
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

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const uploadBox = document.querySelector('.image-upload-box');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.borderColor = '#4CAF50';

                    uploadBox.classList.add('border-success');
                    uploadBox.querySelector('h5').innerText = 'Image Selected';
                    uploadBox.querySelector('p').innerText = 'Click to change image';
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        const form = document.getElementById('addForm');
        const priceInput = document.querySelector('input[name="price"]');
        priceInput.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
        
        form.addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const category = document.querySelector('select[name="category"]').value;
            const price = document.querySelector('input[name="price"]').value;
            const image = document.getElementById('product_image').files.length;
            
            if (!name) {
                e.preventDefault();
                alert('Please enter a product name.');
                return false;
            }
            if (!category) {
                e.preventDefault();
                alert('Please select a category.');
                return false;
            }
            if (!price || parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Please enter a valid price.');
                return false;
            }
            if (!image) {
                e.preventDefault();
                alert('Please upload a product image.');
                return false;
            }
            const saveBtn = document.querySelector('button[name="save"]');
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            saveBtn.disabled = true;
            
            return true;
        });
        
        const formElements = document.querySelectorAll('.form-control, .form-select');
        formElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(10px)';
            
            setTimeout(() => {
                element.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        const imagePreview = document.getElementById('imagePreview');
        imagePreview.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        imagePreview.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
        
        window.previewImage = previewImage;
    });
</script>
</body>
</html>