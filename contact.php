<?php
// contact.php
require_once 'config.php'; // Adaptez le chemin

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et validation des données
    $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    if (empty($message)) $errors[] = "Le message est requis";
    
    if (empty($errors)) {
        try {
            // Enregistrement en BDD
            $stmt = $db->prepare("INSERT INTO contacts (nom, email, message) VALUES (:nom, :email, :message)");
            $stmt->execute([
                ':nom' => $nom,
                ':email' => $email,
                ':message' => $message
            ]);
            
            // Redirection vers index.php
            header("Location: index.html?success=1");
            exit(); // Important pour arrêter l'exécution du script
        } catch (PDOException $e) {
            // En cas d'erreur, redirection avec message d'erreur
            header("Location: index.html?error=db_error");
            exit();
        }
    } else {
        // Redirection avec les erreurs de validation
        $errorString = implode('|', $errors);
        header("Location: index.html?error=" . urlencode($errorString));
        exit();
    }
} else {
    header("Location: index.html");
    exit();
}
?>