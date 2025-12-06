<?php
session_start();

// Connexion à la base de données
require_once '../hello/config.php';

// Debug
error_log("Accès page recherche étudiant - User: ".$_SESSION['id_utilisateur']);

// Vérification de la session et du rôle
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
        // D'abord supprimer toute remarque existante
        $db->prepare("DELETE FROM remarque WHERE id_projet = ?")->execute([$id_projet]);
        
        // Puis ajouter la nouvelle
        $stmt = $db->prepare("INSERT INTO remarque (id_projet, id_enseignant, remarque_text, evaluation) 
                            VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id_projet,
            $id_enseignant,
            $_POST['remarque_text'],
            $_POST['evaluation']
        ]);
        
        $_SESSION['success_message'] = "Remarque enregistrée avec succès";
        header("Location: ".$_SERVER['PHP_SELF']."?search=".urlencode($searchTerm));
        exit();
    } else {
        $_SESSION['error_message'] = "Vous n'êtes pas autorisé à modifier ce projet";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// Récupérer la recherche si elle existe
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$projects = [];

if (!empty($searchTerm)) {
    try {
        // Requête complète avec jointures et filtre par encadrant
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
          WHERE (CONCAT(ue.nom, ' ', ue.prenom) LIKE :search
             OR ue.nom LIKE :search
             OR ue.prenom LIKE :search
             OR e.cne LIKE :search)
          AND p.id_enseignant = :id_enseignant
          GROUP BY p.id_projet
          ORDER BY p.date_soumission DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':search', "%$searchTerm%", PDO::PARAM_STR);
        $stmt->bindValue(':id_enseignant', $id_enseignant, PDO::PARAM_INT);
        $stmt->execute();
        $projects = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Erreur lors de la recherche: " . $e->getMessage();
        error_log($error);
    }
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergia - Recherche par Étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="etudiant.css" rel=stylesheet>
   
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
            <a href="statistique.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a>
            <a href="liste.php" class="nav-link"><i class="fas fa-list-ul me-2"></i>Liste des projets</a>
            <a href="semestre.php" class="nav-link "><i class="fas fa-calendar me-2"></i>Semestre/module</a>
            <a href="#" class="nav-link active"><i class="fas fa-users me-2"></i>Par étudiant</a>
            <a href="annee.php" class="nav-link"><i class="fas fa-calendar-alt me-2"></i>Par date</a>

            <a href="filiere.php" class="nav-link"><i class="fas fa-graduation-cap me-2"></i>Par filière</a>
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
                    <h2 class="fw-bold">Projets par Étudiant</h2>
                    <p >Recherchez et consultez les projets d'un étudiant</p>
                </div>
            </div>

            <!-- Search Box -->
            <div class="search-box card mb-4">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" 
                            class="form-control search-input" 
                            name="search" 
                            placeholder="Nom, prénom ou CNE de l'étudiant..." 
                            value="<?= htmlspecialchars($searchTerm) ?>"
                            aria-label="Rechercher un étudiant">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                </form>
            </div>

            
        <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if (!empty($searchTerm) && empty($projects)): ?>
                <div class="alert alert-info">
                    Aucun projet trouvé pour "<?= htmlspecialchars($searchTerm) ?>"
                </div>
            <?php elseif (!empty($projects)): ?>
                <div class="table-responsive overflow-auto">
                    <table class="table table-hover tableau table-mobile-cards">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Projet</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Livrables</th>
                                <th>Date</th>
                                <th>Actions</th>
                                <th>Validation</th>
                                <th>Remarque/Évaluation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): 
                                // Traitement des remarques
                                $remarques = [];
                                if (!empty($project['remarques'])) {
                                    $remarques_raw = explode('||', $project['remarques']);
                                    foreach ($remarques_raw as $remarque_raw) {
                                        list($text, $eval, $date) = explode('|', $remarque_raw);
                                        $remarques[] = [
                                            'text' => $text,
                                            'evaluation' => $eval,
                                            'date' => $date
                                        ];
                                    }
                                }
                            ?>
                                <tr>
                                    <td data-label="Étudiant">
                                        <strong><?= htmlspecialchars($project['etudiant_prenom'] . ' ' . $project['etudiant_nom']) ?></strong><br>
                                        <small>CNE: <?= htmlspecialchars($project['cne']) ?></small><br>
                                        <small><?= htmlspecialchars($project['filiere']) ?> (<?= htmlspecialchars($project['annee_scolaire']) ?>)</small>
                                    </td>
                                    <td data-label="Projet">
                                        <strong><?= htmlspecialchars($project['titre']) ?></strong><br>
                                        <small><?= htmlspecialchars($project['sujet']) ?></small>
                                    </td>
                                    <td data-label="Statut"><?= htmlspecialchars($project['type_projet']) ?></td>
                                    <td data-label="Type">
                                        <span class="badge-status badge-<?= $project['statut'] ?>">
                                            <?= str_replace('_', ' ', $project['statut']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Livrables">
                                        <?php 
                                        if (!empty($project['livrables'])) {
                                            $livrables = explode('|', $project['livrables']);
                                            foreach ($livrables as $livrable) {
                                                list($type, $chemin) = explode(':', $livrable);
                                                echo '
                                                    <div class="mb-2">
                                                        <a href="download.php?file=' . urlencode($chemin) . '" class="badge bg-secondary text-decoration-none" download>
                                                            ' . htmlspecialchars($type) . '
                                                        </a>
                                                    </div>';
                                            }
                                        } else {
                                            echo '<span >Aucun livrable</span>';
                                        }
                                        ?>
                                    </td>
                                    <td data-label="Date"><?= date('d/m/Y H:i', strtotime($project['date_soumission'])) ?></td>
                                    <td data-label="Actions">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailsModal<?= $project['id_projet'] ?>">
                                            <i class="fas fa-eye"></i> Détails
                                        </button>
                                    </td>

                                    <td data-label="Validation">
                                        <div class="d-flex gap-2">
                                            <!-- Formulaire pour changer le statut -->
                                            <form method="post" action="update_status.php" class="status-form">
                                                <input type="hidden" name="id_projet" value="<?= $project['id_projet'] ?>">
                                                
                                                <select name="nouveau_statut" class="form-select form-select-sm" 
                                                        onchange="this.form.submit()">
                                                    <option value="en_attente" <?= $project['statut'] == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                                    <option value="valide" <?= $project['statut'] == 'valide' ? 'selected' : '' ?>>Validé</option>
                                                    <option value="refuse" <?= $project['statut'] == 'refuse' ? 'selected' : '' ?>>Refusé</option>
                                                </select>
                                                
                                                <button type="submit" class="btn btn-sm btn-primary mt-1 d-none">
                                                    <i class="fas fa-save"></i> Appliquer
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td data-label="Remarque/Évaluation">
                                        <?php if (!empty($project['derniere_remarque'])): ?>
                                            <div class="remarque-unique">
                                                <div class="remarque-text"><?= nl2br(htmlspecialchars($project['derniere_remarque'])) ?></div>
                                                <?php if (!empty($project['derniere_evaluation'])): ?>
                                                    <div class="evaluation-badge">
                                                        <span class="badge bg-primary"><?= htmlspecialchars($project['derniere_evaluation']) ?>/20</span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="date-remarque small ">
                                                    <?= date('d/m/Y H:i', strtotime($project['date_evaluation'])) ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="small">Aucune remarque</span>
                                        <?php endif; ?>
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
                                                            <strong>Email:</strong> <?= htmlspecialchars($project['etudiant_email']) ?><br>
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
                                                            <strong>Date soumission:</strong> <?= date('d/m/Y H:i', strtotime($project['date_soumission'])) ?><br>
                                                            <strong>Statut:</strong> <span class="badge-status badge-<?= $project['statut'] ?>"><?= str_replace('_', ' ', $project['statut']) ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-4">
                                                    <h6>Description</h6>
                                                    <div class="card card-body">
                                                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                                                    </div>
                                                </div>
                                                
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
                                                                echo '<span >Aucun mot-clé</span>';
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
                                                                echo '<span >Aucun module associé</span>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($project['enseignant_nom'])): ?>
                                                    <div class="mb-3">
                                                        <h6>Encadrant</h6>
                                                        <p>
                                                            <?= htmlspecialchars($project['enseignant_prenom'] . ' ' . $project['enseignant_nom']) ?><br>
                                                            <small ><?= htmlspecialchars($project['enseignant_grade']) ?> en <?= htmlspecialchars($project['enseignant_specialite']) ?></small>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Section pour les remarques et évaluations -->
                                                <div class="mb-4">
                                                    <h6>Remarque & Évaluation</h6>
                                                    
                                                    <?php if (!empty($project['derniere_remarque'])): ?>
                                                        <div class="card mb-3">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div>
                                                                        <h6 style="color: var(--text-primary);">Remarque actuelle</h6>
                                                                        <p class="card-text"><?= nl2br(htmlspecialchars($project['derniere_remarque'])) ?></p>
                                                                    </div>
                                                                    <?php if (!empty($project['derniere_evaluation'])): ?>
                                                                        <span class="badge bg-primary rounded-pill fs-6">
                                                                            <?= htmlspecialchars($project['derniere_evaluation']) ?>/20
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class=" small mt-2" style="color: var(--text-secondary);">
                                                                    <i class="far fa-clock me-1"></i>
                                                                    <?= date('d/m/Y à H:i', strtotime($project['date_evaluation'])) ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info">
                                                            Aucune remarque n'a été ajoutée à ce projet
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Formulaire pour ajouter/modifier la remarque -->
                                                    <div class="card">
                                                        <div class="card-header ">
                                                            <h6 class="mb-0"><?= !empty($project['derniere_remarque']) ? 'Modifier' : 'Ajouter' ?> une remarque</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <form method="post" action="">
                                                                <input type="hidden" name="id_projet" value="<?= $project['id_projet'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="remarque_text" class="form-label">Remarque *</label>
                                                                    <textarea class="form-control" id="remarque_text" name="remarque_text" rows="3" required><?= 
                                                                        !empty($project['derniere_remarque']) ? htmlspecialchars($project['derniere_remarque']) : '' 
                                                                    ?></textarea>
                                                                </div>
                                                                
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="evaluation" class="form-label">Évaluation (0-20)</label>
                                                                        <input type="number" class="form-control" id="evaluation" 
                                                                            name="evaluation" min="0" max="20" step="0.5" value="<?= 
                                                                                !empty($project['derniere_evaluation']) ? htmlspecialchars($project['derniere_evaluation']) : '' 
                                                                            ?>">
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="mt-3">
                                                                    <button type="submit" name="submit_remarque" class="btn btn-primary me-2">
                                                                        <i class="fas fa-save me-1"></i>
                                                                        <?= !empty($project['derniere_remarque']) ? 'Mettre à jour' : 'Enregistrer' ?>
                                                                    </button>
                                                                    
                                                                    
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
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