<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';
require_once 'functions.php';

$threadId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$thread = getThread($threadId);

if (!$thread) {
    header('Location: index.php');
    exit;
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$repliesPerPage = 10;
$offset = ($page - 1) * $repliesPerPage;

$replies = getReplies($threadId, $offset, $repliesPerPage);
$totalReplies = getTotalReplies($threadId);
$totalPages = ceil($totalReplies / $repliesPerPage);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $message = $_POST['message'];
    $userId = $_SESSION['user_id'];
    
    if (createReply($threadId, $userId, $message)) {
        header("Location: thread.php?id=$threadId");
        exit;
    } else {
        $error = "Erreur lors de l'ajout de la réponse. Veuillez réessayer.";
    }
}

// Récupération de la photo de profil de l'utilisateur connecté
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
    <title><?php echo htmlspecialchars($thread['title']); ?> - Forum de discussion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1 class="thread-title"><?php echo htmlspecialchars($thread['title']); ?></h1>
        <nav>
            <a href="index.php">Accueil</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Photo de profil" class="profile-picture" width="60" height="60" style="border-radius:10%;">
                <span>Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="register.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <main>
        <article class="thread">
            <p class="author">
                <!-- Affiche la photo de profil de l'auteur du thread -->
                <?php if (!empty($thread['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($thread['profile_picture']); ?>" alt="Photo de profil" class="profile-picture" width="70" height="70" style="border-radius:10%;">
                <?php else: ?>
                    <img src="default-profile.png" alt="Photo de profil par défaut" class="profile-picture" width="70" height="70" style="border-radius:10%;">
                <?php endif; ?>
                Par <?php echo htmlspecialchars($thread['username']); ?>
            </p>
            <p class="date"><?php echo date('d/m/Y H:i', strtotime($thread['created_at'])); ?></p>
            <div class="thread-message"><?php echo nl2br(htmlspecialchars($thread['message'])); ?></div>
        </article>
        
        <h2>Réponses</h2>
        <?php if (empty($replies)): ?>
            <p>Aucune réponse pour le moment.</p>
        <?php else: ?>
            <ul class="replies">
                <?php foreach ($replies as $reply): ?>
                    <li>
                        <p class="author">
                            <!-- Affiche la photo de profil de l'utilisateur qui a répondu -->
                            <?php if (!empty($reply['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($reply['profile_picture']); ?>" alt="Photo de profil" class="profile-picture" width="70" height="70" style="border-radius:10%;">
                            <?php else: ?>
                                <img src="default-profile.png" alt="Photo de profil par défaut" class="profile-picture" width="70" height="70" style="border-radius:10%;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($reply['username']); ?>
                        </p>
                        <p class="date"><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></p>
                        <div class="reply-message"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $threadId; ?>&page=<?php echo $page - 1; ?>">Précédent</a>
                    <?php endif; ?>
                    
                    <span>Page <?php echo $page; ?> sur <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $threadId; ?>&page=<?php echo $page + 1; ?>">Suivant</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <h3>Ajouter une réponse</h3>
            <form action="thread.php?id=<?php echo $threadId; ?>" method="post">
                <textarea name="message" placeholder="Votre réponse" required></textarea>
                <button type="submit">Envoyer</button>
            </form>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; 2023 Forum de discussion</p>
    </footer>
</body>
</html>
