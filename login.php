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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM USERS WHERE USERNAME = '$username' AND PASSWORDHASH = '$password'";
    $result = sqlsrv_query($conn, $sql);

    if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {

        $_SESSION['user'] = [
            'id' => $row['USERID'],
            'username' => $row['USERNAME'],
            'role' => $row['ROLE']
        ];
        header("Location: index.php");
    } else {
        $error = "Invalid username or password.";
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login • Café Lumière</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Raleway:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --starry-night: #0b1e3f;
            --vangogh-yellow: #f4c542;
            --vangogh-blue: #4a8fe7;
            --cafe-cream: #f2e4b7;
            --swirl-orange: #d2691e;
            --olive-green: #6B8E23;
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
            display: flex;
            align-items: center;
            color: var(--cafe-cream);
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(244, 197, 66, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(74, 143, 231, 0.15) 0%, transparent 40%);
            pointer-events: none;
            z-index: -1;
        }
        
        .login-container {
            background: linear-gradient(145deg, 
                        rgba(255, 255, 255, 0.08) 0%, 
                        rgba(255, 255, 255, 0.03) 100%);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            border: 2px solid rgba(244, 197, 66, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4),
                        inset 0 0 100px rgba(244, 197, 66, 0.05);
            overflow: hidden;
            position: relative;
            max-width: 450px;
            width: 100%;
            margin: 2rem auto;
        }
        
        .login-container::before {
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
            animation: swirl 30s linear infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes swirl {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .login-header {
            background: linear-gradient(90deg, 
                        rgba(244, 197, 66, 0.2) 0%, 
                        rgba(74, 143, 231, 0.2) 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .login-header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.5rem;
            background: linear-gradient(45deg, var(--vangogh-yellow), var(--cafe-cream));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: rgba(242, 228, 183, 0.8);
            font-size: 1rem;
            margin: 0;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
            position: relative;
            z-index: 1;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--vangogh-yellow);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(244, 197, 66, 0.3);
            border-radius: 12px;
            color: var(--cafe-cream);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(242, 228, 183, 0.5);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--vangogh-yellow), #e6b800);
            color: var(--starry-night);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
        }
        
        .error-alert {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), transparent);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #f8d7da;
            font-weight: 600;
        }
        
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(244, 197, 66, 0.2);
        }
        
        .register-link a {
            color: var(--vangogh-yellow);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .register-link a:hover {
            color: #ffd700;
            text-decoration: underline;
        }
        
        .back-home {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 10;
        }
        
        .back-home a {
            color: var(--vangogh-yellow);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(244, 197, 66, 0.1);
            border: 1px solid rgba(244, 197, 66, 0.2);
            transition: all 0.3s ease;
        }
        
        .back-home a:hover {
            background: rgba(244, 197, 66, 0.2);
            border-color: var(--vangogh-yellow);
            transform: translateX(-3px);
        }
        
        .star-decoration {
            position: absolute;
            width: 3px;
            height: 3px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            animation: star-twinkle 3s infinite;
            pointer-events: none;
        }
        
        @keyframes star-twinkle {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        .input-group-text {
            background: rgba(244, 197, 66, 0.1);
            border: 2px solid rgba(244, 197, 66, 0.3);
            border-right: none;
            color: var(--vangogh-yellow);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--vangogh-yellow);
            cursor: pointer;
            z-index: 10;
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .back-home {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div id="stars-container"></div>
    
    <div class="back-home">
        <a href="index.php">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Café
        </a>
    </div>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-container">
                    <div class="login-header">
                        <h1>
                            <i class="fas fa-palette me-2"></i>
                            Café Lumière
                        </h1>
                        <p>Where art meets flavor</p>
                    </div>
                    
                    <div class="login-body">
                        <h2 class="text-center mb-4" style="color: var(--vangogh-yellow);">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Welcome Back
                        </h2>
                        
                        <?php if(isset($error)): ?>
                            <div class="error-alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" id="loginForm">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>
                                    Username
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-palette"></i>
                                    </span>
                                    <input type="text" 
                                           name="username" 
                                           id="username" 
                                           class="form-control" 
                                           required 
                                           placeholder="Enter your username"
                                           autocomplete="username">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-key me-2"></i>
                                    Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           name="password" 
                                           id="password" 
                                           class="form-control" 
                                           required 
                                           placeholder="Enter your password"
                                           autocomplete="current-password">
                                    <button type="button" class="toggle-password" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login to Your Artistic Journey
                            </button>
                        </form>
                        
                        <div class="register-link">
                            <p>Don't have an account yet? 
                                <a href="register.php">
                                    <i class="fas fa-paint-brush me-1"></i>
                                    Create your masterpiece
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p>
                        <small>
                            <i class="fas fa-palette me-1"></i>
                            Every login is a new brushstroke in your Café Lumière experience.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const starsContainer = document.getElementById('stars-container');
            const starCount = 80;
            
            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.classList.add('star-decoration');
                
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                
                const size = Math.random() * 2 + 1;
                star.style.width = `${size}px`;
                star.style.height = `${size}px`;
                
                star.style.animationDelay = `${Math.random() * 3}s`;
                star.style.animationDuration = `${Math.random() * 2 + 2}s`;
                
                starsContainer.appendChild(star);
            }
            
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            const loginForm = document.getElementById('loginForm');
            loginForm.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();
                
                if (username.length === 0 || password.length === 0) {
                    e.preventDefault();
                    alert('Please fill in all fields.');
                    return;
                }
                
                const submitBtn = loginForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            });
            
            const formInputs = document.querySelectorAll('.form-control');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
            
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>