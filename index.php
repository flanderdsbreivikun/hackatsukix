<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';
require_once 'functions.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$threadsPerPage = 10;
$offset = ($page - 1) * $threadsPerPage;

$threads = getThreads($offset, $threadsPerPage);
$totalThreads = getTotalThreads();
$totalPages = ceil($totalThreads / $threadsPerPage);

// Récupération de la photo de profil si l'utilisateur est connecté
$profilePicture = "default-profile.png"; // Image par défaut
if (isset($_SESSION['user_id'])) {
    $db = getDB();
    $stmt = $db->prepare('SELECT profile_picture FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    if ($user && !empty($user['profile_picture'])) {
        $profilePicture = $user['profile_picture'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum de discussion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>HACKATSUKI</h1>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Affiche la photo de profil de l'utilisateur connecté -->
                <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Photo de profil" class="profile-picture" width="60" height="60" style="border-radius:10%;">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="market.php">Marketplace</a> <!-- Lien vers le marketplace -->
                <a href="conversations.php">Conversations</a> <!-- Lien vers les conversations -->
                <a href="logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="market.php">Marketplace</a> <!-- Lien vers le marketplace -->
                <a href="login.php">Connexion</a>
                <a href="register.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <main>
        <h2>Threads récents</h2>
        <ul class="thread-list">
            <?php foreach ($threads as $thread): ?>
                <li>
                    <a href="thread.php?id=<?php echo $thread['id']; ?>" class="thread-title">
                        <?php echo htmlspecialchars($thread['title']); ?>
                    </a>
                    <span class="author">
                        <?php if (!empty($thread['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($thread['profile_picture']); ?>" alt="Photo de profil" class="profile-picture" width="60" height="60" style="border-radius:10%;">
                        <?php else: ?>
                            <img src="default-profile.png" alt="Photo de profil par défaut" class="profile-picture" width="60" height="60" style="border-radius:10%;">
                        <?php endif; ?>
                        par <?php echo htmlspecialchars($thread['username']); ?>
                    </span>
                    <span class="date"><?php echo date('d/m/Y H:i', strtotime($thread['last_reply_date'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">Précédent</a>
                <?php endif; ?>
                
                <span>Page <?php echo $page; ?> sur <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Suivant</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <h3>Créer un nouveau thread</h3>
            <form action="create_thread.php" method="post">
                <input type="text" name="title" placeholder="Titre du thread (max 60 caractères)" required maxlength="80">
                <textarea name="message" placeholder="Message" required></textarea>
                <button type="submit">Créer le thread</button>
            </form>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; 2023 Forum de discussion</p>
    </footer>
</body>
</html>

