<?php
session_start();
// Connexion à la base de données
require_once __DIR__ . '/../hello/config.php';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERREUR: Impossible de se connecter. " . $e->getMessage());
}

// Liste des filières disponibles (récupérées à partir des données existantes)
$filieres = [
    ['id_filiere' => 'GI', 'nom' => 'Génie Informatique'],
    ['id_filiere' => 'BIEE', 'nom' => 'Bâtiment Intelligent et Efficacité Energétique'],
    ['id_filiere' => 'GRST', 'nom' => 'Génie Réseau et Systèmes de Télécommunication'],
    ['id_filiere' => 'GE', 'nom' => 'Génie Electrique'],
    ['id_filiere' => 'GM', 'nom' => 'Génie Mécatronique'],
    ['id_filiere' => 'GInd', 'nom' => 'Génie Industriel']
];

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout d'utilisateur
    if (isset($_POST['add_user'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $filiere = $_POST['filiere'] ?? null;
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insertion dans la table utilisateur
        $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $password, $role]);
        $id_utilisateur = $pdo->lastInsertId();
        
        // Insertion dans la table spécifique selon le rôle
        if ($role === 'etudiant') {
            $cne = $_POST['cne'];
            $annee_scolaire = $_POST['annee_scolaire'];
            $stmt = $pdo->prepare("INSERT INTO etudiant (id_etudiant, cne, filiere, annee_scolaire) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_utilisateur, $cne, $filiere, $annee_scolaire]);
        } elseif ($role === 'enseignant') {
            $grade = $_POST['grade'];
            $specialite = $_POST['specialite'];
            $stmt = $pdo->prepare("INSERT INTO enseignant (id_enseignant, grade, specialite) VALUES (?, ?, ?)");
            $stmt->execute([$id_utilisateur, $grade, $specialite]);
        } elseif ($role === 'admin') {
            $departement = $_POST['departement'];
            $stmt = $pdo->prepare("INSERT INTO admin (id_admin, departement) VALUES (?, ?)");
            $stmt->execute([$id_utilisateur, $departement]);
        }
        
        $_SESSION['message'] = "Utilisateur ajouté avec succès!";
        header("Location: gestion_utilisateurs.php");
        exit();
    }
    
    // Mise à jour utilisateur
    if (isset($_POST['update_user'])) {
        $id_utilisateur = $_POST['id_utilisateur'];
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $filiere = $_POST['filiere'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE utilisateur SET nom = ?, prenom = ?, email = ?, role = ? WHERE id_utilisateur = ?");
        $stmt->execute([$nom, $prenom, $email, $role, $id_utilisateur]);
        
        // Mise à jour des tables spécifiques
        if ($role === 'etudiant') {
            $cne = $_POST['cne'];
            $annee_scolaire = $_POST['annee_scolaire'];
            $stmt = $pdo->prepare("UPDATE etudiant SET cne = ?, filiere = ?, annee_scolaire = ? WHERE id_etudiant = ?");
            $stmt->execute([$cne, $filiere, $annee_scolaire, $id_utilisateur]);
        } elseif ($role === 'enseignant') {
            $grade = $_POST['grade'];
            $specialite = $_POST['specialite'];
            $stmt = $pdo->prepare("UPDATE enseignant SET grade = ?, specialite = ? WHERE id_enseignant = ?");
            $stmt->execute([$grade, $specialite, $id_utilisateur]);
        } elseif ($role === 'admin') {
            $departement = $_POST['departement'];
            $stmt = $pdo->prepare("UPDATE admin SET departement = ? WHERE id_admin = ?");
            $stmt->execute([$departement, $id_utilisateur]);
        }
        
        $_SESSION['message'] = "Utilisateur mis à jour!";
        header("Location: gestion_utilisateurs.php");
        exit();
    }
}

// Suppression utilisateur
if (isset($_GET['delete'])) {
    $id_utilisateur = $_GET['delete'];
    
    // Suppression en cascade grâce aux contraintes FOREIGN KEY
    $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$id_utilisateur]);
    
    $_SESSION['message'] = "Utilisateur supprimé!";
    header("Location: gestion_utilisateurs.php");
    exit();
}

// Récupération des utilisateurs avec filtre et jointures
$where = [];
$params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (isset($_GET['role']) && !empty($_GET['role'])) {
    $where[] = "u.role = ?";
    $params[] = $_GET['role'];
}

if (isset($_GET['filiere']) && !empty($_GET['filiere'])) {
    $where[] = "(e.filiere = ? OR a.departement = ?)";
    $params[] = $_GET['filiere'];
    $params[] = $_GET['filiere'];
}

$sql = "SELECT u.*, 
               e.cne, e.annee_scolaire, e.filiere as etudiant_filiere,
               en.grade, en.specialite,
               a.departement as admin_departement
        FROM utilisateur u
        LEFT JOIN etudiant e ON u.id_utilisateur = e.id_etudiant
        LEFT JOIN enseignant en ON u.id_utilisateur = en.id_enseignant
        LEFT JOIN admin a ON u.id_utilisateur = a.id_admin";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY u.id_utilisateur DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération utilisateur pour édition
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT u.*, 
                                  e.cne, e.annee_scolaire, e.filiere as etudiant_filiere,
                                  en.grade, en.specialite,
                                  a.departement as admin_departement
                           FROM utilisateur u
                           LEFT JOIN etudiant e ON u.id_utilisateur = e.id_etudiant
                           LEFT JOIN enseignant en ON u.id_utilisateur = en.id_enseignant
                           LEFT JOIN admin a ON u.id_utilisateur = a.id_admin
                           WHERE u.id_utilisateur = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSA - Gestion Utilisateurs</title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #495057;
        }
        .role-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
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
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a></li>
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

                    <li class="nav-item"><a class="nav-link active text-white bg-secondary" href="gestion_utilisateurs.php"><i class="bi bi-people me-2"></i> Gestion utilisateurs</a></li>
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
                </ul>
                <div class="mt-auto p-3 text-center">
                    <small class="text-muted">ENSA Kenitra © 2024</small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 main-content py-4">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="bg-white p-4 rounded shadow-sm mb-4 d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-people me-2"></i> Gestion des utilisateurs
                </h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Exporter</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-lg me-1"></i> Ajouter utilisateur
                    </button>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="gestion_utilisateurs.php">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Recherche</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Nom, email, etc." value="<?= $_GET['search'] ?? '' ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Rôle</label>
                                <select name="role" class="form-select">
                                    <option value="">Tous les rôles</option>
                                    <option value="admin" <?= (isset($_GET['role']) )&& $_GET['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                                    <option value="enseignant" <?= (isset($_GET['role'])) && $_GET['role'] === 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                    <option value="etudiant" <?= (isset($_GET['role'])) && $_GET['role'] === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Filière</label>
                                <select name="filiere" class="form-select">
                                    <option value="">Toutes filières</option>
                                    <?php foreach ($filieres as $filiere): ?>
                                        <option value="<?= $filiere['id_filiere'] ?>" <?= (isset($_GET['filiere']) )&& $_GET['filiere'] == $filiere['id_filiere'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($filiere['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i> Filtrer</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des utilisateurs (<?= count($utilisateurs) ?>)</h5>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text">Afficher</span>
                            <select class="form-select form-select-sm">
                                <option>10</option>
                                <option selected>25</option>
                                <option>50</option>
                                <option>100</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Filière/Département</th>
                                    <th>Statut</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utilisateurs as $utilisateur): 
                                    // Déterminer la filière/département en fonction du rôle
                                    $filiere_departement = '';
                                    if ($utilisateur['role'] === 'etudiant') {
                                        $filiere_departement = $utilisateur['etudiant_filiere'] ?? 'Non spécifiée';
                                    } elseif ($utilisateur['role'] === 'admin') {
                                        $filiere_departement = $utilisateur['admin_departement'] ?? 'Non spécifié';
                                    }
                                ?>
                                <tr>
                                    <td><?= $utilisateur['id_utilisateur'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2">
                                                <?= substr($utilisateur['nom'], 0, 1) . substr($utilisateur['prenom'] ?? '', 0, 1) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom']) ?></div>
                                                <small class="text-muted">ID: <?= $utilisateur['id_utilisateur'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                                    <td>
                                        <span class="badge <?= 
                                            $utilisateur['role'] === 'admin' ? 'bg-danger' : 
                                            ($utilisateur['role'] === 'enseignant' ? 'bg-success' : 'bg-primary') 
                                        ?> role-badge">
                                            <?= 
                                                $utilisateur['role'] === 'admin' ? 'Admin' : 
                                                ($utilisateur['role'] === 'enseignant' ? 'Enseignant' : 'Étudiant') 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($filiere_departement)): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($filiere_departement) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-success">Actif</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="?edit=<?= $utilisateur['id_utilisateur'] ?>" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete=<?= $utilisateur['id_utilisateur'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirmer la suppression?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item disabled"><a class="page-link" href="#">Précédent</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Suivant</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div> <!-- End main content -->
    </div>
</div>

<!-- Modal Ajout Utilisateur -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i> Ajouter un nouvel utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select" required id="roleSelect" onchange="toggleRoleFields()">
                                <option value="">Sélectionner un rôle</option>
                                <option value="admin">Administrateur</option>
                                <option value="enseignant">Enseignant</option>
                                <option value="etudiant">Étudiant</option>
                            </select>
                        </div>
                        
                        <!-- Champs pour filière/département -->
                        <div class="col-md-6" id="filiereField">
                            <label class="form-label">Filière/Département</label>
                            <select name="filiere" class="form-select">
                                <option value="">Sélectionner</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?= $filiere['id_filiere'] ?>"><?= htmlspecialchars($filiere['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Champs spécifiques pour étudiant -->
                        <div class="col-md-6" id="cneField" style="display: none;">
                            <label class="form-label">CNE</label>
                            <input type="text" name="cne" class="form-control">
                        </div>
                        <div class="col-md-6" id="anneeField" style="display: none;">
                            <label class="form-label">Année scolaire</label>
                            <input type="text" name="annee_scolaire" class="form-control" placeholder="2023-2024">
                        </div>
                        
                        <!-- Champs spécifiques pour enseignant -->
                        <div class="col-md-6" id="gradeField" style="display: none;">
                            <label class="form-label">Grade</label>
                            <input type="text" name="grade" class="form-control">
                        </div>
                        <div class="col-md-6" id="specialiteField" style="display: none;">
                            <label class="form-label">Spécialité</label>
                            <input type="text" name="specialite" class="form-control">
                        </div>
                        
                        <!-- Champs spécifiques pour admin -->
                        <div class="col-md-6" id="departementField" style="display: none;">
                            <label class="form-label">Département</label>
                            <select name="departement" class="form-select">
                                <option value="">Sélectionner</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?= $filiere['nom'] ?>"><?= htmlspecialchars($filiere['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmation</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition Utilisateur -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <?php if ($edit_user): ?>
            <form method="POST">
                <input type="hidden" name="id_utilisateur" value="<?= $edit_user['id_utilisateur'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i> Modifier utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($edit_user['nom']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($edit_user['prenom']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select" required id="editRoleSelect" onchange="toggleEditRoleFields()">
                                <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                                <option value="enseignant" <?= $edit_user['role'] === 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                <option value="etudiant" <?= $edit_user['role'] === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                            </select>
                        </div>
                        
                        <!-- Filière pour étudiant -->
                        <div class="col-md-6" id="editFiliereField" style="<?= $edit_user['role'] === 'etudiant' ? '' : 'display: none;' ?>">
                            <label class="form-label">Filière</label>
                            <select name="filiere" class="form-select">
                                <option value="">Sélectionner une filière</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?= $filiere['id_filiere'] ?>" <?= ($edit_user['role'] === 'etudiant' && $edit_user['etudiant_filiere'] == $filiere['id_filiere']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($filiere['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Département pour admin -->
                        <div class="col-md-6" id="editDepartementField" style="<?= $edit_user['role'] === 'admin' ? '' : 'display: none;' ?>">
                            <label class="form-label">Département</label>
                            <select name="departement" class="form-select">
                                <option value="">Sélectionner un département</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?= $filiere['nom'] ?>" <?= ($edit_user['role'] === 'admin' && $edit_user['admin_departement'] == $filiere['nom']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($filiere['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Champs spécifiques pour étudiant -->
                        <div class="col-md-6" id="editCneField" style="<?= $edit_user['role'] === 'etudiant' ? '' : 'display: none;' ?>">
                            <label class="form-label">CNE</label>
                            <input type="text" name="cne" class="form-control" value="<?= htmlspecialchars($edit_user['cne'] ?? '') ?>">
                        </div>
                        <div class="col-md-6" id="editAnneeField" style="<?= $edit_user['role'] === 'etudiant' ? '' : 'display: none;' ?>">
                            <label class="form-label">Année scolaire</label>
                            <input type="text" name="annee_scolaire" class="form-control" value="<?= htmlspecialchars($edit_user['annee_scolaire'] ?? '') ?>">
                        </div>
                        
                        <!-- Champs spécifiques pour enseignant -->
                        <div class="col-md-6" id="editGradeField" style="<?= $edit_user['role'] === 'enseignant' ? '' : 'display: none;' ?>">
                            <label class="form-label">Grade</label>
                            <input type="text" name="grade" class="form-control" value="<?= htmlspecialchars($edit_user['grade'] ?? '') ?>">
                        </div>
                        <div class="col-md-6" id="editSpecialiteField" style="<?= $edit_user['role'] === 'enseignant' ? '' : 'display: none;' ?>">
                            <label class="form-label">Spécialité</label>
                            <input type="text" name="specialite" class="form-control" value="<?= htmlspecialchars($edit_user['specialite'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fonction pour afficher/masquer les champs en fonction du rôle sélectionné (ajout)
    function toggleRoleFields() {
        const role = document.getElementById('roleSelect').value;
        
        // Masquer tous les champs spécifiques
        document.getElementById('cneField').style.display = 'none';
        document.getElementById('anneeField').style.display = 'none';
        document.getElementById('gradeField').style.display = 'none';
        document.getElementById('specialiteField').style.display = 'none';
        document.getElementById('departementField').style.display = 'none';
        
        // Afficher les champs appropriés
        if (role === 'etudiant') {
            document.getElementById('cneField').style.display = 'block';
            document.getElementById('anneeField').style.display = 'block';
            document.getElementById('filiereField').style.display = 'block';
        } else if (role === 'enseignant') {
            document.getElementById('gradeField').style.display = 'block';
            document.getElementById('specialiteField').style.display = 'block';
            document.getElementById('filiereField').style.display = 'none';
        } else if (role === 'admin') {
            document.getElementById('departementField').style.display = 'block';
            document.getElementById('filiereField').style.display = 'none';
        }
    }
    
    // Fonction pour afficher/masquer les champs en fonction du rôle sélectionné (édition)
    function toggleEditRoleFields() {
        const role = document.getElementById('editRoleSelect').value;
        
        // Masquer tous les champs spécifiques
        document.getElementById('editCneField').style.display = 'none';
        document.getElementById('editAnneeField').style.display = 'none';
        document.getElementById('editGradeField').style.display = 'none';
        document.getElementById('editSpecialiteField').style.display = 'none';
        document.getElementById('editFiliereField').style.display = 'none';
        document.getElementById('editDepartementField').style.display = 'none';
        
        // Afficher les champs appropriés
        if (role === 'etudiant') {
            document.getElementById('editCneField').style.display = 'block';
            document.getElementById('editAnneeField').style.display = 'block';
            document.getElementById('editFiliereField').style.display = 'block';
        } else if (role === 'enseignant') {
            document.getElementById('editGradeField').style.display = 'block';
            document.getElementById('editSpecialiteField').style.display = 'block';
        } else if (role === 'admin') {
            document.getElementById('editDepartementField').style.display = 'block';
        }
    }
    
    // Appeler la fonction au chargement si on est en mode édition
    <?php if ($edit_user): ?>
    window.onload = function() {
        toggleEditRoleFields();
    };
    <?php endif; ?>
</script>
</body>
</html>