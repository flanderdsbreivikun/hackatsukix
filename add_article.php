<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// add_article.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialiser la connexion à la base de données
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $description = htmlspecialchars(trim($_POST['description']));
    $user_id = $_SESSION['user_id'];

    // Gestion de la catégorie
    if (!empty($_POST['new_category'])) {
        $new_category = htmlspecialchars(trim($_POST['new_category']));
        $stmt = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->bindValue(':name', $new_category, SQLITE3_TEXT);
        try {
            $stmt->execute();
            $category_id = $db->lastInsertRowID(); // Méthode pour récupérer l'ID inséré
        } catch (Exception $e) {
            // Si la catégorie existe déjà
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = :name");
            $stmt->bindValue(':name', $new_category, SQLITE3_TEXT);
            $result = $stmt->execute();
            $category = $result->fetchArray(SQLITE3_ASSOC);
            $category_id = $category['id'];
        }
    } else {
        $category_id = (int)$_POST['category_id'];
    }

    // Gestion de l'upload de l'image
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image = 'uploads/' . uniqid() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }

    $stmt = $db->prepare("INSERT INTO articles (title, description, price, quantity, image, category_id, user_id) VALUES (:title, :description, :price, :quantity, :image, :category_id, :user_id)");
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
    $stmt->bindValue(':quantity', $quantity, SQLITE3_INTEGER);
    $stmt->bindValue(':image', $image, SQLITE3_TEXT);
    $stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();

    header('Location: market.php');
    exit();
}
?>
