<?php
session_start();

// Vérification de l'authentification et des permissions
if (!isset($_SESSION['nom']) || !isset($_SESSION['role']) || !isset($_SESSION['prenom'])) {
    header("Location: ../hello/login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Connexion à la base de données
require_once __DIR__ . '/../hello/config.php';

try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("<div class='alert alert-danger'>Erreur de connexion : " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA - Gestion des Projets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ensaprimary:  #212529;
            --ensasecondary: #3498db;
            --ensasuccess: #27ae60;
            --ensawarning: #f39c12;
            --ensadanger: #e74c3c;
            --ensalight: #ecf0f1;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar-teacher {
            background-color: var(--ensaprimary);
            color: white;
            height: 200vh;
        }
        
        .nav-teacher .nav-link {
            color: rgba(255,255,255,0.8);
            border-left: 3px solid transparent;
            margin: 0.25rem 0;
        }
        
        .nav-teacher .nav-link:hover, 
        .nav-teacher .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--ensasecondary);
        }
        
        .teacher-header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
        }
        
        .status-valide {
            background-color: var(--ensasuccess);
            color: white;
        }
        
        .status-en_attente {
            background-color: var(--ensawarning);
            color: #212529;
        }
        
        .status-refuse {
            background-color: var(--ensadanger);
            color: white;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .project-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--ensaprimary);
            border-color: var(--ensaprimary);
        }
        
        .pagination .page-link {
            color: var(--ensaprimary);
        }
        
        /* Media Queries pour une meilleure responsivité */
@media (max-width: 1400px) {
    /* Ajustements pour les grands écrans */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 992px) {
    /* Ajustements pour les tablettes */
    .sidebar-teacher {
        position: fixed;
        height: 100vh;
        z-index: 1000;
        width: 280px;
        transform: translateX(-280px);
        transition: transform 0.3s;
    }
    
    .sidebar-teacher.show {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .filter-section .col-md-3 {
        margin-bottom: 1rem;
    }
    
    .teacher-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .teacher-header > div {
        margin-bottom: 1rem;
    }
}

@media (max-width: 768px) {
    /* Ajustements pour les petites tablettes */
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .card-header h5 {
        margin-bottom: 1rem;
    }
    
    .project-actions .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .modal-dialog {
        margin: 0.5rem auto;
    }
    
    .navbar-brand {
        font-size: 1rem;
    }
    
    .dropdown-menu {
        position: absolute;
    }
}

@media (max-width: 576px) {
    /* Ajustements pour les mobiles */
    body {
        font-size: 14px;
    }
    
    .table td, .table th {
        padding: 0.5rem;
    }
    
    .status-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
    }
    
    .filter-section .row > div {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .filter-section .col-md-3 {
        width: 100%;
    }
    
    .pagination .page-item .page-link {
        padding: 0.25rem 0.5rem;
    }
    
    .modal-dialog {
        max-width: 95%;
    }
    
    .modal-body .row > div {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .project-actions .d-flex {
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .project-actions .dropdown-menu {
        position: absolute;
        right: 0;
        left: auto;
    }
}

@media (max-width: 400px) {
    /* Ajustements pour les très petits mobiles */
    .navbar-nav {
        flex-direction: row;
    }
    
    .nav-item {
        margin-right: 0.5rem;
    }
    
    .dropdown-menu {
        position: absolute;
        right: 0;
        left: auto;
    }
    
    .card-header h5 {
        font-size: 1rem;
    }
    
    .table th, .table td {
        font-size: 0.8rem;
    }
    
    .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--ensaprimary);">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-project-diagram"></i>
                ENSA Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-bell"></i></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['prenom'] . ' ' . htmlspecialchars($_SESSION['nom']) )?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="parametres.php"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../hello/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar-teacher p-0">
                <div class="d-flex flex-column h-100">
                    <div class="p-4 text-center border-bottom border-secondary">
                        <div class="mb-3">
                            <div class="ratio ratio-1x1 mx-auto rounded-circle bg-light justify-content-center align-items-center" style="width: 80px;">
                                <div class="d-flex align-items-center justify-content-center text-dark fw-bold fs-3">
                                    <?= substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1) ?>
                                </div>
                            </div>
                        </div>
                        <h5 class="mb-1 text-white"><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></h5>
                        <small class="text-muted">Administrateur</small>
                    </div>
                    
                    <ul class="nav flex-column nav-teacher p-3">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
  <a class="nav-link d-flex align-items-center gap-2 text-white" href="actualite.php">
    <i class="bi bi-newspaper fs-5"></i>
    <span class="fw-semibold">Actualités</span>
  </a>
</li>

                        <li class="nav-item">
                            <a class="nav-link" href="gestion_utilisateurs.php">
                                <i class="bi bi-people me-2"></i>
                                Gestion utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="gestion_projets.php">
                                <i class="bi bi-folder me-2"></i>
                                Tous les projets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="filiere.php">
                                <i class="bi bi-building me-2"></i>
                                Filières/Modules
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="statistiques.php">
                                <i class="bi bi-bar-chart me-2"></i>
                                Statistiques
                            </a>
                        </li>
                        <li class="nav-item">
  <a class="nav-link d-flex align-items-center gap-2 text-white" href="message.php">
    <i class="bi bi-envelope-fill fs-5"></i>
    <span class="fw-semibold">Messages</span>
  </a>
</li>

                        <li class="nav-item mt-3">
                            <a class="nav-link" href="parametres.php">
                                <i class="bi bi-gear me-2"></i>
                                Paramètres
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-auto p-3 text-center">
                        <small class="text-muted">ENSA Kenitra © 2024</small>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 py-4">
                <!-- Header -->
                <div class="teacher-header rounded-3 p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">
                                <i class="bi bi-folder me-2"></i>
                                Gestion des Projets
                            </h2>
                            <p class="text-muted mb-0">Consulter et gérer tous les projets</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary">
                                <i class="bi bi-download me-1"></i> Exporter
                            </button>
                            
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filter-section p-4 mb-4">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Statut</label>
                                <select id="statusFilter" name="status" class="form-select">
                                    <option value="" selected>Tous les statuts</option>
                                    <option value="valide">Validé</option>
                                    <option value="en_attente">En attente</option>
                                    <option value="refuse">Refusé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="typeFilter" class="form-label">Type</label>
                                <select id="typeFilter" name="type" class="form-select">
                                    <option value="" selected>Tous les types</option>
                                    <option value="Stage d'initiation">Stage d'initiation</option>
                                    <option value="Stage d'ingénieur adjoint">Stage d'ingénieur adjoint</option>
                                    <option value="Stage de fin d'études - PFE">Stage de fin d'études - PFE</option>
                                    <option value="projet pédagogique">Projet pédagogique</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="yearFilter" class="form-label">Année</label>
                                <select id="yearFilter" name="year" class="form-select">
                                    <option value="" selected>Toutes les années</option>
                                    <option value="2024">2023-2024</option>
                                    <option value="2023">2022-2023</option>
                                    <option value="2022">2021-2022</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel me-1"></i> Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Liste des projets -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>
                            Liste des projets
                            <?php
                            try {
                                $count = $db->query("SELECT COUNT(*) FROM projet")->fetchColumn();
                                echo "($count)";
                            } catch(PDOException $e) {
                                echo "(Erreur)";
                            }
                            ?>
                        </h5>
                        <div class="d-flex gap-2">
                            <form method="GET" action="" class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" name="search" class="form-control" placeholder="Rechercher...">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="25%">Titre</th>
                                        <th width="15%">Étudiant</th>
                                        <th width="10%">Type</th>
                                        <th width="10%">Date</th>
                                        <th width="10%">Statut</th>
                                        <th width="15%">Encadrant</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        // Construction de la requête avec filtres
                                        $sql = "SELECT p.*, 
                                               CONCAT(u1.prenom, ' ', u1.nom) AS etudiant,
                                               CONCAT(u2.prenom, ' ', u2.nom) AS encadrant
                                               FROM projet p
                                               LEFT JOIN etudiant e ON p.id_etudiant = e.id_etudiant
                                               LEFT JOIN utilisateur u1 ON e.id_etudiant = u1.id_utilisateur
                                               LEFT JOIN enseignant en ON p.id_enseignant = en.id_enseignant
                                               LEFT JOIN utilisateur u2 ON en.id_enseignant = u2.id_utilisateur
                                               WHERE 1=1";
                                        
                                        $params = [];
                                        
                                        if (!empty($_GET['status'])) {
                                            $sql .= " AND p.statut = :status";
                                            $params[':status'] = $_GET['status'];
                                        }
                                        
                                        if (!empty($_GET['type'])) {
                                            $sql .= " AND p.type_projet = :type";
                                            $params[':type'] = $_GET['type'];
                                        }
                                        
                                        if (!empty($_GET['year'])) {
                                            $sql .= " AND YEAR(p.date_soumission) = :year";
                                            $params[':year'] = $_GET['year'];
                                        }
                                        
                                        if (!empty($_GET['search'])) {
                                            $sql .= " AND (p.titre LIKE :search OR p.sujet LIKE :search)";
                                            $params[':search'] = '%' . $_GET['search'] . '%';
                                        }
                                        
                                        $sql .= " ORDER BY p.date_soumission DESC LIMIT 20";
                                        
                                        $stmt = $db->prepare($sql);
                                        $stmt->execute($params);
                                        
                                        while ($projet = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            // Déterminer la classe CSS pour le statut
                                            $status_class = 'status-' . $projet['statut'];
                                            $status_text = '';
                                            
                                            switch ($projet['statut']) {
                                                case 'valide':
                                                    $status_text = 'Validé';
                                                    break;
                                                case 'en_attente':
                                                    $status_text = 'En attente';
                                                    break;
                                                case 'refuse':
                                                    $status_text = 'Refusé';
                                                    break;
                                            }
                                            
                                            // Formater la date
                                            $date = date('d/m/Y', strtotime($projet['date_soumission']));
                                            
                                            echo '<tr>
                                                <td>' . $projet['id_projet'] . '</td>
                                                <td>
                                                    <strong>' . htmlspecialchars($projet['titre']) . '</strong>
                                                    <div class="text-muted small">' . htmlspecialchars($projet['sujet']) . '</div>
                                                </td>
                                                <td>' . htmlspecialchars($projet['etudiant']) . '</td>
                                                <td>' . htmlspecialchars($projet['type_projet']) . '</td>
                                                <td>' . $date . '</td>
                                                <td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>
                                                <td>' . htmlspecialchars($projet['encadrant']) . '</td>
                                                <td class="project-actions">
                                                    <div class="d-flex gap-1">
                                                        <button class="btn btn-sm btn-outline-primary" title="Voir">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" title="Valider">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" title="Rejeter">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Plus">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="#"><i class="bi bi-download me-2"></i>Télécharger</a></li>
                                                                <li><a class="dropdown-item" href="#"><i class="bi bi-pencil-square me-2"></i>Modifier</a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Supprimer</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>';
                                        }
                                        
                                        if ($stmt->rowCount() === 0) {
                                            echo '<tr><td colspan="8" class="text-center py-4">Aucun projet trouvé</td></tr>';
                                        }
                                        
                                    } catch(PDOException $e) {
                                        echo '<tr><td colspan="8" class="text-center py-4 text-danger">Erreur: ' . $e->getMessage() . '</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Précédent</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Suivant</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Projet -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProjectModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Ajouter un nouveau projet
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="projectForm" action="save_project.php" method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="projectTitle" class="form-label">Titre du projet</label>
                                <input type="text" class="form-control" id="projectTitle" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label for="projectType" class="form-label">Type de projet</label>
                                <select class="form-select" id="projectType" name="type" required>
                                    <option value="" selected disabled>Sélectionner...</option>
                                    <option value="Stage d'initiation">Stage d'initiation</option>
                                    <option value="Stage d'ingénieur adjoint">Stage d'ingénieur adjoint</option>
                                    <option value="Stage de fin d'études - PFE">Stage de fin d'études - PFE</option>
                                    <option value="projet pédagogique">Projet pédagogique</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="projectStudent" class="form-label">Étudiant</label>
                                <select class="form-select" id="projectStudent" name="student" required>
                                    <option value="" selected disabled>Sélectionner...</option>
                                    <?php
                                    try {
                                        $stmt = $db->query("SELECT e.id_etudiant, u.nom, u.prenom FROM etudiant e JOIN utilisateur u ON e.id_etudiant = u.id_utilisateur ORDER BY u.nom");
                                        while ($student = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $student['id_etudiant'] . '">' . htmlspecialchars($student['nom'] . ' ' . htmlspecialchars($student['prenom']) ). '</option>';
                                        }
                                    } catch(PDOException $e) {
                                        echo '<option value="">Erreur de chargement</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="projectSupervisor" class="form-label">Encadrant</label>
                                <select class="form-select" id="projectSupervisor" name="supervisor" required>
                                    <option value="" selected disabled>Sélectionner...</option>
                                    <?php
                                    try {
                                        $stmt = $db->query("SELECT en.id_enseignant, u.nom, u.prenom FROM enseignant en JOIN utilisateur u ON en.id_enseignant = u.id_utilisateur ORDER BY u.nom");
                                        while ($teacher = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $teacher['id_enseignant'] . '">' . htmlspecialchars($teacher['nom'] . ' ' . htmlspecialchars($teacher['prenom'])) . '</option>';
                                        }
                                    } catch(PDOException $e) {
                                        echo '<option value="">Erreur de chargement</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="projectSubject" class="form-label">Sujet</label>
                                <input type="text" class="form-control" id="projectSubject" name="subject" required>
                            </div>
                            <div class="col-12">
                                <label for="projectDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="projectDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="projectStatus" class="form-label">Statut initial</label>
                                <select class="form-select" id="projectStatus" name="status">
                                    <option value="en_attente">En attente</option>
                                    <option value="valide">Validé</option>
                                    <option value="refuse">Refusé</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="projectForm" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour valider/rejeter un projet
        function updateProjectStatus(projectId, status) {
            if (confirm('Êtes-vous sûr de vouloir modifier le statut de ce projet ?')) {
                fetch('update_project_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${projectId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Statut mis à jour avec succès');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue');
                });
            }
        }

        // Initialisation des tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>