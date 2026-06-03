<?php
// Démarrez la session au tout début du fichier
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['nom']) || !isset($_SESSION['prenom']) || !isset($_SESSION['role'])) {
    header("Location: ../hello/login.php");
    exit();
}

// Connexion à la base de données pour récupérer les statistiques
require_once __DIR__ . '/../hello/config.php';

// Initialisation des variables avec des valeurs par défaut
$stats = [
    'users' => 0,
    'projects' => 0,
    'modules' => 0,
    'alerts' => 0
];

try {
    // Récupération des statistiques depuis la base de données
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['users'] = $stmt->fetchColumn() ?: 0;

    $stmt = $db->query("SELECT COUNT(*) FROM projects");
    $stats['projects'] = $stmt->fetchColumn() ?: 0;

    $stmt = $db->query("SELECT COUNT(*) FROM modules");
    $stats['modules'] = $stmt->fetchColumn() ?: 0;

    $stmt = $db->query("SELECT COUNT(*) FROM alerts WHERE status = 'pending'");
    $stats['alerts'] = $stmt->fetchColumn() ?: 0;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA - Mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-avatar {
            width: 100px;
            height: 100px;
            background-color:rgb(148, 74, 222);
            font-size: 2rem;
        }
    </style>
</head>
<body>

<!-- Barre de navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="admin_dashboard.php">
            <i class="bi bi-journal-bookmark-fill me-2"></i> ENSA Admin 
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person-circle me-1"></i> 
                        <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Carte de profil -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i> Mon Profil</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center profile-avatar">
                        <?= substr($_SESSION['prenom'] ?? '', 0, 1) . substr($_SESSION['nom'] ?? '', 0, 1) ?>
                    </div>
                    <h5><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></h5>
                    <p class="text-muted mb-3"><?= htmlspecialchars($_SESSION['role']) ?></p>
                    
                    <form action="update_profile.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Changer la photo</label>
                            <input class="form-control form-control-sm" type="file" id="avatar" name="avatar">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Projets</h6>
                                <h3 class="mb-0"><?= $stats['projects'] ?></h3>
                                <small class="text-white-50">36% PFE</small>
                            </div>
                            <i class="bi bi-folder-fill fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-warning text-dark">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-dark-50">Modules</h6>
                                <h3 class="mb-0"><?= $stats['modules'] ?></h3>
                                <small class="text-dark-50">5 nouveaux</small>
                            </div>
                            <i class="bi bi-book-fill fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire de modification du profil -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Modifier mes informations</h5>
                </div>
                <div class="card-body">
                    <form action="update_profile.php" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="prenom" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?= htmlspecialchars($_SESSION['prenom']) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?= htmlspecialchars($_SESSION['nom']) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted">Laissez vide pour ne pas changer</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>