<?php
session_start();

require_once '../hello/config.php';

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['id_utilisateur'])) {
        header("Location: login.php");
        exit();
    }

    // Récupération des données du formulaire
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type_projet = $_POST['type_projet'] ?? '';
    $sujet = trim($_POST['sujet'] ?? '');
    $email_encadrant = filter_var(trim($_POST['email_encadrant'] ?? ''), FILTER_VALIDATE_EMAIL);
    $nom_module = trim($_POST['nom_module'] ?? '');
    $semestre = $_POST['semestre'] ?? '';
    $mots_cles = array_filter(array_map('trim', explode(',', $_POST['mots_cles'] ?? '')));
    $id_etudiant = $_SESSION['id_utilisateur'];

    // Validation des champs obligatoires
    if (empty($titre) || empty($description) || empty($type_projet) || empty($sujet) || !$email_encadrant) {
        $_SESSION['error_message'] = "Tous les champs obligatoires doivent être remplis";
        header("Location: nv_prj.php");
        exit();
    }

    try {
        $db->beginTransaction();

        // 1. Vérifier que l'utilisateur est bien un étudiant
        $stmt = $db->prepare("SELECT * FROM etudiant WHERE id_etudiant = ?");
        $stmt->execute([$id_etudiant]);
        if (!$stmt->fetch()) {
            throw new Exception("Vous devez être un étudiant pour soumettre un projet.");
        }

        // 2. Trouver l'enseignant encadrant par son email
        $stmt = $db->prepare("SELECT u.id_utilisateur 
                             FROM utilisateur u 
                             JOIN enseignant e ON u.id_utilisateur = e.id_enseignant 
                             WHERE u.email = ?");
        $stmt->execute([$email_encadrant]);
        $enseignant = $stmt->fetch();
        
        if (!$enseignant) {
            throw new Exception("Aucun enseignant trouvé avec cet email.");
        }
        $id_enseignant = $enseignant['id_utilisateur'];

        // 3. Insérer le projet
        $stmt = $db->prepare("INSERT INTO projet (titre, description, type_projet, sujet, statut, id_etudiant, id_enseignant) 
                             VALUES (?, ?, ?, ?, 'en_attente', ?, ?)");
        $stmt->execute([$titre, $description, $type_projet, $sujet, $id_etudiant, $id_enseignant]);
        $id_projet = $db->lastInsertId();

        // 4. Gérer le module (créer si nécessaire puis lier)
        if (!empty($nom_module) && !empty($semestre)) {
            // Vérifier si le module existe déjà
            $stmt = $db->prepare("SELECT id_module FROM module WHERE nom_module = ? AND semestre = ?");
            $stmt->execute([$nom_module, $semestre]);
            $module = $stmt->fetch();
            
            if (!$module) {
                // Créer le nouveau module
                $stmt = $db->prepare("INSERT INTO module (nom_module, semestre, annee_module) 
                                     VALUES (?, ?, ?)");
                $annee = date('Y') . '-' . (date('Y') + 1);
                $stmt->execute([$nom_module, $semestre, $annee]);
                $id_module = $db->lastInsertId();
            } else {
                $id_module = $module['id_module'];
            }
            
            // Lier le module au projet
            $stmt = $db->prepare("INSERT INTO projet_module (id_projet, id_module) VALUES (?, ?)");
            $stmt->execute([$id_projet, $id_module]);
        }

        // 5. Gérer les mots-clés
        foreach ($mots_cles as $mot) {
            if (!empty($mot)) {
                // Vérifier si le mot-clé existe déjà
                $stmt = $db->prepare("SELECT id_mot_cle FROM mot_cle WHERE terme = ?");
                $stmt->execute([$mot]);
                $mot_cle = $stmt->fetch();
                
                if (!$mot_cle) {
                    // Insérer le nouveau mot-clé
                    $stmt = $db->prepare("INSERT INTO mot_cle (terme) VALUES (?)");
                    $stmt->execute([$mot]);
                    $id_mot_cle = $db->lastInsertId();
                } else {
                    $id_mot_cle = $mot_cle['id_mot_cle'];
                }
                
                // Associer le mot-clé au projet
                $stmt = $db->prepare("INSERT INTO projet_mot_cle (id_projet, id_mot_cle) VALUES (?, ?)");
                $stmt->execute([$id_projet, $id_mot_cle]);
            }
        }

        // 6. Gérer les fichiers livrables
        $upload_dir = 'uploads/livrables/';
        
        // Vérifier si des fichiers ont été soumis
        if (isset($_FILES['livrables']) && is_uploaded_file($_FILES['livrables']['tmp_name'][0])) {
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Impossible de créer le répertoire de destination");
                }
            }

            // Vérifier que le répertoire est accessible en écriture
            if (!is_writable($upload_dir)) {
                throw new Exception("Le répertoire de destination n'est pas accessible en écriture");
            }

            foreach ($_FILES['livrables']['tmp_name'] as $key => $tmp_name) {
                // Vérifier qu'un fichier a bien été uploadé
                if ($_FILES['livrables']['error'][$key] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                // Vérifier les erreurs d'upload
                if ($_FILES['livrables']['error'][$key] !== UPLOAD_ERR_OK) {
                    $upload_errors = [
                        1 => 'La taille du fichier dépasse la limite autorisée',
                        2 => 'La taille du fichier dépasse la limite spécifiée dans le formulaire',
                        3 => 'Le fichier n\'a été que partiellement uploadé',
                        4 => 'Aucun fichier n\'a été uploadé',
                        6 => 'Dossier temporaire manquant',
                        7 => 'Échec de l\'écriture du fichier sur le disque',
                        8 => 'Une extension PHP a arrêté l\'upload du fichier'
                    ];
                    throw new Exception("Erreur d'upload: " . ($upload_errors[$_FILES['livrables']['error'][$key]] ?? 'Erreur inconnue'));
                }

                $nom = $_FILES['livrables']['name'][$key];
                $tmp = $_FILES['livrables']['tmp_name'][$key];
                $type_mime = $_FILES['livrables']['type'][$key];
                $size = $_FILES['livrables']['size'][$key];

                // Valider la taille du fichier (max 10MB)
                $max_size = 10 * 1024 * 1024; // 10MB
                if ($size > $max_size) {
                    throw new Exception("Le fichier $nom dépasse la taille maximale autorisée (10MB)");
                }

                // Valider l'extension
                $extension = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
                $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar'];
                if (!in_array($extension, $allowed_extensions)) {
                    throw new Exception("Type de fichier non autorisé pour $nom. Extensions autorisées: " . implode(', ', $allowed_extensions));
                }

                // Nettoyer le nom du fichier
                $clean_name = preg_replace('/[^a-zA-Z0-9._-]/', '', $nom);
                $nom_unique = uniqid() . '_' . $clean_name;
                $chemin = $upload_dir . $nom_unique;
                
                // Déplacer le fichier
                if (!move_uploaded_file($tmp, $chemin)) {
                    throw new Exception("Erreur lors du déplacement du fichier: " . $nom);
                }

                // Déterminer le type de livrable
                $type_livrable = 'rapport'; // Valeur par défaut
                if (strpos($type_mime, 'zip') !== false || strpos($type_mime, 'rar') !== false) {
                    $type_livrable = 'code_source';
                } elseif (strpos($type_mime, 'presentation') !== false || strpos($type_mime, 'ppt') !== false || strpos($type_mime, 'powerpoint') !== false) {
                    $type_livrable = 'diaporama';
                }

                // Enregistrer en base de données
                $stmt = $db->prepare("INSERT INTO livrable (id_projet, type_livrable, chemin_fichier, date_upload) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$id_projet, $type_livrable, $chemin]);
            }
        } else {
            // Optionnel: Message si aucun fichier n'est sélectionné
            $_SESSION['warning_message'] = "Aucun fichier n'a été joint au projet";
        }

        // 7. Ajouter une entrée dans l'historique
        $stmt = $db->prepare("INSERT INTO historique_projet (id_projet, id_utilisateur, action, date_action) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id_projet, $id_etudiant, "Soumission initiale du projet"]);

        $db->commit();
        
        // Redirection avec message de succès
        $_SESSION['success_message'] = "Projet soumis avec succès!";
        header("Location: Mesprojets.php");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        header("Location: nv_prj.php");
        exit();
    }
}

// Récupérer la liste des modules pour le select
$modules = $db->query("SELECT * FROM module ORDER BY nom_module")->fetchAll();

// Récupérer la liste des enseignants pour l'autocomplétion
$enseignants = $db->query("SELECT u.email, u.nom, u.prenom, e.grade 
                           FROM utilisateur u 
                           JOIN enseignant e ON u.id_utilisateur = e.id_enseignant 
                           ORDER BY u.nom")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Projet - ENSA Kenitra</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-color: #f5f7fa;
            --dark-color: #333;
            --gray-color: #95a5a6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            border: 2px solid white;
        }

        .user-name {
            font-weight: 500;
        }

        /* Main Container */
        .container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 2rem 1rem;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
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
            background-color: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: white;
            margin: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .page-header h2 {
            color: var(--secondary-color);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .page-header h2 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 8px;
        }

        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-secondary {
            background-color: var(--gray-color);
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        /* Form Styles */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary-color);
        }

        .form-group label.required:after {
            content: " *";
            color: var(--danger-color);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .select-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        /* File Upload Styles */
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-upload-btn {
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .file-upload-btn:hover {
            background-color: #2980b9;
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-list {
            margin-top: 1rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .file-item:hover {
            background-color: #e9ecef;
        }

        .file-item i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .file-item-name {
            flex: 1;
            font-size: 0.9rem;
        }

        .file-item-size {
            font-size: 0.8rem;
            color: #777;
            margin-left: 10px;
        }

        .file-item-remove {
            color: var(--danger-color);
            cursor: pointer;
            margin-left: 10px;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .file-item-remove:hover {
            background-color: rgba(231, 76, 60, 0.1);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        /* Form Layout */
        .form-row {
            display: flex;
            gap: 1.5rem;
        }

        .form-col {
            flex: 1;
        }

        /* Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }

        /* Autocomplete */
        .autocomplete {
            position: relative;
        }

        .autocomplete-items {
            position: absolute;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
            border-top: none;
            background-color: white;
            width: 100%;
            z-index: 99;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .autocomplete-items div {
            padding: 0.8rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
        }

        .autocomplete-items div:hover {
            background-color: #f5f7fa;
        }

        .autocomplete-items div strong {
            color: var(--primary-color);
        }

        /* Character Counter */
        .char-counter {
            font-size: 0.8rem;
            color: #666;
            text-align: right;
            margin-top: 0.5rem;
        }

        .char-counter.warning {
            color: var(--warning-color);
        }

        .char-counter.error {
            color: var(--danger-color);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
                padding: 1.5rem 0.8rem;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
            
            .btn, .btn-secondary {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                position: sticky;
                top: 0;
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
            
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                position: fixed;
                left: -250px;
                top: 80px;
                bottom: 0;
                width: 250px;
                z-index: 1000;
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(250px);
            }
            
            .main-content {
                margin-left: 0;
                margin-top: 1rem;
            }
            
            .page-header h2 {
                font-size: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .form-actions .btn, 
            .form-actions .btn-secondary {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .page-header h2 {
                font-size: 1.3rem;
            }
            
            .form-control, .select-control {
                padding: 0.7rem 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            
            <div class="logo">
                <i class="fas fa-project-diagram"></i>
                <div class="logo-text">
                    <h1>Synergia</h1>
                    <p>Gestion des Projets Étudiants</p>
                </div>
            </div>
            <div class="user-profile">
                <span class="user-name">
                    <?php 
                    if (isset($_SESSION['id_utilisateur'])) {
                        $stmt = $db->prepare("SELECT nom, prenom FROM utilisateur WHERE id_utilisateur = ?");
                        $stmt->execute([$_SESSION['id_utilisateur']]);
                        $user = $stmt->fetch();
                        echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']);
                    }
                    ?>
                </span>
            </div>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li><a href="Acceuil1.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="nv_prj.php" class="active"><i class="fas fa-plus-circle"></i> Nouveau projet</a></li>
                <li><a href="Mesprojets.php"><i class="fas fa-folder-open"></i> Mes projets</a></li>
                 <li><a href="remarques.php"><i class="fas fa-comments"></i> Remarques</a></li>
                <li><a href="actualite.php"><i class="fas fa-bullhorn"></i> Actualités</a></li> <!-- Nouvelle entrée -->
                <li><a href="message.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="param.php"><i class="fas fa-cog"></i> Paramètres</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <h2><i class="fas fa-plus-circle"></i> Nouveau Projet</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="titre">Titre du projet *</label>
                            <input type="text" id="titre" name="titre" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="type_projet">Type de projet *</label>
                            <select id="type_projet" name="type_projet" class="select-control" required>
                                <option value="">Sélectionnez un type</option>
                                <option value="Stage d'initiation">Stage d'initiation</option>
                                <option value="Stage d'ingénieur adjoint">Stage d'ingénieur adjoint</option>
                                <option value="Stage de fin d'études - PFE">Stage de fin d'études (PFE)</option>
                                <option value="projet pédagogique">Projet pédagogique</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description du projet *</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="sujet">Sujet *</label>
                            <input type="text" id="sujet" name="sujet" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group autocomplete">
                            <label for="email_encadrant">Email de l'encadrant *</label>
                            <input type="email" id="email_encadrant" name="email_encadrant" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="nom_module">Nom du module</label>
                            <input type="text" id="nom_module" name="nom_module" class="form-control">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="semestre">Semestre</label>
                            <select id="semestre" name="semestre" class="select-control">
                                <option value="">Sélectionnez un semestre</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                                <option value="S4">S4</option>
                                <option value="S5">S5</option>
                                <option value="S6">S6</option>
                                <option value="S6">S7</option>
                                <option value="S6">S8</option>
                                <option value="S6">S9</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mots_cles">Mots-clés</label>
                    <input type="text" id="mots_cles" name="mots_cles" class="form-control" placeholder="Séparez les mots-clés par des virgules">
                </div>

                <div class="form-group">
                    <label>Livrables</label>
                    <div class="file-upload">
                        <button type="button" class="file-upload-btn" onclick="document.getElementById('livrables').click()">
                            <i class="fas fa-cloud-upload-alt"></i> Ajouter des fichiers
                        </button>
                        <input type="file" id="livrables" name="livrables[]" multiple style="display: none;">
                    </div>
                    <div class="file-list" id="file-list">
                        <p style="color: #777; font-style: italic;">Aucun fichier sélectionné</p>
                    </div>
                    <small class="form-text">Formats acceptés: PDF, PPT, DOC, ZIP (max. 10MB par fichier)</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Soumettre le projet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Autocomplétion pour l'email de l'encadrant
        const enseignants = <?php echo json_encode($enseignants); ?>;
        const emailInput = document.getElementById('email_encadrant');
        const autocompleteContainer = document.createElement('div');
        autocompleteContainer.className = 'autocomplete-items';
        emailInput.parentNode.appendChild(autocompleteContainer);

        emailInput.addEventListener('input', function() {
            const input = this.value.toLowerCase();
            autocompleteContainer.innerHTML = '';
            
            if (input.length < 2) return;
            
            const filtered = enseignants.filter(ens => 
                ens.email.toLowerCase().includes(input) || 
                ens.nom.toLowerCase().includes(input) ||
                ens.prenom.toLowerCase().includes(input)
            );
            
            filtered.forEach(ens => {
                const item = document.createElement('div');
                item.innerHTML = `${ens.grade} ${ens.prenom} ${ens.nom} - <strong>${ens.email}</strong>`;
                item.addEventListener('click', function() {
                    emailInput.value = ens.email;
                    autocompleteContainer.innerHTML = '';
                });
                autocompleteContainer.appendChild(item);
            });
        });

        // Fermer l'autocomplétion quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if (e.target !== emailInput) {
                autocompleteContainer.innerHTML = '';
            }
        });

        // Gestion de l'affichage des fichiers sélectionnés
        const fileInput = document.getElementById('livrables');
        const fileList = document.getElementById('file-list');
        const maxFileSize = 10 * 1024 * 1024; // 10MB

        function getFileIcon(file) {
            if (file.type.includes('pdf')) return 'fa-file-pdf';
            if (file.type.includes('zip') || file.type.includes('rar')) return 'fa-file-archive';
            if (file.type.includes('presentation') || file.type.includes('powerpoint') || file.type.includes('ppt')) return 'fa-file-powerpoint';
            if (file.type.includes('word')) return 'fa-file-word';
            if (file.type.includes('image')) return 'fa-file-image';
            return 'fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' bytes';
            else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
            else return (bytes / 1048576).toFixed(2) + ' MB';
        }

        fileInput.addEventListener('change', function() {
            fileList.innerHTML = '';
            
            if (this.files.length === 0) {
                fileList.innerHTML = '<p style="color: #777; font-style: italic;">Aucun fichier sélectionné</p>';
                return;
            }

            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                
                if (file.size > maxFileSize) {
                    alert(`Le fichier "${file.name}" (${formatFileSize(file.size)}) dépasse la taille maximale de 10MB et ne sera pas téléchargé.`);
                    continue;
                }

                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.dataset.index = i;
                
                fileItem.innerHTML = `
                    <i class="fas ${getFileIcon(file)}"></i>
                    <span class="file-item-name">${file.name} (${formatFileSize(file.size)})</span>
                    <span class="file-item-remove"><i class="fas fa-times"></i></span>
                `;

                fileItem.querySelector('.file-item-remove').addEventListener('click', (e) => {
                    e.stopPropagation();
                    removeFile(this, fileItem.dataset.index);
                });

                fileList.appendChild(fileItem);
            }
        });

        function removeFile(input, index) {
            const files = Array.from(input.files);
            files.splice(index, 1);
            
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
            
            const event = new Event('change');
            input.dispatchEvent(event);
        }

        // Validation du formulaire
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const requiredFields = ['titre', 'type_projet', 'description', 'sujet', 'email_encadrant'];
            let isValid = true;

            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    alert(`Le champ "${field.previousElementSibling.textContent.replace('*', '').trim()}" est obligatoire.`);
                    field.focus();
                    isValid = false;
                    e.preventDefault();
                    return false;
                }
            });

            if (!isValid) return false;

            if (fileInput.files.length === 0 && !confirm('Aucun fichier n\'a été sélectionné. Souhaitez-vous tout de même soumettre votre projet ?')) {
                e.preventDefault();
                return false;
            }
        });

        // Compteur de caractères pour la description
        const descriptionField = document.getElementById('description');
        const charCounter = document.createElement('div');
        charCounter.style.fontSize = '0.8rem';
        charCounter.style.color = '#666';
        charCounter.style.textAlign = 'right';
        charCounter.style.marginTop = '0.5rem';
        descriptionField.parentNode.appendChild(charCounter);
        
        descriptionField.addEventListener('input', function() {
            const remaining = 2000 - this.value.length;
            charCounter.textContent = `${this.value.length}/2000 caractères (${remaining} restants)`;
            
            if (remaining < 0) {
                charCounter.style.color = 'red';
            } else if (remaining < 100) {
                charCounter.style.color = 'orange';
            } else {
                charCounter.style.color = '#666';
            }
        });
        
        descriptionField.dispatchEvent(new Event('input'));
    </script>
</body>
</html>