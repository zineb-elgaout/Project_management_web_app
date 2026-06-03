# Synergia

**Synergia** est une application web de gestion et de valorisation des projets étudiants, développée pour l'environnement ENSA. Elle propose une plateforme centralisée multi-profils pour les étudiants, enseignants et administrateurs.

## Vue d'ensemble

- Plateforme web PHP + HTML/CSS
- Base de données MySQL
- Trois espaces utilisateurs principaux :
  - `admin/` : gestion administrative des utilisateurs, filières, modules, projets et sauvegarde des données
  - `teacher/` : consultation des projets, filtres par année/filière/étudiant, téléchargement de fichiers
  - `etudiant/` : création et suivi des projets, messagerie, tableau de bord personnel
- Interface publique et pages d'accueil dans `hello/`

## Fonctionnalités clés

- Authentification multi-rôles (étudiant, enseignant, administrateur)
- Gestion des projets étudiants et livrables
- Recherche et consultation des projets par critère
- Messagerie interne
- Export / sauvegarde de base de données côté administrateur
- Pages de conditions et confidentialité

## Structure du projet

- `admin/` : administration et configuration du système
- `teacher/` : espace enseignant
- `etudiant/` : espace étudiant
- `hello/` : page d'accueil, login et pages publiques
- `projet_synergia (1).sql` : script SQL de la base de données

## Prérequis

- PHP 7.x ou supérieur
- MySQL / MariaDB
- Serveur web local (XAMPP, WAMP, MAMP, etc.)
- Navigateur moderne

## Installation

1. Placez le projet dans le répertoire web du serveur local, par exemple `htdocs`.
2. Importez le fichier SQL `projet_synergia (1).sql` dans MySQL :
   - via phpMyAdmin ou un outil équivalent
   - ou en ligne de commande :
     ```bash
     mysql -u <utilisateur> -p < projet_synergia\ (1).sql
     ```
3. Vérifiez la configuration de la base de données dans `hello/config.php` :
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
4. Ouvrez le navigateur et naviguez vers :
   - `http://localhost/final_synergia1/synergia_full_stack_web_app/hello/index.html`

## Points d'accès

- Page d'accueil publique : `hello/index.html`
- Page de connexion : `hello/login.php`
- Conditions d'utilisation : `hello/conditions.html`
- Politique de confidentialité : `hello/confidentialite.html`

## Recommandations

- Sécuriser l'accès aux dossiers et fichiers sensibles en production
- Mettre à jour les identifiants de base de données et définir des permissions MySQL adaptées
- Vérifier les chemins d'upload si le projet est déplacé

## Notes

- Le projet a été conçu pour un usage académique et pédagogique.
- Le nom de la base de données est configuré dans `hello/config.php` comme `projet_synergia`.

---

Pour toute amélioration ou déploiement, commencez par vérifier les fichiers de configuration et adaptez les paramètres au serveur cible.
