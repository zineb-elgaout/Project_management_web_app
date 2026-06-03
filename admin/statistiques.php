<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['nom']) || !isset($_SESSION['role']) || !isset($_SESSION['prenom'])) {
    header("Location: ../hello/login.php");
    exit();
}

// Vérifier si c'est bien un admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Connexion à la base de données
require_once __DIR__ . '/../hello/config.php';

// Récupération des statistiques générales
$stats = [
    'total_projets' => 0,
    'projets_valides' => 0,
    'taux_validation' => 0,
    'utilisateurs_actifs' => 0
];

try {
    // Récupération des statistiques générales
    $query = "SELECT 
                COUNT(*) as total_projets,
                SUM(CASE WHEN statut = 'valide' THEN 1 ELSE 0 END) as projets_valides
              FROM projet";
    $stmt = $db->query($query);
    $stats_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats_data) {
        $stats['total_projets'] = $stats_data['total_projets'] ?? 0;
        $stats['projets_valides'] = $stats_data['projets_valides'] ?? 0;
        $stats['taux_validation'] = $stats['total_projets'] > 0 
            ? round(($stats['projets_valides'] / $stats['total_projets']) * 100, 1)
            : 0;
    }

    // Récupération des utilisateurs actifs
    $query = "SELECT COUNT(DISTINCT id_utilisateur) as utilisateurs_actifs
              FROM (
                SELECT id_etudiant as id_utilisateur FROM projet WHERE id_etudiant IS NOT NULL
                UNION
                SELECT id_enseignant FROM projet WHERE id_enseignant IS NOT NULL
              ) as active_users";
    $stmt = $db->query($query);
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['utilisateurs_actifs'] = $active_users['utilisateurs_actifs'] ?? 0;

    // Récupération des soumissions par mois
    $query = "SELECT 
                MONTHNAME(date_soumission) as mois,
                COUNT(*) as soumissions,
                SUM(CASE WHEN statut = 'valide' THEN 1 ELSE 0 END) as validations
              FROM projet
              GROUP BY MONTH(date_soumission)
              ORDER BY MONTH(date_soumission)";
    $stmt = $db->query($query);
    $soumissions_par_mois = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération de la répartition par filière
    $query = "SELECT 
                e.filiere,
                COUNT(p.id_projet) as nombre_projets
              FROM projet p
              JOIN etudiant e ON p.id_etudiant = e.id_etudiant
              GROUP BY e.filiere
              ORDER BY nombre_projets DESC";
    $stmt = $db->query($query);
    $repartition_filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul du pourcentage pour chaque filière
    $total_projets_filieres = array_sum(array_column($repartition_filieres, 'nombre_projets'));
    foreach ($repartition_filieres as &$filiere) {
        $filiere['pourcentage'] = $total_projets_filieres > 0 
            ? round(($filiere['nombre_projets'] / $total_projets_filieres) * 100, 1)
            : 0;
    }

    // Récupération du top 5 des encadrants
    $query = "SELECT 
                u.nom, u.prenom,
                COUNT(p.id_projet) as projets_encadres,
                (SUM(CASE WHEN p.statut = 'valide' THEN 1 ELSE 0 END) / COUNT(p.id_projet)) * 100 as taux_validation
              FROM projet p
              JOIN enseignant e ON p.id_enseignant = e.id_enseignant
              JOIN utilisateur u ON e.id_enseignant = u.id_utilisateur
              GROUP BY u.id_utilisateur
              ORDER BY projets_encadres DESC
              LIMIT 5";
    $stmt = $db->query($query);
    $top_encadrants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des types de projets
    $query = "SELECT 
                type_projet,
                COUNT(*) as nombre,
                (COUNT(*) / (SELECT COUNT(*) FROM projet)) * 100 as pourcentage
              FROM projet
              GROUP BY type_projet
              ORDER BY nombre DESC";
    $stmt = $db->query($query);
    $types_projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Gestion des erreurs
    error_log("Erreur de base de données: " . $e->getMessage());
    $soumissions_par_mois = [];
    $repartition_filieres = [];
    $top_encadrants = [];
    $types_projets = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA - Statistiques</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ensaprimary: #2c3e50;
            --ensasecondary:rgb(40, 50, 57);
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
        .stat-card {
            border-left: 4px solid var(--ensasecondary);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
        }
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            .stat-card {
                margin-bottom: 1rem;
            }
        }
        .user-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--ensasecondary);
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <div class="d-flex align-items-center">
                            <div class="user-badge me-2"><?= substr($_SESSION['nom'], 0, 1) . substr($_SESSION['prenom'], 0, 1) ?></div>
                            <span><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="parametres.php"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../hello/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 sidebar-teacher p-0 min-vh-100">
            <div class="d-flex flex-column h-100">
                <div class="p-4 text-center border-bottom border-secondary">
                    <div class="mb-3">
                        <div class="ratio ratio-1x1 mx-auto rounded-circle bg-light justify-content-center align-items-center" style="width: 80px;">
                            <div class="d-flex align-items-center justify-content-center text-dark fw-bold fs-3">
                                <?= substr($_SESSION['nom'], 0, 1) . substr($_SESSION['prenom'], 0, 1) ?>
                            </div>
                        </div>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></h5>
                    <small class="text-muted">Administrateur</small>
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
                    <li class="nav-item"><a class="nav-link active text-white" href="statistiques.php"><i class="bi bi-bar-chart me-2"></i> Statistiques</a></li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 text-white" href="message.php">
                            <i class="bi bi-envelope-fill fs-5"></i>
                            <span class="fw-semibold">Messages</span>
                        </a>
                    </li>
                    <li class="nav-item mt-3"><a class="nav-link text-white" href="parametres.php"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
                </ul>
                <div class="mt-auto p-3 text-center">
                    <small class="text-muted">ENSA Kenitra © <?= date('Y') ?></small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 main-content py-4">
            <div class="bg-white p-4 rounded shadow-sm mb-4 d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-bar-chart me-2"></i> Tableau de statistiques
                </h2>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar me-1"></i> Année <?= date('Y') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><?= date('Y') ?></a></li>
                            <li><a class="dropdown-item" href="#"><?= date('Y')-1 ?></a></li>
                            <li><a class="dropdown-item" href="#"><?= date('Y')-2 ?></a></li>
                        </ul>
                    </div>
                    <button class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Exporter
                    </button>
                </div>
            </div>

            <!-- Filtres rapides -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Projets soumis</h6>
                                    <h3 class="mb-0"><?= number_format($stats['total_projets']) ?></h3>
                                    <small class="text-muted">Total projets</small>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Projets validés</h6>
                                    <h3 class="mb-0"><?= number_format($stats['projets_valides']) ?></h3>
                                    <small class="text-muted"><?= $stats['taux_validation'] ?>% du total</small>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-check-circle text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Taux de validation</h6>
                                    <h3 class="mb-0"><?= number_format($stats['taux_validation'], 1) ?>%</h3>
                                    <small class="text-muted">Projets validés</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-percent text-warning fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Utilisateurs actifs</h6>
                                    <h3 class="mb-0"><?= number_format($stats['utilisateurs_actifs']) ?></h3>
                                    <small class="text-muted">Enseignants et étudiants</small>
                                </div>
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-people text-info fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques principaux -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Soumissions par mois</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="submissionsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Répartition par filière</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="departmentsChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <?php foreach ($repartition_filieres as $filiere): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-primary me-2">&nbsp;</span>
                                    <small><?= htmlspecialchars($filiere['filiere']) ?></small>
                                    <small class="ms-auto fw-bold"><?= number_format($filiere['pourcentage'], 1) ?>%</small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau et autres graphiques -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Top encadrants</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Encadrant</th>
                                            <th>Projets encadrés</th>
                                            <th>Taux validation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_encadrants as $encadrant): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-badge me-2">
                                                        <?= substr($encadrant['prenom'], 0, 1) . substr($encadrant['nom'], 0, 1) ?>
                                                    </div>
                                                    <div>Pr. <?= htmlspecialchars($encadrant['prenom'] . ' ' . $encadrant['nom']) ?></div>
                                                </div>
                                            </td>
                                            <td><?= $encadrant['projets_encadres'] ?></td>
                                            <td>
                                                <span class="badge <?= ($encadrant['taux_validation'] >= 80) ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= number_format($encadrant['taux_validation'], 0) ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Types de projets</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="projectTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End main content -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
    // Préparation des données pour les graphiques
    const moisLabels = <?= json_encode(array_column($soumissions_par_mois, 'mois')) ?>;
    const soumissionsData = <?= json_encode(array_column($soumissions_par_mois, 'soumissions')) ?>;
    const validationsData = <?= json_encode(array_column($soumissions_par_mois, 'validations')) ?>;
    
    const filieresLabels = <?= json_encode(array_column($repartition_filieres, 'filiere')) ?>;
    const filieresData = <?= json_encode(array_column($repartition_filieres, 'nombre_projets')) ?>;
    
    const typesProjetsLabels = <?= json_encode(array_column($types_projets, 'type_projet')) ?>;
    const typesProjetsData = <?= json_encode(array_column($types_projets, 'nombre')) ?>;

    // Graphique des soumissions par mois
    if (document.getElementById('submissionsChart')) {
        const submissionsCtx = document.getElementById('submissionsChart').getContext('2d');
        const submissionsChart = new Chart(submissionsCtx, {
            type: 'bar',
            data: {
                labels: moisLabels.length > 0 ? moisLabels : ['Mai'],
                datasets: [
                    {
                        label: 'Soumissions',
                        data: soumissionsData.length > 0 ? soumissionsData : [4],
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Validations',
                        data: validationsData.length > 0 ? validationsData : [2],
                        backgroundColor: 'rgba(46, 204, 113, 0.7)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }

    // Graphique des filières
    if (document.getElementById('departmentsChart')) {
        const departmentsCtx = document.getElementById('departmentsChart').getContext('2d');
        const departmentsChart = new Chart(departmentsCtx, {
            type: 'doughnut',
            data: {
                labels: filieresLabels.length > 0 ? filieresLabels : ['Génie Informatique', 'Génie Mécatronique', 'Génie Industriel', 'Bâtiment Intelligent'],
                datasets: [{
                    data: filieresData.length > 0 ? filieresData : [1, 1, 1, 1],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.7)',
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(241, 196, 15, 0.7)',
                        'rgba(231, 76, 60, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    }

    // Graphique des types de projets
    if (document.getElementById('projectTypesChart')) {
        const projectTypesCtx = document.getElementById('projectTypesChart').getContext('2d');
        const projectTypesChart = new Chart(projectTypesCtx, {
            type: 'polarArea',
            data: {
                labels: typesProjetsLabels.length > 0 ? typesProjetsLabels : ['Projet pédagogique', 'Stage d\'initiation', 'Stage de fin d\'études'],
                datasets: [{
                    data: typesProjetsData.length > 0 ? typesProjetsData : [2, 1, 1],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.7)',
                        'rgba(155, 89, 182, 0.7)',
                        'rgba(26, 188, 156, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
</script>
</body>
</html>