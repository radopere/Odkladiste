<?php
require_once __DIR__ . '/config.php';

// Ověří, zda je uživatel přihlášen
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Ověří, zda je uživatel admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Pokus o přihlášení
function login($password) {
    global $USERS;

    if (isset($USERS[$password])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['role'] = $USERS[$password];
        $_SESSION['last_activity'] = time();
        return true;
    }

    return false;
}

// Odhlášení uživatele
function logout() {
    session_unset();
    session_destroy();
}

// Ověření timeoutu
function check_session_timeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        logout();
        header("Location: index.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}
