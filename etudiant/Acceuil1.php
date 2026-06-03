<?php
session_start();
require_once __DIR__ . '/../hello/config.php';

// Vérification de la session
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: ../hello/login.php");
    exit();
}

// Récupération des données de l'étudiant
$id_utilisateur = $_SESSION['id_utilisateur'];

// Récupération des statistiques des projets
try {
    // Projets en cours (statut 'en_attente')
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM projet p
        JOIN etudiant e ON p.id_etudiant = e.id_etudiant
        WHERE e.id_etudiant = ? AND p.statut = 'en_attente'
    ");
    $stmt->execute([$id_utilisateur]);
    $en_cours = $stmt->fetchColumn();

    // Projets validés
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM projet p
        JOIN etudiant e ON p.id_etudiant = e.id_etudiant
        WHERE e.id_etudiant = ? AND p.statut = 'valide'
    ");
    $stmt->execute([$id_utilisateur]);
    $valides = $stmt->fetchColumn();

    // Dernière soumission
    $stmt = $db->prepare("
        SELECT MAX(p.date_soumission) FROM projet p
        JOIN etudiant e ON p.id_etudiant = e.id_etudiant
        WHERE e.id_etudiant = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $derniere = $stmt->fetchColumn();

    // Total des projets
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM projet p
        JOIN etudiant e ON p.id_etudiant = e.id_etudiant
        WHERE e.id_etudiant = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $total = $stmt->fetchColumn();

    // Remarques en attente (pas de statut 'a_traiter' dans la table remarque, donc on prend toutes les remarques)
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM remarque r
        JOIN projet p ON r.id_projet = p.id_projet
        JOIN etudiant e ON p.id_etudiant = e.id_etudiant
        WHERE e.id_etudiant = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $remarques_attente = $stmt->fetchColumn();

    $stats = [
        'en_cours' => $en_cours,
        'valides' => $valides,
        'derniere' => $derniere,
        'total' => $total,
        'remarques_attente' => $remarques_attente
    ];

} catch (PDOException $e) {
    // En cas d'erreur, initialiser les stats à 0
    $stats = [
        'en_cours' => 0,
        'valides' => 0,
        'derniere' => null,
        'total' => 0,
        'remarques_attente' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Étudiant - Gestion des Projets ENSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* [Conservez tous vos styles CSS existants] */
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
            height: 100px;
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

        .sidebar-menu a:hover, .sidebar-menu a.active {
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

        .welcome-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .welcome-section h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .welcome-section p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
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

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }

        .stat-card h3 {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .projects-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
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
            
            .welcome-section, .projects-section {
                padding: 1.5rem;
            }
            
            .stats-cards {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
            
            .stats-cards {
                grid-template-columns: 1fr 1fr;
            }
            
            .welcome-section h2 {
                font-size: 1.5rem;
            }
            
            .btn {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        /* Styles pour les très petits écrans */
        @media (max-width: 480px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .section-header .btn {
                margin-top: 1rem;
                align-self: flex-end;
            }
            
            .welcome-section h2 {
                font-size: 1.3rem;
            }
            
            .welcome-section p {
                font-size: 0.9rem;
            }
        }
        .last-projects {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .project-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .project-item:hover {
            background: #e9ecef;
        }
        
        .project-name {
            font-weight: 500;
        }
        
        .project-status {
            padding: 0.3rem 0.8rem;
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
        
        .status-refuse {
            background-color: #e74c3c;
            color: white;
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
                <li><a href="Acceuil1.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="nv_prj.php"><i class="fas fa-plus-circle"></i> Nouveau projet</a></li>
                <li><a href="Mesprojets.php"><i class="fas fa-folder-open"></i> Mes projets</a></li>
                <li><a href="remarques.php"><i class="fas fa-comments"></i> Remarques</a></li>
                <li><a href="actualite.php"><i class="fas fa-bullhorn"></i> Actualités</a></li> <!-- Nouvelle entrée -->
                <li><a href="message.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="param.php"><i class="fas fa-cog"></i> Paramètres</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </aside>
       
        <main class="main-content">
            <section class="welcome-section">
                <h2>Bienvenue dans votre espace étudiant, <?= htmlspecialchars($_SESSION['prenom']) ?> !</h2>
                <p>
                    Gérez tous vos projets académiques en un seul endroit. Soumettez vos rapports de stage, 
                    projets pédagogiques et suivez leur progression.
                </p>
                <a href="nv_prj.php" class="btn"><i class="fas fa-plus"></i> Nouveau Projet</a>
            </section>
            
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Projets en cours</h3>
                    <div class="value"><?= $stats['en_cours'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Projets validés</h3>
                    <div class="value"><?= $stats['valides'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Remarques</h3>
                    <div class="value"><?= $stats['remarques_attente'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Dernière soumission</h3>
                    <div class="value">
                        <?= $stats['derniere'] ? date('d/m/Y', strtotime($stats['derniere'])) : '—' ?>
                    </div>
                </div>
            </div>

            <section class="projects-section">
                <div class="section-header">
                    <h2>Mes projets récents</h2>
                    <a href="Mesprojets.php" class="btn">Voir tout</a>
                </div>
                
                <?php if ($stats['total'] > 0): ?>
                    <?php
                    // Récupérer les 3 derniers projets
                    $stmt = $db->prepare("
                        SELECT p.id_projet, p.titre, p.statut, p.date_soumission
                        FROM projet p
                        JOIN etudiant e ON p.id_etudiant = e.id_etudiant
                        WHERE e.id_etudiant = ?
                        ORDER BY p.date_soumission DESC
                        LIMIT 3
                    ");
                    $stmt->execute([$id_utilisateur]);
                    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="last-projects">
                        <?php foreach ($projets as $projet): ?>
                            <div class="project-item">
                                <span class="project-name"><?= htmlspecialchars($projet['titre']) ?></span>
                                <span class="project-status status-<?= htmlspecialchars($projet['statut']) ?>">
                                    <?php
                                    switch ($projet['statut']) {
                                        case 'en_attente': echo 'En attente'; break;
                                        case 'valide': echo 'Validé'; break;
                                        case 'refuse': echo 'Refusé'; break;
                                        default: echo ucfirst($projet['statut']);
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        Commencez par ajouter votre premier projet.
                    </p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <footer style="background:#2c3e50; color:white; padding:1rem; text-align:center; margin-top:2rem;">
        <p>© <?= date('Y') ?> ENSA Projets - Tous droits réservés</p>
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

        // Surligner le lien actif
        const currentPage = location.pathname.split('/').pop();
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>