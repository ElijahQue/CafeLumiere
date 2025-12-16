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

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $fullname = trim($_POST['fullname']);

    if ($username && $password && $role) {
$checkSql = "SELECT USERNAME FROM USERS WHERE USERNAME = '$username'";
$checkStmt = sqlsrv_query($conn, $checkSql);

if ($checkStmt === false) {
    $message = "Database error while checking username.";
    $messageType = 'error';
} else {
    if (sqlsrv_has_rows($checkStmt)) {
        $message = "Error: Username '$username' already exists. Please choose a different username.";
        $messageType = 'error';
    } else {
        $insertSql = "INSERT INTO USERS (USERNAME, PASSWORDHASH, ROLE, FULLNAME) VALUES ('$username', '$password', '$role', '$fullname')";
        $result = sqlsrv_query($conn, $insertSql);

        if ($result) {
            $message = "Account created successfully! Welcome to Café Lumière!";
            $messageType = 'success';
            $_POST = array();
        } else {
            $errorInfo = sqlsrv_errors();
            $errorMessage = "Error creating account.";
            if ($errorInfo && isset($errorInfo[0]['message'])) {
                $errorMessage .= " Details: " . $errorInfo[0]['message'];
            }
            $message = $errorMessage;
            $messageType = 'error';
        }
    }
}

        if ($checkStmt) {
            sqlsrv_free_stmt($checkStmt);
        }

    } else {
        $message = "Please fill in all required fields.";
        $messageType = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register • Café Lumière</title>
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
        
        .register-container {
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
            max-width: 500px;
            width: 100%;
            margin: 2rem auto;
        }
        
        .register-container::before {
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
        
        .register-header {
            background: linear-gradient(90deg, 
                        rgba(244, 197, 66, 0.2) 0%, 
                        rgba(74, 143, 231, 0.2) 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .register-header h1 {
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
        
        .register-header p {
            color: rgba(242, 228, 183, 0.8);
            font-size: 1rem;
            margin: 0;
        }
        
        .register-body {
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
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(244, 197, 66, 0.3);
            border-radius: 12px;
            color: var(--cafe-cream);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(242, 228, 183, 0.5);
        }
        
        .btn-register {
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
        
        .btn-register:hover {
            background: linear-gradient(135deg, #ffd700, var(--vangogh-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
        }
        
        .message-alert {
            background: linear-gradient(135deg, 
                        rgba(25, 135, 84, 0.2) 0%, 
                        transparent);
            border: 1px solid rgba(25, 135, 84, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #d1e7dd;
            font-weight: 600;
        }
        
        .message-alert.error {
            background: linear-gradient(135deg, 
                        rgba(220, 53, 69, 0.2) 0%, 
                        transparent);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #f8d7da;
        }
        
        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(244, 197, 66, 0.2);
        }
        
        .login-link a {
            color: var(--vangogh-yellow);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .login-link a:hover {
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
        
        .role-select {
            background: rgba(244, 197, 66, 0.1);
            border-radius: 12px;
            padding: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .role-select .form-check {
            display: inline-block;
            margin-right: 1rem;
        }
        
        .role-select .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--vangogh-yellow);
        }
        
        .role-select .form-check-input:checked {
            background-color: var(--vangogh-yellow);
            border-color: var(--vangogh-yellow);
        }
        
        .role-select .form-check-label {
            color: var(--cafe-cream);
            margin-left: 0.5rem;
        }
        
        @media (max-width: 576px) {
            .register-container {
                margin: 1rem;
            }
            
            .register-header {
                padding: 2rem 1.5rem;
            }
            
            .register-body {
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
                <div class="register-container">
                    <div class="register-header">
                        <h1>
                            <i class="fas fa-palette me-2"></i>
                            Café Lumière
                        </h1>
                        <p>Happy to have you!</p>
                    </div>
                    <div class="register-body">
                        <h2 class="text-center mb-4" style="color: var(--vangogh-yellow);">
                            <i class="fas fa-user-plus me-2"></i>
                            User Registration
                        </h2>
                        <?php if ($message): ?>
                            <div class="message-alert <?php echo $messageType; ?>">
                                <?php if ($messageType === 'success'): ?>
                                    <i class="fas fa-check-circle me-2"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" id="registerForm">
                            <div class="mb-4">
                                <label for="fullname" class="form-label">
                                    <i class="fas fa-user me-2"></i>
                                    Full Name
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-signature"></i>
                                    </span>
                                    <input type="text" 
                                           name="fullname" 
                                           id="fullname" 
                                           class="form-control" 
                                           required 
                                           placeholder="Enter your full name"
                                           autocomplete="name">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user-tag me-2"></i>
                                    Username
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           name="username" 
                                           id="username" 
                                           class="form-control" 
                                           required 
                                           placeholder="Choose a username"
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
                                           placeholder="Create a secure password"
                                           autocomplete="new-password">
                                    <button type="button" class="toggle-password" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text" style="color: rgba(242, 228, 183, 0.6);">
                                    At least 6 characters recommended
                                </small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-user-tie me-2"></i>
                                    Role
                                </label>
                                <select name="role" class="form-control" required>
                                    <option value="" style="background: white; color: black;">Select your role</option>
                                    <option value="staff" style="background: white; color: black;">Staff - Café Team Member</option>
                                    <option value="admin" style="background: white; color: black;">Admin - Café Administrator</option>
                                </select>
                                <small class="form-text" style="color: rgba(242, 228, 183, 0.6);">
                                    Staff: Access to order management<br>
                                    Admin: Full system access
                                </small>
                            </div>
                            <button type="submit" class="btn-register">
                                <i class="fas fa-paint-brush me-2"></i>
                                Create Account
                            </button>
                        </form>
                        <div class="login-link">
                            <p>Already have an account?<br>
                                <a href="login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Login Here!
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <p>
                        <small>
                            <i class="fas fa-palette me-1"></i>
                            By registering, you join our community of art and flavor enthusiasts.
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
            
            const registerForm = document.getElementById('registerForm');
            registerForm.addEventListener('submit', function(e) {
                const fullname = document.getElementById('fullname').value.trim();
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();
                const role = document.querySelector('select[name="role"]').value;
                if (fullname.length === 0 || username.length === 0 || password.length === 0 || role.length === 0) {
                    e.preventDefault();
                    showAlert('Please fill in all required fields.', 'error');
                    return;
                }
                
                if (username.length < 3) {
                    e.preventDefault();
                    showAlert('Username must be at least 3 characters long.', 'error');
                    return;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    showAlert('Password should be at least 6 characters for security.', 'warning');
                }
                
                const submitBtn = registerForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            });
            
            const formInputs = document.querySelectorAll('.form-control, .form-select');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
            
            document.getElementById('fullname').focus();
            function showAlert(message, type) {
                const existingAlert = document.querySelector('.message-alert:not(.php)');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                const alertDiv = document.createElement('div');
                alertDiv.className = `message-alert ${type}`;
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                `;
                
                const header = document.querySelector('.register-body h2');
                header.parentNode.insertBefore(alertDiv, header.nextSibling);
                
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        });
    </script>
</body>
</html>