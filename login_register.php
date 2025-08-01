<?php
session_start();
include "connection.php"; // Ensure this file correctly connects to your DB

$message = '';

// -------------------- Handle Registration --------------------
if (isset($_POST['register'])) {
    $fullname = trim($_POST['reg_fullname']);
    $password = trim($_POST['reg_password']);
    $email = trim($_POST['reg_email']);

    if (empty($fullname) || empty($password) || empty($email)) {
        $message = "All fields are required for registration.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) die("MySQL Prepare Error (Registration Check): " . $conn->error);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Email already exists. Please login instead.";
        } else {
            // Register new user
            $stmt = $conn->prepare("INSERT INTO users (fullname, password, email) VALUES (?, ?, ?)");
            if (!$stmt) die("MySQL Prepare Error (Registration Insert): " . $conn->error);
            $stmt->bind_param("sss", $fullname, $hashed_password, $email);

            if ($stmt->execute()) {
                $message = "Registration successful! Please login.";
            } else {
                $message = "Registration failed. Try again later.";
            }
        }
        $stmt->close();
    }
}

// -------------------- Handle Login --------------------
if (isset($_POST['login'])) {
    $email = trim($_POST['login_email']);
    $password = trim($_POST['login_password']);

    if (empty($email) || empty($password)) {
        $message = "Both email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        if (!$stmt) die("MySQL Prepare Error (Login Check): " . $conn->error);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['fullname'];

                // Redirect after login
                $redirect_url = "index.php"; // default redirect
                if (isset($_GET['return']) && !empty($_GET['return'])) {
                    $return_param = htmlspecialchars($_GET['return']);
                    if (strpos($return_param, 'cart.php') !== false) {
                        $redirect_url = "admin/cart.php";
                    } elseif (strpos($return_param, 'women_collection.php') !== false) {
                        $redirect_url = "women_collection.php";
                    }
                }

                header("Location: " . $redirect_url);
                exit;
            } else {
                $message = "Invalid email or password.";
            }
        } else {
            $message = "Invalid email or password.";
        }
        $stmt->close();
    }
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <style>
        /* --- Global Styles & Resets (from your cart.php, adapted) --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #673ab7;
            --secondary-color: #ffc107;
            --accent-color: #f44336;
            --text-dark: #333;
            --text-light: #666;
            --background-light: #fefefe;
            --border-color: #eee;
            --shadow-soft: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.18);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--text-light);
            background-color: var(--background-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--shadow-medium);
            display: flex;
            overflow: hidden;
        }

        .form-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-section.login {
            background-color: #f9f9f9;
        }

        .form-section h2 {
            font-family: 'Pacifico', cursive;
            font-size: 2.5rem;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            color: var(--text-dark);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(103, 58, 183, 0.1);
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .message {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .toggle-form {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .toggle-form a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .toggle-form a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 450px;
            }
            .form-section {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section login" id="loginFormSection">
            <h2>Login</h2>
            <?php if ($message && (isset($_POST['login']) || (isset($_POST['register']) && strpos($message, 'successful') === false))): ?>
                <div class="message <?php echo (strpos($message, 'successful') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form action="login_register.php<?php echo isset($_GET['return']) ? '?return=' . htmlspecialchars($_GET['return']) : ''; ?>" method="POST">
                <div class="form-group">
                    <label for="login_email">Email</label>
                    <input type="email" id="login_email" name="login_email" required>
                </div>
                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="login_password" required>
                </div>
                <button type="submit" name="login" class="btn-submit">Login</button>
            </form>
            <div class="toggle-form">
                Don't have an account? <a href="#" id="showRegister">Register here</a>
            </div>
        </div>

        <div class="form-section register" id="registerFormSection" style="display: none;">
            <h2>Register</h2>
            <?php if ($message && isset($_POST['register']) && strpos($message, 'successful') !== false): ?>
                <div class="message success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form action="login_register.php<?php echo isset($_GET['return']) ? '?return=' . htmlspecialchars($_GET['return']) : ''; ?>" method="POST">
                <div class="form-group">
                    <label for="reg_fullname">Full Name</label>
                    <input type="text" id="reg_fullname" name="reg_fullname" required>
                </div>
                <div class="form-group">
                    <label for="reg_email">Email</label>
                    <input type="email" id="reg_email" name="reg_email" required>
                </div>
                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="reg_password" required>
                </div>
                <button type="submit" name="register" class="btn-submit">Register</button>
            </form>
            <div class="toggle-form">
                Already have an account? <a href="index.php" id="showLogin">Login here</a>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.6.0.js"></script>
    <script>
        $(document).ready(function() {
            $('#showRegister').on('click', function(e) {
                e.preventDefault();
                $('#loginFormSection').hide();
                $('#registerFormSection').show();
            });

            $('#showLogin').on('click', function(e) {
                e.preventDefault();
                $('#registerFormSection').hide();
                $('#loginFormSection').show();
            });
        });
    </script>
</body>
</html>