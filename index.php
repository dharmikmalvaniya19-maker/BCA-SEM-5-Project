<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Temporary md5 for testing

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION['admin'] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "❌ Invalid username or password!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login</title>

  <!-- Font Awesome -->
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

    .background-container {
      position: absolute;
      top: 0;
      left: 0;
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
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      width: 200px;
    }

    .bg-options.active {
      display: flex;
    }

    .bg-option {
      padding: 12px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      color: white;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .bg-option.active {
      background: rgba(255, 255, 255, 0.3);
    }

    .container {
      position: relative;
      width: 100%;
      max-width: 400px;
      margin: auto;
      z-index: 10;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 40px 30px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.8s ease-out;
    }

    .form-title {
      text-align: center;
      color: white;
      margin-bottom: 10px;
      font-size: 24px;
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
      display: block;
      margin-bottom: 8px;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
      font-size: 14px;
    }

    .input-group input {
      width: 100%;
      padding: 14px 20px;
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 10px;
      font-size: 16px;
      color: white;
    }

    .input-group i {
      position: absolute;
      right: 20px;
      top: 42px;
      color: rgba(255, 255, 255, 0.8);
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
      margin-top: 10px;
    }

    .btn:hover {
      background: rgba(255, 255, 255, 0.35);
      transform: translateY(-2px);
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .input-group input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}
.input-group input::-webkit-input-placeholder {
    color: rgba(255, 255, 255, 0.7);
}
.input-group input::-moz-placeholder {
    color: rgba(255, 255, 255, 0.7);
}
.input-group input:-ms-input-placeholder {
    color: rgba(255, 255, 255, 0.7);
}
  </style>
</head>
<body>
  <div class="background-container" id="background-container"></div>

  

  <div class="container">
    <div class="glass-card">
      <h2 class="form-title">Admin Login</h2>
      <p class="form-subtitle">Continue with your account</p>
      <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
      <form method="POST" action="">
        <div class="input-group">
          <label for="username">Username</label>
          <input type="text" name="username" id="username" placeholder="Enter your username" required>
          <i class="fas fa-user"></i>
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" placeholder="Enter your password" required>
          <i class="fas fa-lock"></i>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
    </div>
  </div>

  <script>
    function createBubbles() {
      const container = document.getElementById('background-container');
      container.innerHTML = '';
      container.className = 'background-container background-bubbles';
      for (let i = 0; i < 15; i++) {
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        const size = Math.floor(Math.random() * 100) + 20;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${Math.floor(Math.random() * 15) + 10}s`;
        bubble.style.setProperty('--x-drift', (Math.random() - 0.5) * 2);
        bubble.style.animationDelay = `${Math.random() * 5}s`;
        bubble.style.opacity = Math.random() * 0.3 + 0.4;
        container.appendChild(bubble);
      }
    }

    createBubbles();

    document.getElementById('bg-toggle').addEventListener('click', () => {
      document.getElementById('bg-options').classList.toggle('active');
    });
  </script>
</body>
</html>
