<?php
session_start();
include_once __DIR__ . '/../hello/config.php';
$pdo = $db;

// Vérifier que l'utilisateur est connecté avec nom et prenom en session
if (!isset($_SESSION['nom']) || !isset($_SESSION['prenom'])) {
    header('Location: ../login.php');
    exit();
}

$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];

// Récupérer les infos de l'étudiant via nom + prenom
try {
    $stmt = $pdo->prepare("
        SELECT u.*, e.filiere 
        FROM utilisateur u
        LEFT JOIN etudiant e ON u.id_utilisateur = e.id_etudiant
        WHERE u.nom = ? AND u.prenom = ?
    ");
    $stmt->execute([$nom, $prenom]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$etudiant) {
        throw new Exception("Utilisateur introuvable dans la base de données");
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}

// Exemple : gérer changement mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8 || !preg_match('/[a-zA-Z]/', $new_password) || !preg_match('/\d/', $new_password)) {
        $_SESSION['success'] = "Le nouveau mot de passe doit comporter au moins 8 caractères, avec des lettres et des chiffres.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['success'] = "Les nouveaux mots de passe ne correspondent pas.";
    } else {
        // Récupérer mot de passe haché (et id_utilisateur)
        $stmt = $pdo->prepare("SELECT mot_de_passe, id_utilisateur FROM utilisateur WHERE nom = ? AND prenom = ?");
        $stmt->execute([$nom, $prenom]);
        $userPwd = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ici ton code hash était sha256, adapte en fonction du mode d'enregistrement
        // Si tu utilises password_hash / password_verify il faut adapter
        $hashedCurrentPassword = hash('sha256', $current_password);

        if ($userPwd && $userPwd['mot_de_passe'] === $hashedCurrentPassword) {
            $hashed_password = hash('sha256', $new_password);
            $update = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?");
            $update->execute([$hashed_password, $userPwd['id_utilisateur']]);
            $_SESSION['success'] = "Mot de passe modifié avec succès.";
        } else {
            $_SESSION['success'] = "Mot de passe actuel incorrect.";
        }
    }
    header("Location: param.php?tab=security");
    exit();
}
$sessions = []; // initialisation obligatoire

try {
    $stmt = $pdo->prepare("SELECT id, device, ip_address AS ip, last_activity, session_token FROM user_sessions WHERE user_id = ? ORDER BY last_activity DESC");

    $user_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $current_token = $_COOKIE['session_token'] ?? '';

    foreach ($user_sessions as $session) {
        $session['current'] = ($session['session_token'] === $current_token);
        $sessions[] = $session;
    }
} catch (PDOException $e) {
    // Optionnel : log ou afficher un message d'erreur
    $sessions = [];
}

?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Gestion des Projets ENSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- META TAGS ESSENTIELLES -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- POLICES ET ICÔNES -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- STYLE GLOBAL -->
    <link rel="stylesheet" href="assets/css/global.css">
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

        .btn-secondary {
            background-color: #95a5a6;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .settings-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .tab {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            color: #555;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tab.active {
            color: #3498db;
            border-bottom: 3px solid #3498db;
        }

        .tab:hover:not(.active) {
            color: #2980b9;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }

        .avatar-upload {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1.5rem;
            border: 3px solid #eee;
        }

        .avatar-upload-btn {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .avatar-upload-btn input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .notification-label {
            font-weight: 500;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #3498db;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .security-session {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .session-info {
            flex: 1;
        }

        .session-info h4 {
            font-size: 1rem;
            margin-bottom: 0.3rem;
        }

        .session-info p {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .session-active {
            color: #2ecc71;
            font-weight: 500;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            gap: 1rem;
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
            
            .settings-container {
                padding: 1.5rem;
            }
            
            .btn, .btn-secondary, .btn-danger {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
            
            .avatar-preview {
                width: 80px;
                height: 80px;
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
            
            .settings-tabs {
                flex-direction: column;
                border-bottom: none;
            }
            
            .tab {
                border-bottom: 1px solid #eee;
                border-left: 3px solid transparent;
            }
            
            .tab.active {
                border-left: 3px solid #3498db;
                border-bottom: 1px solid #eee;
            }
            
            .avatar-upload {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .avatar-preview {
                margin-bottom: 1rem;
                margin-right: 0;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
                text-align: center;
                margin-bottom: 0.5rem;
            }
        }

        /* Styles pour les très petits écrans */
        @media (max-width: 480px) {
            .notification-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .switch {
                margin-top: 0.5rem;
                align-self: flex-end;
            }
            
            .security-session {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .security-session .btn-secondary {
                margin-top: 0.5rem;
                align-self: flex-end;
            }
            
            .form-control {
                padding: 0.7rem 0.9rem;
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
            <img src="images/logo.png" alt="ENSA Kenitra Logo">
            <div class="logo-text">
                <h1> Synergia </h1>
                <p>Gestion des Projets Étudiants</p>
            </div>
        </div>
       <div class="user-profile">
    <img src="<?php echo isset($etudiant['photo']) && !empty($etudiant['photo']) ? 'uploads/'.$etudiant['photo'] : 'images/default-profile.png'; ?>" alt="Profil étudiant">
</div>
    </header>

    <div class="container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li><a href="Acceuil1.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="nv prj.php"><i class="fas fa-plus-circle"></i> Nouveau projet</a></li>
                <li><a href="Mesprojets.php"><i class="fas fa-folder-open"></i> Mes projets</a></li>
                <li><a href="remarques.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="actualite.php"><i class="fas fa-bullhorn"></i> Actualités</a></li> <!-- Nouvelle entrée -->
                <li><a href="message.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="param.php" class="active"><i class="fas fa-cog"></i> Paramètres</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-cog"></i> Paramètres du Compte</h2>
            </div>

            <div class="settings-container">
                <div class="settings-tabs">
    <div class="tab <?php echo $active_tab == 'profile' ? 'active' : ''; ?>" data-tab="profile">Profil</div>
    <div class="tab <?php echo $active_tab == 'notifications' ? 'active' : ''; ?>" data-tab="notifications">Notifications</div>
    <div class="tab <?php echo $active_tab == 'security' ? 'active' : ''; ?>" data-tab="security">Sécurité</div>
    <div class="tab <?php echo $active_tab == 'privacy' ? 'active' : ''; ?>" data-tab="privacy">Confidentialité</div>
</div>

                <!-- Onglet Profil -->
                   <div class="tab-content <?php echo $active_tab == 'profile' ? 'active' : ''; ?>" id="profile-tab">
    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <form id="profile-form" method="post" action="param.php">
        <div class="avatar-upload">
            <img src="<?php echo isset($etudiant['photo']) && !empty($etudiant['photo']) ? 'uploads/'.$etudiant['photo'] : 'images/default-profile.png'; ?>" alt="Photo de profil" class="avatar-preview" id="avatarPreview">
            <div class="avatar-upload-btn">
                <button type="button" class="btn"><i class="fas fa-camera"></i> Changer la photo</button>
                <input type="file" id="avatarInput" accept="image/*" name="avatar">
            </div>
        </div>

        <div class="form-group">
            <label for="first-name">Prénom</label>
            <input type="text" id="first-name" name="prenom" class="form-control" value="<?php echo isset($etudiant['prenom']) ? htmlspecialchars($etudiant['prenom']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="last-name">Nom</label>
            <input type="text" id="last-name" name="nom" class="form-control" value="<?php echo htmlspecialchars($etudiant['nom']); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($etudiant['email']); ?>">
        </div>

        <div class="form-group">
            <label for="phone">Téléphone</label>
            <input type="tel" id="phone" name="telephone" class="form-control" value="<?php echo htmlspecialchars($etudiant['telephone'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="filiere">Filière</label>
            <select id="filiere" name="filiere" class="form-control">
                <option value="gti" <?php echo ($etudiant['filiere'] == 'gti') ? 'selected' : ''; ?>>Génie Informatique</option>
                <option value="gtr" <?php echo ($etudiant['filiere'] == 'gtr') ? 'selected' : ''; ?>>Génie Reseaux et Télécommunications</option>
                <option value="gind" <?php echo ($etudiant['filiere'] == 'gind') ? 'selected' : ''; ?>>Génie Industriel</option>
                <option value="gelec" <?php echo ($etudiant['filiere'] == 'gelec') ? 'selected' : ''; ?>>Génie electrique</option>
                <option value="gmeca" <?php echo ($etudiant['filiere'] == 'gmeca') ? 'selected' : ''; ?>>Génie Mecatronique</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-secondary">Annuler</button>
            <button type="submit" name="update_profile" class="btn">Enregistrer les modifications</button>
        </div>
    </form>
</div>

                <!-- Onglet Notifications -->
                <div class="tab-content" id="notifications-tab">
                    <h3 style="margin-bottom: 1.5rem;">Préférences de notification</h3>
                    
                    <div class="notification-item">
                        <span class="notification-label">Notifications par email</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-item">
                        <span class="notification-label">Nouveaux feedbacks</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-item">
                        <span class="notification-label">Rappels de projet</span>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-item">
                        <span class="notification-label">Annonces importantes</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-item">
                        <span class="notification-label">Notifications push</span>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn">Enregistrer les préférences</button>
                    </div>
                </div>

                <!-- Onglet Sécurité -->
                <!-- Onglet Sécurité -->
<div class="tab-content <?php echo $active_tab == 'security' ? 'active' : ''; ?>" id="security-tab">
    <h3 style="margin-bottom: 1.5rem;">Sécurité du compte</h3>

    <?php if (!empty($errors_password)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <?php foreach ($errors_password as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success']) && $active_tab == 'security'): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="param.php?tab=security">
        <div class="form-group">
            <label for="current-password">Mot de passe actuel</label>
            <input type="password" id="current-password" name="current_password" class="form-control" 
                   placeholder="Entrez votre mot de passe actuel" required>
        </div>

        <div class="form-group">
            <label for="new-password">Nouveau mot de passe</label>
            <input type="password" id="new-password" name="new_password" class="form-control" 
                   placeholder="Entrez votre nouveau mot de passe" required minlength="8">
            <small style="color: #7f8c8d; display: block; margin-top: 0.5rem;">Minimum 8 caractères avec chiffres et lettres</small>
        </div>
        <?php if (!empty($sessions) && is_array($sessions)): ?>
    <?php foreach ($sessions as $session): ?>
        <div class="security-session">
            <div class="session-info">
                <h4><?php echo htmlspecialchars($session['device']); ?></h4>
                <p>Dernière activité: <?php echo htmlspecialchars($session['last_activity']); ?></p>
                <?php if ($session['current']): ?>
                    <p class="session-active">Cette session</p>
                <?php else: ?>
                    <p>IP: <?php echo htmlspecialchars($session['ip']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>


        <div class="form-group">
            <label for="confirm-password">Confirmer le nouveau mot de passe</label>
            <input type="password" id="confirm-password" name="confirm_password" class="form-control" 
                   placeholder="Confirmez votre nouveau mot de passe" required minlength="8">
        </div>

        <div class="form-actions">
            <a href="param.php?tab=security" class="btn-secondary">Annuler</a>
            <button type="submit" name="change_password" class="btn">Changer le mot de passe</button>
        </div>
    </form>

    <h3 style="margin-top: 2rem; margin-bottom: 1.5rem;">Sessions actives</h3>

    <?php foreach ($sessions as $session): ?>
        <div class="security-session">
            <div class="session-info">
                <h4><?php echo htmlspecialchars($session['device']); ?></h4>
                <p>Dernière activité: <?php echo htmlspecialchars($session['last_activity']); ?></p>
                <?php if ($session['current']): ?>
                    <p class="session-active">Cette session</p>
                <?php else: ?>
                    <p>IP: <?php echo htmlspecialchars($session['ip']); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!$session['current']): ?>
                <form method="post" action="logout_session.php" style="margin:0;">
                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['id']); ?>">
                    <button type="submit" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                        Déconnecter
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

                <!-- Onglet Confidentialité -->
                <div class="tab-content" id="privacy-tab">
                    <h3 style="margin-bottom: 1.5rem;">Paramètres de confidentialité</h3>

                    <div class="notification-item">
                        <span class="notification-label">Projets visibles par les autres étudiants</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-item">
                        <span class="notification-label">Profil visible dans l'annuaire</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-item">
                        <span class="notification-label">Autoriser les messages des autres étudiants</span>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <label for="data-export">Export des données</label>
                        <button type="button" id="data-export" class="btn-secondary" style="width: 100%; text-align: left; padding: 0.8rem 1rem;">
                            <i class="fas fa-file-export"></i> Demander un export de mes données
                        </button>
                        <small style="color: #7f8c8d; display: block; margin-top: 0.5rem;">Vous recevrez un lien par email pour télécharger toutes vos données</small>
                    </div>

                    <div class="form-group">
                        <label for="account-delete">Suppression du compte</label>
                        <button type="button" id="account-delete" class="btn-danger" style="width: 100%; text-align: left; padding: 0.8rem 1rem;">
                            <i class="fas fa-trash-alt"></i> Supprimer définitivement mon compte
                        </button>
                        <small style="color: #7f8c8d; display: block; margin-top: 0.5rem;">Cette action est irréversible et supprimera tous vos projets et données</small>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer style="background:#2c3e50; color:white; padding:1rem; text-align:center; margin-top:2rem;">
        <a href="full-access.html" style="color:#3498db; text-decoration:none;">
            <i class="fas fa-expand"></i> Accès complet
        </a>
        <p style="margin-top:0.5rem;">© 2025 ENSA Projets</p>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // ... (garder le code existant pour le menu toggle)

    // Gestion des onglets avec prise en compte de l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeTabParam = urlParams.get('tab');
    
    if (activeTabParam) {
        // Désactiver tous les onglets
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Activer l'onglet spécifié dans l'URL
        const tabToActivate = document.querySelector(`.tab[data-tab="${activeTabParam}"]`);
        if (tabToActivate) {
            tabToActivate.classList.add('active');
            document.getElementById(`${activeTabParam}-tab`).classList.add('active');
        }
    }

    // ... (garder le reste du code JavaScript existant)
});
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');

            // Gestion du menu toggle pour mobile
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            // Fermer le menu si on clique à l'extérieur
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            });

            // Gestion des onglets
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Désactiver tous les onglets
                    tabs.forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });

                    // Activer l'onglet sélectionné
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });

            // Prévisualisation de l'avatar
            const avatarInput = document.getElementById('avatarInput');
            const avatarPreview = document.getElementById('avatarPreview');

            avatarInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarPreview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Simulation de changement de mot de passe
            const profileForm = document.getElementById('profile-form');
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Profil mis à jour avec succès!');
            });

            // Confirmation pour la suppression du compte
            const deleteBtn = document.getElementById('account-delete');
            deleteBtn.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir supprimer définitivement votre compte ? Cette action est irréversible.')) {
                    alert('Demande de suppression envoyée. Un email de confirmation vous a été envoyé.');
                }
            });

            // Simulation d'export de données
            const exportBtn = document.getElementById('data-export');
            exportBtn.addEventListener('click', function() {
                alert('Votre demande d\'export a été enregistrée. Vous recevrez un email avec vos données sous 24h.');
            });

            // Surligne le lien actif dans le menu
            const currentPage = location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
    
    <!-- SCRIPT DE FORCE LAYOUT -->
    <script src="assets/js/force-layout.js"></script>
</body>
</html>