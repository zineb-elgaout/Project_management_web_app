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

// Récupérer les filières distinctes des étudiants
try {
    $sql_filieres = "SELECT DISTINCT filiere AS nom_filiere FROM etudiant";
    $stmt_filieres = $db->query($sql_filieres);
    $filieres = $stmt_filieres->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $filieres = [];
    error_log("Erreur lors de la récupération des filières: " . $e->getMessage());
}

// Récupérer tous les modules
try {
    $sql_modules = "SELECT * FROM module";
    $stmt_modules = $db->query($sql_modules);
    $modules = $stmt_modules->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $modules = [];
    error_log("Erreur lors de la récupération des modules: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Filières et Modules - ENSA</title>
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
    
    .action-btn-group {
      display: flex;
      gap: 5px;
    }
    
    .action-btn-group .btn {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
    }
    
    .badge-semester {
      background-color: #6f42c1;
    }
    
    .badge-year {
      background-color: #20c997;
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
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-gear me-1"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
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
          <h5 class="mb-1"><?= htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']) ?></h5>
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
          <li class="nav-item"><a class="nav-link active text-white" href="filiere.php"><i class="bi bi-building me-2"></i> Filières/Modules</a></li>
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
          <small class="text-muted">ENSA Kenitra © <?= date('Y') ?></small>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-10 main-content py-4">
      <div class="bg-white p-4 rounded shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <h2 class="mb-0">
          <i class="bi bi-building me-2"></i> Gestion des Filières et Modules
        </h2>
        <div>
          <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModuleModal">
            <i class="bi bi-plus-circle me-1"></i> Ajouter un module
          </a>
        </div>
      </div>

      <!-- Filières Table -->
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Liste des Filières</h5>
            <span class="badge bg-primary status-badge"><?= count($filieres) ?> filière(s)</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Nom de la filière</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($filieres)): ?>
                  <?php $index = 1; ?>
                  <?php foreach($filieres as $filiere): ?>
                    <tr>
                      <td><?= $index++ ?></td>
                      <td><?= htmlspecialchars($filiere['nom_filiere']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="2" class="text-center py-4">
                      <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-building-x fs-1 text-muted mb-2"></i>
                        <p class="mb-0">Aucune filière trouvée</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modules Table -->
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Liste des Modules</h5>
            <span class="badge bg-success status-badge"><?= count($modules) ?> module(s)</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Nom</th>
                  <th>Semestre</th>
                  <th>Année</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($modules)): ?>
                  <?php foreach($modules as $module): ?>
                    <tr>
                      <td><?= htmlspecialchars($module['id_module']) ?></td>
                      <td><?= htmlspecialchars($module['nom_module']) ?></td>
                      <td><span class="badge badge-semester"><?= htmlspecialchars($module['semestre']) ?></span></td>
                      <td><span class="badge bg-info"><?= htmlspecialchars($module['annee_module']) ?></span></td>
                      <td>
                        <div class="action-btn-group">
                          <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#editModuleModal" 
                                  data-id="<?= $module['id_module'] ?>" 
                                  data-nom="<?= htmlspecialchars($module['nom_module']) ?>" 
                                  data-semestre="<?= htmlspecialchars($module['semestre']) ?>" 
                                  data-annee="<?= htmlspecialchars($module['annee_module']) ?>">
                            <i class="bi bi-pencil"></i> Éditer
                          </button>
                          <a href="supprimer_module.php?id=<?= $module['id_module'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce module ?');">
                            <i class="bi bi-trash"></i> Supprimer
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center py-4">
                      <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-book-x fs-1 text-muted mb-2"></i>
                        <p class="mb-0">Aucun module trouvé</p>
                        <button class="btn btn-sm btn-success mt-2" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                          <i class="bi bi-plus-circle me-1"></i> Ajouter un module
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div> <!-- End Main Content -->
  </div>
</div>

<!-- Modal pour ajouter un module -->
<div class="modal fade" id="addModuleModal" tabindex="-1" aria-labelledby="addModuleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addModuleModalLabel">Ajouter un nouveau module</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="ajouter_module.php" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label for="nomModule" class="form-label">Nom du module</label>
            <input type="text" class="form-control" id="nomModule" name="nomModule" required>
          </div>
          <div class="mb-3">
            <label for="semestreModule" class="form-label">Semestre</label>
            <select class="form-select" id="semestreModule" name="semestreModule" required>
              <option value="S1">S1</option>
              <option value="S2">S2</option>
              <option value="S3">S3</option>
              <option value="S4">S4</option>
              <option value="S5">S5</option>
              <option value="S6">S6</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="anneeModule" class="form-label">Année</label>
            <input type="text" class="form-control" id="anneeModule" name="anneeModule" placeholder="Ex: 2023-2024" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal pour modifier un module -->
<div class="modal fade" id="editModuleModal" tabindex="-1" aria-labelledby="editModuleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModuleModalLabel">Modifier le module</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="modifier_module.php" method="POST">
        <input type="hidden" id="editIdModule" name="idModule">
        <div class="modal-body">
          <div class="mb-3">
            <label for="editNomModule" class="form-label">Nom du module</label>
            <input type="text" class="form-control" id="editNomModule" name="nomModule" required>
          </div>
          <div class="mb-3">
            <label for="editSemestreModule" class="form-label">Semestre</label>
            <select class="form-select" id="editSemestreModule" name="semestreModule" required>
              <option value="S1">S1</option>
              <option value="S2">S2</option>
              <option value="S3">S3</option>
              <option value="S4">S4</option>
              <option value="S5">S5</option>
              <option value="S6">S6</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="editAnneeModule" class="form-label">Année</label>
            <input type="text" class="form-control" id="editAnneeModule" name="anneeModule" placeholder="Ex: 2023-2024" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Script pour remplir le modal d'édition avec les données du module
  document.addEventListener('DOMContentLoaded', function() {
    var editModuleModal = document.getElementById('editModuleModal');
    editModuleModal.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;
      var id = button.getAttribute('data-id');
      var nom = button.getAttribute('data-nom');
      var semestre = button.getAttribute('data-semestre');
      var annee = button.getAttribute('data-annee');
      
      document.getElementById('editIdModule').value = id;
      document.getElementById('editNomModule').value = nom;
      document.getElementById('editSemestreModule').value = semestre;
      document.getElementById('editAnneeModule').value = annee;
    });
  });
</script>
</body>
</html>