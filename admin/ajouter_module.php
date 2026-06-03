<?php
session_start();

// Vérification de l'authentification et des droits admin
if (!isset($_SESSION['nom']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../hello/login.php");
    exit();
}

// Connexion à la base de données
require_once __DIR__ . '/../hello/config.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validation des données
    $nom_module = trim($_POST['nom_module'] ?? '');
    $semestre = trim($_POST['semestre'] ?? '');
    $annee_module = trim($_POST['annee_module'] ?? '');

    if (empty($nom_module)) {
        $errors[] = "Le nom du module est obligatoire";
    }
    
    if (empty($semestre)) {
        $errors[] = "Le semestre est obligatoire";
    }
    
    if (empty($annee_module)) {
        $errors[] = "L'année du module est obligatoire";
    }

    // Si pas d'erreurs, insertion en base
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO module (nom_module, semestre, annee_module) 
                    VALUES (:nom_module, :semestre, :annee_module)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nom_module' => $nom_module,
                ':semestre' => $semestre,
                ':annee_module' => $annee_module
            ]);
            
            $_SESSION['success_message'] = "Module ajouté avec succès!";
            header("Location: filiere.php");
            exit();
            
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout du module: " . $e->getMessage();
            error_log("Erreur base de données: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajouter un Module - ENSA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
    
    .card-header {
      background-color: var(--ensaprimary);
      color: white;
    }
    
    .btn-ensa {
      background-color: var(--ensasecondary);
      color: white;
    }
    
    .btn-ensa:hover {
      background-color: #2980b9;
      color: white;
    }
    
    .form-section {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .required-field::after {
      content: " *";
      color: var(--ensadanger);
    }
  </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">
      <i class="bi bi-journal-bookmark-fill me-2"></i> ENSA Admin 
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="profil.php">
            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nom'] . ' ' . $_SESSION['prenom']) ?>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="bi bi-book me-2"></i> Ajouter un nouveau module
          </h4>
          <a href="filiere.php" class="btn btn-sm btn-outline-light">
            <i class="bi bi-arrow-left me-1"></i> Retour
          </a>
        </div>
        
        <div class="card-body">
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Erreurs</h5>
              <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                  <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          
          <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
              <label for="nom_module" class="form-label required-field">Nom du module</label>
              <input type="text" class="form-control" id="nom_module" name="nom_module" 
                     value="<?= htmlspecialchars($_POST['nom_module'] ?? '') ?>" required>
              <div class="invalid-feedback">
                Veuillez saisir le nom du module.
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="semestre" class="form-label required-field">Semestre</label>
                <select class="form-select" id="semestre" name="semestre" required>
                  <option value="" disabled selected>Sélectionnez un semestre</option>
                  <option value="S1" <?= (($_POST['semestre'] ?? '') === 'S1') ? 'selected' : '' ?>>Semestre 1</option>
                  <option value="S2" <?= (($_POST['semestre'] ?? '') === 'S2') ? 'selected' : '' ?>>Semestre 2</option>
                  <option value="S3" <?= (($_POST['semestre'] ?? '') === 'S3') ? 'selected' : '' ?>>Semestre 3</option>
                  <option value="S4" <?= (($_POST['semestre'] ?? '') === 'S4') ? 'selected' : '' ?>>Semestre 4</option>
                  <option value="S5" <?= (($_POST['semestre'] ?? '') === 'S5') ? 'selected' : '' ?>>Semestre 5</option>
                  <option value="S6" <?= (($_POST['semestre'] ?? '') === 'S6') ? 'selected' : '' ?>>Semestre 6</option>
                </select>
                <div class="invalid-feedback">
                  Veuillez sélectionner un semestre.
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="annee_module" class="form-label required-field">Année</label>
                <input type="text" class="form-control" id="annee_module" name="annee_module" 
                       placeholder="Ex: 2023-2024" value="<?= htmlspecialchars($_POST['annee_module'] ?? '') ?>" required>
                <div class="invalid-feedback">
                  Veuillez saisir l'année du module.
                </div>
              </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
              <button type="reset" class="btn btn-outline-secondary me-md-2">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
              </button>
              <button type="submit" class="btn btn-ensa">
                <i class="bi bi-save me-1"></i> Enregistrer le module
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Validation côté client Bootstrap
(function () {
  'use strict'
  
  // Récupérer tous les formulaires auxquels nous voulons appliquer des styles de validation Bootstrap personnalisés
  var forms = document.querySelectorAll('.needs-validation')
  
  // Boucle sur eux et empêcher la soumission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>
</body>
</html>