<?php
session_start();
include("db.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullname, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['fullname'] = $fullname;
                header("Location: http://localhost/new%20shoes%20house/index.php");
                exit;
            } else {
                $message = "❌ Invalid password. Please try again.";
            }
        } else {
            $message = "❌ No account found with that email.";
        }
    } else {
        $message = "❌ All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%);
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        .container {
            position: relative;
            width: 100%;
            max-width: 450px;
            z-index: 10;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        .form-title {
            text-align: center;
            color: white;
            margin-bottom: 5px;
            font-size: 24px;
        }
        .form-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
            font-weight: 400;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            font-size: 14px;
        }
        .input-group input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            font-size: 16px;
            color: white;
            transition: all 0.3s;
        }
        .input-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }
        .input-group i {
            position: absolute;
            left: 350px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }
        .input-group .toggle-password {
            left: auto;
            right: 20px;
            cursor: pointer;
        }
        .error-msg {
            color: #ff6b6b;
            font-size: 12px;
            margin-top: 5px;
            min-height: 20px;
        }
        .forgot-password {
            display: block;
            text-align: right;
            margin-bottom: 25px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.25);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .signup-text {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
        }
        .signup-text a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }
        /* Bubble Animation */
        .bubbles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        .bubble {
            position: absolute;
            bottom: -100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
            animation: float 15s infinite;
            opacity: 0.5;
        }
        .bubble:nth-child(1) {
            width: 40px;
            height: 40px;
            left: 10%;
            animation-duration: 15s;
        }
        .bubble:nth-child(2) {
            width: 60px;
            height: 60px;
            left: 20%;
            animation-duration: 18s;
            animation-delay: 2s;
        }
        .bubble:nth-child(3) {
            width: 30px;
            height: 30px;
            left: 35%;
            animation-duration: 12s;
            animation-delay: 4s;
        }
        .bubble:nth-child(4) {
            width: 50px;
            height: 50px;
            left: 50%;
            animation-duration: 16s;
            animation-delay: 1s;
        }
        .bubble:nth-child(5) {
            width: 70px;
            height: 70px;
            left: 65%;
            animation-duration: 14s;
            animation-delay: 3s;
        }
        .bubble:nth-child(6) {
            width: 45px;
            height: 45px;
            left: 80%;
            animation-duration: 17s;
        }
        .bubble:nth-child(7) {
            width: 55px;
            height: 55px;
            left: 90%;
            animation-duration: 13s;
            animation-delay: 5s;
        }
        @keyframes float {
            0% {
                transform: translateY(0);
                opacity: 0.5;
            }
            50% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(-100vh) translateX(20px);
                opacity: 0;
            }
        }
        
    </style>
</head>
<body>
    <div class="bubbles">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>
    <div class="container">
        <div class="glass-card">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Sign in to continue your journey</p>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email">
                    <i class="fas fa-envelope"></i>
                    <div class="error-msg" id="email-error"></div>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password">
                    <i class="fas fa-eye toggle-password" data-toggle="password"></i>
                    <div class="error-msg" id="password-error"></div>
                </div>
                
                <a href="#" class="forgot-password">Forgot password?</a>
                
                <button type="submit" class="btn" id="login-btn">Login</button>
            </form>
            
            <p class="signup-text">Not a member? <a href="user_register.php" id="switch-to-signup">Signup now</a></p>
            
            <?php if (!empty($message)): ?>
                <p class="error-msg"><?php echo $message; ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.querySelector('.toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye', isPassword);
            this.classList.toggle('fa-eye-slash', !isPassword);
        });

        document.getElementById('login-btn').addEventListener('click', function (e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            document.getElementById('email-error').textContent = '';
            document.getElementById('password-error').textContent = '';

            let isValid = true;

            if (!email) {
                document.getElementById('email-error').textContent = 'Email is required';
                isValid = false;
            } else if (!email.includes('@')) {
                document.getElementById('email-error').textContent = 'Invalid email format';
                isValid = false;
            }
            if (!password) {
                document.getElementById('password-error').textContent = 'Password is required';
                isValid = false;
            }

            if (isValid) {
                document.querySelector('form').submit();
            }
        });
    </script>
    

  <style>
    /* === Base Reset === */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    html, body {
      height: 100%;
      overflow: hidden;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%);
      position: relative;
    }

    /* === Background Animation Container === */
    .background-container {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      overflow: hidden;
    }

    .background-bubbles .bubble {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      animation: float 15s infinite linear;
      bottom: -100px;
    }

    @keyframes float {
      0% {
        transform: translateY(0) translateX(0) rotate(0deg);
        opacity: 0.8;
      }
      50% {
        transform: translateX(calc(20px * var(--x-drift))) translateY(-50vh) rotate(180deg);
        opacity: 0.9;
      }
      100% {
        transform: translateX(calc(40px * var(--x-drift))) translateY(-100vh) rotate(360deg);
        opacity: 0.5;
      }
    }

    .bg-control {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 100;
    }

    .bg-toggle {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: white;
      font-size: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .bg-options {
      position: absolute;
      top: 60px;
      right: 0;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(15px);
      border-radius: 15px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 10px;
      display: none;
      flex-direction: column;
      gap: 10px;
      width: 200px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .bg-options.active {
      display: flex;
    }

    .bg-option {
      padding: 10px;
      background: rgba(255, 255, 255, 0.1);
      color: white;
      cursor: pointer;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
    }

    .bg-option.active {
      background: rgba(255, 255, 255, 0.3);
    }

    .container {
      position: relative;
      width: 100%;
      max-width: 450px;
      z-index: 10;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 40px 30px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }

    .form-title {
      text-align: center;
      color: white;
      font-size: 24px;
      margin-bottom: 10px;
    }

    .form-subtitle {
      text-align: center;
      color: rgba(255, 255, 255, 0.8);
      margin-bottom: 30px;
    }

    .input-group {
      margin-bottom: 20px;
      position: relative;
    }

    .input-group label {
      color: white;
      display: block;
      margin-bottom: 8px;
    }

    .input-group input {
      width: 100%;
      padding: 14px 20px;
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 10px;
      color: white;
    }

    .btn {
      width: 100%;
      padding: 14px;
      background: rgba(255, 255, 255, 0.2);
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: bold;
      cursor: pointer;
    }

    .signup-text {
      color: white;
      text-align: center;
      margin-top: 15px;
    }

    .error-msg {
      color: #ff4d4d;
      font-size: 13px;
      margin-top: 5px;
    }
  </style>
</head>


  <!-- 🌠 JS for Animated Backgrounds -->
  <script>
    // Create initial bubbles
    createBubbles();

    function createBubbles() {
      const container = document.getElementById('background-container');
      container.innerHTML = '';
      container.className = 'background-container background-bubbles';

      const bubbleCount = 20;
      for (let i = 0; i < bubbleCount; i++) {
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        const size = Math.floor(Math.random() * 80) + 20;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        bubble.style.left = `${Math.random() * 100}%`;
        const duration = Math.random() * 15 + 10;
        bubble.style.animationDuration = `${duration}s`;
        const drift = (Math.random() - 0.5) * 2;
        bubble.style.setProperty('--x-drift', drift);
        container.appendChild(bubble);
      }
    }

    const bgToggle = document.getElementById('bg-toggle');
    const bgOptions = document.getElementById('bg-options');
    const bgOptionEls = document.querySelectorAll('.bg-option');

    bgToggle.addEventListener('click', () => {
      bgOptions.classList.toggle('active');
    });

    bgOptionEls.forEach(option => {
      option.addEventListener('click', function () {
        bgOptionEls.forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        const bg = this.getAttribute('data-bg');
        switch (bg) {
          case 'bubbles': createBubbles(); break;
          default: createBubbles(); break; // You can implement waves, particles, grid later
        }
      });
    });
  </script>

</body>
</html>