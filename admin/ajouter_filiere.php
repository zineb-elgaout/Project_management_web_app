<?php
session_start();
// Connexion à la base de données
require_once __DIR__ . '/../hello/config.php';

// Vérification que les constantes sont bien définies
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    die("Erreur de configuration : les constantes de connexion ne sont pas définies dans config.php");
}

// Affichage des erreurs (pour développement)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification des permissions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$errors = [];
$nom = $description = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($nom)) {
        $errors['nom'] = "Le nom est obligatoire";
    } elseif (strlen($nom) > 100) {
        $errors['nom'] = "Le nom ne doit pas dépasser 100 caractères";
    }

    if (strlen($description) > 500) {
        $errors['description'] = "La description ne doit pas dépasser 500 caractères";
    }

    // Insertion en base si pas d'erreurs
    if (empty($errors)) {
        try {
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            $stmt = $pdo->prepare("INSERT INTO filieres (nom, description) VALUES (:nom, :description)");
            $stmt->execute([
                ':nom' => $nom,
                ':description' => $description
            ]);

            $message = '<div class="alert alert-success">Filière ajoutée avec succès !</div>';
            $nom = $description = ''; // reset
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['nom'] = "Cette filière existe déjà";
            } else {
                $message = '<div class="alert alert-danger">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
                // Pour le débogage :
                error_log("Erreur PDO: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Filière - ENSA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .error { color: #dc3545; font-size: 0.875em; }
        .card { max-width: 800px; margin: 0 auto; }
        textarea { resize: vertical; min-height: 100px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <?php if (!empty($message)) echo $message; ?>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Ajouter une nouvelle filière</h4>
                <a href="filiere.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            </div>
            <div class="card-body">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom de la filière <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" 
                               id="nom" name="nom" value="<?= htmlspecialchars($nom) ?>" required maxlength="100">
                        <?php if (isset($errors['nom'])): ?>
                            <div class="invalid-feedback"><?= $errors['nom'] ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Maximum 100 caractères</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                  id="description" name="description" maxlength="500"><?= htmlspecialchars($description) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback"><?= $errors['description'] ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Maximum 500 caractères (optionnel)</small>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-eraser me-1"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                <small>Les champs marqués d'un <span class="text-danger">*</span> sont obligatoires</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>