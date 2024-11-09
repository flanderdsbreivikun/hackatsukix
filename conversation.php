<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// conversation.php
session_start();
require 'db.php';

// Initialiser la connexion à la base de données
$db = getDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conversation_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Vérifier que l'utilisateur fait bien partie de la conversation
$stmt = $db->prepare("SELECT * FROM conversations WHERE id = :conversation_id AND (user1_id = :user_id OR user2_id = :user_id)");
$stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$conversation = $result->fetchArray(SQLITE3_ASSOC);

if (!$conversation) {
    echo "Conversation not found.";
    exit();
}

// Gérer l'envoi de nouveau message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['content'])) {
    $content = htmlspecialchars(trim($_POST['content']));
    $stmt = $db->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (:conversation_id, :sender_id, :content)");
    $stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
    $stmt->bindValue(':sender_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    $stmt->execute();
}

// Pagination configuration
$messages_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $messages_per_page;

// Calculer le nombre total de messages pour la pagination
$stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = :conversation_id");
$stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
$total_messages_result = $stmt->execute();
$total_messages = $total_messages_result->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_messages / $messages_per_page);

// Récupérer les messages pour la page actuelle
$stmt = $db->prepare("SELECT messages.*, users.username FROM messages
                      JOIN users ON messages.sender_id = users.id
                      WHERE conversation_id = :conversation_id
                      ORDER BY created_at DESC LIMIT :start, :limit");
$stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
$stmt->bindValue(':start', $start, SQLITE3_INTEGER);
$stmt->bindValue(':limit', $messages_per_page, SQLITE3_INTEGER);
$messagesResult = $stmt->execute();
$messages = [];
while ($row = $messagesResult->fetchArray(SQLITE3_ASSOC)) {
    $messages[] = $row;
}
$messages = array_reverse($messages); // Inverser pour montrer les plus anciens d'abord
?>
<!DOCTYPE html>
<html>
<head>
    <title>Conversation</title>
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
        .conversation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #1a1a1a;
            border: 1px solid #ff1a1a;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #ff1a1a;
            background-color: #141414;
        }
        .message strong {
            color: #ff1a1a;
        }
        .message p {
            margin: 0.5rem 0;
        }
        .message small {
            color: #888;
        }
        .pagination {
            text-align: center;
            margin-top: 2rem;
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
        form {
            margin-top: 2rem;
            background-color: #1a1a1a;
            padding: 1rem;
            border: 1px solid #ff1a1a;
        }
        textarea {
            width: 100%;
            padding: 0.7rem;
            background: #0a0a0a;
            border: 1px solid #ff1a1a;
            color: #fff;
            font-family: inherit;
            margin-bottom: 1rem;
            resize: vertical;
            min-height: 80px;
        }
        button {
            background-color: #ff1a1a;
            color: #0a0a0a;
            padding: 0.5rem 1.5rem;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #990000;
        }
        </style>
</head>
<body>
    <header>
        <h1>Conversation</h1>
    </header>

    <main class="conversation-container">
        <?php foreach($messages as $message): ?>
            <div class="message">
                <p><strong><?= htmlspecialchars($message['username']); ?>:</strong> <?= nl2br(htmlspecialchars($message['content'])); ?></p>
                <p><small><?= $message['created_at']; ?></small></p>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="conversation.php?id=<?= $conversation_id; ?>&page=<?= $page - 1; ?>">Précédent</a>
            <?php endif; ?>
            <span>Page <?= $page; ?> sur <?= $total_pages; ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="conversation.php?id=<?= $conversation_id; ?>&page=<?= $page + 1; ?>">Suivant</a>
            <?php endif; ?>
        </div>

        <h2 class="neon">Send a Message</h2>
        <form method="post" action="conversation.php?id=<?= $conversation_id; ?>">
            <textarea name="content" required></textarea><br>
            <button type="submit">Send</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2023 Akatsuki Chat</p>
    </footer>
</body>
</html>
