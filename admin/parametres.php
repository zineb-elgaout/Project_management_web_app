<?php
session_start();
require_once __DIR__ . '/../hello/config.php';

// Vérifie si c'est bien un admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit();
}
// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['nom']) || !isset($_SESSION['role']) || !isset($_SESSION['prenom'])) {
    header("Location: ../hello/login.php");
    exit();
}

// Récupération des paramètres depuis la base de données
$settings = [];
try {
    $stmt = $db->query("SELECT setting_name, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Erreur de chargement des paramètres: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Fonction pour vérifier les états des checkboxes
function isChecked($settingName, $default = false) {
    global $settings;
    return isset($settings[$settingName]) && $settings[$settingName] ? 'checked' : '';
}

// Fonction pour sélectionner les options
function isSelected($settingName, $value) {
    global $settings;
    return (isset($settings[$settingName]) && $settings[$settingName] == $value) ? 'selected' : '';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    
    try {
        // CORRECTION APPLIQUÉE ICI (-> au lieu de >)
        $db->beginTransaction();
        
        $updates = [];
        
        if ($formType === 'general') {
            // Traitement des paramètres généraux
            $updates = [
                'school_name' => $_POST['school_name'] ?? 'ENSA Kenitra',
                'academic_year' => $_POST['academic_year'] ?? '2024-2025',
                'description' => $_POST['description'] ?? ''
            ];
            
            // Gestion des fichiers uploadés
            $fileFields = ['logo', 'favicon'];
            foreach ($fileFields as $field) {
                if (!empty($_FILES[$field]['name'])) {
                    $targetDir = "uploads/settings/";
                    
                    // Créer le répertoire s'il n'existe pas
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    
                    $fileName = basename($_FILES[$field]['name']);
                    $targetFile = $targetDir . uniqid() . '_' . $fileName;
                    
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetFile)) {
                        $updates[$field] = $targetFile;
                    }
                }
            }
            
        } elseif ($formType === 'soumissions') {
            // Paramètres de soumission
            $updates = [
                'submissions_active' => isset($_POST['soumissionsActives']) ? 1 : 0,
                'submission_start' => $_POST['submission_start'] ?? '',
                'submission_end' => $_POST['submission_end'] ?? '',
                'max_file_size' => $_POST['max_file_size'] ?? 20,
                'allowed_formats' => implode(',', $_POST['allowed_formats'] ?? ['PDF'])
            ];
            
        } elseif ($formType === 'notifications') {
            // Paramètres de notifications
            $updates = [
                'notif_new_project' => isset($_POST['notif_new_project']) ? 1 : 0,
                'notif_project_validated' => isset($_POST['notif_project_validated']) ? 1 : 0,
                'notif_comment' => isset($_POST['notif_comment']) ? 1 : 0,
                'notif_reminder' => isset($_POST['notif_reminder']) ? 1 : 0,
                'notif_maintenance' => isset($_POST['notif_maintenance']) ? 1 : 0,
                'notif_security' => isset($_POST['notif_security']) ? 1 : 0
            ];
            
        } elseif ($formType === 'sauvegarde') {
            // Paramètres de sauvegarde
            $updates = [
                'backup_frequency' => $_POST['backup_frequency'] ?? 'weekly',
                'backup_time' => $_POST['backup_time'] ?? '02:00',
                'backup_cloud' => isset($_POST['backup_cloud']) ? 1 : 0
            ];
            
        } elseif ($formType === 'securite') {
            // Paramètres de sécurité
            $updates = [
                'auth_2fa' => isset($_POST['auth_2fa']) ? 1 : 0,
                'auth_captcha' => isset($_POST['auth_captcha']) ? 1 : 0,
                'auth_session_expire' => isset($_POST['auth_session_expire']) ? 1 : 0,
                'password_length' => $_POST['password_length'] ?? 8,
                'password_complexity' => $_POST['password_complexity'] ?? 'medium',
                'password_expiry' => $_POST['password_expiry'] ?? 90,
                'password_history' => $_POST['password_history'] ?? 3
            ];
            
        } elseif ($formType === 'api') {
            // Paramètres API
            $updates = [
                'api_active' => isset($_POST['api_active']) ? 1 : 0,
                'api_rate_limit' => $_POST['api_rate_limit'] ?? 60,
                'api_allowed_domains' => $_POST['api_allowed_domains'] ?? ''
            ];
            
            // Générer une nouvelle clé API si demandé
            if (isset($_POST['generate_api_key'])) {
                $updates['api_key'] = 'sk_'.bin2hex(random_bytes(16));
            }
            
        } elseif ($formType === 'maintenance') {
            // Paramètres de maintenance
            $updates = [
                'maintenance_active' => isset($_POST['maintenance_active']) ? 1 : 0,
                'maintenance_message' => $_POST['maintenance_message'] ?? '',
                'maintenance_start' => $_POST['maintenance_start'] ?? '',
                'maintenance_end' => $_POST['maintenance_end'] ?? ''
            ];
        }
        
        // Mise à jour de la base de données
        foreach ($updates as $key => $value) {
            $stmt = $db->prepare("REPLACE INTO settings (setting_name, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
        
        $db->commit();
        $_SESSION['message'] = "Paramètres mis à jour avec succès!";
        $_SESSION['message_type'] = "success";
        
        // Recharger les paramètres
        $stmt = $db->query("SELECT setting_name, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['message'] = "Erreur lors de la mise à jour: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: parametres.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA - Paramètres Administrateur</title>
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
        }
        .sidebar-teacher {
            background-color: #212529;
            color: white;
            height: 200vh;
        }
        .nav-teacher .nav-link {
            color: white;
            border-left: 3px solid transparent;
            margin: 0.25rem 0;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
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
        .admin-log {
            font-size: 0.85rem;
        }
        .card-ensablue {
            background-color: var(--ensasecondary);
            color: white;
        }
        .settings-card {
            border-left: 4px solid var(--ensasecondary);
        }
        .nav-settings {
            border-right: 1px solid #dee2e6;
        }
        .nav-settings .nav-link {
            color: #495057;
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
        }
        .nav-settings .nav-link.active {
            color: var(--ensasecondary);
            background-color: rgba(52, 152, 219, 0.1);
            font-weight: 500;
        }
        @media (max-width: 992px) {
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
            }
            .nav-settings {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">
            <i class="fas fa-project-diagram"></i> ENSA Admin 
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bell"></i></a></li>
                 <li class="nav-item">
          <a class="nav-link" href="profil.php">
            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']) ?>
          </a>
        </li>
                <li class="nav-item dropdown">
                    
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="parametres.php"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>


<!-- Affichage des messages -->
<?php if (isset($_SESSION['message'])) : ?>
<div class="container-fluid mt-3">
    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php 
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
endif; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 sidebar-teacher p-0 min-vh-100">
            <div class="d-flex flex-column h-100">
                <div class="p-4 text-center border-bottom border-secondary">
                    <div class="mb-3">
                        <div class="ratio ratio-1x1 mx-auto rounded-circle bg-light justify-content-center align-items-center" style="width: 80px;">
                            <div class="d-flex align-items-center justify-content-center text-dark fw-bold fs-3">AD</div>
                        </div>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']) ?></h5>
                    <small class="text-muted">Super Admin</small>
                </div>
                <ul class="nav flex-column nav-teacher p-3">
                    <li class="nav-item"><a class="nav-link text-white" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Tableau de bord</a></li>
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

                    <li class="nav-item mt-3"><a class="nav-link active text-white bg-secondary" href="parametres.php"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
                </ul>
                <div class="mt-auto p-3 text-center">
                    <small class="text-muted">ENSA Kenitra © <?= date('Y') ?></small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 main-content py-4">
            <div class="bg-white p-4 rounded shadow-sm mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-gear me-2"></i> Paramètres du système
                </h2>
            </div>

            <div class="row">
                <!-- Menu des paramètres -->
                <div class="col-lg-3">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-0">
                            <ul class="nav flex-column nav-settings p-3">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#general" data-bs-toggle="tab">
                                        <i class="bi bi-sliders me-2"></i> Général
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#soumissions" data-bs-toggle="tab">
                                        <i class="bi bi-send me-2"></i> Soumissions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#notifications" data-bs-toggle="tab">
                                        <i class="bi bi-bell me-2"></i> Notifications
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#sauvegarde" data-bs-toggle="tab">
                                        <i class="bi bi-database me-2"></i> Sauvegarde
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#securite" data-bs-toggle="tab">
                                        <i class="bi bi-shield-lock me-2"></i> Sécurité
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#api" data-bs-toggle="tab">
                                        <i class="bi bi-plug me-2"></i> API
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#maintenance" data-bs-toggle="tab">
                                        <i class="bi bi-tools me-2"></i> Maintenance
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Contenu des paramètres -->
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- Onglet Général -->
                        <div class="tab-pane fade show active" id="general">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="card shadow-sm mb-4 settings-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-sliders me-2"></i> Paramètres généraux</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Nom de l'établissement</label>
                                                <input type="text" class="form-control" name="school_name" 
                                                    value="<?= htmlspecialchars($settings['school_name'] ?? 'ENSA Kenitra') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Année académique</label>
                                                <select class="form-select" name="academic_year">
                                                    <option value="2024-2025" <?= isSelected('academic_year', '2024-2025') ?>>2024-2025</option>
                                                    <option value="2023-2024" <?= isSelected('academic_year', '2023-2024') ?>>2023-2024</option>
                                                    <option value="2022-2023" <?= isSelected('academic_year', '2022-2023') ?>>2022-2023</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Logo</label>
                                                <input type="file" class="form-control" name="logo">
                                                <?php if (!empty($settings['logo'])) : ?>
                                                    <small class="text-muted">Fichier actuel: <?= basename($settings['logo']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Favicon</label>
                                                <input type="file" class="form-control" name="favicon">
                                                <?php if (!empty($settings['favicon'])) : ?>
                                                    <small class="text-muted">Fichier actuel: <?= basename($settings['favicon']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3"><?= 
                                                    htmlspecialchars($settings['description'] ?? 'École Nationale des Sciences Appliquées de Kenitra - Plateforme de gestion des projets')
                                                ?></textarea>
                                            </div>
                                        </div>
                                        <input type="hidden" name="form_type" value="general">
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet Soumissions -->
                        <div class="tab-pane fade" id="soumissions">
                            <form method="POST">
                                <div class="card shadow-sm mb-4 settings-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-send me-2"></i> Paramètres de soumission</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="soumissionsActives" 
                                                    id="soumissionsActives" <?= isChecked('submissions_active', true) ?>>
                                                <label class="form-check-label" for="soumissionsActives">Soumissions actives</label>
                                            </div>
                                            <small class="text-muted">Autoriser les étudiants à soumettre des projets</small>
                                        </div>
                                        
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Date d'ouverture</label>
                                                <input type="date" class="form-control" name="submission_start" 
                                                    value="<?= $settings['submission_start'] ?? '2024-09-01' ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Date de fermeture</label>
                                                <input type="date" class="form-control" name="submission_end" 
                                                    value="<?= $settings['submission_end'] ?? '2024-12-15' ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Taille max. des fichiers (MB)</label>
                                                <input type="number" class="form-control" name="max_file_size" 
                                                    value="<?= $settings['max_file_size'] ?? 20 ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Formats autorisés</label>
                                                <select class="form-select" name="allowed_formats[]" multiple>
                                                    <?php 
                                                    $formats = explode(',', $settings['allowed_formats'] ?? 'PDF,DOCX,ZIP');
                                                    $allFormats = ['PDF', 'DOCX', 'ZIP', 'PPTX'];
                                                    foreach ($allFormats as $format) : ?>
                                                        <option value="<?= $format ?>" <?= in_array($format, $formats) ? 'selected' : '' ?>>
                                                            <?= $format ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <input type="hidden" name="form_type" value="soumissions">
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet Notifications -->
                        <div class="tab-pane fade" id="notifications">
                            <form method="POST">
                                <div class="card shadow-sm mb-4 settings-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-bell me-2"></i> Paramètres de notification</h5>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="mb-3">Notifications par email</h6>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notif_new_project" 
                                                    id="notifProjetSoumis" <?= isChecked('notif_new_project', true) ?>>
                                                <label class="form-check-label" for="notifProjetSoumis">Nouveau projet soumis</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notif_project_validated" 
                                                    id="notifProjetValide" <?= isChecked('notif_project_validated', true) ?>>
                                                <label class="form-check-label" for="notifProjetValide">Projet validé</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notif_comment" 
                                                    id="notifCommentaire" <?= isChecked('notif_comment', true) ?>>
                                                <label class="form-check-label" for="notifCommentaire">Nouveau commentaire</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notif_reminder" 
                                                    id="notifRappel" <?= isChecked('notif_reminder', true) ?>>
                                                <label class="form-check-label" for="notifRappel">Rappels de délai</label>
                                            </div>
                                        </div>

                                        <h6 class="mb-3 mt-4">Notifications système</h6>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notif_maintenance" 
                                                    id="notifMaintenance" <?= isChecked('notif_maintenance', true) ?>>
                                                <label class="form-check-label" for="notifMaintenance">Maintenance planifiée</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notif_security" 
                                                    id="notifSecurite" <?= isChecked('notif_security', true) ?>>
                                                <label class="form-check-label" for="notifSecurite">Alertes de sécurité</label>
                                            </div>
                                        </div>

                                        <input type="hidden" name="form_type" value="notifications">
                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet Sauvegarde -->
                        <div class="tab-pane fade" id="sauvegarde">
                            <form method="POST">
                                <div class="card shadow-sm mb-4 settings-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-database me-2"></i> Sauvegarde des données</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($settings['last_backup'])) : ?>
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i> Dernière sauvegarde: <?= $settings['last_backup'] ?>
                                            </div>
                                        <?php else : ?>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i> Aucune sauvegarde effectuée
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Fréquence des sauvegardes</label>
                                                <select class="form-select" name="backup_frequency">
                                                    <option value="daily" <?= isSelected('backup_frequency', 'daily') ?>>Quotidienne</option>
                                                    <option value="weekly" <?= isSelected('backup_frequency', 'weekly') ?>>Hebdomadaire</option>
                                                    <option value="monthly" <?= isSelected('backup_frequency', 'monthly') ?>>Mensuelle</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Heure de sauvegarde</label>
                                                <input type="time" class="form-control" name="backup_time" 
                                                    value="<?= $settings['backup_time'] ?? '02:00' ?>">
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="backup_cloud" 
                                                        id="sauvegardeCloud" <?= isChecked('backup_cloud', true) ?>>
                                                    <label class="form-check-label" for="sauvegardeCloud">Sauvegarde sur le cloud</label>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="form_type" value="sauvegarde">
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-outline-primary">
                                                <i class="bi bi-download me-1"></i> Télécharger la dernière sauvegarde
                                            </button>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-database me-1"></i> Sauvegarder maintenant
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet Sécurité -->
                        <div class="tab-pane fade" id="securite">
                            <form method="POST">
                                <div class="card shadow-sm mb-4 settings-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i> Paramètres de sécurité</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-4">
                                            <h6>Authentification</h6>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="auth_2fa" 
                                                    id="auth2fa" <?= isChecked('auth_2fa', true) ?>>
                                                <label class="form-check-label" for="auth2fa">Authentification à deux facteurs (2FA) pour les admins</label>
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="auth_captcha" 
                                                    id="authCaptcha" <?= isChecked('auth_captcha', true) ?>>
                                                <label class="form-check-label" for="authCaptcha">CAPTCHA après 3 tentatives échouées</label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auth_session_expire" 
                                                    id="authExpire" <?= isChecked('auth_session_expire', true) ?>>
                                                <label class="form-check-label" for="authExpire">Expiration de session après 30 minutes d'inactivité</label>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <h6>Politique de mot de passe</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Longueur minimale</label>
                                                    <input type="number" class="form-control" name="password_length" 
                                                        value="<?= $settings['password_length'] ?? 8 ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Complexité</label>
                                                    <select class="form-select" name="password_complexity">
                                                        <option value="low" <?= isSelected('password_complexity', 'low') ?>>Lettres seulement</option>
                                                        <option value="medium" <?= isSelected('password_complexity', 'medium') ?>>Lettres et chiffres</option>
                                                        <option value="high" <?= isSelected('password_complexity', 'high') ?>>Lettres, chiffres et caractères spéciaux</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Durée de validité (jours)</label>
                                                    <input type="number" class="form-control" name="password_expiry" 
                                                        value="<?= $settings['password_expiry'] ?? 90 ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Historique (anciens mots de passe)</label>
                                                    <input type="number" class="form-control" name="password_history" 
                                                        value="<?= $settings['password_history'] ?? 3 ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="form_type" value="securite">
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet API -->
                        <div class="tab-pane fade" id="api">
                            <form method="POST">
                                <div class="card shadow-sm mb-4 settings-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-plug me-2"></i> Paramètres API</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i> Ces paramètres sont réservés aux développeurs avancés.
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="api_active" 
                                                    id="apiActive" <?= isChecked('api_active', true) ?>>
                                                <label class="form-check-label" for="apiActive">API REST activée</label>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Clé API principale</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" 
                                                        value="<?= $settings['api_key'] ?? 'sk_test_'.bin2hex(random_bytes(8)) ?>" 
                                                        readonly>
                                                    <button class="btn btn-outline-secondary" type="button" id="showApiKey">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-secondary" type="submit" name="generate_api_key">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Limite de requêtes (par minute)</label>
                                                <input type="number" class="form-control" name="api_rate_limit" 
                                                    value="<?= $settings['api_rate_limit'] ?? 60 ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Domaines autorisés</label>
                                                <textarea class="form-control" name="api_allowed_domains" rows="3"><?= 
                                                    htmlspecialchars($settings['api_allowed_domains'] ?? '') 
                                                ?></textarea>
                                            </div>
                                        </div>
                                        <input type="hidden" name="form_type" value="api">
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet Maintenance -->
                        <div class="tab-pane fade" id="maintenance">
                            <form method="POST">
                                <div class="card shadow-sm mb-4 settings-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-tools me-2"></i> Mode maintenance</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-danger">
                                            <i class="bi bi-exclamation-octagon me-2"></i> Le mode maintenance restreint l'accès au site aux administrateurs seulement.
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenance_active" 
                                                    id="maintenanceActive" <?= isChecked('maintenance_active', true) ?>>
                                                <label class="form-check-label" for="maintenanceActive">Activer le mode maintenance</label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Message de maintenance</label>
                                            <textarea class="form-control" name="maintenance_message" rows="3"><?= 
                                                htmlspecialchars($settings['maintenance_message'] ?? 'Le site est actuellement en maintenance. Nous serons de retour bientôt. Merci pour votre patience.')
                                            ?></textarea>
                                        </div>
                                        
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Heure de début</label>
                                                <input type="datetime-local" class="form-control" name="maintenance_start" 
                                                    value="<?= $settings['maintenance_start'] ?? '' ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Heure de fin (estimée)</label>
                                                <input type="datetime-local" class="form-control" name="maintenance_end" 
                                                    value="<?= $settings['maintenance_end'] ?? '' ?>">
                                            </div>
                                        </div>
                                        <input type="hidden" name="form_type" value="maintenance">
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End main content -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script pour afficher/masquer la clé API
    document.getElementById('showApiKey')?.addEventListener('click', function() {
        const apiKeyField = this.closest('.input-group').querySelector('input');
        if (apiKeyField.type === 'password') {
            apiKeyField.type = 'text';
            this.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
            apiKeyField.type = 'password';
            this.innerHTML = '<i class="bi bi-eye"></i>';
        }
    });
    
    // Initialisation des onglets
    document.addEventListener('DOMContentLoaded', function() {
        // Activer le premier onglet
        const firstTab = document.querySelector('.nav-settings .nav-link.active');
        if (firstTab) {
            const tab = new bootstrap.Tab(firstTab);
            tab.show();
        }
    });
</script>
</body>
</html>