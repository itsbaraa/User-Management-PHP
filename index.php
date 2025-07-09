<?php
// --- DATABASE CONNECTION ---
$servername = "localhost";
$username = "root";
$password = ""; // Empty password
$dbname = "mydb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . ". Please make sure the database 'mydb' exists.");
}

// --- NEW: CHECK IF TABLE EXISTS AND CREATE IT IF NOT ---
$tableName = 'users';
$checkTable = $conn->query("SHOW TABLES LIKE '$tableName'");
if ($checkTable->num_rows == 0) {
    // Table does not exist, so create it
    $sqlCreateTable = "CREATE TABLE users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        age INT(11) NOT NULL,
        status BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($sqlCreateTable)) {
        die("Error creating table: " . $conn->error);
    }
}


// --- LOGIC TO HANDLE FORM SUBMISSIONS ---

// 1. Handle TOGGLE status request
if (isset($_GET['toggle_id'])) {
    $id_to_toggle = (int)$_GET['toggle_id'];

    // The "NOT status" query flips the boolean value (0 to 1, 1 to 0)
    $stmt = $conn->prepare("UPDATE users SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $id_to_toggle);
    $stmt->execute();

    // Redirect back to the main page to show the change and clean the URL
    header("Location: index.php");
    exit();
}

// 2. Handle ADD new user request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $age = (int)$_POST['age'];
    // A checkbox sends 'on' if checked, but nothing if not. We convert it to 1 or 0.
    $status = isset($_POST['status']) ? 1 : 0;

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (name, age, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $name, $age, $status);
    $stmt->execute();

    // Redirect back to the main page to prevent form resubmission on refresh
    header("Location: index.php");
    exit();
}


// --- FETCH ALL USERS FROM DATABASE TO DISPLAY ---
$users = [];
$sql = "SELECT id, name, age, status FROM users ORDER BY id ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Close the connection at the end of the script
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        form {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        input[type="text"], input[type="number"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        button {
            background-color: #007BFF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .toggle-btn {
            background-color: #28a745;
            text-decoration: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .toggle-btn.inactive {
            background-color: #ffc107;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: #d9534f;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>User Management System</h1>

    <!-- FORM TO ADD A NEW USER -->
    <h2>Add New User</h2>
    <form action="index.php" method="post">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="age">Age:</label>
            <input type="number" id="age" name="age" required>
        </div>
        <div class="form-group">
            <label for="status">
                <input type="checkbox" id="status" name="status" checked> Active
            </label>
        </div>
        <button type="submit" name="add_user">Add User</button>
    </form>

    <!-- TABLE TO DISPLAY ALL USERS -->
    <h2>User List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Age</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['age']); ?></td>
                        <td>
                            <?php if ($user['status']): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?toggle_id=<?php echo $user['id']; ?>" class="toggle-btn <?php echo $user['status'] ? '' : 'inactive'; ?>">
                                <?php echo $user['status'] ? 'Set to Inactive' : 'Set to Active'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No users found in the database. Add one above!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>