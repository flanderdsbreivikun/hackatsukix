<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// conversations.php
session_start();
require 'db.php';

// Initialiser la connexion à la base de données
$db = getDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les conversations de l'utilisateur
$stmt = $db->prepare("SELECT * FROM conversations WHERE user1_id = :user_id OR user2_id = :user_id");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$conversations = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $conversations[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Conversations</title>
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
        .conversations-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #1a1a1a;
            border: 1px solid #ff1a1a;
        }
        .conversation-list {
            list-style-type: none;
            padding: 0;
            margin: 1rem 0;
        }
        .conversation-list li {
            background-color: #141414;
            margin: 0.5rem 0;
            padding: 1rem;
            border: 1px solid #ff1a1a;
            box-shadow: 0 0 5px rgba(255, 26, 26, 0.2);
        }
        .conversation-list a {
            color: #ff1a1a;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .conversation-list a:hover {
            color: #ffffff;
            text-shadow: 0 0 5px #ff1a1a;
        }
    </style>
</head>
<body>
    <header>
        <h1>Your Conversations</h1>
    </header>

    <main class="conversations-container">
        <ul class="conversation-list">
            <?php foreach($conversations as $conv): ?>
                <?php
                $other_user_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
                $stmt = $db->prepare("SELECT username FROM users WHERE id = :other_user_id");
                $stmt->bindValue(':other_user_id', $other_user_id, SQLITE3_INTEGER);
                $userResult = $stmt->execute();
                $other_user = $userResult->fetchArray(SQLITE3_ASSOC);
                ?>
                <li>
                    <a href="conversation.php?id=<?= $conv['id']; ?>">
                        Conversation with <?= htmlspecialchars($other_user['username']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>

    <footer>
        <p>&copy; 2023 Akatsuki Conversations</p>
    </footer>
</body>
</html>
