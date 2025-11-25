<?php
session_start();

if (isset($_SESSION['upload_dir']) && is_dir($_SESSION['upload_dir'])) {
    $dir = $_SESSION['upload_dir'];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir($dir);
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to index.php
header('Location: index.php');
exit;
