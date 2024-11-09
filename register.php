<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $profilePicturePath = null;

    if ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérification et déplacement du fichier de la photo de profil
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = uniqid() . '-' . $_FILES['profile_picture']['name'];
            $destination = 'uploads/' . $fileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $profilePicturePath = $destination;
            } else {
                $error = "Erreur lors de l'upload de la photo de profil.";
            }
        }

        if (!isset($error)) {
            try {
                if (registerUser($username, $password, $profilePicturePath)) {
                    $_SESSION['success'] = "Inscription réussie. Vous pouvez maintenant vous connecter.";
                    header('Location: login.php');
                    exit;
                } else {
                    $error = "Erreur lors de l'inscription. Veuillez réessayer.";
                }
            } catch (Exception $e) {
                $error = "Ce nom d'utilisateur est déjà pris.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Forum de discussion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Inscription</h1>
        <nav>
            <a href="index.php">Accueil</a>
            <a href="login.php">Connexion</a>
        </nav>
    </header>
    
    <main>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form action="register.php" method="post" enctype="multipart/form-data">
            <label for="username">Nom d'utilisateur :</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <label for="profile_picture">Photo de profil :</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            
            <button type="submit">S'inscrire</button>
        </form>
    </main>
    
    <footer>
        <p>&copy; 2023 Forum de discussion</p>
    </footer>
</body>
</html>
