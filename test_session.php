<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Session</title>
</head>
<body>
    <h1>Test Session Status</h1>
    
    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
        <h2>Session Variables:</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
        <h2>Login Status:</h2>
        <?php if (isset($_SESSION['status']) && $_SESSION['status'] == 'login') { ?>
            <p style="color: green;"><strong>✓ User sudah LOGIN</strong></p>
            <p>ID User: <?php echo $_SESSION['id_user'] ?? 'N/A'; ?></p>
            <p>Nama: <?php echo $_SESSION['nama'] ?? 'N/A'; ?></p>
            <p>Email: <?php echo $_SESSION['email'] ?? 'N/A'; ?></p>
        <?php } else { ?>
            <p style="color: red;"><strong>✗ User BELUM LOGIN atau Session tidak terbaca</strong></p>
        <?php } ?>
    </div>

    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
        <h2>Navigation:</h2>
        <ul>
            <li><a href="index.php">Back to Home</a></li>
            <li><a href="login.php">Go to Login</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
</body>
</html>
