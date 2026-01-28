<?php
require_once 'db_connection.php';

// Fetch all users
$query = "SELECT id, full_name, email, phone, username, registration_date FROM users ORDER BY registration_date Asc";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registered Users</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .container { max-width: 1000px; margin: auto; }
        a { color: #4CAF50; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registered Users</h2>
        <a href="register.php">Back to Registration</a>
        
        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Username</th>
                    <th>Registration Date</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo $row['registration_date']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <p>Total registered users: <?php echo $result->num_rows; ?></p>
        <?php else: ?>
            <p>No users registered yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>