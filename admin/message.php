<?php
session_start();

// Connexion à la base de données
require_once '../hello/config.php';

// Vérification de la session
if (!isset($_SESSION['id_utilisateur'])) {
    die("Accès non autorisé");
}

// Récupération des messages reçus
$queryReceived = "SELECT m.*, u.nom as expediteur_nom, u.prenom as expediteur_prenom 
                 FROM message m 
                 JOIN utilisateur u ON m.expediteur_id = u.id_utilisateur 
                 WHERE m.destinataire_id = :user_id 
                 ORDER BY m.date_envoi DESC";
$stmtReceived = $db->prepare($queryReceived);
$stmtReceived->bindParam(':user_id', $_SESSION['id_utilisateur']);
$stmtReceived->execute();
$messagesRecus = $stmtReceived->fetchAll();

// Récupération des messages envoyés
$querySent = "SELECT m.*, u.nom as destinataire_nom, u.prenom as destinataire_prenom 
              FROM message m 
              JOIN utilisateur u ON m.destinataire_id = u.id_utilisateur 
              WHERE m.expediteur_id = :user_id 
              ORDER BY m.date_envoi DESC";
$stmtSent = $db->prepare($querySent);
$stmtSent->bindParam(':user_id', $_SESSION['id_utilisateur']);
$stmtSent->execute();
$messagesEnvoyes = $stmtSent->fetchAll();

// Traitement de l'envoi d'un nouveau message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_message'])) {
    $destinataire_email = trim($_POST['destinataire']);
    $sujet = trim($_POST['sujet']);
    $contenu = trim($_POST['contenu']);
    
    // Vérifier que tous les champs sont remplis
    if (empty($destinataire_email) || empty($sujet) || empty($contenu)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        // Récupérer l'ID du destinataire à partir de son email
        $queryDest = "SELECT id_utilisateur FROM utilisateur WHERE email = :email";
        $stmtDest = $db->prepare($queryDest);
        $stmtDest->bindParam(':email', $destinataire_email);
        $stmtDest->execute();
        
        if ($stmtDest->rowCount() > 0) {
            $destinataire = $stmtDest->fetch();
            $destinataire_id = $destinataire['id_utilisateur'];
            
            // Insérer le message dans la base de données
            $queryInsert = "INSERT INTO message (expediteur_id, destinataire_id, sujet, contenu, date_envoi) 
                           VALUES (:exp_id, :dest_id, :sujet, :contenu, NOW())";
            $stmtInsert = $db->prepare($queryInsert);
            $stmtInsert->bindParam(':exp_id', $_SESSION['id_utilisateur']);
            $stmtInsert->bindParam(':dest_id', $destinataire_id);
            $stmtInsert->bindParam(':sujet', $sujet);
            $stmtInsert->bindParam(':contenu', $contenu);
            
            if ($stmtInsert->execute()) {
                $success = "Message envoyé avec succès";
                // Recharger les messages
                header("Location: message.php");
                exit();
            } else {
                $error = "Erreur lors de l'envoi du message";
            }
        } else {
            $error = "Destinataire introuvable";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergia - Messagerie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../teacher/etudiant.css" rel="stylesheet">
    <style>
        .message-tabs .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .message-tabs .nav-link.active {
            color: var(--primary);
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
        }
        
        .message-card {
            border-left: 3px solid var(--primary);
            transition: all 0.2s;
        }
        
        .message-card:hover {
            background-color: var(--hover-bg);
        }
        
        
        .message-content {
            display: none;
        }
        
        .message-content.show {
            display: block;
            width:fit-content;
        }
        
        .new-message-form {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
        }
        .card-title{
            color: var(--text-primary);
        }
        .sidebar{
            background-color: #212529;
        }
        .sidebar {
        color: white;
    }
    .sidebar a {
        color: white !important;
    }
    .sidebar .nav-link {
        color: white !important;
    }
    .sidebar .brand-text {
        color: white !important;
    }
    .sidebar .theme-btn {
        color: white !important;
    }
    .sidebar .nav-link:hover {
    color: #f0f0f0 !important;
    background-color: rgba(255,255,255,0.1);
}
 .sidebar .nav-link.active {
    color: #f0f0f0 !important;
    background-color: rgba(255,255,255,0.1);
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
            <a href="admin_dashboard.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i> Tableau de bord</a>
            <a href="actualite.php" class="nav-link"><i class="bi bi-newspaper me-2"></i> Actualités</a>
            <a href="gestion_utilisateurs.php" class="nav-link "><i class="bi bi-people me-2"></i> Gestion utilisateurs</a>
            <a href="gestion_projets.php" class="nav-link"><i class="bi bi-folder me-2"></i> Tous les projets</a>
            <a href="statistiques.php" class="nav-link"><i class="bi bi-bar-chart me-2"></i> Statistiques</a>
            <a href="#" class="nav-link active"><i class="bi bi-envelope-fill me-2"></i>Messages</a>
            <a href="parametres.php" class="nav-link"><i class="bi bi-gear me-2"></i> Paramètres</a>
            <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a>
        </nav>
        
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
                            <h2 class="display-5 fw-bold mb-3"><span class="text-primary">Messagerie </span> <span id="teacherName"><?php echo $_SESSION['prenom'].' '.$_SESSION['nom']; ?></span> !</h2>
                            <p class="lead mb-4">Envoyez et recevez des messages avec les autres utilisateurs de la plateforme.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barre d'onglets -->
            <div class="row mb-4">
                <div class="col-12">
                    <ul class="nav message-tabs" id="messageTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="received-tab" data-bs-toggle="tab" data-bs-target="#received" type="button" role="tab">
                                <i class="fas fa-inbox me-2"></i>Messages reçus
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
                                <i class="fas fa-paper-plane me-2"></i>Messages envoyés
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab">
                                <i class="fas fa-plus-circle me-2"></i>Nouveau message
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contenu des onglets -->
            <div class="tab-content" id="messageTabsContent">
                <!-- Messages reçus -->
                <div class="tab-pane fade show active" id="received" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4"><i class="fas fa-inbox me-2"></i>Messages reçus</h4>
                            
                            <?php if (empty($messagesRecus)): ?>
                                <div class="alert alert-info">
                                    Vous n'avez aucun message reçu.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover tableau">
                                        <thead>
                                            <tr>
                                                <th>Expéditeur</th>
                                                <th>Sujet</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($messagesRecus as $message): ?>
                                                <tr class="message-card <?= $message['lu'] ? '' : 'message-unread' ?>">
                                                    <td>
                                                        <?= htmlspecialchars($message['expediteur_prenom'] . ' ' . $message['expediteur_nom']) ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($message['sujet']) ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary view-message" data-id="<?= $message['id_message'] ?>">
                                                            <i class="fas fa-eye"></i> Voir
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="message-content" id="message-content-<?= $message['id_message'] ?>">
                                                    <td colspan="4">
                                                        <div class="card card-body mb-3">
                                                            <p><strong>De:</strong> <?= htmlspecialchars($message['expediteur_prenom'] . ' ' . $message['expediteur_nom']) ?></p>
                                                            <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></p>
                                                            <p><strong>Sujet:</strong> <?= htmlspecialchars($message['sujet']) ?></p>
                                                            <hr>
                                                            <p><?= nl2br(htmlspecialchars($message['contenu'])) ?></p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Messages envoyés -->
                <div class="tab-pane fade" id="sent" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4"><i class="fas fa-paper-plane me-2"></i>Messages envoyés</h4>
                            
                            <?php if (empty($messagesEnvoyes)): ?>
                                <div class="alert alert-info">
                                    Vous n'avez envoyé aucun message.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover tableau">
                                        <thead>
                                            <tr>
                                                <th>Destinataire</th>
                                                <th>Sujet</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($messagesEnvoyes as $message): ?>
                                                <tr class="message-card">
                                                    <td>
                                                        <?= htmlspecialchars($message['destinataire_prenom'] . ' ' . $message['destinataire_nom']) ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($message['sujet']) ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary view-message" data-id="<?= $message['id_message'] ?>">
                                                            <i class="fas fa-eye"></i> Voir
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="message-content " id="message-content-<?= $message['id_message'] ?>">
                                                    <td colspan="4">
                                                        <div class="card card-body mb-3">
                                                            <p><strong>À:</strong> <?= htmlspecialchars($message['destinataire_prenom'] . ' ' . $message['destinataire_nom']) ?></p>
                                                            <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></p>
                                                            <p><strong>Sujet:</strong> <?= htmlspecialchars($message['sujet']) ?></p>
                                                            <hr>
                                                            <p><?= nl2br(htmlspecialchars($message['contenu'])) ?></p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Nouveau message -->
                <div class="tab-pane fade" id="new" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4"><i class="fas fa-plus-circle me-2"></i>Nouveau message</h4>
                            
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success"><?= $success ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="" class="new-message-form">
                                <div class="mb-3">
                                    <label for="destinataire" class="form-label">Destinataire (email)</label>
                                    <input type="email" class="form-control" id="destinataire" name="destinataire" required style="background: var(--surface); color:var(--text-primary)">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sujet" class="form-label">Sujet</label>
                                    <input type="text" class="form-control" id="sujet" name="sujet" required style="background: var(--surface); color:var(--text-primary)">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contenu" class="form-label">Message</label>
                                    <textarea class="form-control" id="contenu" name="contenu" rows="5" required style="background: var(--surface); color:var(--text-primary)"></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="envoyer_message" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                                    </button>
                                </div>
                            </form>
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
        

        // Menu mobile
        document.getElementById('mobileMenu').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        document.getElementById('mobileMenu_back').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Affichage des messages
        document.querySelectorAll('.view-message').forEach(button => {
            button.addEventListener('click', function() {
                const messageId = this.getAttribute('data-id');
                const messageContent = document.getElementById(`message-content-${messageId}`);
                
                // Basculer l'affichage du contenu
                messageContent.classList.toggle('show');
                
                // Changer l'icône du bouton
                const icon = this.querySelector('i');
                if (messageContent.classList.contains('show')) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
                
                
            });
        });
    </script>
</body>
</html>