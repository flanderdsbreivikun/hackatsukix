<?php
function getThreads($offset, $limit) {
    $db = getDB();
    $stmt = $db->prepare('SELECT t.id, t.title, t.created_at, t.last_reply_date, u.username, u.profile_picture 
                          FROM threads t 
                          JOIN users u ON t.user_id = u.id 
                          ORDER BY t.last_reply_date DESC 
                          LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $threads = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $threads[] = $row;
    }
    return $threads;
}

function getTotalThreads() {
    $db = getDB();
    $result = $db->query('SELECT COUNT(*) as count FROM threads');
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row['count'];
}

function getThread($id) {
    $db = getDB();
    $stmt = $db->prepare('SELECT t.*, u.username, u.profile_picture 
                          FROM threads t 
                          JOIN users u ON t.user_id = u.id 
                          WHERE t.id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function getReplies($threadId, $offset, $limit) {
    $db = getDB();
    $stmt = $db->prepare('SELECT r.*, u.username, u.profile_picture 
                          FROM replies r 
                          JOIN users u ON r.user_id = u.id 
                          WHERE r.thread_id = :thread_id 
                          ORDER BY r.created_at ASC 
                          LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':thread_id', $threadId, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $replies = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $replies[] = $row;
    }
    return $replies;
}

function getTotalReplies($threadId) {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM replies WHERE thread_id = :thread_id');
    $stmt->bindValue(':thread_id', $threadId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row['count'];
}

function createThread($userId, $title, $message) {
    $db = getDB();
    $title = substr($title, 0, 60);
    $stmt = $db->prepare('INSERT INTO threads (user_id, title, message) VALUES (:user_id, :title, :message)');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    return $stmt->execute();
}

function createReply($threadId, $userId, $message) {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO replies (thread_id, user_id, message) VALUES (:thread_id, :user_id, :message)');
    $stmt->bindValue(':thread_id', $threadId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    if ($result) {
        $stmt = $db->prepare('UPDATE threads SET last_reply_date = CURRENT_TIMESTAMP WHERE id = :thread_id');
        $stmt->bindValue(':thread_id', $threadId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    return $result;
}

function registerUser($username, $password, $profilePicturePath = null) {
    $db = getDB();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password, profile_picture) VALUES (:username, :password, :profile_picture)');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
    $stmt->bindValue(':profile_picture', $profilePicturePath, SQLITE3_TEXT);
    return $stmt->execute();
}

function loginUser($username, $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}
