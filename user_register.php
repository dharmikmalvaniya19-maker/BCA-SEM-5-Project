<?php
session_start();
include("db.php"); // Make sure this includes your database connection

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate required fields
    if (!empty($fullname) && !empty($email) && !empty($password)) {
        // Check if the email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Email already registered. Please login or use another email.";
        } else {
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user
            $insert = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $fullname, $email, $hashed_password);

            if ($insert->execute()) {
                $message = "Registration successful. You can now <a href='user_login.php'>login</a>.";
            } else {
                $message = "Error: Unable to register.";
            }

            $insert->close();
        }

        $stmt->close();
    } else {
        $message = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
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
            padding: 14px 20px;
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
        .input-group .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
        }
        .error-msg {
            color: #ff6b6b;
            font-size: 12px;
            margin-top: 5px;
            min-height: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <h2 class="form-title">Create Account</h2>
            <p class="form-subtitle">Join us today for exclusive features</p>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name">
                    <div class="error-msg" id="fullname-error"></div>
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email">
                    <div class="error-msg" id="email-error"></div>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password">
                    <i class="fas fa-eye toggle-password" data-toggle="password"></i>
                    <div class="error-msg" id="password-error"></div>
                </div>

                <div class="input-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm your password">
                    <i class="fas fa-eye toggle-password" data-toggle="confirm-password"></i>
                    <div class="error-msg" id="confirm-password-error"></div>
                </div>

                <button type="submit" class="btn" id="signup-btn">Create Account</button>
            </form>
            
            <p class="signup-text">Already have an account? <a href="user_login.php" id="switch-to-login">Login now</a></p>
            
            <?php if (!empty($message)): ?>
                <p class="error-msg"><?php echo $message; ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function () {
                const targetId = this.getAttribute('data-toggle');
                const input = document.getElementById(targetId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.classList.toggle('fa-eye', isPassword);
                this.classList.toggle('fa-eye-slash', !isPassword);
            });
        });

        document.getElementById('signup-btn').addEventListener('click', function (e) {
            e.preventDefault();
            
            const fullname = document.getElementById('fullname').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirm-password').value.trim();
            
            document.getElementById('fullname-error').textContent = '';
            document.getElementById('email-error').textContent = '';
            document.getElementById('password-error').textContent = '';
            document.getElementById('confirm-password-error').textContent = '';

            let isValid = true;

            if (!fullname) {
                document.getElementById('fullname-error').textContent = 'Full name is required';
                isValid = false;
            }
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
            } else if (password.length < 6) {
                document.getElementById('password-error').textContent = 'Password must be at least 6 characters';
                isValid = false;
            }
            if (!confirmPassword) {
                document.getElementById('confirm-password-error').textContent = 'Please confirm your password';
                isValid = false;
            } else if (password !== confirmPassword) {
                document.getElementById('confirm-password-error').textContent = 'Passwords do not match';
                isValid = false;
            }

            if (isValid) {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>