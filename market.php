<?php
// market.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db.php';

// Initialisation de la connexion à la base de données
$db = getDB();

// Pagination settings
$articles_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $articles_per_page;

$total_articles = $db->querySingle("SELECT COUNT(*) FROM articles");
$total_pages = ceil($total_articles / $articles_per_page);

// Fetch latest articles
$stmt = $db->prepare("SELECT articles.*, users.username FROM articles
                      JOIN users ON articles.user_id = users.id
                      ORDER BY created_at DESC LIMIT :start, :limit");
$stmt->bindValue(':start', $start, SQLITE3_INTEGER);
$stmt->bindValue(':limit', $articles_per_page, SQLITE3_INTEGER);
$result = $stmt->execute();
$articles = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $articles[] = $row;
}

// Fetch categories
$categoryResult = $db->query("SELECT * FROM categories");
$categories = [];
while ($row = $categoryResult->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Marketplace</title>
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
        .marketplace-item {
            display: flex;
            align-items: center;
            border: 1px solid #ff1a1a;
            padding: 1rem;
            margin: 1rem 0;
            background-color: #1a1a1a;
        }
        .marketplace-item img {
            margin-right: 1rem;
            max-width: 100px;
            border: 2px solid #ff1a1a;
            box-shadow: 0 0 10px rgba(255, 26, 26, 0.5);
        }
        .marketplace-item h3 {
            color: #ff1a1a;
            margin: 0;
        }
        .marketplace-item p {
            margin: 0.5rem 0;
        }
        .neon {
            color: #ff1a1a;
            text-shadow: 0 0 5px #ff1a1a, 0 0 10px #ff1a1a;
        }
        button, .btn-contact {
            background-color: #ff1a1a;
            color: #0a0a0a;
            padding: 0.5rem 1.5rem;
            border: none;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
        }
        button:hover, .btn-contact:hover {
            background-color: #990000;
        }
        .pagination {
            margin-top: 2rem;
            text-align: center;
        }
        .pagination a, 
        .pagination span {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            background: #0a0a0a;
            border: 1px solid #ff1a1a;
            color: #fff;
            text-decoration: none;
        }
        .pagination .current-page {
            background: #ff1a1a;
            color: #0a0a0a;
        }
        ul.category-list {
            list-style-type: none;
            padding: 0;
        }
        ul.category-list li {
            margin: 0.5rem 0;
        }
        ul.category-list a {
            color: #ff1a1a;
            text-decoration: none;
        }
        ul.category-list a:hover {
            color: #ffffff;
        }
        form {
            background-color: #1a1a1a;
            padding: 1rem;
            border: 1px solid #ff1a1a;
            margin-top: 2rem;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.5rem;
            background: #0a0a0a;
            border: 1px solid #ff1a1a;
            color: #fff;
            margin-bottom: 1rem;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <header>
        <h1 class="neon">Welcome to the Marketplace</h1>
    </header>

    <main>
        <?php if(isset($_SESSION['user_id'])): ?>
            <p>Logged in as User ID: <?= $_SESSION['user_id']; ?> | <a href="logout.php" class="neon">Logout</a></p>
        <?php else: ?>
            <p><a href="register.php" class="neon">Register</a> | <a href="login.php" class="neon">Login</a></p>
        <?php endif; ?>

        <h2 class="neon">Latest Articles</h2>
        <?php foreach($articles as $article): ?>
            <div class="marketplace-item">
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
        <a href="market.php?page=<?= $page - 1; ?>">Précédent</a>
    <?php endif; ?>
    
    <span>Page <?= $page; ?> of <?= $total_pages; ?></span>
    
    <?php if ($page < $total_pages): ?>
        <a href="market.php?page=<?= $page + 1; ?>">Suivant</a>
    <?php endif; ?>
</div>


        <h2 class="neon">Categories</h2>
        <ul class="category-list">
            <?php foreach($categories as $category): ?>
                <li><a href="category.php?id=<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></a></li>
            <?php endforeach; ?>
        </ul>

        <?php if(isset($_SESSION['user_id'])): ?>
            <h2 class="neon">Add New Article</h2>
            <form method="post" action="add_article.php" enctype="multipart/form-data">
                <label>Title:</label>
                <input type="text" name="title" required><br>
                <label>Quantity:</label>
                <input type="number" name="quantity" required><br>
                <label>Price:</label>
                <input type="number" step="0.01" name="price" required><br>
                <label>Description:</label>
                <textarea name="description" required></textarea><br>
                <label>Image:</label>
                <input type="file" name="image"><br>
                <label>Category:</label>
                <select name="category_id">
                    <?php foreach($categories as $category): ?>
                        <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Or Create New Category:</label>
                <input type="text" name="new_category"><br>
                <button type="submit">Add Article</button>
            </form>
        <?php else: ?>
            <p><a href="login.php" class="neon">Login</a> to add an article.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2023 Akatsuki Marketplace</p>
    </footer>
</body>
</html>

