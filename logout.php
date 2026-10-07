<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_post() && is_logged_in() && csrf_valid($_POST['csrf'] ?? null)) {
    audit(db(), $_SESSION['id'], 'Logged out');
    lh_end_session();
    session_start();
    session_regenerate_id(true);
    flash('success', 'You have been logged out. Your vault is locked.');
}

redirect('login.php');
