<?php
session_start();
include_once __DIR__ . '/../hello/config.php';

// Vérification rôle étudiant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: ../hello/login.php");
    exit();
}

// Récupérer l'ID utilisateur depuis la session
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: ../hello/login.php");
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// Récupérer les paramètres de filtre
$filter_project = $_GET['project'] ?? '';
$filter_teacher = $_GET['teacher'] ?? '';
$filter_date = $_GET['date'] ?? 'recent'; // Valeur par défaut

// Récupérer la liste des projets de l'étudiant
$stmt_projets = $db->prepare("SELECT id_projet, titre FROM projet WHERE id_etudiant = ?");
$stmt_projets->execute([$id_utilisateur]);
$projets = $stmt_projets->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les enseignants qui ont donné des remarques
$stmt_teachers = $db->prepare("
    SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom 
    FROM remarque r
    JOIN enseignant e ON r.id_enseignant = e.id_enseignant
    JOIN utilisateur u ON e.id_enseignant = u.id_utilisateur
    JOIN projet p ON r.id_projet = p.id_projet
    WHERE p.id_etudiant = ?
");
$stmt_teachers->execute([$id_utilisateur]);
$enseignants = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);

// Construire la requête pour les remarques
$sql = "
    SELECT 
        r.id_remarque,
        r.remarque_text,
        r.evaluation,
        r.date_remarque,
        p.id_projet,
        p.titre AS projet_titre,
        u.id_utilisateur,
        u.nom AS enseignant_nom,
        u.prenom AS enseignant_prenom,
        u.email AS enseignant_email
    FROM remarque r
    JOIN projet p ON r.id_projet = p.id_projet
    JOIN enseignant e ON r.id_enseignant = e.id_enseignant
    JOIN utilisateur u ON e.id_enseignant = u.id_utilisateur
    WHERE p.id_etudiant = ?
";

$params = [$id_utilisateur];

// Ajouter les filtres
if (!empty($filter_project)) {
    $sql .= " AND r.id_projet = ?";
    $params[] = $filter_project;
}

if (!empty($filter_teacher)) {
    $sql .= " AND r.id_enseignant = ?";
    $params[] = $filter_teacher;
}

// Ajouter le tri par date
if ($filter_date === 'ancien') {
    $sql .= " ORDER BY r.date_remarque ASC";
} else {
    $sql .= " ORDER BY r.date_remarque DESC"; // Par défaut: plus récent d'abord
}

// Exécuter la requête
$stmt_remarques = $db->prepare($sql);
$stmt_remarques->execute($params);
$remarques = $stmt_remarques->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour convertir la note en étoiles (0-20 → 0-5)
function noteToStars($note) {
    if ($note === null) return 'Non évalué';
    
    $stars = round($note / 4); // Convertit 0-20 en 0-5
    $fullStars = floor($stars);
    $halfStar = ($stars - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $html = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star"></i>';
    }
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star"></i>';
    }
    
    return $html . ' (' . $note . '/20)';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remarques - Gestion des Projets ENSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            padding: 2rem 1rem;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 1;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 1rem;
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
        }

        .sidebar-menu a.active {
            background-color: #3498db;
            color: white;
        }

        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-x: hidden;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .feedback-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .feedback-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .feedback-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .feedback-project {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .feedback-date {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .feedback-teacher {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .teacher-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .teacher-info h4 {
            font-size: 1rem;
            color: #2c3e50;
            margin-bottom: 0.2rem;
        }

        .teacher-info p {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .feedback-content {
            margin-bottom: 1.5rem;
            line-height: 1.6;
            color: #555;
        }

        .feedback-rating {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .rating-label {
            margin-right: 1rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .rating-stars {
            color: #f1c40f;
            font-size: 1.2rem;
        }

        .feedback-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bdc3c7;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .filter-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        /* Menu toggle pour mobile */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
        }

        /* Styles pour les écrans moyens (tablettes) */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
                padding: 1.5rem 0.8rem;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .feedback-container {
                padding: 1.5rem;
            }
            
            .btn {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        /* Styles pour les petits écrans (téléphones) */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                position: relative;
            }
            
            .logo img {
                height: 40px;
            }
            
            .logo-text h1 {
                font-size: 1.2rem;
            }
            
            .logo-text p {
                font-size: 0.8rem;
            }
            
            .user-name {
                display: none;
            }
            
            .user-profile img {
                width: 35px;
                height: 35px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .container {
                position: relative;
            }
            
            .sidebar {
                position: fixed;
                left: 0;
                top: 80px;
                bottom: 0;
                width: 250px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                width: 100%;
                padding: 1rem;
                margin-left: 0;
            }
            
            .page-header h2 {
                font-size: 1.5rem;
            }
            
            .feedback-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .feedback-date {
                align-self: flex-end;
            }
            
            .filters {
                flex-direction: column;
                gap: 1rem;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .feedback-actions {
                justify-content: center;
            }
            
            .feedback-actions .btn {
                width: 100%;
                text-align: center;
                margin-bottom: 0.5rem;
            }
        }

        /* Styles pour les très petits écrans */
        @media (max-width: 480px) {
            .feedback-project {
                font-size: 1.1rem;
            }
            
            .feedback-content {
                font-size: 0.9rem;
            }
            
            .rating-stars {
                font-size: 1rem;
            }
            
            .teacher-avatar {
                width: 35px;
                height: 35px;
            }
            
            .teacher-info h4 {
                font-size: 0.9rem;
            }
        }
        footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    text-align: center;
    padding: 15px 0;
    background :linear-gradient(135deg, #2c3e50, #3498db);
    border-top: 1px solid #e9ecef;
    z-index: 100;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

footer a, footer p {
    margin: 0;
    padding: 0;
}
        .remarque-card {
            border-left: 4px solid #3498db;
        }
        
        .status-a_traiter {
            background-color: #f39c12;
        }
        
        .status-traite {
            background-color: #2ecc71;
        }
        
        .status-rejete {
            background-color: #e74c3c;
        }
        .rating-stars {
            color: #f1c40f;
            font-size: 1.2rem;
            margin-left: 0.5rem;
        }
        
        .note-value {
            font-weight: bold;
            color: #2c3e50;
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
                <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>
            </span>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li><a href="Acceuil1.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="nv_prj.php"><i class="fas fa-plus-circle"></i> Nouveau projet</a></li>
                <li><a href="Mesprojets.php"><i class="fas fa-folder-open"></i> Mes projets</a></li>
                <li><a href="#" class="active"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="actualite.php"><i class="fas fa-bullhorn"></i> Actualités</a></li> <!-- Nouvelle entrée -->
                <li><a href="message.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="param.php"><i class="fas fa-cog"></i> Paramètres</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-comments"></i> Remarques des Enseignants</h2>
        </div>

        <div class="feedback-container">
            <form method="get" action="" class="filters">
                <div class="filter-group">
                    <label for="filter-project">Projet</label>
                    <select id="filter-project" name="project" class="filter-control">
                        <option value="">Tous les projets</option>
                        <?php foreach ($projets as $projet): ?>
                            <option value="<?= $projet['id_projet'] ?>" 
                                <?= ($filter_project == $projet['id_projet']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($projet['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-teacher">Enseignant</label>
                    <select id="filter-teacher" name="teacher" class="filter-control">
                        <option value="">Tous les enseignants</option>
                        <?php foreach ($enseignants as $enseignant): ?>
                            <option value="<?= $enseignant['id_utilisateur'] ?>" 
                                <?= ($filter_teacher == $enseignant['id_utilisateur']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($enseignant['prenom'] . ' ' . $enseignant['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-date">Trier par</label>
                    <select id="filter-date" name="date" class="filter-control">
                        <option value="recent" <?= ($filter_date == 'recent') ? 'selected' : '' ?>>Plus récent</option>
                        <option value="ancien" <?= ($filter_date == 'ancien') ? 'selected' : '' ?>>Plus ancien</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn" name="filter" value="1">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="remarques.php" class="btn-secondary">
                        <i class="fas fa-sync-alt"></i> Réinitialiser
                    </a>
                </div>
            </form>

            <?php if (empty($remarques)): ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <h3>Aucune remarque disponible</h3>
                    <p>Vous n'avez pas encore reçu de remarques sur vos projets.</p>
                </div>
            <?php else: ?>
                <?php foreach ($remarques as $remarque): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="feedback-project">
                                <?= htmlspecialchars($remarque['projet_titre']) ?>
                            </div>
                            <div class="feedback-date">
                                <?= date('d/m/Y H:i', strtotime($remarque['date_remarque'])) ?>
                            </div>
                        </div>
                        <div class="feedback-teacher">
                            <div class="teacher-info">
                                <h4><?= htmlspecialchars($remarque['enseignant_prenom'] . ' ' . $remarque['enseignant_nom']) ?></h4>
                                <p><?= htmlspecialchars($remarque['enseignant_email']) ?></p>
                            </div>
                        </div>
                        <?php if (isset($remarque['evaluation'])): ?>
                            <div class="feedback-rating">
                                <span class="rating-label">Évaluation:</span>
                                <span class="rating-stars">
                                    <?= noteToStars($remarque['evaluation']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="feedback-content">
                            <p><?= nl2br(htmlspecialchars($remarque['remarque_text'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    </div>

    <footer>
        <div style="text-align: center; width: 100%;">
            <p style="margin-top:0.5rem;">© <?= date('Y') ?> ENSA Projets</p>
        </div>
    </footer>

    <script>
        // Gestion du menu mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Fermer le menu si on clique à l'extérieur
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (!sidebar.contains(event.target) && event.target !== menuToggle) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>