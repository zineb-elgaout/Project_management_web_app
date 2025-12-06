<?php
session_start();

require_once __DIR__ . '/../hello/config.php';

// Vérification de la session
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: ../hello/login.php");
    exit();
}

// Vérifier que l'ID du projet est présent dans l'URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: Mesprojets.php");
    exit();
}

$projet_id = $_GET['id'];

try {
    // Récupérer les détails complets du projet
    $sql = "
        SELECT 
            p.*,
            ue.nom AS enseignant_nom,
            ue.prenom AS enseignant_prenom,
            ue.email AS enseignant_email,
            ud.nom AS etudiant_nom,
            ud.prenom AS etudiant_prenom,
            ud.email AS etudiant_email,
            GROUP_CONCAT(DISTINCT m.nom_module SEPARATOR ', ') AS modules,
            GROUP_CONCAT(DISTINCT mc.terme SEPARATOR ', ') AS mots_cles,
            GROUP_CONCAT(DISTINCT CONCAT(l.type_livrable, ':', l.chemin_fichier) SEPARATOR '|') AS livrables
        FROM projet p
        LEFT JOIN enseignant e ON p.id_enseignant = e.id_enseignant
        LEFT JOIN utilisateur ue ON e.id_enseignant = ue.id_utilisateur
        LEFT JOIN etudiant et ON p.id_etudiant = et.id_etudiant
        LEFT JOIN utilisateur ud ON et.id_etudiant = ud.id_utilisateur
        LEFT JOIN projet_module pm ON p.id_projet = pm.id_projet
        LEFT JOIN module m ON pm.id_module = m.id_module
        LEFT JOIN projet_mot_cle pmc ON p.id_projet = pmc.id_projet
        LEFT JOIN mot_cle mc ON pmc.id_mot_cle = mc.id_mot_cle
        LEFT JOIN livrable l ON p.id_projet = l.id_projet
        WHERE p.id_projet = ?
        GROUP BY p.id_projet
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$projet_id]);
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$projet) {
        header("Location: Mesprojets.php");
        exit();
    }

    // Traitement des livrables
    $livrables = [];
    if (!empty($projet['livrables'])) {
        $livrables_raw = explode('|', $projet['livrables']);
        foreach ($livrables_raw as $livrable) {
            list($type, $chemin) = explode(':', $livrable);
            $livrables[] = [
                'type' => $type,
                'chemin' => $chemin,
                'nom_fichier' => basename($chemin)
            ];
        }
    }

} catch (PDOException $e) {
    die("Erreur lors de la récupération des détails du projet: " . $e->getMessage());
}

// Fonction pour obtenir l'icône du fichier
function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf': return 'fa-file-pdf';
        case 'doc':
        case 'docx': return 'fa-file-word';
        case 'ppt':
        case 'pptx': return 'fa-file-powerpoint';
        case 'zip':
        case 'rar': return 'fa-file-archive';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif': return 'fa-file-image';
        default: return 'fa-file';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Projet - ENSA Kenitra</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            background-color: #f5f7fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 8px;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-secondary {
            background-color: #95a5a6;
        }

        .project-details {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .project-title {
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .project-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-en_attente {
            background-color: #f1c40f;
            color: white;
        }

        .status-valide {
            background-color: #2ecc71;
            color: white;
        }

        .status-rejete {
            background-color: #e74c3c;
            color: white;
        }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .meta-group {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .meta-group h3 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .meta-item {
            margin-bottom: 1rem;
        }

        .meta-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.3rem;
            display: block;
        }

        .meta-value {
            color: #333;
        }

        .project-description {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .project-files {
            margin-top: 2rem;
        }

        .files-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            background-color: #e9ecef;
        }

        .file-icon {
            margin-right: 1rem;
            font-size: 1.5rem;
            color: #3498db;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .file-type {
            font-size: 0.8rem;
            color: #666;
        }

        .file-actions {
            margin-left: 1rem;
        }

        .btn-download {
            background-color: transparent;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .project-meta {
                grid-template-columns: 1fr;
            }
            
            .files-list {
                grid-template-columns: 1fr;
            }
            
            .project-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .project-status {
                align-self: flex-start;
            }
        }
        .files-list {
        width: 100%;
    }
    .file-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        margin-bottom: 10px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .file-item:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .file-icon {
        margin-right: 15px;
        font-size: 24px;
        color: #3498db;
    }
    .file-info {
        flex-grow: 1;
    }
    .file-name {
        font-weight: 500;
        margin-bottom: 3px;
    }
    .file-type {
        font-size: 0.85em;
        color: #666;
    }
    .file-actions .btn-download {
        color: #3498db;
        font-size: 18px;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    .file-actions .btn-download:hover {
        background: #f0f7fd;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Détails du Projet</h1>
            <a href="Mesprojets.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <div class="project-details">
            <div class="project-header">
                <h2 class="project-title"><?= htmlspecialchars($projet['titre']) ?></h2>
                <span class="project-status status-<?= htmlspecialchars($projet['statut']) ?>">
                    <?php 
                    switch ($projet['statut']) {
                        case 'en_attente': echo "En attente"; break;
                        case 'valide': echo "Validé"; break;
                        case 'rejete': echo "Rejeté"; break;
                        default: echo ucfirst($projet['statut']);
                    }
                    ?>
                </span>
            </div>

            <div class="project-meta">
                <div class="meta-group">
                    <h3>Informations de base</h3>
                    <div class="meta-item">
                        <span class="meta-label">Type de projet</span>
                        <span class="meta-value"><?= htmlspecialchars($projet['type_projet']) ?></span>
                    </div>
                    <?php if (!empty($projet['modules'])): ?>
                    <div class="meta-item">
                        <span class="meta-label">Modules associés</span>
                        <span class="meta-value"><?= htmlspecialchars($projet['modules']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($projet['mots_cles'])): ?>
                    <div class="meta-item">
                        <span class="meta-label">Mots-clés</span>
                        <span class="meta-value"><?= htmlspecialchars($projet['mots_cles']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span class="meta-label">Date de soumission</span>
                        <span class="meta-value"><?= date('d/m/Y', strtotime($projet['date_soumission'])) ?></span>
                    </div>
                </div>

                <div class="meta-group">
                    <h3>Encadrement</h3>
                    <?php if (!empty($projet['enseignant_prenom']) && !empty($projet['enseignant_nom'])): ?>
                    <div class="meta-item">
                        <span class="meta-label">Encadrant</span>
                        <span class="meta-value">
                            <?= htmlspecialchars($projet['enseignant_prenom'] . ' ' . $projet['enseignant_nom']) ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Email encadrant</span>
                        <span class="meta-value"><?= htmlspecialchars($projet['enseignant_email']) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="meta-item">
                        <span class="meta-value">Aucun encadrant attribué</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="meta-group">
                    <h3>Étudiant</h3>
                    <div class="meta-item">
                        <span class="meta-label">Porteur du projet</span>
                        <span class="meta-value">
                            <?= htmlspecialchars($projet['etudiant_prenom'] . ' ' . $projet['etudiant_nom']) ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Email étudiant</span>
                        <span class="meta-value"><?= htmlspecialchars($projet['etudiant_email']) ?></span>
                    </div>
                </div>
            </div>

            <div class="project-description">
                <h3>Description du projet</h3>
                <p><?= nl2br(htmlspecialchars($projet['description'])) ?></p>
            </div>

            <?php if (!empty($livrables)): ?>
            <div class="project-files">
                <h3>Livrables</h3>
                <div class="files-list">
                    <?php foreach ($livrables as $livrable): ?>
                    <div class="file-item ">
                        <div class="file-icon">
                            <i class="fas <?= getFileIcon($livrable['nom_fichier']) ?>"></i>
                        </div>
                        <div class="file-info">
                            <div class="file-name"><?= htmlspecialchars($livrable['nom_fichier']) ?></div>
                            <div class="file-type"><?= htmlspecialchars($livrable['type']) ?></div>
                        </div>
                        <div class="file-actions" >
                            <a href="<?= htmlspecialchars($livrable['chemin']) ?>" download class="btn-download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
    
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="project-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                <a href="Mesprojets.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                
            </div>
        </div>
    </div>
</body>
</html>