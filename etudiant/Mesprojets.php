<?php
session_start();

require_once __DIR__ . '/../hello/config.php';

// Vérification de la session et du rôle
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: ../hello/login.php");
    exit();
}

if ($_SESSION['role'] !== 'etudiant') {
    header("Location: ../hello/unauthorized.php");
    exit();
}

// Récupération des données de l'étudiant
try {
    $stmt = $db->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['id_utilisateur']]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$etudiant) {
        die("Étudiant introuvable.");
    }
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

// Récupération des filtres
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$annee_filter = $_GET['annee'] ?? '';
$search_filter = $_GET['search'] ?? '';

// Construction de la requête SQL avec filtres
$sql = "
    SELECT p.*, 
           ue.prenom AS enseignant_prenom, ue.nom AS enseignant_nom,
           GROUP_CONCAT(DISTINCT m.nom_module SEPARATOR ', ') AS modules,
           GROUP_CONCAT(DISTINCT mc.terme SEPARATOR ', ') AS mots_cles,
           m.semestre,
           CONCAT(m.annee_module) AS annee_scolaire
    FROM projet p
    LEFT JOIN enseignant e ON p.id_enseignant = e.id_enseignant
    LEFT JOIN utilisateur ue ON e.id_enseignant = ue.id_utilisateur
    LEFT JOIN projet_module pm ON p.id_projet = pm.id_projet
    LEFT JOIN module m ON pm.id_module = m.id_module
    LEFT JOIN projet_mot_cle pmc ON p.id_projet = pmc.id_projet
    LEFT JOIN mot_cle mc ON pmc.id_mot_cle = mc.id_mot_cle
    WHERE p.id_etudiant = ?
";

// Ajout des conditions de filtrage
$params = [$_SESSION['id_utilisateur']];

if (!empty($type_filter)) {
    $sql .= " AND p.type_projet = ?";
    $params[] = $type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND p.statut = ?";
    $params[] = $status_filter;
}

if (!empty($annee_filter)) {
    $sql .= " AND m.annee_module LIKE ?";
    $params[] = "%$annee_filter%";
}

if (!empty($search_filter)) {
    $sql .= " AND (p.titre LIKE ? OR p.description LIKE ? OR mc.terme LIKE ?)";
    $search_term = "%$search_filter%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " GROUP BY p.id_projet ORDER BY p.date_soumission DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération projets: " . $e->getMessage());
    die("Erreur lors de la récupération des projets.");
}

// Récupération des années disponibles pour le filtre
try {
    $stmt = $db->query("SELECT DISTINCT annee_module FROM module ORDER BY annee_module DESC");
    $annees = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $annees = [];
}

// Fonctions utilitaires
function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf': return 'fa-file-pdf';
        case 'doc':
        case 'docx': return 'fa-file-word';
        case 'ppt':
        case 'pptx': return 'fa-file-powerpoint';
        case 'zip':
        case 'rar': return 'fa-file-archive';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif': return 'fa-file-image';
        default: return 'fa-file';
    }
}

function getFileName($path) {
    return basename($path);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Projets - Gestion des Projets ENSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-color: #f5f7fa;
            --dark-color: #333;
            --gray-color: #95a5a6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
            display: none;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 50px;
            margin-right: 15px;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .logo-text p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid white;
        }

        .user-name {
            font-weight: 500;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        .sidebar {
            width: 250px;
            background-color: white;
            padding: 1.5rem 1rem;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: #555;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background-color: #3498db;
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background-color: #3498db;
            color: white;
            font-weight: 500;
        }

        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: white;
            margin: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        a{
            text-decoration: none;
            text-align: center;
            border-radius: 5px;
        }
        .page-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .page-header h2 i {
            margin-right: 10px;
            color: #3498db;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.5rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 8px;
        }

        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-secondary {
            background-color: #95a5a6;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-success {
            background-color: #2ecc71;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-warning {
            background-color: #f39c12;
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .filter-control {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .project-list {
            display: grid;
            gap: 1.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #777;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: #555;
        }

        .project-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .project-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .project-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .project-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .project-meta i {
            color: #3498db;
        }

        .project-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-en_attente {
            background-color: #f1c40f;
            color: white;
        }

        .status-valide {
            background-color: #2ecc71;
            color: white;
        }

        .status-rejete {
            background-color: #e74c3c;
            color: white;
        }

        .project-description {
            color: #555;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .project-files {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .file-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.8rem;
            background-color: #f8f9fa;
            border-radius: 20px;
            font-size: 0.8rem;
            gap: 0.5rem;
        }

        .file-badge i {
            color: #3498db;
        }

        .project-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #3498db;
        }

        .page-link:hover {
            background-color: #f8f9fa;
        }

        .page-link.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        footer {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            text-align: center;
            margin-top: 2rem;
        }

        footer a {
            color: #3498db;
            text-decoration: none;
        }

        @media (max-width: 992px) {
            .filters {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                position: fixed;
                left: -250px;
                top: 80px;
                bottom: 0;
                width: 250px;
                z-index: 1000;
            }

            .sidebar.active {
                transform: translateX(250px);
            }

            .main-content {
                margin-left: 0;
                margin-top: 0;
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .project-actions {
                flex-direction: column;
            }

            .project-actions .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .project-header {
                flex-direction: column;
                gap: 1rem;
            }

            .project-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .project-status {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="logo">
            <i class="fas fa-project-diagram"></i>
            <div class="logo-text">
                <h1>Synergia</h1>
                <p>Gestion des Projets Étudiants</p>
            </div>
        </div>
        <div class="user-profile">
            <img src="images/default-profile.png" alt="Profil étudiant">
            <span class="user-name">
                <?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>
            </span>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li><a href="Acceuil1.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="nv_prj.php"><i class="fas fa-plus-circle"></i> Nouveau projet</a></li>
                <li><a href="Mesprojets.php" class="active"><i class="fas fa-folder-open"></i> Mes projets</a></li>
                <li><a href="remarques.php"><i class="fas fa-comments"></i> Remarques</a></li>
                <li><a href="actualite.php"><i class="fas fa-bullhorn"></i> Actualités</a></li> <!-- Nouvelle entrée -->
                <li><a href="message.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="param.php"><i class="fas fa-cog"></i> Paramètres</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-folder-open"></i> Mes Projets</h2>
                <a href="nv_prj.php" class="btn"><i class="fas fa-plus"></i> Nouveau Projet</a>
            </div>

            <form method="get" action="Mesprojets.php" class="filters">
                <div class="filter-group">
                    <label for="filter-type">Type de projet</label>
                    <select id="filter-type" name="type" class="filter-control">
                        <option value="">Tous les types</option>
                        <option value="Stage d'initiation" <?= ($type_filter == "Stage d'initiation") ? 'selected' : '' ?>>Stage d'initiation</option>
                        <option value="Stage d'ingénieur adjoint" <?= ($type_filter == "Stage d'ingénieur adjoint") ? 'selected' : '' ?>>Stage ingénieur adjoint</option>
                        <option value="Stage de fin d'études - PFE" <?= ($type_filter == "Stage de fin d'études - PFE") ? 'selected' : '' ?>>PFE</option>
                        <option value="projet pédagogique" <?= ($type_filter == "projet pédagogique") ? 'selected' : '' ?>>Projet pédagogique</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-status">Statut</label>
                    <select id="filter-status" name="status" class="filter-control">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?= ($status_filter == "en_attente") ? 'selected' : '' ?>>En attente</option>
                        <option value="valide" <?= ($status_filter == "valide") ? 'selected' : '' ?>>Validé</option>
                        <option value="rejete" <?= ($status_filter == "rejete") ? 'selected' : '' ?>>Rejeté</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-year">Année</label>
                    <select id="filter-year" name="annee" class="filter-control">
                        <option value="">Toutes les années</option>
                        <?php foreach ($annees as $annee): ?>
                            <option value="<?= htmlspecialchars($annee) ?>" <?= ($annee_filter == $annee) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($annee) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-search">Recherche</label>
                    <input type="text" id="filter-search" name="search" class="filter-control" 
                           placeholder="Rechercher un projet..." value="<?= htmlspecialchars($search_filter) ?>">
                </div>
                <div class="filter-group" style="grid-column: 1 / -1; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrer</button>
                    <a href="Mesprojets.php" class="btn-secondary"><i class="fas fa-sync-alt"></i> Réinitialiser</a>
                </div>
            </form>
               
            <div class="project-list">
                <?php if (empty($projets)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>Aucun projet trouvé</h3>
                        <p>Vous n'avez pas encore de projets ou aucun projet ne correspond à vos critères de recherche.</p>
                        <a href="nv_prj.php" class="btn" style="margin-top: 1rem;"><i class="fas fa-plus"></i> Créer un nouveau projet</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($projets as $projet): ?>
                        <div class="project-card">
                            <div class="project-header">
                                <div>
                                    <h3 class="project-title"><?= htmlspecialchars($projet['titre']) ?></h3>
                                    <div class="project-meta">
                                        <span>
                                            <i class="fas <?= ($projet['type_projet'] == 'projet pédagogique') ? 'fa-book' : 'fa-briefcase' ?>"></i> 
                                            <?= htmlspecialchars($projet['type_projet']) ?>
                                        </span>
                                        <?php if (!empty($projet['modules'])): ?>
                                            <span><i class="fas fa-layer-group"></i> <?= htmlspecialchars($projet['modules']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($projet['annee_scolaire'])): ?>
                                            <span><i class="fas fa-calendar"></i> <?= htmlspecialchars($projet['annee_scolaire']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($projet['enseignant_prenom']) && !empty($projet['enseignant_nom'])): ?>
                                            <span>
                                                <i class="fas fa-user-tie"></i> 
                                                <?= htmlspecialchars($projet['enseignant_prenom'] . ' ' . $projet['enseignant_nom']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="project-status status-<?= htmlspecialchars($projet['statut']) ?>">
                                    <?php 
                                    switch ($projet['statut']) {
                                        case 'en_attente': echo "En attente"; break;
                                        case 'valide': echo "Validé"; break;
                                        case 'rejete': echo "Rejeté"; break;
                                        default: echo ucfirst($projet['statut']);
                                    }
                                    ?>
                                </span>
                            </div>
                            <p class="project-description">
                                <?= nl2br(htmlspecialchars($projet['description'])) ?>
                            </p>
                            
                            <?php if (!empty($projet['mots_cles'])): ?>
                                <div class="project-files">
                                    <span class="file-badge">
                                        <i class="fas fa-tags"></i> 
                                        <?= htmlspecialchars($projet['mots_cles']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="project-actions">
                                <a href="detail_projet.php?id=<?= $projet['id_projet'] ?>" class="btn"><i class="fas fa-eye"></i> Voir détails</a>
    
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="pagination">
                <a href="#" class="page-link">&laquo;</a>
                <a href="#" class="page-link active">1</a>
                <a href="#" class="page-link">2</a>
                <a href="#" class="page-link">3</a>
                <a href="#" class="page-link">&raquo;</a>
            </div>
        </main>
    </div>

    <footer>
        <a href="full-access.html">
            <i class="fas fa-expand"></i> Accès complet
        </a>
        <p>© <?= date('Y') ?> ENSA Projets</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion du menu toggle pour mobile
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            // Fermer le menu si on clique à l'extérieur
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && event.target !== menuToggle) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Filtrage côté client (optionnel)
            const filterSearch = document.getElementById('filter-search');
            const projectCards = document.querySelectorAll('.project-card');
            
            if (filterSearch && projectCards.length > 0) {
                filterSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    projectCards.forEach(card => {
                        const title = card.querySelector('.project-title').textContent.toLowerCase();
                        const description = card.querySelector('.project-description').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || description.includes(searchTerm)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>