<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['nom']) || !isset($_SESSION['role']) ||!isset($_SESSION['prenom']) ) {
    // Redirige vers la page de login si non connecté
    header("Location: ../hello/login.php");
    exit();
}

// Vérifie si c'est bien un enseignant
if ($_SESSION['role'] !== 'enseignant') {
    // Redirige ailleurs selon le rôle (optionnel)
    header("Location: ../unauthorized.php"); // ou simplement login
    exit();
}


?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergia - Tableau de Bord Enseignant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="etudiant.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a id="mobileMenu_back" class="sidebar-brand" style="text-decoration: none; ">
            <div class="brand-logo"><i class="fas fa-project-diagram"></i></div>
            <span class="brand-text">Synergia</span>
        </a>
        <nav class="nav flex-column">
            <a href="#" class="nav-link active"><i class="fas fa-home me-2"></i>Accueil</a>
            <a href="actualite.php" class="nav-link "><i class="fas fa-compass me-2"></i>Actualité</a>
            <a href="message.php" class="nav-link"><i class="fas fa-envelope me-2"></i>Messages</a>
            <a href="statistique.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a>
            <a href="liste.php" class="nav-link"><i class="fas fa-list-ul me-2"></i>Liste des projets</a>
            <a href="semestre.php" class="nav-link "><i class="fas fa-calendar me-2"></i>Semestre/module</a>
            <a href="etudiant.php" class="nav-link"><i class="fas fa-users me-2"></i>Par étudiant</a>
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
                            <h2 class="display-5 fw-bold mb-3"><span class="text-primary">Bienvenue </span> <span id="teacherName"><?php echo $_SESSION['prenom'].' '.$_SESSION['nom']; ?></span> !</h2>
                            <p class="lead mb-4">Vous pouvez gérer vos projets , suivre les étudiants et collaborer avec vos collègues.</p>
                            <a href="liste.php" class="btn btn-primary px-4">
                                <i class="fas fa-rocket me-2"></i>Commencer
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ... (autres sections optimisées avec structure Bootstrap améliorée) ... -->
             <!-- À propos de Synergia -->
            <section class="mb-5 animate">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="display-6 fw-bold mb-4">Découvrez <span class="text-primary">Synergia</span></h2>
                        
                        <div class="row g-5 align-items-center">
                            <div class="col-lg-6 order-lg-1">
                                <div class="d-grid gap-4">
                                    <div class="d-flex align-items-center">
                                        <div class="feature-icon ">
                                            <i class="fas fa-check text-primary"></i>
                                        </div>
                                        <p class="mb-0 ms-3 fs-5">Centralisez tous vos projets éducatifs</p>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <div class="feature-icon ">
                                            <i class="fas fa-check text-primary"></i>
                                        </div>
                                        <p class="mb-0 ms-3 fs-5">Évaluez les travaux en temps réel</p>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <div class="feature-icon ">
                                            <i class="fas fa-check text-primary"></i>
                                        </div>
                                        <p class="mb-0 ms-3 fs-5">Collaborez avec votre équipe pédagogique</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-6 order-lg-2 align-items-center justify-content-center text-center">
                                <img src="img/creativity.svg" alt="Interface Synergia" class="img-fluid about-image" style="max-height: 50vh; max-width:50vh">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Fonctionnalités clés -->
            <section class="mb-5">
                <h2 class="fw-bold mb-4 animate">Fonctionnalités clés</h2>
                
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4 animate" style="animation-delay: 0.2s;">
                        <div class="card h-100 feature-card">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="feature-icon ">
                                        <i class="fas fa-tasks text-primary"></i>
                                    </div>
                                    <h5 class="fw-bold mb-0 ms-3">Gestion des projets</h5>
                                </div>
                                <p >Créez, organisez et suivez l'avancement de tous vos projets pédagogiques en un seul endroit.</p>
                                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1415&q=80" alt="Gestion de projet" class="img-fluid rounded mt-3">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 animate" style="animation-delay: 0.4s;">
                        <div class="card h-100 feature-card">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="feature-icon ">
                                        <i class="fas fa-user-graduate text-primary"></i>
                                    </div>
                                    <h5 class="fw-bold mb-0 ms-3">Suivi des étudiants</h5>
                                </div>
                                <p >Accédez aux profils complets de vos étudiants, consultez leurs travaux et leurs progressions.</p>
                                <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="Suivi étudiants" class="img-fluid rounded mt-3">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 animate" style="animation-delay: 0.6s;">
                        <div class="card h-100 feature-card">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="feature-icon ">
                                        <i class="fas fa-chart-line text-primary"></i>
                                    </div>
                                    <h5 class="fw-bold mb-0 ms-3">Analytique et rapports</h5>
                                </div>
                                <p >Générez des rapports détaillés et visualisez les statistiques de vos classes et projets.</p>
                                <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80"  alt="Analytique" class="img-fluid rounded mt-3">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="py-4  mt-auto">
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

    

    <<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du thème
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

        const savedTheme = localStorage.getItem('theme') || 
                         (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        html.setAttribute('data-theme', savedTheme);

        themeToggle.innerHTML = savedTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';

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