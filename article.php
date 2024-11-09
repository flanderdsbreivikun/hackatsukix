<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// article.php
session_start();
require 'db.php';

// Initialiser la connexion à la base de données
$db = getDB();

$article_id = (int)$_GET['id'];

// Récupérer les détails de l'article
$stmt = $db->prepare("SELECT articles.*, users.username FROM articles
                      JOIN users ON articles.user_id = users.id
                      WHERE articles.id = :id");
$stmt->bindValue(':id', $article_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$article = $result->fetchArray(SQLITE3_ASSOC);

if (!$article) {
    echo "Article not found.";
    exit();
}

// Récupérer les commentaires
$stmt = $db->prepare("SELECT comments.*, users.username FROM comments
                      JOIN users ON comments.user_id = users.id
                      WHERE comments.article_id = :article_id
                      ORDER BY created_at ASC");
$stmt->bindValue(':article_id', $article_id, SQLITE3_INTEGER);
$commentsResult = $stmt->execute();
$comments = [];
while ($row = $commentsResult->fetchArray(SQLITE3_ASSOC)) {
    $comments[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($article['title']); ?></title>
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
        main {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #141414;
            border: 1px solid #ff1a1a;
        }
        .article-image {
            display: block;
            margin: 1rem auto;
            border: 2px solid #ff1a1a;
            box-shadow: 0 0 10px rgba(255, 26, 26, 0.5);
        }
        .article-info p {
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
        .comment {
            background-color: #1a1a1a;
            border: 1px solid #ff1a1a;
            padding: 1rem;
            margin-top: 1rem;
            color: #cccccc;
        }
        .comment strong {
            color: #ff1a1a;
        }
        textarea[name="content"] {
            width: 100%;
            background-color: #0a0a0a;
            color: #ffffff;
            border: 1px solid #ff1a1a;
            padding: 0.5rem;
            margin-top: 1rem;
        }
        .comment small {
            color: #888888;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <header>
        <h1 class="neon"><?= htmlspecialchars($article['title']); ?></h1>
    </header>
    
    <main>
        <img src="<?= htmlspecialchars($article['image']); ?>" alt="<?= htmlspecialchars($article['title']); ?>" class="article-image" width="200">
        
        <div class="article-info">
            <p class="neon">Description:</p>
            <p><?= nl2br(htmlspecialchars($article['description'])); ?></p>
            <p class="neon">Price: $<?= htmlspecialchars($article['price']); ?></p>
            <p class="neon">Quantity: <?= htmlspecialchars($article['quantity']); ?></p>
            <p class="neon">Seller: <?= htmlspecialchars($article['username']); ?></p>
        </div>

        <!-- Bouton pour contacter le vendeur -->
        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $article['user_id']): ?>
            <form method="post" action="start_conversation.php">
                <input type="hidden" name="seller_id" value="<?= $article['user_id']; ?>">
                <button type="submit" class="btn-contact">Contact Seller</button>
            </form>
        <?php endif; ?>

        <h2 class="neon">Comments</h2>
        <?php foreach($comments as $comment): ?>
            <div class="comment">
                <p><strong><?= htmlspecialchars($comment['username']); ?>:</strong> <?= nl2br(htmlspecialchars($comment['content'])); ?></p>
                <p><small><?= $comment['created_at']; ?></small></p>
            </div>
        <?php endforeach; ?>

        <?php if(isset($_SESSION['user_id'])): ?>
            <h3 class="neon">Add a Comment</h3>
            <form method="post" action="add_comment.php">
                <input type="hidden" name="article_id" value="<?= $article['id']; ?>">
                <textarea name="content" required></textarea><br>
                <button type="submit">Post Comment</button>
            </form>
        <?php else: ?>
            <p><a href="login.php" class="neon">Login</a> to post a comment.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2023 Akatsuki Marketplace</p>
    </footer>
</body>
</html>

