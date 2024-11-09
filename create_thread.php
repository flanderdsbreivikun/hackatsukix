<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $userId = $_SESSION['user_id'];
    
    if (createThread($userId, $title, $message)) {
        header('Location: index.php');
        exit;
    } else {
        $error = "Erreur lors de la création du thread. Veuillez réessayer.";
    }
}

header('Location: index.php');
exit;
