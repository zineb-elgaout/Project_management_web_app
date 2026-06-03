<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['nom']) || !isset($_SESSION['role']) || !isset($_SESSION['prenom'])) {
    header("Location: ../hello/login.php");
    exit();
}

// Vérifie si c'est bien un admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Connexion à la base de données
require_once __DIR__ . '/../hello/config.php';

// Initialisation des variables avec des valeurs par défaut
$stats = [
    'users' => 0,
    'projects' => 0,
    'modules' => 0,
    'alerts' => 0
];

$activities = [];
$recent_users = [];
$settings = [];
$academic_year = '2023-2024';
$submission_active = false;

// Citations pour le message de bienvenue
$citations = [
    "L'excellence administrative est la clé d'un système éducatif performant.",
    "Un bon administrateur anticipe les besoins avant qu'ils ne se manifestent.",
    "La gestion efficace commence par une vision claire et une organisation rigoureuse.",
    "Votre travail discret fait toute la différence dans le fonctionnement de l'établissement.",
    "Chaque détail compte dans la recherche de l'excellence académique."
];
$citation_aleatoire = $citations[array_rand($citations)];

// Déterminer le moment de la journée
$heure = date('H');
if ($heure < 12) {
    $moment = "matin";
} elseif ($heure < 18) {
    $moment = "après-midi";
} else {
    $moment = "soir";
}

try {
    // Nombre d'utilisateurs
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['users'] = $stmt->fetchColumn() ?: 0;

    // Nombre de projets
    $stmt = $db->query("SELECT COUNT(*) FROM projects");
    $stats['projects'] = $stmt->fetchColumn() ?: 0;

    // Nombre de modules
    $stmt = $db->query("SELECT COUNT(*) FROM modules");
    $stats['modules'] = $stmt->fetchColumn() ?: 0;

    // Nombre d'alertes non traitées
    $stmt = $db->query("SELECT COUNT(*) FROM alerts WHERE status = 'pending'");
    $stats['alerts'] = $stmt->fetchColumn() ?: 0;

    // Activité récente
    $stmt = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Derniers utilisateurs
    $stmt = $db->query("SELECT u.id, u.full_name, u.email, r.name as role_name, u.created_at 
                       FROM users u 
                       JOIN roles r ON u.role_id = r.id 
                       ORDER BY u.created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Paramètres système
    $stmt = $db->query("SELECT name, value FROM system_settings");
    $settings_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($settings_results as $row) {
        $settings[$row['name']] = $row['value'];
    }

    $academic_year = $settings['academic_year'] ?? '2023-2024';
    $submission_active = isset($settings['submission_active']) && $settings['submission_active'] == '1';

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Traitement du formulaire de paramètres rapides
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['academic_year'])) {
        $academic_year = filter_input(INPUT_POST, 'academic_year', FILTER_SANITIZE_STRING);
        try {
            $stmt = $db->prepare("UPDATE system_settings SET value = ? WHERE name = 'academic_year'");
            $stmt->execute([$academic_year]);
        } catch (PDOException $e) {
            error_log("Error updating academic year: " . $e->getMessage());
        }
    }

    if (isset($_POST['submission_status'])) {
        $status = $_POST['submission_status'] === 'on' ? 1 : 0;
        try {
            $stmt = $db->prepare("UPDATE system_settings SET value = ? WHERE name = 'submission_active'");
            $stmt->execute([$status]);
            $submission_active = (bool)$status;
        } catch (PDOException $e) {
            error_log("Error updating submission status: " . $e->getMessage());
        }
    }

    if (isset($_POST['backup'])) {
        try {
            $backup_file = 'backups/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_file;
            system($command, $output);
            
            if ($output === 0) {
                $backup_success = true;
                $stmt = $db->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], "Sauvegarde de la base de données effectuée"]);
            } else {
                $backup_error = true;
            }
        } catch (PDOException $e) {
            error_log("Error during backup: " . $e->getMessage());
            $backup_error = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA - Espace Administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ensaprimary: #2c3e50;
            --ensasecondary: #3498db;
            --ensasuccess: #27ae60;
            --ensawarning: #f39c12;
            --ensadanger: #e74c3c;
            --ensalight: #ecf0f1;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* Nouveau conteneur pour la sidebar et le contenu */
        .page-container {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Sidebar amélioré */
        .sidebar-teacher {
            background-color: #212529;
            color: white;
            height: 200vh;
            width: 210px;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .nav-teacher .nav-link {
            color: white;
            border-left: 3px solid transparent;
            margin: 0.25rem 0;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }
        
        .nav-teacher .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-teacher .nav-link.active {
            color: white;
            background-color: #6c757d;
            border-left-color: var(--ensasecondary);
        }
        
        .main-content {
            flex: 1;
            transition: all 0.3s;
            min-height: 100vh;
            overflow-y: auto;
        }
        
        /* Header */
        .teacher-header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Media queries pour le responsive */
        @media (max-width: 992px) {
            .sidebar-teacher {
                position: fixed;
                transform: translateX(-100%);
            }
            
            .sidebar-teacher.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }
            
            .menu-overlay.show {
                display: block;
            }
        }
        
        /* Autres styles */
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
        }
        
        .admin-log {
            font-size: 0.85rem;
        }
        
        .card-ensablue {
            background-color: var(--ensasecondary);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Overlay pour mobile -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <!-- Topbar avec bouton menu -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark teacher-header">
        <div class="container-fluid">
            <button class="btn btn-icon text-white me-2 d-lg-none" id="mobileMenuToggle">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-project-diagram"></i> ENSA Admin 
            </a>
            <div class="d-flex align-items-center ms-auto">
                <a href="#" class="text-white me-3"><i class="bi bi-bell"></i></a>
                <div class="dropdown">
                    <a class="dropdown-toggle text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['prenom']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i> Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../hello/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteneur principal pour la sidebar et le contenu -->
    <div class="page-container">
        <!-- Sidebar -->
        <div class="sidebar-teacher p-0 min-vh-100" id="sidebar">
            <div class="d-flex flex-column h-100">
                <div class="p-4 text-center border-bottom border-secondary">
                    <div class="mb-3">
                        <div class="ratio ratio-1x1 mx-auto rounded-circle bg-light justify-content-center align-items-center" style="width: 80px;">
                            <div class="d-flex align-items-center justify-content-center text-dark fw-bold fs-3">
                                <?= substr($_SESSION['nom'], 0, 1) . substr($_SESSION['prenom'], 0, 1) ?>
                            </div>
                        </div>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']) ?></h5>
                    <small class="text-muted">Administrateur</small>
                </div>
                <ul class="nav flex-column nav-teacher p-3">
                    <li class="nav-item"><a class="nav-link active text-white bg-secondary" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Tableau de bord</a></li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 text-white" href="actualite.php">
                            <i class="bi bi-newspaper fs-5"></i>
                            <span class="fw-semibold">Actualités</span>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link text-white" href="gestion_utilisateurs.php"><i class="bi bi-people me-2"></i> Gestion utilisateurs</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="gestion_projets.php"><i class="bi bi-folder me-2"></i> Tous les projets</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="filiere.php"><i class="bi bi-building me-2"></i> Filières/Modules</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="statistiques.php"><i class="bi bi-bar-chart me-2"></i> Statistiques</a></li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 text-white" href="message.php">
                            <i class="bi bi-envelope-fill fs-5"></i>
                            <span class="fw-semibold">Messages</span>
                        </a>
                    </li>
          <li class="nav-item mt-3"><a class="nav-link text-white" href="parametres.php"><i class="bi bi-gear me-2"></i> Paramètres</a></li>

                    <li class="nav-item"><a class="nav-link text-white" href="../admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a></li>
                </ul>
                <div class="mt-auto p-3 text-center">
                    <small class="text-muted">ENSA Kenitra © <?= date('Y') ?></small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid py-4">
                <?php if (isset($backup_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Sauvegarde effectuée avec succès!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif (isset($backup_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Erreur lors de la sauvegarde. Veuillez réessayer.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-4 rounded shadow-sm mb-4 d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">
                        <i class="bi bi-speedometer2 me-2"></i> Tableau de bord administratif
                    </h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Exporter</button>
                        <a href="Nouvelle_action.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Nouvelle action
                        </a>
                    </div>
                </div>

                <!-- Message de bienvenue -->
                <div class="alert alert-info mt-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-quote fs-1 opacity-50"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="alert-heading">Bon<?= $heure < 12 ? 'jour' : 'soir' ?> <?= htmlspecialchars($_SESSION['prenom']) ?>,</h4>
                            <p class="mb-1"><?= $citation_aleatoire ?></p>
                            <hr>
                            <p class="mb-0 small">
                                <i class="bi bi-info-circle me-1"></i>
                                Nous sommes le <?= date('d/m/Y') ?> - <?= date('H:i') ?> | 
                                <?= $stats['users'] ?> utilisateurs | <?= $stats['projects'] ?> projets | 
                                <?= $stats['alerts'] ?> alerte<?= $stats['alerts'] > 1 ? 's' : '' ?> en attente
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Utilisateurs</h6>
                                    <h3 class="mb-0"><?= $stats['users'] ?></h3>
                                    <small class="text-white-50">+12 ce mois</small>
                                </div>
                                <i class="bi bi-people-fill fs-1 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Alertes</h6>
                                    <h3 class="mb-0"><?= $stats['alerts'] ?></h3>
                                    <small class="text-white-50">À traiter</small>
                                </div>
                                <i class="bi bi-exclamation-triangle-fill fs-1 opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activité et utilisateurs -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Activité récente</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($activities)): ?>
                                        <?php foreach ($activities as $activity): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between admin-log">
                                                    <span><?= htmlspecialchars($activity['action'] ?? 'Action inconnue') ?></span>
                                                    <small class="text-muted"><?= formatDate($activity['created_at'] ?? 'now') ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-group-item">
                                            <div class="text-center text-muted py-3">
                                                Aucune activité récente à afficher
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i> Derniers utilisateurs</h5>
                                <a href="gestion_utilisateurs.php?action=add" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Ajouter</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Rôle</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_users)): ?>
                                                <?php foreach ($recent_users as $user): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($user['full_name'] ?? 'Inconnu') ?></td>
                                                        <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                                        <td><span class="badge bg-<?= getRoleColor($user['role_name'] ?? '') ?>"><?= htmlspecialchars($user['role_name'] ?? 'Inconnu') ?></span></td>
                                                        <td><?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">
                                                        Aucun utilisateur récent à afficher
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paramètres rapides -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-sliders me-2"></i>
                            Paramètres rapides
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Année académique</label>
                                        <select class="form-select" name="academic_year">
                                            <option value="2023-2024" <?= $academic_year === '2023-2024' ? 'selected' : '' ?>>2023-2024</option>
                                            <option value="2022-2023" <?= $academic_year === '2022-2023' ? 'selected' : '' ?>>2022-2023</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Statut du système</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="submission_status" <?= $submission_active ? 'checked' : '' ?>>
                                            <label class="form-check-label">Soumissions actives</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Actions</label>
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="backup" class="btn btn-warning">
                                                <i class="bi bi-database me-1"></i> Sauvegarder
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du menu mobile
        const sidebar = document.getElementById('sidebar');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const menuOverlay = document.getElementById('menuOverlay');
        
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            menuOverlay.classList.toggle('show');
        });
        
        menuOverlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            menuOverlay.classList.remove('show');
        });
        
        // Fermer le menu quand un lien est cliqué (sur mobile)
        document.querySelectorAll('.nav-teacher .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    menuOverlay.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>

<?php
function formatDate($date) {
    $now = new DateTime();
    $date = new DateTime($date);
    $diff = $now->diff($date);
    
    if ($diff->y > 0) return "Il y a {$diff->y} an" . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return "Il y a {$diff->m} mois";
    if ($diff->d > 0) return "Il y a {$diff->d} jour" . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return "Il y a {$diff->h} heure" . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return "Il y a {$diff->i} minute" . ($diff->i > 1 ? 's' : '');
    return "À l'instant";
}

function getRoleColor($role) {
    switch (strtolower($role)) {
        case 'admin': return 'warning text-dark';
        case 'encadrant': return 'success';
        case 'étudiant':
        case 'etudiant': return 'primary';
        default: return 'secondary';
    }
}
?>