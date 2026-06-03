<?php
session_start();
include("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pwd = $_POST['mot_de_passe'];
    $hashed_pwd = hash('sha256', $pwd);

    // Vérifier que l'email se termine par @uit.ac.ma
    if (substr($email, -10) !== "@uit.ac.ma") {
        $error = "L'email doit se terminer par @uit.ac.ma.";
    } else {
        // Requête PDO pour vérifier l'utilisateur
        $stmt = $db->prepare("SELECT * FROM utilisateur WHERE email = :email AND mot_de_passe = :mot_de_passe");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':mot_de_passe', $hashed_pwd);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(); // Suppression de la ligne en double
            
            // Stockage des informations utilisateur en session
            $_SESSION['id_utilisateur'] = $user['id_utilisateur']; 
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email']; 

            // Debug: vérifier le contenu de la session
            error_log("Utilisateur connecté: " . print_r($_SESSION, true));

            // Redirection selon le rôle
            switch ($user['role']) {
                case 'etudiant':
                    header("Location: ../etudiant/Acceuil1.php");
                    break;
                case 'enseignant':
                    header("Location: ../teacher/index.php");
                    break;
                case 'admin':
                    header("Location: ../admin/admin_dashboard.php");
                    break;
                default:
                    $error = "Rôle inconnu.";
                    error_log("Rôle inconnu détecté: " . $user['role']);
            }
            exit();
        } else {
            $error = "Identifiants incorrects.";
            error_log("Tentative de connexion échouée pour: " . $email);
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
  <link  href="img/logo-blue.png" type="image/png" rel="icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="bootstrap5/bootstrap.min.css" rel="stylesheet" />
    <title>Se connecter</title>
    <style>
      :root {
        /* Light Theme */
        --primary-color: #6366f1;
        --secondary-color: #4f46e5;
        --background: #ffffff;
        --surface: #f8fafc;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --border-color: #e2e8f0;
        --hover-bg: #f1f5f9;
        --active-bg: #e0e7ff;
        --card-bg: #ffffff;
        --icon-bg: rgba(99, 102, 241, 0.1);
        --shadow: 0 1px 3px rgba(0,0,0,0.05);
        --sidebar-width: 250px;
      }

      [data-theme="dark"] {
        /* Dark Theme */
        --primary-color: #818cf8;
        --secondary-color: #6366f1;
        --background: #0f172a;
        --surface: #1e293b;
        --text-primary: #f8fafc;
        --text-secondary: #94a3b8;
        --border-color: #334155;
        --hover-bg: #1e293b;
        --active-bg: #475569;
        --card-bg: #1e293b;
        --icon-bg: rgba(129, 140, 248, 0.1);
        --shadow: 0 1px 3px rgba(0,0,0,0.3);
      }

      * {
        font-family: 'Poppins', sans-serif;
        transition: background-color 0.3s ease, color 0.2s ease, border-color 0.3s ease;
      }

      body {
        background: var(--surface);
        color: var(--text-primary);
        min-height: 100vh;
        line-height: 1.6;
      }
      /* Thème Switch */
      .theme-switch {
        position: relative;
        margin-top: auto;
      }

      .theme-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--icon-bg);
        color: var(--primary-color);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
      }

      .theme-btn:hover {
        background: var(--hover-bg);
        transform: rotate(15deg);
      }
      .card{
        background-color: var(--background);
        color: var(--text-primary);
      }
      /*form input*/
      /* Styles personnalisés pour les inputs */
    .form-control {
        background: var(--background) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        padding: 0.75rem 1rem;
        border-radius: 8px !important;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        border-color: var(--primary-color) !important;
        outline: none;
    }

    .form-control::placeholder {
        color: var(--text-secondary);
        opacity: 0.7;
    }
      
      /* Buttons */
      .btn-primary {
        background: var(--primary-color);
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        letter-spacing: 0.5px;
      }

      .btn-primary:hover {
        background: var(--secondary-color);
        transform: translateY(-1px);
      }
    </style>
</head>
<body>
    <section class="vh-100">
        <div class="container h-100">
          <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-lg-12 col-xl-11">
              <div class="card " style="border-radius: 25px;">
                <div class="card-body p-md-5">
                  <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-6 col-xl-5 order-2 order-lg-1">
      
                      <p class="text-center h1 fw-bold mb-4 mx-1 mx-md-4 mt-4">Se connecter</p>
                      
                        <div class="form-check d-flex justify-content-center me-3 ">
                          <?php if (isset($error)) echo "<p style='color:red;'>$error</p>";?>
                          
                        </div>
      
                      <form class="mx-1 mx-md-4" action="login.php" method="post" enctype="multipart/form-data">
                        
      
                        <div class="d-flex flex-row align-items-center mb-4">
                          <div  class="form-outline flex-fill mb-0">
                            <input type="email" name="email" class="form-control" placeholder="exemple@uit.ac.ma" required="required"/>
                          </div>
                        </div>
      
                        <div class="d-flex flex-row align-items-center mb-4">
                          <div class="form-outline flex-fill mb-0">
                            <input type="password" name="mot_de_passe" class="form-control" placeholder="Mot De Passe" required="required"/>
                          </div>
                        </div>
      
      
                        <div class="form-check d-flex justify-content-center mb-4">
                          <input id="accept" class="form-check-input me-2" type="checkbox" value="" required="required"/>
                          <label class="form-check-label" for="accept">
                            J'accepte les <a href="conditions.html">Conditions d'utilisation</a>
                          </label>
                        </div>
                        
      
                        <div class="d-flex justify-content-center mx-4 mb-3 mb-lg-4">
                          <button  type="submit"class="btn btn-primary btn-lg">Se connecter</button>
                        </div>
                        
                        <div class="d-flex justify-content-center align-content-center theme-switch mt-auto mb-3">
                            <button class="theme-btn me-3" id="themeToggle"><i class="fas fa-moon"></i></button>
                        
                            <a href="index.html" class="btn-retour">
                                <i class="fas fa-home theme-btn"></i> 
                            </a>
                        </div>
                        
                      </form>
      
                    </div>
                    <div class="col-md-10 col-lg-6 col-xl-7 d-flex align-items-center order-1 order-lg-2">
      
                      <img src="img/programming.svg"
                        class="img-fluid" alt="Sample image">
      
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <script>
         // Basculer le thème
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
        document.documentElement.setAttribute('data-theme', 
            document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'
        );
        themeToggle.innerHTML = document.documentElement.getAttribute('data-theme') === 'dark' ? 
            '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });
      </script>
</body>
</html>