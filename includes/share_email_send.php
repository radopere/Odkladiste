<?php
require_once 'auth.php';
require_once 'functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = $_POST['recipient'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $shareLink = $_POST['share_link'] ?? '';
    $fileName = $_POST['file_name'] ?? '';
    
    if (empty($recipient) || empty($subject) || empty($message) || empty($shareLink)) {
        set_notification('Všechna pole musí být vyplněna.', 'danger');
        header("Location: shared_files.php");
        exit;
    }
    
    // Nahrazení zástupného textu skutečným odkazem
    $message = str_replace('[ODKAZ]', $shareLink, $message);
    
    // Přidání hlaviček
    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Reply-To: " . $_SESSION['user'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Odeslání emailu
    if (mail($recipient, $subject, $message, $headers)) {
        set_notification('Email byl úspěšně odeslán.', 'success');
    } else {
        set_notification('Nepodařilo se odeslat email.', 'danger');
    }
    
    header("Location: ../shared_files.php");
    exit;
}

// Pokud někdo přistoupí přímo k této stránce
header("Location: ../shared_files.php");
exit;
?>
