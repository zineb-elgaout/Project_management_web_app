<?php
session_start();
require_once '../hello/config.php';

// Vérifier l'authentification
if (!isset($_SESSION['nom']) || $_SESSION['role'] !== 'enseignant') {
    die("Accès non autorisé");
}

// Vérifier les données reçues
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_projet'], $_POST['nouveau_statut'])) {
    try {
        // Mettre à jour le statut
        $stmt = $db->prepare("UPDATE projet SET statut = :statut WHERE id_projet = :id_projet");
        $stmt->execute([
            ':statut' => $_POST['nouveau_statut'],
            ':id_projet' => $_POST['id_projet']
        ]);

        // Ajouter à l'historique
        $stmt = $db->prepare("INSERT INTO historique_projet (id_projet, id_utilisateur, action) 
                             VALUES (:id_projet, :id_utilisateur, :action)");
        $stmt->execute([
            ':id_projet' => $_POST['id_projet'],
            ':id_utilisateur' => $_SESSION['id_utilisateur'],
            ':action' => "Changement de statut: " . $_POST['nouveau_statut']
        ]);

        // Rediriger avec un message de succès
        $_SESSION['success_message'] = "Statut mis à jour avec succès!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
    }
}

// Redirection vers la page précédente
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>