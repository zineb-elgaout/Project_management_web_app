<?php
session_start();

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: ../hello/login.php");
    exit();
}

try {
    // Nettoyage de toutes les variables de session
    $_SESSION = [];

    // Supprime le cookie de session si utilisé
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destruction de la session
    session_destroy();

    // Redirection vers la page de connexion
    header("Location: ../hello/login.php?logout=1");
    exit();

} catch (Exception $e) {
    // En cas d’erreur lors de la déconnexion
    echo "<script>alert('Erreur lors de la déconnexion : " . addslashes($e->getMessage()) . "');</script>";
}
if (isset($_GET['logout'])) {
    echo "<p style='color: green;'>Déconnexion réussie.</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Gestion des Projets ENSA</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .logout-container {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .logout-icon {
            font-size: 4rem;
            color: #3498db;
            margin-bottom: 1.5rem;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        p {
            color: #7f8c8d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: transparent;
            color: #7f8c8d;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }

        .loading-bar {
            width: 100%;
            height: 4px;
            background-color: #ecf0f1;
            border-radius: 2px;
            margin-top: 2rem;
            overflow: hidden;
            display: none;
        }

        .loading-progress {
            height: 100%;
            background-color: #3498db;
            width: 0%;
            transition: width 0.3s ease;
        }

        @media (max-width: 480px) {
            .logout-container {
                padding: 2rem 1.5rem;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h1>Déconnexion</h1>
        <p>Êtes-vous sûr de vouloir vous déconnecter de votre compte ? Vous devrez saisir à nouveau vos identifiants pour vous reconnecter.</p>
   
        <div class="container">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="Acceuil1.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="nv prj.php"><i class="fas fa-plus-circle"></i> Nouveau projet</a></li>
                    <li><a href="Mesprojets.php"  ><i class="fas fa-folder-open"></i> Mes projets</a></li>
                    <li><a href="upload.php"><i class="fas fa-file-upload"></i> Livrables</a></li>
                    <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                    <li><a href="param.php" ><i class="fas fa-cog"></i> Paramètres</a></li>
                    <li><a href="logout.php" class="active"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </aside>
        <div>
            <a href="logout.php" class="btn btn-primary">Oui, me déconnecter</a>
            <a href="Acceuil1.php" class="btn btn-secondary">Annuler</a>
        </div>
        
        <div class="loading-bar" id="loadingBar">
            <div class="loading-progress" id="loadingProgress"></div>
        </div>
    </div>
<script>
    confirmBtn.addEventListener('click', async function() {
        loadingBar.style.display = 'block';
        
        try {
            // Envoyer la requête de déconnexion au serveur
            const response = await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('userToken')}`
                }
            });
            
            if (response.ok) {
                // Nettoyer le frontend
                localStorage.removeItem('userToken');
                sessionStorage.clear();
                
                // Rediriger vers login
                window.location.href = 'login.html';
            } else {
                alert('Erreur lors de la déconnexion');
                loadingBar.style.display = 'none';
            }
        } catch (error) {
            console.error('Erreur:', error);
            loadingBar.style.display = 'none';
        }
    });
</script>
<script>
    // Surligne le lien actif dans le menu
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = location.pathname.split('/').pop();
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    });
</script>
<footer style="background:#2c3e50; color:white; padding:1rem; text-align:center; margin-top:2rem;">
    <a href="full-access.html" style="color:#3498db; text-decoration:none;">
        <i class="fas fa-expand"></i> Accès complet
    </a>
    <p style="margin-top:0.5rem;">© 2023 ENSA Projets</p>
</footer>
<!-- SCRIPT DE FORCE LAYOUT -->
<script src="assets/js/force-layout.js"></script>
          
</body>
</html>