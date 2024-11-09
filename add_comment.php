<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// add_comment.php
session_start();
require 'db.php';

// Initialiser la connexion à la base de données
$db = getDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $article_id = (int)$_POST['article_id'];
    $content = htmlspecialchars(trim($_POST['content']));
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO comments (article_id, user_id, content) VALUES (:article_id, :user_id, :content)");
    $stmt->bindValue(':article_id', $article_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    $stmt->execute();

    header("Location: article.php?id=$article_id");
    exit();
}
