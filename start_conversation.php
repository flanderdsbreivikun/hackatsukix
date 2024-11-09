<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$seller_id = (int)$_POST['seller_id'];
$user_id = $_SESSION['user_id'];

if ($user_id === $seller_id) {
    echo "Vous ne pouvez pas démarrer une conversation avec vous-même.";
    exit();
}

$db = getDB();

// Vérifier si la conversation existe déjà
$stmt = $db->prepare("SELECT id FROM conversations 
                      WHERE (user1_id = :user_id AND user2_id = :seller_id) 
                      OR (user1_id = :seller_id AND user2_id = :user_id)");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$stmt->bindValue(':seller_id', $seller_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$conversation = $result->fetchArray(SQLITE3_ASSOC);

if ($conversation) {
    $conversation_id = $conversation['id'];
} else {
    // Créer une nouvelle conversation
    $stmt = $db->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (:user1_id, :user2_id)");
    $stmt->bindValue(':user1_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user2_id', $seller_id, SQLITE3_INTEGER);
    $stmt->execute();
    $conversation_id = $db->lastInsertRowID();
}

// Rediriger vers la page de conversation
header("Location: conversation.php?id=$conversation_id");
exit();
?>
