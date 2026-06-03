<?php
session_start();

// Vérification de la connexion et du rôle
if (!isset($_SESSION['nom']) || !isset($_SESSION['role']) || !isset($_SESSION['prenom'])) {
    header("Location: ../hello/login.php");
    exit();
}

if ($_SESSION['role'] !== 'enseignant') {
    header("Location: ../unauthorized.php");
    exit();
}

if (isset($_GET['file'])) {
    $filePath = urldecode($_GET['file']);
    
    // Vérification que le fichier existe et est dans le bon répertoire
    if (file_exists($filePath) ){
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        
        // En-têtes pour le téléchargement
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Content-Length: $fileSize");
        
        // Lire le fichier et l'envoyer au navigateur
        readfile($filePath);
        exit;
    } else {
        die("Fichier non trouvé.");
    }
} else {
    die("Paramètre de fichier manquant.");
}
?>