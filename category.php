<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// category.php
session_start();
require 'db.php';

// Initialisation de la connexion à la base de données
$db = getDB();

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch category name
$stmt = $db->prepare("SELECT name FROM categories WHERE id = :id");
$stmt->bindValue(':id', $category_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$category = $result->fetchArray(SQLITE3_ASSOC);

if (!$category) {
    echo "Category not found.";
    exit();
}

// Pagination settings
$articles_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $articles_per_page;

// Fetch articles in the category
$stmt = $db->prepare("SELECT articles.*, users.username FROM articles
                      JOIN users ON articles.user_id = users.id
                      WHERE category_id = :category_id
                      ORDER BY created_at DESC LIMIT :start, :limit");
$stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
$stmt->bindValue(':start', $start, SQLITE3_INTEGER);
$stmt->bindValue(':limit', $articles_per_page, SQLITE3_INTEGER);
$result = $stmt->execute();

$articles = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $articles[] = $row;
}

// Get total number of articles in the category for pagination
$stmt = $db->prepare("SELECT COUNT(*) as count FROM articles WHERE category_id = :category_id");
$stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
$total_articles = $stmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_articles / $articles_per_page);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Category: <?= htmlspecialchars($category['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #0a0a0a;
            color: #ffffff;
            font-family: 'Chivo Mono', monospace;
            line-height: 1.6;
        }
        header, footer {
            background-color: #141414;
            text-align: center;
            padding: 1.5rem;
            border-bottom: 2px solid #ff1a1a;
            box-shadow: 0 2px 10px rgba(255, 26, 26, 0.2);
        }
        header h1 {
            color: #ff1a1a;
            text-shadow: 0 0 10px rgba(255, 26, 26, 0.8);
            font-size: 2rem;
        }
        .category-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #1a1a1a;
            border: 1px solid #ff1a1a;
        }
        .category-container h2 {
            color: #ff1a1a;
            text-shadow: 0 0 5px #ff1a1a;
        }
        .article-item {
            display: flex;
            align-items: center;
            border: 1px solid #ff1a1a;
            padding: 1rem;
            margin: 1rem 0;
            background-color: #141414;
        }
        .article-item img {
            margin-right: 1rem;
            max-width: 100px;
            border: 2px solid #ff1a1a;
            box-shadow: 0 0 10px rgba(255, 26, 26, 0.5);
        }
        .article-item h3 {
            color: #ff1a1a;
            margin: 0;
        }
        .article-item p {
            margin: 0.5rem 0;
        }
        .neon {
            color: #ff1a1a;
            text-shadow: 0 0 5px #ff1a1a, 0 0 10px #ff1a1a;
        }
        .pagination {
            text-align: center;
            margin-top: 2rem;
        }
        .pagination a {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            background: #0a0a0a;
            border: 1px solid #ff1a1a;
            color: #fff;
            text-decoration: none;
        }
        .pagination a:hover {
            background: #ff1a1a;
            color: #0a0a0a;
        }
    </style>
</head>
<body>
    <header>
        <h1 class="neon">Category: <?= htmlspecialchars($category['name']); ?></h1>
    </header>

    <main class="category-container">
        <?php if(isset($_SESSION['user_id'])): ?>
            <p>Logged in as User ID: <?= $_SESSION['user_id']; ?> | <a href="logout.php" class="neon">Logout</a></p>
        <?php else: ?>
            <p><a href="register.php" class="neon">Register</a> | <a href="login.php" class="neon">Login</a></p>
        <?php endif; ?>

        <h2 class="neon">Articles in this Category</h2>
        <?php if ($articles): ?>
            <?php foreach($articles as $article): ?>
                <div class="article-item">
                    <img src="<?= htmlspecialchars($article['image']); ?>" alt="<?= htmlspecialchars($article['title']); ?>">
                    <div>
                        <h3><a href="article.php?id=<?= $article['id']; ?>" class="neon"><?= htmlspecialchars($article['title']); ?></a></h3>
                        <p>Price: $<?= htmlspecialchars($article['price']); ?></p>
                        <p>Seller: <?= htmlspecialchars($article['username']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="category.php?id=<?= $category_id; ?>&page=<?= $page - 1; ?>">Précédent</a>
                <?php endif; ?>
                <span>Page <?= $page; ?> of <?= $total_pages; ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="category.php?id=<?= $category_id; ?>&page=<?= $page + 1; ?>">Suivant</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>No articles found in this category.</p>
        <?php endif; ?>

        <p><a href="index.php" class="neon">Back to Home</a></p>
    </main>

    <footer>
        <p>&copy; 2023 Akatsuki Marketplace</p>
    </footer>
</body>
</html>
