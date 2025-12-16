<?php
session_start();

$serverName = "Elijah\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "LUMIERE",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Account Management • Café Lumière</title>
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
      --admin-purple: #8a2be2;
      --success-green: #28a745;
      --danger-red: #dc3545;
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
    
    .management-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .page-header {
      background: linear-gradient(145deg, 
                  rgba(255, 255, 255, 0.08) 0%, 
                  rgba(255, 255, 255, 0.03) 100%);
      backdrop-filter: blur(15px);
      border-radius: 25px;
      border: 2px solid var(--admin-purple);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4),
                  inset 0 0 80px rgba(138, 43, 226, 0.1);
      padding: 2.5rem;
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
          rgba(138, 43, 226, 0.05) 2px,
          rgba(138, 43, 226, 0.05) 4px
      );
      animation: swirl 40s linear infinite;
      pointer-events: none;
    }
    
    @keyframes swirl {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .page-title {
      font-family: 'Playfair Display', serif;
      font-weight: 900;
      font-size: 2.8rem;
      color: var(--vangogh-yellow);
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
      margin-bottom: 0.5rem;
    }
    
    .page-subtitle {
      color: rgba(242, 228, 183, 0.8);
      font-size: 1.1rem;
      margin-bottom: 1.5rem;
    }

    .accounts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 1.5rem;
    }

    .account-card {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
      border: 1px solid rgba(138, 43, 226, 0.2);
      overflow: hidden;
      transition: all 0.3s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .account-card:hover {
      transform: translateY(-5px);
      border-color: var(--vangogh-yellow);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .account-header {
      background: linear-gradient(90deg, 
                  rgba(138, 43, 226, 0.2) 0%, 
                  rgba(11, 30, 63, 0.4) 100%);
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .account-username {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--vangogh-yellow);
      font-size: 1.2rem;
    }

    .account-body {
      padding: 1.5rem;
      flex-grow: 1;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.75rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .info-row:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .info-label {
      font-size: 0.85rem;
      color: rgba(242, 228, 183, 0.6);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .info-value {
      color: white;
      font-weight: 500;
    }
    
    .role-badge {
      display: inline-block;
      padding: 0.35rem 1rem;
      border-radius: 25px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: rgba(0, 0, 0, 0.3);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      color: #ffffff !important;
      border: 1px solid;
      min-width: 90px;
      text-align: center;
    }
    
    .role-admin { 
      background: linear-gradient(135deg, var(--admin-purple), #5e2a84) !important;
      border-color: var(--admin-purple) !important;
    }
    
    .role-staff { 
      background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800) !important;
      border-color: var(--vangogh-yellow) !important;
      color: var(--starry-night) !important;
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
    
    .btn-delete {
      background: linear-gradient(135deg, var(--danger-red), #c82333);
      color: white !important;
      border: none;
      border-radius: 50px;
      padding: 0.5rem 1.25rem;
      font-size: 0.9rem;
      font-weight: 600;
      transition: all 0.3s ease;
      width: 100%;
      margin-top: 1rem;
      box-shadow: 0 3px 8px rgba(220, 53, 69, 0.3);
    }
    
    .btn-delete:hover {
      background: linear-gradient(135deg, #dc3545, var(--danger-red));
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
      color: white !important;
    }

    .search-container {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      border: 1px solid rgba(244, 197, 66, 0.2);
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
    
    .search-wrapper {
      position: relative;
      max-width: 500px;
    }

    .search-icon {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--vangogh-yellow);
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: rgba(255, 255, 255, 0.5);
      border: 2px dashed rgba(255, 255, 255, 0.1);
      border-radius: 15px;
    }
    
    .empty-state i {
      font-size: 4rem;
      color: var(--vangogh-yellow);
      margin-bottom: 1.5rem;
      opacity: 0.5;
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
      .accounts-grid {
          grid-template-columns: 1fr;
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
        <a href="index.php" class="btn btn-action">
          <i class="fas fa-coffee me-1"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="management-container">
  <div class="page-header">
    <h1 class="page-title">
      <i class="fas fa-users-cog me-2"></i>
      Account Management
    </h1>
    <p class="page-subtitle">Manage staff accounts, assign roles, and maintain your café's artistic team.</p>
    
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
      <div class="d-flex gap-2">
        <a href="register.php" class="btn btn-action">
          <i class="fas fa-user-plus me-2"></i>Add New User
        </a>
        <a href="admin_dashboard.php" class="btn btn-outline-custom">
          <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="search-container">
    <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="userSearch" class="search-input" placeholder="Search by username or name...">
    </div>
  </div>

  <div class="accounts-grid" id="accountsGrid">
    <?php
    $sql = "SELECT * FROM USERS ORDER BY USERNAME";
    $stmt = sqlsrv_query($conn, $sql);
    $hasUsers = sqlsrv_has_rows($stmt);
    
    if (!$hasUsers) {
      echo '<div class="empty-state w-100" style="grid-column: 1/-1;">
              <i class="fas fa-users"></i>
              <h3 class="text-warning mb-3">No Users Found</h3>
              <p>Start by adding your first team member!</p>
              <a href="register.php" class="btn btn-action mt-3">
                <i class="fas fa-user-plus me-2"></i>Add First User
              </a>
            </div>';
    } else {
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $roleClass = 'role-user';
        if (strtolower($row['ROLE']) === 'admin') {
            $roleClass = 'role-admin';
        } elseif (strtolower($row['ROLE']) === 'staff') {
            $roleClass = 'role-staff';
        }
        
    ?>
        <div class="account-card">
            <div class="account-header">
                <span class="account-username">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo htmlspecialchars($row['USERNAME']); ?>
                </span>
                <span class="text-white-50 small">ID: <?php echo $row['USERID']; ?></span>
            </div>
            <div class="account-body">
                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value name-value"><?php echo htmlspecialchars($row['FULLNAME'] ?? 'Not set'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="role-badge <?php echo $roleClass; ?>">
                        <i class="fas <?php echo strtolower($row['ROLE']) === 'admin' ? 'fa-crown' : 'fa-user-tie'; ?> me-1"></i>
                        <?php echo htmlspecialchars($row['ROLE']); ?>
                    </span>
                </div>
                
                <a href="delete_account.php?id=<?php echo $row['USERID']; ?>" 
                   class="btn btn-delete" 
                   onclick="return confirmDelete('<?php echo htmlspecialchars(addslashes($row['USERNAME'])); ?>', '<?php echo htmlspecialchars($row['ROLE']); ?>')">
                    <i class="fas fa-trash me-2"></i>Remove Account
                </a>
            </div>
        </div>
    <?php
      }
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
                        &copy; <?php echo date('Y'); ?> Café Lumière. Account Management
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

    const cards = document.querySelectorAll('.account-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    const searchInput = document.getElementById('userSearch');
    const accountsGrid = document.getElementById('accountsGrid');
    
    if (searchInput && accountsGrid) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = accountsGrid.querySelectorAll('.account-card');
        
        cards.forEach(card => {
          const username = card.querySelector('.account-username').textContent.toLowerCase();
          const fullname = card.querySelector('.name-value').textContent.toLowerCase();
          
          if (username.includes(searchTerm) || fullname.includes(searchTerm)) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
      });
    }

    window.confirmDelete = function(username, role) {
      if (role.toLowerCase() === 'admin') {
        return confirm(`WARNING: Deleting administrator "${username}"!\n\nThis user has full system access. Are you sure you want to proceed?\n\nThis action cannot be undone.`);
      }
      return confirm(`Are you sure you want to delete user "${username}"?\n\nThis action will permanently remove the user account.`);
    };
  });
</script>
</body>
</html>