<?php
session_start(); // Make sure session is started to potentially use session data later
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            padding-top: 50px;
            background-color: #f4f7f6;
            line-height: 1.7;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1 {
            font-family: 'Pacifico', cursive;
            color: #28a745; /* Green color for success */
            margin-bottom: 20px;
            font-size: 2.5em;
        }
        h1 i {
            margin-right: 10px;
        }
        p {
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .total-display {
            font-size: 1.5em;
            font-weight: bold;
            color: #673ab7; /* Your primary color */
            margin-top: 20px;
            margin-bottom: 30px;
            padding: 10px;
            border: 1px dashed #673ab7;
            display: inline-block;
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #673ab7;
            border-color: #673ab7;
            padding: 12px 25px;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: background-color 0.3s ease;
            text-decoration: none; /* Ensure it looks like a button */
            display: inline-block;
        }
        .btn-primary:hover {
            background-color: #512da8;
            border-color: #512da8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-check-circle"></i> Order Confirmed!</h1>
        <p>Thank you for your purchase.</p>
        <p>Your order has been placed successfully.</p>

        <?php
        $order_id = $_GET['order_id'] ?? 'N/A';
        $order_total = $_GET['order_total'] ?? 0;
        ?>

        <?php if ($order_id !== 'N/A'): ?>
            <p>Your Order ID: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
        <?php endif; ?>

        <?php if ($order_total > 0): ?>
            <p class="total-display">Total Amount: ₹<?php echo number_format($order_total, 2); ?></p>
        <?php endif; ?>

        <a href="http://localhost/new%20shoes%20house/index.php" class="btn btn-primary">Continue Shopping</a>
    </div>
</body>
</html>