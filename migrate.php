<?php
// migrate.php — Run once to apply new tables and create the initial super-admin.
// DELETE THIS FILE after running.
require_once 'includes/db_connect.php';

$sql = file_get_contents(__DIR__ . '/database/schema.sql');

// Run schema (CREATE IF NOT EXISTS is safe to re-run)
try {
    $pdo->exec($sql);
    echo "Schema applied.<br>";
} catch (PDOException $e) {
    echo "Schema error: " . $e->getMessage() . "<br>";
}

// Create default super admin (username: admin, password: Admin@1234)
try {
    $existing = $pdo->query("SELECT id FROM users WHERE username='admin'")->fetch();
    if (!$existing) {
        $hash = password_hash('Admin@1234', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $hash, 'System Administrator', 'super_admin']);
        echo "Admin user created. Username: admin / Password: Admin@1234<br>";
        echo "<strong>Change the password immediately after first login.</strong><br>";
    } else {
        echo "Admin user already exists.<br>";
    }
} catch (PDOException $e) {
    echo "User creation error: " . $e->getMessage() . "<br>";
}

// Link Dr. Laila and Dr. Sara to specialist user accounts
try {
    $specialists = $pdo->query("SELECT id, name FROM specialists")->fetchAll();
    foreach ($specialists as $s) {
        $uname = strtolower(str_replace([' ', '.'], '_', $s['name']));
        $existing = $pdo->query("SELECT id FROM users WHERE username='$uname'")->fetch();
        if (!$existing) {
            $hash = password_hash('Change@Me1', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, specialist_id) VALUES (?,?,?,?,?)");
            $stmt->execute([$uname, $hash, $s['name'], 'specialist', $s['id']]);
            echo "Specialist user created: $uname / Change@Me1<br>";
        }
    }
} catch (PDOException $e) {
    echo "Specialist user error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Done. Delete this file now.</strong>";
?>
