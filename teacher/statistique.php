<?php
session_start();

// Connexion à la base de données
require_once '../hello/config.php';

// Vérification de la session
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'enseignant') {
    die("Accès non autorisé");
}

// Récupération de l'ID de l'enseignant connecté
$id_enseignant = $_SESSION['id_utilisateur'];

// Traitement POST pour les remarques
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_remarque'])) {
    $id_projet = $_POST['id_projet'];
    
    // Vérifier que l'enseignant est bien l'encadrant du projet
    $stmt = $db->prepare("SELECT id_projet FROM projet WHERE id_projet = ? AND id_enseignant = ?");
    $stmt->execute([$id_projet, $id_enseignant]);
    
    if ($stmt->rowCount() === 1) {
        // Supprimer les remarques existantes
        $db->prepare("DELETE FROM remarque WHERE id_projet = ?")->execute([$id_projet]);
        
        // Ajouter la nouvelle remarque
        $stmt = $db->prepare("INSERT INTO remarque (id_projet, id_enseignant, remarque_text, evaluation) 
                            VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id_projet,
            $id_enseignant,
            $_POST['remarque_text'],
            $_POST['evaluation']
        ]);
        
        $_SESSION['success_message'] = "Remarque enregistrée avec succès";
    } else {
        $_SESSION['error_message'] = "Vous n'êtes pas l'encadrant de ce projet";
    }
    
    header("Location: ".$_SERVER['PHP_SELF']."?filiere=".urlencode($selectedFiliere)."&annee=".urlencode($selectedAnnee)."&mois=".urlencode($selectedMois)."&statut=".urlencode($selectedStatut)."&type_projet=".urlencode($selectedTypeProjet));
    exit();
}

// Récupérer les paramètres de filtre
$selectedFiliere = isset($_GET['filiere']) ? trim($_GET['filiere']) : '';
$selectedAnnee = isset($_GET['annee']) ? trim($_GET['annee']) : '';
$selectedMois = isset($_GET['mois']) ? trim($_GET['mois']) : '';
$selectedStatut = isset($_GET['statut']) ? trim($_GET['statut']) : '';
$selectedTypeProjet = isset($_GET['type_projet']) ? trim($_GET['type_projet']) : '';
$projects = [];

// Récupérer les listes pour les filtres
$filieres = $db->query("SELECT DISTINCT filiere FROM etudiant ORDER BY filiere")->fetchAll(PDO::FETCH_COLUMN);
$typesProjet = $db->query("SELECT DISTINCT type_projet FROM projet ORDER BY type_projet")->fetchAll(PDO::FETCH_COLUMN);
$annees = $db->query("SELECT DISTINCT YEAR(date_soumission) as annee FROM projet ORDER BY annee DESC")->fetchAll(PDO::FETCH_COLUMN);
$mois = $db->query("SELECT DISTINCT MONTH(date_soumission) as mois FROM projet ORDER BY mois")->fetchAll(PDO::FETCH_COLUMN);
$statuts = $db->query("SELECT DISTINCT statut FROM projet ORDER BY statut")->fetchAll(PDO::FETCH_COLUMN);

// Fonction pour récupérer les statistiques des projets encadrés par l'enseignant
function getProjectStats($db, $id_enseignant, $filiere = '') {
    $query = "SELECT 
        p.statut,
        e.filiere,
        COUNT(*) as count
    FROM projet p
    JOIN etudiant e ON p.id_etudiant = e.id_etudiant
    WHERE p.id_enseignant = :id_enseignant";
    
    $params = [':id_enseignant' => $id_enseignant];
    
    if (!empty($filiere)) {
        $query .= " AND e.filiere = :filiere";
        $params[':filiere'] = $filiere;
    }
    
    $query .= " GROUP BY p.statut, e.filiere";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'total' => 0,
        'valide' => 0,
        'refuse' => 0,
        'en_attente' => 0,
        'par_filiere' => []
    ];
    
    foreach ($results as $row) {
        $stats['total'] += $row['count'];
        
        if (!isset($stats['par_filiere'][$row['filiere']])) {
            $stats['par_filiere'][$row['filiere']] = [
                'total' => 0,
                'valide' => 0,
                'refuse' => 0,
                'en_attente' => 0
            ];
        }
        
        $stats['par_filiere'][$row['filiere']]['total'] += $row['count'];
        $stats['par_filiere'][$row['filiere']][$row['statut']] = $row['count'];
        
        $stats[$row['statut']] += $row['count'];
    }
    
    return $stats;
}

// Fonction pour récupérer les dernières remarques sur les projets encadrés
function getLatestRemarks($db, $id_enseignant, $limit = 5) {
    $query = "SELECT 
        r.remarque_text,
        r.evaluation,
        r.date_remarque,
        p.titre as projet_titre,
        CONCAT(u.prenom, ' ', u.nom) as etudiant_nom,
        e.filiere
    FROM remarque r
    JOIN projet p ON r.id_projet = p.id_projet
    JOIN etudiant e ON p.id_etudiant = e.id_etudiant
    JOIN utilisateur u ON e.id_etudiant = u.id_utilisateur
    WHERE p.id_enseignant = :id_enseignant
    ORDER BY r.date_remarque DESC
    LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id_enseignant', $id_enseignant, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer les derniers livrables des projets encadrés
function getLatestDeliverables($db, $id_enseignant, $limit = 5) {
    $query = "SELECT 
        l.type_livrable,
        l.chemin_fichier,
        l.date_upload as date_depot,
        p.titre as projet_titre,
        CONCAT(u.prenom, ' ', u.nom) as etudiant_nom,
        e.filiere
    FROM livrable l
    JOIN projet p ON l.id_projet = p.id_projet
    JOIN etudiant e ON p.id_etudiant = e.id_etudiant
    JOIN utilisateur u ON e.id_etudiant = u.id_utilisateur
    WHERE p.id_enseignant = :id_enseignant
    ORDER BY l.date_upload DESC
    LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id_enseignant', $id_enseignant, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer les statistiques, remarques et livrables pour les projets encadrés
$stats = getProjectStats($db, $id_enseignant, $selectedFiliere);
$latestRemarks = getLatestRemarks($db, $id_enseignant);
$latestDeliverables = getLatestDeliverables($db, $id_enseignant);

try {
    // Construire la requête avec les filtres pour les projets encadrés
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
        en.grade AS enseignant_grade,
        en.specialite AS enseignant_specialite,
        GROUP_CONCAT(DISTINCT mk.terme SEPARATOR ', ') AS mots_cles,
        GROUP_CONCAT(DISTINCT CONCAT(m.nom_module, ' (S', m.semestre, ')') SEPARATOR ', ') AS modules,
        GROUP_CONCAT(DISTINCT CONCAT(l.type_livrable, ':', l.chemin_fichier) SEPARATOR '|') AS livrables,
        (SELECT remarque_text FROM remarque WHERE id_projet = p.id_projet ORDER BY date_remarque DESC LIMIT 1) AS derniere_remarque,
        (SELECT evaluation FROM remarque WHERE id_projet = p.id_projet ORDER BY date_remarque DESC LIMIT 1) AS derniere_evaluation,
        (SELECT date_remarque FROM remarque WHERE id_projet = p.id_projet ORDER BY date_remarque DESC LIMIT 1) AS date_evaluation
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
      LEFT JOIN remarque r ON p.id_projet = r.id_projet
      WHERE p.id_enseignant = :id_enseignant";
    
    $params = [':id_enseignant' => $id_enseignant];
    
    // Ajouter les filtres supplémentaires
    if (!empty($selectedFiliere)) {
        $query .= " AND e.filiere = :filiere";
        $params[':filiere'] = $selectedFiliere;
    }
    
    if (!empty($selectedTypeProjet)) {
        $query .= " AND p.type_projet = :type_projet";
        $params[':type_projet'] = $selectedTypeProjet;
    }
    
    if (!empty($selectedAnnee)) {
        $query .= " AND YEAR(p.date_soumission) = :annee";
        $params[':annee'] = $selectedAnnee;
    }
    
    if (!empty($selectedMois)) {
        $query .= " AND MONTH(p.date_soumission) = :mois";
        $params[':mois'] = $selectedMois;
    }
    
    if (!empty($selectedStatut)) {
        $query .= " AND p.statut = :statut";
        $params[':statut'] = $selectedStatut;
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
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergia - Recherche par Filière</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="etudiant.css" rel=stylesheet>
    <style>
        
        .stat-card {
            border-radius: 10px;
            padding: 15px;
            height: fit-content;
            min-height: 25vh;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .stat-value {
            font-size: 5vh;
            font-weight: bold;
        }
        .stat-card .stat-label {
            font-size: 2.5vh;
            opacity: 0.8;
        }
        .stat-card.total { background-color:rgb(214, 235, 250);color: black; border-left: 5px solid rgb(135, 200, 251);}
        .stat-card.valide {  background-color:rgb(221, 246, 223);  ;color: black; border-left: 5px solid rgb(145, 216, 152); }
        .stat-card.refuse {  background-color: rgb(254, 222, 227);color: black;border-left: 5px solid rgb(249, 171, 184); }
        .stat-card.en_attente { background-color: rgb(251, 241, 208) ;color: black;border-left: 5px solid rgb(247, 223, 143);}
        
        
        .latest-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .latest-item:last-child {
            border-bottom: none;
        }
        .card-header{
            color: var(--text-primary);
            padding: 2vh;
        }
        .card-body{
            color: var(--text-secondary);
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
            <a href="index.php" class="nav-link"><i class="fas fa-home me-2"></i>Accueil</a>
            <a href="actualite.php" class="nav-link"><i class="fas fa-compass me-2"></i>Actualité</a>
            <a href="message.php" class="nav-link"><i class="fas fa-envelope me-2"></i>Messages</a>
            <a href="#" class="nav-link active"><i class="fas fa-tachometer-alt "></i>Tableau de bord</a>
            <a href="liste.php" class="nav-link "><i class="fas fa-list-ul me-2"></i>Liste des projets</a>
            <a href="semestre.php" class="nav-link "><i class="fas fa-calendar me-2"></i>Semestre/module</a>
            <a href="etudiant.php" class="nav-link"><i class="fas fa-users me-2"></i>Par étudiant</a>
            <a href="annee.php" class="nav-link"><i class="fas fa-calendar-alt me-2"></i>Par date</a>
            <a href="filiere.php" class="nav-link "><i class="fas fa-graduation-cap me-2"></i>Par filière</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a>
        </nav>
        <div class="theme-switch mt-auto">
            <button class="theme-btn" id="themeToggle"><i class="fas fa-moon"></i></button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
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
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="fw-bold">Tableau de bord</h2>
                    <p>Consultez les statistiques des projets </p>
                </div>
            </div>

            <!-- Cartes de statistiques -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="stat-card total">
                        <div class="stat-value"><?= $stats['total'] ?></div>
                        <div class="stat-label">Projets Totaux</div>
                        <?php if (!empty($selectedFiliere)): ?>
                            <div class="small mt-2">Filière: <?= htmlspecialchars($selectedFiliere) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="stat-card valide">
                        <div class="stat-value"><?= $stats['valide'] ?></div>
                        <div class="stat-label">Projets Validés</div>
                        <div class="small mt-2"><?= $stats['total'] > 0 ? round(($stats['valide'] / $stats['total']) * 100, 2) : 0 ?>%</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="stat-card refuse">
                        <div class="stat-value"><?= $stats['refuse'] ?></div>
                        <div class="stat-label">Projets Refusés</div>
                        <div class="small mt-2"><?= $stats['total'] > 0 ? round(($stats['refuse'] / $stats['total']) * 100, 2) : 0 ?>%</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="stat-card en_attente">
                        <div class="stat-value"><?= $stats['en_attente'] ?></div>
                        <div class="stat-label">Projets en Attente</div>
                        <div class="small mt-2"><?= $stats['total'] > 0 ? round(($stats['en_attente'] / $stats['total']) * 100, 2) : 0 ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Statistiques par filière (si aucune filière spécifique n'est sélectionnée) -->
            <?php if (empty($selectedFiliere) && !empty($stats['par_filiere'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Statistiques par Filière</h5>
                    </div>
                    <div class="card-body" >
                        <div class="table-responsive overflow-auto">

                            <div class="table-responsive overflow-auto tableau table-mobile-cards">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Filière</th>
                                            <th>Total</th>
                                            <th>Validés</th>
                                            <th>Refusés</th>
                                            <th>En Attente</th>
                                            <th>Taux Validation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['par_filiere'] as $filiere => $filiereStats): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($filiere) ?></td>
                                                <td><?= $filiereStats['total'] ?></td>
                                                <td><?= $filiereStats['valide'] ?></td>
                                                <td><?= $filiereStats['refuse'] ?></td>
                                                <td><?= $filiereStats['en_attente'] ?></td>
                                                <td>
                                                    <?= $filiereStats['total'] > 0 ? round(($filiereStats['valide'] / $filiereStats['total']) * 100, 2) : 0 ?>%
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dernières remarques et livrables -->
            <div class="row mb-4">
                <div class="col-lg-6 col-md-12 col-sm-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Dernières Remarques & Évaluations</h5>
                        </div>
                        <div class="card-body latest-items">
                            <?php if (!empty($latestRemarks)): ?>
                                <?php foreach ($latestRemarks as $remark): ?>
                                    <div class="latest-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($remark['projet_titre']) ?></strong>
                                            <?php if (!empty($remark['evaluation'])): ?>
                                                <span class="badge bg-primary"><?= htmlspecialchars($remark['evaluation']) ?>/20</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small"><?= htmlspecialchars($remark['etudiant_nom']) ?> (<?= htmlspecialchars($remark['filiere']) ?>)</div>
                                        <div class="mt-2"><?= nl2br(htmlspecialchars(substr($remark['remarque_text'], 0, 100))) ?><?= strlen($remark['remarque_text']) > 100 ? '...' : '' ?></div>
                                        <div class="small mt-1">
                                            <?= date('d/m/Y H:i', strtotime($remark['date_remarque'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div >Aucune remarque récente</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 col-sm-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Derniers Livrables</h5>
                        </div>
                        <div class="card-body latest-items">
                            <?php if (!empty($latestDeliverables)): ?>
                                <?php foreach ($latestDeliverables as $deliverable): ?>
                                    <div class="latest-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($deliverable['projet_titre']) ?></strong>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($deliverable['type_livrable']) ?></span>
                                        </div>
                                        <div class="small"><?= htmlspecialchars($deliverable['etudiant_nom']) ?> (<?= htmlspecialchars($deliverable['filiere']) ?>)</div>
                                        <div class="mt-2">
                                            <a href="download.php?file=<?= urlencode($deliverable['chemin_fichier']) ?>" class="text-primary" download>
                                                <i class="fas fa-download me-1"></i> Télécharger
                                            </a>
                                        </div>
                                        <div class="small mt-1">
                                            <?= date('d/m/Y H:i', strtotime($deliverable['date_depot'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div >Aucun livrable récent</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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