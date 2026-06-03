<?php
session_start();

// Connexion à la base de données
require_once '../hello/config.php';


// Récupération des paramètres de filtre
$selectedModule = isset($_GET['module']) ? trim($_GET['module']) : '';
$selectedAnnee = isset($_GET['annee']) ? trim($_GET['annee']) : '';
$selectedMotCle = isset($_GET['mot_cle']) ? trim($_GET['mot_cle']) : '';
$selectedTypeStage = isset($_GET['type_stage']) ? trim($_GET['type_stage']) : '';
$selectedFiliere = isset($_GET['filiere']) ? trim($_GET['filiere']) : '';

// Récupérer les listes pour les filtres
$modules = $db->query("SELECT id_module, CONCAT(nom_module, ' (S', semestre, ')') AS module FROM module ORDER BY nom_module")->fetchAll(PDO::FETCH_ASSOC);
$motsCles = $db->query("SELECT id_mot_cle, terme FROM mot_cle ORDER BY terme")->fetchAll(PDO::FETCH_ASSOC);
$typesStage = $db->query("SELECT DISTINCT type_projet FROM projet WHERE type_projet LIKE '%stage%' ORDER BY type_projet")->fetchAll(PDO::FETCH_COLUMN);
$filieres = $db->query("SELECT DISTINCT filiere FROM etudiant ORDER BY filiere")->fetchAll(PDO::FETCH_COLUMN);
$annees = $db->query("SELECT DISTINCT YEAR(date_soumission) as annee FROM projet ORDER BY annee DESC")->fetchAll(PDO::FETCH_COLUMN);

// Nouveau paramètre de recherche
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Modifier la requête SQL pour inclure la recherche
try {
    $query = "SELECT 
        p.*,
        ue.nom AS etudiant_nom, 
        ue.prenom AS etudiant_prenom,
        ue.email AS etudiant_email,
        e.cne, 
        e.filiere,
        e.annee_scolaire,
        uen.nom AS enseignant_nom, 
        uen.prenom AS enseignant_prenom,
        GROUP_CONCAT(DISTINCT mk.terme SEPARATOR ', ') AS mots_cles,
        GROUP_CONCAT(DISTINCT CONCAT(m.nom_module, ' (S', m.semestre, ')') SEPARATOR ', ') AS modules,
        GROUP_CONCAT(DISTINCT CONCAT(l.type_livrable, ':', l.chemin_fichier) SEPARATOR '|') AS livrables,
        (SELECT COUNT(*) FROM likes WHERE id_projet = p.id_projet) AS likes_count,
        (SELECT COUNT(*) FROM likes WHERE id_projet = p.id_projet AND id_utilisateur = :current_user) AS user_liked
      FROM projet p
      JOIN etudiant e ON p.id_etudiant = e.id_etudiant
      JOIN utilisateur ue ON e.id_etudiant = ue.id_utilisateur
      LEFT JOIN enseignant en ON p.id_enseignant = en.id_enseignant
      LEFT JOIN utilisateur uen ON en.id_enseignant = uen.id_utilisateur
      LEFT JOIN projet_mot_cle pmk ON p.id_projet = pmk.id_projet
      LEFT JOIN mot_cle mk ON pmk.id_mot_cle = mk.id_mot_cle
      LEFT JOIN projet_module pmod ON p.id_projet = pmod.id_projet
      LEFT JOIN module m ON pmod.id_module = m.id_module
      LEFT JOIN livrable l ON p.id_projet = l.id_projet
      WHERE p.statut = 'valide'";
    
    $params = [':current_user' => $_SESSION['id_utilisateur']];
    
    // Ajouter la recherche par étudiant si un terme est saisi
    if (!empty($searchTerm)) {
        $query .= " AND (ue.nom LIKE :search OR ue.prenom LIKE :search OR e.cne LIKE :search OR ue.email LIKE :search)";
        $params[':search'] = "%$searchTerm%";
    }
    
    
    // Ajouter les filtres supplémentaires
    if (!empty($selectedModule)) {
        $query .= " AND m.id_module = :module";
        $params[':module'] = $selectedModule;
    }
    
    if (!empty($selectedMotCle)) {
        $query .= " AND mk.id_mot_cle = :mot_cle";
        $params[':mot_cle'] = $selectedMotCle;
    }
    
    if (!empty($selectedTypeStage)) {
        $query .= " AND p.type_projet = :type_stage";
        $params[':type_stage'] = $selectedTypeStage;
    }
    
    if (!empty($selectedFiliere)) {
        $query .= " AND e.filiere = :filiere";
        $params[':filiere'] = $selectedFiliere;
    }
    
    if (!empty($selectedAnnee)) {
        $query .= " AND YEAR(p.date_soumission) = :annee";
        $params[':annee'] = $selectedAnnee;
    }
    
    $query .= " GROUP BY p.id_projet ORDER BY p.date_soumission DESC";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la recherche: " . $e->getMessage();
    error_log($error);
}

// Traitement du like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_project'])) {
    $id_projet = $_POST['id_projet'];
    $id_utilisateur = $_SESSION['id_utilisateur'];
    
    try {
        // Vérifier si l'utilisateur a déjà liké ce projet
        $check = $db->prepare("SELECT * FROM likes WHERE id_projet = ? AND id_utilisateur = ?");
        $check->execute([$id_projet, $id_utilisateur]);
        
        if ($check->rowCount() > 0) {
            // Retirer le like
            $db->prepare("DELETE FROM likes WHERE id_projet = ? AND id_utilisateur = ?")->execute([$id_projet, $id_utilisateur]);
        } else {
            // Ajouter le like
            $db->prepare("INSERT INTO likes (id_projet, id_utilisateur) VALUES (?, ?)")->execute([$id_projet, $id_utilisateur]);
        }
        
        header("Location: ".$_SERVER['PHP_SELF']."?module=".urlencode($selectedModule)."&annee=".urlencode($selectedAnnee)."&mot_cle=".urlencode($selectedMotCle)."&type_stage=".urlencode($selectedTypeStage)."&filiere=".urlencode($selectedFiliere));
        exit();
    } catch (PDOException $e) {
        $error = "Erreur lors du traitement du like: " . $e->getMessage();
        error_log($error);
    }
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergia - Consultation des Projets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../teacher/etudiant.css" rel="stylesheet">
    <style>
         
        .like-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--secondary);
            transition: all 0.2s;
        }
        
        .like-btn:hover {
            color: red;
        }
        
        .like-btn.liked {
            color: red;
        }
        
        .like-count {
            font-size: 0.9rem;
            color: var(--secondary);
            margin-left: 0.3rem;
        }
        .sidebar{
            background-color: white;
        }
        .sidebar {
        color: white;
    }
    .sidebar a {
        color:  #2980b9;
    }
    .sidebar .nav-link {
        color:  #2980b9;
    }
    .sidebar .brand-text {
        color:  #2980b9;
    }
    .sidebar .theme-btn {
        color:   #2980b9;
    }
    .sidebar .nav-link:hover {
    color: #f0f0f0 !important;
    background-color: #2980b9;
}
 .sidebar .nav-link.active {
    color: #f0f0f0 !important;
    background-color: #2980b9;
}


    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a id="mobileMenu_back" class="sidebar-brand" style="text-decoration: none;">
            <div class="brand-logo"><i class="fas fa-project-diagram"></i></div>
            <span class="brand-text">Synergia</span>
        </a>
        <nav class="nav flex-column">
            <a href="Acceuil1.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
            <a href="#" class="nav-link active "><i class="fas fa-compass me-2"></i>Actualité</a>
            <a href="nv prj.php" class="nav-link"><i class="fas fa-plus-circle"></i> Nouveau projet</a></li>
            <a href="Mesprojets.php" class="nav-link"><i class="fas fa-folder-open"></i> Mes projets</a></li>
            <a href="feedback.php" class="nav-link "><i class="fas fa-comments"></i> Feedback</a></li>
            <a href="message.php" class="nav-link"><i class="fas fa-envelope me-2"></i>Messages</a>
            <a href="param.php" class="nav-link "><i class="fas fa-cog"></i> Paramètres</a></li>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </nav>
        <div class="theme-switch mt-auto">
            <button class="theme-btn" id="themeToggle"><i class="fas fa-moon"></i></button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <nav class="topbar navbar navbar-expand">
            <button class="btn btn-icon" id="mobileMenu"><i class="fas fa-bars text-secondary"></i></button>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3"><?php echo $_SESSION['prenom'].' '.$_SESSION['nom']; ?></span>
                <div class="dropdown">
                    <button class="btn btn-icon" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fs-4 text-primary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Contenu principal -->
        <div class="container py-5 flex-grow-1">
            <!-- Section Bienvenue -->
            <div class="welcome-card card animate mb-5">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-8 text-center text-lg-start">
                            <h2 class="display-5 fw-bold mb-3"><span class="text-primary">Bienvenue </span> <span id="teacherName"><?php echo $_SESSION['prenom'].' '.$_SESSION['nom']; ?></span> !</h2>
                            <p class="lead mb-4">Vous pouvez consulter tous les projets, suivre les étudiants et collaborer avec vos collègues.</p>
                            
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="fw-bold">Consultation des Projets Validés</h2>
                    <p>Filtrez et consultez les projets validés selon vos critères</p>
                </div>
            </div>

            <!-- Filtres -->
            <div class="search-box card mb-4">
                <form method="GET" action="">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <label for="module" class="form-label">Module</label>
                            <select class="form-select" name="module" id="module">
                                <option value="">Tous les modules</option>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?= $module['id_module'] ?>" <?= $selectedModule == $module['id_module'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($module['module']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Nouveau champ de recherche -->
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <label for="search" class="form-label">Recherche étudiant</label>
                            <input type="text" class="form-control" name="search" id="search" style=" background-color: var(--background);color: var(--text-primary); border: 1px solid var(--border-color);"
                                placeholder="Nom, prénom, CNE ou email" value="<?= htmlspecialchars($searchTerm) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <label for="annee" class="form-label">Année</label>
                            <select class="form-select" name="annee" id="annee">
                                <option value="">Toutes les années</option>
                                <?php foreach ($annees as $annee): ?>
                                    <option value="<?= htmlspecialchars($annee) ?>" <?= $selectedAnnee == $annee ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($annee) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <label for="mot_cle" class="form-label">Mot-clé</label>
                            <select class="form-select" name="mot_cle" id="mot_cle">
                                <option value="">Tous les mots-clés</option>
                                <?php foreach ($motsCles as $motCle): ?>
                                    <option value="<?= $motCle['id_mot_cle'] ?>" <?= $selectedMotCle == $motCle['id_mot_cle'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($motCle['terme']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <label for="type_stage" class="form-label">Type de stage</label>
                            <select class="form-select" name="type_stage" id="type_stage">
                                <option value="">Tous les types</option>
                                <?php foreach ($typesStage as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>" <?= $selectedTypeStage === $type ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                            <label for="filiere" class="form-label">Filière</label>
                            <select class="form-select" name="filiere" id="filiere">
                                <option value="">Toutes les filières</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?= htmlspecialchars($filiere) ?>" <?= $selectedFiliere === $filiere ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($filiere) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-4 col-md-6 col-sm-12 d-flex align-items-end mb-3">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-rocket me-2"></i> Chercher
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if (empty($projects)): ?>
                <div class="alert alert-info">
                    Aucun projet validé trouvé pour les critères sélectionnés
                    <?php 
                    $filters = [];
                    if (!empty($selectedModule)) {
                        foreach ($modules as $m) {
                            if ($m['id_module'] == $selectedModule) {
                                $filters[] = "module: " . htmlspecialchars($m['module']);
                                break;
                            }
                        }
                    }
                    if (!empty($selectedMotCle)) {
                        foreach ($motsCles as $m) {
                            if ($m['id_mot_cle'] == $selectedMotCle) {
                                $filters[] = "mot-clé: " . htmlspecialchars($m['terme']);
                                break;
                            }
                        }
                    }
                    if (!empty($selectedTypeStage)) $filters[] = "type: " . htmlspecialchars($selectedTypeStage);
                    if (!empty($selectedFiliere)) $filters[] = "filière: " . htmlspecialchars($selectedFiliere);
                    if (!empty($selectedAnnee)) $filters[] = "année: " . htmlspecialchars($selectedAnnee);
                    
                    if (!empty($filters)) {
                        echo " (" . implode(", ", $filters) . ")";
                    }
                    ?>
                </div>
            <?php elseif (!empty($projects)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>
                        Projets validés
                        <?php if (!empty($selectedModule)) {
                            foreach ($modules as $m) {
                                if ($m['id_module'] == $selectedModule) {
                                    echo " - Module: " . htmlspecialchars($m['module']);
                                    break;
                                }
                            }
                        } ?>
                        <?php if (!empty($selectedMotCle)) {
                            foreach ($motsCles as $m) {
                                if ($m['id_mot_cle'] == $selectedMotCle) {
                                    echo " - Mot-clé: " . htmlspecialchars($m['terme']);
                                    break;
                                }
                            }
                        } ?>
                        <?php if (!empty($selectedTypeStage)) echo " - Type: " . htmlspecialchars($selectedTypeStage); ?>
                        <?php if (!empty($selectedFiliere)) echo " - Filière: " . htmlspecialchars($selectedFiliere); ?>
                        <?php if (!empty($selectedAnnee)) echo " - Année: " . htmlspecialchars($selectedAnnee); ?>
                    </h4>
                    <span class="badge bg-primary"><?= count($projects) ?> projet(s)</span>
                </div>
                
                <div class="table-responsive overflow-auto">
                    <table class="table table-hover tableau">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Projet</th>
                                <th>Type</th>
                                <th>Modules</th>
                                <th>Mots-clés</th>
                                <th>Livrables</th>
                                <th>Date</th>
                                <th>Actions</th>
                                <th>Likes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($project['etudiant_prenom'] . ' ' . $project['etudiant_nom']) ?></strong><br>
                                        <small>CNE: <?= htmlspecialchars($project['cne']) ?></small><br>
                                        <small><?= htmlspecialchars($project['filiere']) ?> (<?= htmlspecialchars($project['annee_scolaire']) ?>)</small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($project['titre']) ?></strong><br>
                                        <small><?= htmlspecialchars($project['sujet']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($project['type_projet']) ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($project['modules'])) {
                                            echo htmlspecialchars($project['modules']);
                                        } else {
                                            echo '<span>Aucun module</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($project['mots_cles'])) {
                                            $keywords = explode(', ', $project['mots_cles']);
                                            foreach ($keywords as $keyword) {
                                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(trim($keyword)) . '</span>';
                                            }
                                        } else {
                                            echo '<span>Aucun mot-clé</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($project['livrables'])) {
                                            $livrables = explode('|', $project['livrables']);
                                            foreach ($livrables as $livrable) {
                                                list($type, $chemin) = explode(':', $livrable);
                                                echo '
                                                    <div class="mb-2">
                                                        <a href="download.php?file=' . urlencode($chemin) . '" class="badge bg-primary text-decoration-none" download>
                                                            <i class="fas fa-download me-1"></i>' . htmlspecialchars($type) . '
                                                        </a>
                                                    </div>';
                                            }
                                        } else {
                                            echo '<span>Aucun livrable</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($project['date_soumission'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailsModal<?= $project['id_projet'] ?>">
                                            <i class="fas fa-eye"></i> Détails
                                        </button>
                                    </td>
                                    <td>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="id_projet" value="<?= $project['id_projet'] ?>">
                                            <button type="submit" name="like_project" class="like-btn <?= isset($project['user_liked']) && $project['user_liked'] ? 'liked' : '' ?>">
                                                <i class="fas fa-heart"></i>
                                                <span class="like-count"><?= $project['likes_count'] ?></span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal pour les détails complets -->
                                <div class="modal fade" id="detailsModal<?= $project['id_projet'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Détails du projet</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-4">
                                                    <div class="col-md-6">
                                                        <h6>Informations sur l'étudiant</h6>
                                                        <p>
                                                            <strong>Nom:</strong> <?= htmlspecialchars($project['etudiant_prenom'] . ' ' . $project['etudiant_nom']) ?><br>
                                                            <strong>CNE:</strong> <?= htmlspecialchars($project['cne']) ?><br>
                                                            <strong>Filière:</strong> <?= htmlspecialchars($project['filiere']) ?><br>
                                                            <strong>Année scolaire:</strong> <?= htmlspecialchars($project['annee_scolaire']) ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Informations sur le projet</h6>
                                                        <p>
                                                            <strong>Titre:</strong> <?= htmlspecialchars($project['titre']) ?><br>
                                                            <strong>Type:</strong> <?= htmlspecialchars($project['type_projet']) ?><br>
                                                            <strong>Sujet:</strong> <?= htmlspecialchars($project['sujet']) ?><br>
                                                            <strong>Date soumission:</strong> <?= date('d/m/Y', strtotime($project['date_soumission'])) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-4">
                                                    <h6>Description</h6>
                                                    <div class="card card-body ">
                                                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($project['enseignant_nom'])): ?>
                                                    <div class="mb-3">
                                                        <h6>Encadrant</h6>
                                                        <p>
                                                            <?= htmlspecialchars($project['enseignant_prenom'] . ' ' . $project['enseignant_nom']) ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <h6>Mots-clés</h6>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <?php 
                                                            if (!empty($project['mots_cles'])) {
                                                                $keywords = explode(', ', $project['mots_cles']);
                                                                foreach ($keywords as $keyword) {
                                                                    echo '<span class="badge bg-secondary">' . htmlspecialchars(trim($keyword)) . '</span>';
                                                                }
                                                            } else {
                                                                echo '<span>Aucun mot-clé</span>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-3">
                                                        <h6>Modules associés</h6>
                                                        <div>
                                                            <?php 
                                                            if (!empty($project['modules'])) {
                                                                echo htmlspecialchars($project['modules']);
                                                            } else {
                                                                echo '<span>Aucun module associé</span>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6>Livrables</h6>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php 
                                                        if (!empty($project['livrables'])) {
                                                            $livrables = explode('|', $project['livrables']);
                                                            foreach ($livrables as $livrable) {
                                                                list($type, $chemin) = explode(':', $livrable);
                                                                echo '
                                                                    <a href="download.php?file=' . urlencode($chemin) . '" class="badge bg-primary text-decoration-none" download>
                                                                        <i class="fas fa-download me-1"></i>' . htmlspecialchars($type) . '
                                                                    </a>';
                                                            }
                                                        } else {
                                                            echo '<span>Aucun livrable</span>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="py-4 mt-auto">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div class="mb-3 mb-md-0">
                        <p class="mb-0 small">&copy; 2025 Synergia. Tous droits réservés.</p>
                    </div>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-decoration-none text-secondary"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-decoration-none text-secondary"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="text-decoration-none text-secondary"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-decoration-none text-secondary"><i class="fab fa-x"></i></a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du thème
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

        // Vérifie le thème stocké ou le préféré par le système
        const savedTheme = localStorage.getItem('theme') || 
                         (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        html.setAttribute('data-theme', savedTheme);

        // Met à jour l'icône
        themeToggle.innerHTML = savedTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';

        // Gère le basculement du thème
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            themeToggle.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });

        // Menu mobile
        document.getElementById('mobileMenu').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        document.getElementById('mobileMenu_back').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    </script>
</body>
</html>