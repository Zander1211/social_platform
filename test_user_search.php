<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Search Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .error { color: red; }
        .success { color: green; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .search-form { margin: 10px 0; }
        .search-form input, .search-form button { padding: 8px; margin: 5px; }
    </style>
</head>
<body>
    <h1>User Search Diagnostic Tool</h1>
    
    <?php
    require_once 'social-platform-school/config/database.php';
    require_once 'social-platform-school/src/Controller/UserController.php';
    
    $userController = new UserController($pdo);
    $message = '';
    $messageType = '';
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_test_user':
                    $testUserData = [
                        'name' => 'Prince Ondoy',
                        'email' => 'prince.ondoy@example.com',
                        'password' => 'password123',
                        'role' => 'student',
                        'contact_number' => '09123456789'
                    ];
                    
                    $result = $userController->register($testUserData);
                    if ($result['status'] === 'success') {
                        $message = 'Test user "Prince Ondoy" created successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to create test user: ' . ($result['message'] ?? 'Unknown error');
                        $messageType = 'error';
                    }
                    break;
                    
                case 'create_custom_user':
                    $customUserData = [
                        'name' => $_POST['name'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'password' => $_POST['password'] ?? 'password123',
                        'role' => $_POST['role'] ?? 'student',
                        'contact_number' => $_POST['contact_number'] ?? ''
                    ];
                    
                    if ($customUserData['name'] && $customUserData['email']) {
                        $result = $userController->register($customUserData);
                        if ($result['status'] === 'success') {
                            $message = 'User "' . $customUserData['name'] . '" created successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to create user: ' . ($result['message'] ?? 'Unknown error');
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Please provide both name and email';
                        $messageType = 'error';
                    }
                    break;
            }
        }
    }
    
    // Get search query
    $searchQuery = $_GET['search'] ?? '';
    ?>
    
    <?php if ($message): ?>
        <div class="section <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Database Connection Test -->
    <div class="section">
        <h2>1. Database Connection Test</h2>
        <?php
        try {
            $stmt = $pdo->query("SELECT 1");
            echo '<span class="success">✅ Database connection: OK</span>';
        } catch (Exception $e) {
            echo '<span class="error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
        ?>
    </div>
    
    <!-- Users Table Check -->
    <div class="section">
        <h2>2. Users Table Structure</h2>
        <?php
        try {
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<table>';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } catch (Exception $e) {
            echo '<span class="error">❌ Error checking table structure: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
        ?>
    </div>
    
    <!-- All Users Display -->
    <div class="section">
        <h2>3. All Users in Database</h2>
        <?php
        try {
            $allUsers = $userController->getAllUsers(100);
            echo '<p class="info">Total users found: ' . count($allUsers) . '</p>';
            
            if (count($allUsers) > 0) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>';
                foreach ($allUsers as $user) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="error">No users found in database!</p>';
            }
        } catch (Exception $e) {
            echo '<span class="error">❌ Error getting users: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
        ?>
    </div>
    
    <!-- Search Test -->
    <div class="section">
        <h2>4. Search Test</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Enter search query" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search Users</button>
        </form>
        
        <?php if ($searchQuery): ?>
            <h3>Search Results for: "<?php echo htmlspecialchars($searchQuery); ?>"</h3>
            <?php
            try {
                $searchResults = $userController->searchUsers($searchQuery, 50);
                echo '<p class="info">Search results found: ' . count($searchResults) . '</p>';
                
                if (count($searchResults) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>';
                    foreach ($searchResults as $user) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p class="error">No users found matching your search.</p>';
                }
                
                // Test different variations
                echo '<h4>Testing search variations:</h4>';
                $variations = [
                    strtolower($searchQuery),
                    strtoupper($searchQuery),
                    ucfirst(strtolower($searchQuery))
                ];
                
                foreach ($variations as $variation) {
                    if ($variation !== $searchQuery) {
                        $varResults = $userController->searchUsers($variation, 50);
                        echo '<p>Search for "' . htmlspecialchars($variation) . '": ' . count($varResults) . ' results</p>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<span class="error">❌ Search error: ' . htmlspecialchars($e->getMessage()) . '</span>';
            }
            ?>
        <?php endif; ?>
    </div>
    
    <!-- Create Test User -->
    <div class="section">
        <h2>5. Create Test User</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_test_user">
            <button type="submit">Create Test User "Prince Ondoy"</button>
        </form>
        
        <h3>Or Create Custom User:</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_custom_user">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="contact_number" placeholder="Contact Number">
            <select name="role">
                <option value="student">Student</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Create User</button>
        </form>
    </div>
    
    <!-- Direct SQL Test -->
    <div class="section">
        <h2>6. Direct SQL Search Test</h2>
        <?php if ($searchQuery): ?>
            <?php
            try {
                $sql = "SELECT id, name, email, role FROM users WHERE name LIKE :q OR email LIKE :q ORDER BY name ASC LIMIT 20";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':q' => "%$searchQuery%"]);
                $directResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p class="info">Direct SQL search results: ' . count($directResults) . '</p>';
                echo '<p><strong>SQL Query:</strong> ' . htmlspecialchars($sql) . '</p>';
                echo '<p><strong>Parameter:</strong> %' . htmlspecialchars($searchQuery) . '%</p>';
                
                if (count($directResults) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>';
                    foreach ($directResults as $user) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<span class="error">❌ Direct SQL error: ' . htmlspecialchars($e->getMessage()) . '</span>';
            }
            ?>
        <?php else: ?>
            <p>Enter a search query above to test direct SQL search.</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>7. Quick Search Tests</h2>
        <p>Try these quick searches:</p>
        <a href="?search=Prince">Search for "Prince"</a> | 
        <a href="?search=prince">Search for "prince"</a> | 
        <a href="?search=Ondoy">Search for "Ondoy"</a> | 
        <a href="?search=@">Search for "@"</a> | 
        <a href="?search=.com">Search for ".com"</a>
    </div>
</body>
</html>