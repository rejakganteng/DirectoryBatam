<?php
// db.php: simple PDO wrapper
$config = include __DIR__ . '/config.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}

function fetchAllCategories($pdo) {
    $stmt = $pdo->query('SELECT * FROM categories ORDER BY parent_id IS NOT NULL, parent_id, name');
    return $stmt->fetchAll();
}

function buildCategoryTree(array $categories) {
    $map = [];
    foreach ($categories as $c) {
        $c['children'] = [];
        $map[$c['id']] = $c;
    }
    $tree = [];
    foreach ($map as $id => $c) {
        if ($c['parent_id'] && isset($map[$c['parent_id']])) {
            $map[$c['parent_id']]['children'][] = &$map[$id];
        } else {
            $tree[] = &$map[$id];
        }
    }
    return $tree;
}

function getCategoriesTree($pdo) {
    $cats = fetchAllCategories($pdo);
    return buildCategoryTree($cats);
}

function fetchAllListings($pdo, $limit = 100) {
    $stmt = $pdo->prepare('SELECT l.*, c.name AS category_name FROM listings l LEFT JOIN categories c ON l.category_id = c.id ORDER BY l.title ASC LIMIT :limit');
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetchListing($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM listings WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

function fetchListingWithCategory($pdo, $id) {
    $stmt = $pdo->prepare('SELECT l.*, c.name AS category_name, c.parent_id AS parent_id, p.name AS parent_category_name, u.name AS owner_name FROM listings l LEFT JOIN categories c ON l.category_id = c.id LEFT JOIN categories p ON c.parent_id = p.id LEFT JOIN users u ON l.owner_id = u.id WHERE l.id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

// user helpers
function getUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    return $stmt->fetch();
}

function createUser($pdo, $name, $email, $passwordHash, $role = 'user', $birth_date = null, $phone = null, $address = null) {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, birth_date, phone, address) VALUES (:name, :email, :pw, :role, :birth_date, :phone, :address)');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'pw' => $passwordHash,
        'role' => $role,
        'birth_date' => !empty($birth_date) ? $birth_date : null,
        'phone' => !empty($phone) ? $phone : null,
        'address' => !empty($address) ? $address : null
    ]);
    return $pdo->lastInsertId();
}

function getUserById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}
