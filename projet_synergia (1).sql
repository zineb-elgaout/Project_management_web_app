-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3307
-- Généré le : lun. 02 juin 2025 à 08:39
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projet_synergia`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `departement` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id_admin`, `departement`) VALUES
(1, 'Génie Informatique'),
(2, 'Bâtiment Intelligent et Efficacité Energétique'),
(3, 'Génie Réseau et Systèmes de Télécommunication'),
(4, 'Génie Electrique'),
(5, 'Génie Mécatronique'),
(6, 'Génie Industriel');

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contacts`
--

INSERT INTO `contacts` (`id`, `nom`, `email`, `message`, `date_creation`, `lu`) VALUES
(1, 'zozo', 'zineb@gmail.com', 'salma cv  , hda meaassga epour tester le contact', '2025-05-31 07:56:33', 0),
(2, 'zozo', 'zineb@gmail.com', 'salma cv  , hda meaassga epour tester le contact', '2025-05-31 07:57:33', 0),
(3, 'zozo', 'zineb@gmail.com', 'salma cv  , hda meaassga epour tester le contact', '2025-05-31 07:59:54', 0),
(4, 'zozo', 'zineb@gmail.com', 'salma cv  , hda meaassga epour tester le contact', '2025-05-31 08:00:01', 0),
(5, 'zozo', 'zineb@gmail.com', 'salma cv  , hda meaassga epour tester le contact', '2025-05-31 08:23:32', 0),
(6, 'salwa', 'salwa@gmail.com', 'bonjour a tous !', '2025-06-02 06:13:33', 0);

-- --------------------------------------------------------

--
-- Structure de la table `enseignant`
--

CREATE TABLE `enseignant` (
  `id_enseignant` int(11) NOT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `specialite` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `enseignant`
--

INSERT INTO `enseignant` (`id_enseignant`, `grade`, `specialite`) VALUES
(7, 'Professeur', 'Algorithmique Avancée'),
(8, 'Maître de conférences', 'Base de Données'),
(9, 'Professeur', 'Efficacité Energétique'),
(10, 'Maître assistant', 'Domotique'),
(11, 'Professeur', 'Réseaux 5G'),
(12, 'Maître de conférences', 'Sécurité Réseaux'),
(13, 'Professeur', 'Énergies Renouvelables'),
(14, 'Maître assistant', 'Smart Grids'),
(15, 'Professeur', 'Robotique Industrielle'),
(16, 'Maître de conférences', 'Automatisme'),
(17, 'Professeur', 'Logistique'),
(18, 'Maître assistant', 'Gestion de Production');

-- --------------------------------------------------------

--
-- Structure de la table `etudiant`
--

CREATE TABLE `etudiant` (
  `id_etudiant` int(11) NOT NULL,
  `cne` varchar(50) DEFAULT NULL,
  `filiere` varchar(100) DEFAULT NULL,
  `annee_scolaire` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etudiant`
--

INSERT INTO `etudiant` (`id_etudiant`, `cne`, `filiere`, `annee_scolaire`) VALUES
(19, 'G135792468', 'Génie Informatique', '2023-2024'),
(20, 'G246813579', 'Génie Mécatronique', '2024-2025'),
(21, 'G987654321', 'Génie Industriel', '2023-2024'),
(22, 'G159753468', 'Bâtiment Intelligent et Efficacité Energétique', '2023-2024'),
(23, 'G258456123', 'Bâtiment Intelligent et Efficacité Energétique', '2023-2024'),
(24, 'G357159468', 'Génie Réseau et Systèmes de Télécommunication', '2023-2024'),
(25, 'G456789123', 'Génie Electrique', '2023-2024'),
(26, 'G654987321', 'Génie Electrique', '2023-2024'),
(27, 'G753951486', 'Génie Mécatronique', '2023-2024'),
(28, 'G852741963', 'Génie Industriel', '2023-2024');

-- --------------------------------------------------------

--
-- Structure de la table `historique_projet`
--

CREATE TABLE `historique_projet` (
  `id_historique` int(11) NOT NULL,
  `id_projet` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `date_action` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_projet`
--

INSERT INTO `historique_projet` (`id_historique`, `id_projet`, `id_utilisateur`, `action`, `date_action`) VALUES
(1, 1, 7, 'Validation du projet après soutenance', '2025-05-21 21:43:33'),
(2, 1, 19, 'Soumission du rapport final', '2025-05-21 21:43:33'),
(3, 3, 7, 'Correction des remarques de la première soutenance', '2025-05-21 21:43:33'),
(4, 3, 21, 'Dépôt de la version finale du code', '2025-05-21 21:43:33'),
(5, 3, NULL, 'Changement de statut: refuse', '2025-05-22 00:43:57'),
(6, 3, NULL, 'Changement de statut: a_modifier', '2025-05-22 00:44:02'),
(7, 3, NULL, 'Changement de statut: a_modifier', '2025-05-22 00:44:08'),
(8, 3, NULL, 'Changement de statut: valide', '2025-05-22 00:44:11'),
(9, 3, NULL, 'Changement de statut: refuse', '2025-05-22 00:44:14'),
(10, 2, NULL, 'Changement de statut: valide', '2025-05-22 00:44:31'),
(11, 2, NULL, 'Changement de statut: a_modifier', '2025-05-22 00:45:32'),
(12, 2, NULL, 'Changement de statut: a_modifier', '2025-05-22 00:47:06'),
(13, 2, NULL, 'Changement de statut: valide', '2025-05-22 00:49:24'),
(14, 3, NULL, 'Changement de statut: valide', '2025-05-22 01:01:48'),
(15, 3, NULL, 'Changement de statut: a_modifier', '2025-05-22 01:03:40'),
(16, 3, NULL, 'Changement de statut: refuse', '2025-05-22 01:04:01'),
(17, 3, NULL, 'Changement de statut: valide', '2025-05-22 01:09:55'),
(18, 3, NULL, 'Changement de statut: a_modifier', '2025-05-22 01:11:40'),
(19, 3, NULL, 'Changement de statut: valide', '2025-05-22 01:11:48'),
(20, 3, NULL, 'Changement de statut: en_attente', '2025-05-22 01:11:59'),
(21, 3, NULL, 'Changement de statut: a_modifier', '2025-05-22 01:21:37'),
(22, 3, NULL, 'Changement de statut: refuse', '2025-05-22 01:54:04'),
(23, 4, NULL, 'Changement de statut: valide', '2025-05-22 01:57:46'),
(24, 3, NULL, 'Changement de statut: valide', '2025-05-22 01:58:29'),
(25, 4, NULL, 'Changement de statut: en_attente', '2025-05-22 02:01:13'),
(26, 4, NULL, 'Changement de statut: valide', '2025-05-22 02:01:26'),
(27, 4, NULL, 'Changement de statut: refuse', '2025-05-22 10:20:22'),
(28, 4, NULL, 'Changement de statut: a_modifier', '2025-05-22 10:22:01'),
(29, 4, NULL, 'Changement de statut: refuse', '2025-05-22 11:24:15'),
(30, 4, NULL, 'Changement de statut: a_modifier', '2025-05-22 13:30:38'),
(31, 1, NULL, 'Changement de statut: refuse', '2025-05-23 00:42:55'),
(32, 1, NULL, 'Changement de statut: en_attente', '2025-05-23 00:56:25'),
(33, 1, NULL, 'Changement de statut: refuse', '2025-05-23 00:57:26'),
(34, 4, NULL, 'Changement de statut: a_modifier', '2025-05-23 00:57:42'),
(35, 4, NULL, 'Changement de statut: a_modifier', '2025-05-23 01:10:03'),
(36, 4, NULL, 'Changement de statut: refuse', '2025-05-23 01:13:06'),
(37, 4, NULL, 'Changement de statut: en_attente', '2025-05-23 01:13:08'),
(38, 4, NULL, 'Changement de statut: valide', '2025-05-23 01:13:20'),
(39, 4, NULL, 'Changement de statut: en_attente', '2025-05-23 01:13:23'),
(40, 1, NULL, 'Changement de statut: en_attente', '2025-05-23 01:15:45'),
(41, 1, 7, 'Changement de statut: valide', '2025-05-24 01:07:53'),
(42, 4, 7, 'Changement de statut: valide', '2025-05-24 01:07:56'),
(43, 4, 7, 'Changement de statut: refuse', '2025-05-24 19:15:52'),
(44, 6, 22, 'Soumission initiale du projet', '2025-06-02 00:42:34'),
(45, 6, 7, 'Changement de statut: valide', '2025-06-02 00:44:36'),
(46, 6, 7, 'Changement de statut: en_attente', '2025-06-02 00:45:13'),
(47, 7, 22, 'Soumission initiale du projet', '2025-06-02 00:54:50'),
(48, 8, 22, 'Soumission initiale du projet', '2025-06-02 01:19:43'),
(49, 8, 7, 'Changement de statut: valide', '2025-06-02 01:56:18'),
(50, 7, 7, 'Changement de statut: valide', '2025-06-02 07:22:49');

-- --------------------------------------------------------

--
-- Structure de la table `likes`
--

CREATE TABLE `likes` (
  `id_like` int(11) NOT NULL,
  `id_projet` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_like` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `likes`
--

INSERT INTO `likes` (`id_like`, `id_projet`, `id_utilisateur`, `date_like`) VALUES
(1, 2, 7, '2025-05-24 01:07:09'),
(2, 3, 7, '2025-05-24 01:07:12'),
(3, 3, 18, '2025-05-24 01:12:15'),
(5, 1, 18, '2025-05-24 01:12:23'),
(11, 2, 18, '2025-05-24 01:20:30'),
(13, 4, 7, '2025-05-24 19:14:41'),
(15, 8, 7, '2025-06-02 07:20:45'),
(16, 7, 7, '2025-06-02 07:24:41');

-- --------------------------------------------------------

--
-- Structure de la table `livrable`
--

CREATE TABLE `livrable` (
  `id_livrable` int(11) NOT NULL,
  `id_projet` int(11) DEFAULT NULL,
  `type_livrable` enum('rapport','code_source','diaporama') DEFAULT NULL,
  `chemin_fichier` varchar(255) DEFAULT NULL,
  `date_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `livrable`
--

INSERT INTO `livrable` (`id_livrable`, `id_projet`, `type_livrable`, `chemin_fichier`, `date_upload`) VALUES
(1, 1, 'rapport', 'uploads/livrables/projet1_rapport.pdf', '2025-05-21 21:43:33'),
(2, 1, 'code_source', 'uploads/livrables/projet1_code.zip', '2025-05-21 21:43:33'),
(3, 1, 'diaporama', 'uploads/livrables/projet1_presentation.pptx', '2025-05-21 21:43:33'),
(4, 2, 'rapport', 'uploads/livrables/projet2_rapport.pdf', '2025-05-21 21:43:33'),
(5, 2, 'code_source', 'uploads/livrables/projet2_code.rar', '2025-05-21 21:43:33'),
(6, 3, 'rapport', 'uploads/livrables/projet3_rapport.pdf', '2025-05-21 21:43:33'),
(7, 3, 'code_source', 'uploads/livrables/projet3_source.zip', '2025-05-21 21:43:33'),
(8, 3, 'diaporama', 'uploads/livrables/projet3_soutenance.pptx', '2025-05-21 21:43:33'),
(9, 7, 'diaporama', 'uploads/livrables/683ce84a1d894_firefly.pptx', '2025-06-02 00:54:50'),
(10, 8, 'rapport', 'uploads/livrables/683cee1ef3751_firefly.pdf', '2025-06-02 01:19:42'),
(11, 8, 'diaporama', 'uploads/livrables/683cee1ef3d6d_firefly.pptx', '2025-06-02 01:19:43');

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

CREATE TABLE `message` (
  `id_message` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `date_envoi` datetime DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `message`
--

INSERT INTO `message` (`id_message`, `expediteur_id`, `destinataire_id`, `sujet`, `contenu`, `date_envoi`, `lu`) VALUES
(1, 7, 7, 'tester la base de donnée', 'salam fatima kidayra cv', '2025-05-31 09:50:19', 1),
(2, 7, 1, 'demande de certificat', 'bonjour monsieur mohammed , \r\nj\'epère que vous allez bien ,\r\nveuillez m\'envoyer ma certificat de participation à la compétition organisée par le club EIC \r\ncordialement ,', '2025-05-31 09:58:38', 0),
(3, 7, 1, 'test', 'bonjourno !\r\ncbkzckbhejcbbbbbbbcjhehbv jhbvhv,d ; kj zjhbkfh n,vjkfbvkjbfjvbhv,  ,vjbvmljcv  njkvhimv,:nv ,nbhvjkc  nvkjhjvbskHNDJKV NBCV ? SINCKJDNHCKJ? CHJZBFJHCN ?DL/ NKJFJNVBV  .VNUHFVIURVN.VNFKJNNV ? HKIJN?V?HDBVHJ VHBJVGSJDBHCNDS ?NSDBHJBHCN?QS.N?CQ?DB CQDJNVJKLMDJKJFJQLKFZIEFNFQN', '2025-05-31 13:02:41', 0),
(4, 7, 4, 'message_titre', 'bonjour samira !', '2025-06-02 07:21:51', 0);

-- --------------------------------------------------------

--
-- Structure de la table `module`
--

CREATE TABLE `module` (
  `id_module` int(11) NOT NULL,
  `nom_module` varchar(100) DEFAULT NULL,
  `semestre` varchar(10) DEFAULT NULL,
  `annee_module` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `module`
--

INSERT INTO `module` (`id_module`, `nom_module`, `semestre`, `annee_module`) VALUES
(1, 'Base de Données Avancées', 'S4', '2023-2024'),
(2, 'Développement Web', 'S4', '2023-2024'),
(3, 'Algorithmique Distribuée', 'S6', '2023-2024'),
(4, 'technologie web', 'S6', '2025-2026');

-- --------------------------------------------------------

--
-- Structure de la table `mot_cle`
--

CREATE TABLE `mot_cle` (
  `id_mot_cle` int(11) NOT NULL,
  `terme` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `mot_cle`
--

INSERT INTO `mot_cle` (`id_mot_cle`, `terme`) VALUES
(11, 'académique'),
(20, 'bootstrap'),
(18, 'css'),
(8, 'Django'),
(9, 'gestion de projet'),
(17, 'html'),
(3, 'JavaScript'),
(6, 'MongoDB'),
(2, 'MySQL'),
(5, 'Node.js'),
(1, 'PHP'),
(7, 'Python'),
(4, 'React'),
(19, 'sql'),
(10, 'web'),
(21, 'xampp');

-- --------------------------------------------------------

--
-- Structure de la table `post`
--

CREATE TABLE `post` (
  `id_post` int(11) NOT NULL,
  `id_projet` int(11) NOT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `visible` tinyint(1) DEFAULT 1,
  `signales` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post`
--

INSERT INTO `post` (`id_post`, `id_projet`, `date_publication`, `visible`, `signales`) VALUES
(1, 3, '2025-05-22 00:44:11', 1, 0),
(2, 2, '2025-05-22 00:44:31', 1, 0),
(3, 2, '2025-05-22 00:49:24', 1, 0),
(4, 3, '2025-05-22 01:01:48', 1, 0),
(5, 3, '2025-05-22 01:09:55', 1, 0),
(6, 3, '2025-05-22 01:11:48', 1, 0),
(7, 4, '2025-05-22 01:57:46', 1, 0),
(8, 3, '2025-05-22 01:58:29', 1, 0),
(9, 4, '2025-05-22 02:01:26', 1, 0),
(10, 4, '2025-05-23 01:13:20', 1, 0),
(11, 1, '2025-05-24 01:07:53', 1, 0),
(12, 4, '2025-05-24 01:07:56', 1, 0),
(13, 6, '2025-06-02 00:44:36', 1, 0),
(14, 8, '2025-06-02 01:56:18', 1, 0),
(15, 7, '2025-06-02 07:22:49', 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `projet`
--

CREATE TABLE `projet` (
  `id_projet` int(11) NOT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type_projet` enum('Stage d''initiation','Stage d''ingénieur adjoint','Stage de fin d''études - PFE','projet pédagogique') DEFAULT NULL,
  `sujet` varchar(100) DEFAULT NULL,
  `date_soumission` datetime DEFAULT current_timestamp(),
  `statut` enum('en_attente','valide','refuse') DEFAULT 'en_attente',
  `id_etudiant` int(11) DEFAULT NULL,
  `id_enseignant` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `projet`
--

INSERT INTO `projet` (`id_projet`, `titre`, `description`, `type_projet`, `sujet`, `date_soumission`, `statut`, `id_etudiant`, `id_enseignant`) VALUES
(1, 'Système de gestion de bibliothèque', 'Développement d\'une application web pour la gestion des prêts de livres avec interface admin', 'projet pédagogique', 'Informatique', '2025-05-21 21:43:33', 'valide', 19, 7),
(2, 'Plateforme e-learning', 'Création d\'une plateforme d\'apprentissage en ligne avec suivi des progrès', 'Stage d\'initiation', 'Technologies Web', '2025-05-21 21:43:33', 'valide', 20, 7),
(3, 'Application de gestion de tâches', 'Application mobile et web de gestion de tâches avec synchronisation en temps réel', 'Stage de fin d\'études - PFE', 'Développement Fullstack', '2025-05-21 21:43:33', 'valide', 21, 7),
(4, 'Système de gestion des projets académiques', 'Développement d\'une plateforme web pour la gestion et le suivi des projets étudiants', 'projet pédagogique', 'Informatique', '2025-05-22 01:57:27', 'refuse', 21, 7),
(6, 'projet de site web synergia', 'ce projet a trois espaces : etudiant , enseignant , et admin', 'projet pédagogique', 'informatique', '2025-06-02 00:42:34', 'en_attente', 22, 7),
(7, 'projet de site web ensa_commerce', 'ce projet est realise avec html , css, javascript,sql,bootstrap,php, xampp , dans le cadrede module de technologie web', 'Stage d\'ingénieur adjoint', 'informatique', '2025-06-02 00:54:50', 'valide', 22, 7),
(8, 'projet de site web ensa_transport', 'projet de site web ensa_transport , outils: html , css, javascript,sql,bootstrap,php, xampp', 'Stage de fin d\'études - PFE', 'informatique', '2025-06-02 01:19:42', 'valide', 22, 7);

--
-- Déclencheurs `projet`
--
DELIMITER $$
CREATE TRIGGER `after_projet_validate` AFTER UPDATE ON `projet` FOR EACH ROW BEGIN
    IF NEW.statut = 'valide' AND OLD.statut != 'valide' THEN
        INSERT INTO post (id_projet) VALUES (NEW.id_projet);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `projet_module`
--

CREATE TABLE `projet_module` (
  `id_projet` int(11) NOT NULL,
  `id_module` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `projet_module`
--

INSERT INTO `projet_module` (`id_projet`, `id_module`) VALUES
(1, 1),
(1, 2),
(2, 2),
(3, 1),
(3, 3),
(4, 2),
(6, 4),
(7, 4),
(8, 4);

-- --------------------------------------------------------

--
-- Structure de la table `projet_mot_cle`
--

CREATE TABLE `projet_mot_cle` (
  `id_projet` int(11) NOT NULL,
  `id_mot_cle` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `projet_mot_cle`
--

INSERT INTO `projet_mot_cle` (`id_projet`, `id_mot_cle`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 3),
(2, 4),
(2, 5),
(3, 6),
(3, 7),
(3, 8),
(4, 9),
(4, 10),
(4, 11),
(6, 1),
(6, 3),
(6, 17),
(6, 18),
(6, 19),
(6, 20),
(6, 21),
(7, 1),
(7, 3),
(7, 17),
(7, 18),
(7, 19),
(7, 20),
(7, 21),
(8, 1),
(8, 3),
(8, 17),
(8, 18),
(8, 19),
(8, 20),
(8, 21);

-- --------------------------------------------------------

--
-- Structure de la table `remarque`
--

CREATE TABLE `remarque` (
  `id_remarque` int(11) NOT NULL,
  `id_projet` int(11) DEFAULT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `remarque_text` text DEFAULT NULL,
  `evaluation` int(11) DEFAULT NULL CHECK (`evaluation` >= 0 and `evaluation` <= 20),
  `date_remarque` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `remarque`
--

INSERT INTO `remarque` (`id_remarque`, `id_projet`, `id_enseignant`, `remarque_text`, `evaluation`, `date_remarque`) VALUES
(1, 1, 7, 'Excellent travail sur la partie base de données. L\'interface pourrait être améliorée.', 16, '2025-05-21 21:43:33'),
(13, 3, NULL, 'tres bien youssef', 17, '2025-05-22 01:09:43'),
(16, 4, 7, 'mauvais travail', 10, '2025-05-24 19:16:16'),
(17, 8, 7, 'tres bon travail , super!', 18, '2025-06-02 01:56:33'),
(18, 7, 7, 'excellent travail', 15, '2025-06-02 07:24:13'),
(19, 2, 7, 'bon travvail lela ', 20, '2025-06-02 07:26:09');

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `setting_name` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`setting_name`, `setting_value`) VALUES
('academic_year', '2024-2025'),
('favicon', ''),
('logo', ''),
('school_name', 'ENSA Kenitra'),
('submissions_active', '1');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `role` enum('etudiant','enseignant','admin') DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `date_creation`) VALUES
(1, 'Alaoui', 'Mohamed', 'mohamed.alaoui@uit.ac.ma', '80ea677463f1309a0b3cec2a23b2e60c9604aa0530ceee6cde33474248fe2d55', 'admin', '2025-05-21 13:22:26'),
(2, 'Benbrahim', 'Karima', 'karima.benbrahim@uit.ac.ma', 'f216a1e9b17fe0768563d8b9423afbf154aadf7cd8091e32ac7b6ad6fe5d3736', 'admin', '2025-05-21 13:22:26'),
(3, 'El Fassi', 'Driss', 'driss.elfassi@uit.ac.ma', 'd5ba8c6a2a41cb79c8d29ed2e67929e54fb44e2cd27b80a83d40341b33ee9af8', 'admin', '2025-05-21 13:22:26'),
(4, 'Zouhair', 'Samira', 'samira.zouhair@uit.ac.ma', '55ba9b6d39c02630a4014f5b3cdc70db96ff6b386dbffc4e487324cb5adfcabf', 'admin', '2025-05-21 13:22:26'),
(5, 'Mansouri', 'Youssef', 'youssef.mansouri@uit.ac.ma', 'a3a88320fe64d0b75e939d3146a351409f6380384ecdf77b1072eeb8489d57ae', 'admin', '2025-05-21 13:22:26'),
(6, 'Rachidi', 'Leila', 'leila.rachidi@uit.ac.ma', '413bec0712ee7136cfd73ad2972280d863b0acdd0e5fd84b57acdab8f4c66a76', 'admin', '2025-05-21 13:22:26'),
(7, 'Benali', 'Fatima', 'fatima.benali@uit.ac.ma', '02ba52a3fadc57255f7c96df47e3f85888a0e3432fad9367c87721d686b050de', 'enseignant', '2025-05-21 13:22:26'),
(8, 'El Kadi', 'Amine', 'amine.elkadi@uit.ac.ma', '16ae9680a4835ef042d8db8f0fe5ec9ece9bd8f018d58f68f2c5dea3590cf92d', 'enseignant', '2025-05-21 13:22:26'),
(9, 'Harrak', 'Hassan', 'hassan.harrak@uit.ac.ma', '2465aa57f2b4796a5f604d2406ddaecbaa133223a4e710868f3232d0d0225af5', 'enseignant', '2025-05-21 13:22:26'),
(10, 'Daoudi', 'Nadia', 'nadia.daoudi@uit.ac.ma', '0180ef887e3166d9765f7ddec0ec9c0f854a4debb2c14c3a2e62d48c6f7f5486', 'enseignant', '2025-05-21 13:22:26'),
(11, 'Sbai', 'Khalid', 'khalid.sbai@uit.ac.ma', '1b6558118fef0cd841319c6aebcb5cb36cafdeb21144f4d485d9d927db5efdcb', 'enseignant', '2025-05-21 13:22:26'),
(12, 'El Filali', 'Samira', 'samira.elfilali@uit.ac.ma', '26f7919f5497e8c351ab9edfd33652065df9a755bfb0bdd4a397934c21450ddc', 'enseignant', '2025-05-21 13:22:26'),
(13, 'Radi', 'Abdelkrim', 'abdelkrim.radi@uit.ac.ma', '5c8360417e09eca28d7be4858343afed316aedd3cd27bb2224057abb11517b27', 'enseignant', '2025-05-21 13:22:26'),
(14, 'Chraibi', 'Leila', 'leila.chraibi@uit.ac.ma', 'c653ce07aab3dc4f673e32690896c2fbc0770ce1f8b34c19e4fa3975b7a60ef0', 'enseignant', '2025-05-21 13:22:26'),
(15, 'Zahir', 'Mohamed', 'mohamed.zahir@uit.ac.ma', 'a1559628579bb4956b3b9430f812a6d036ba7715a547fad06ac99cf939296902', 'enseignant', '2025-05-21 13:22:26'),
(16, 'Bouzidi', 'Nadia', 'nadia.bouzidi@uit.ac.ma', '05ba593610611c45e1fd785c26470dafa30462735c28a9528c8f5fb1dc3324f2', 'enseignant', '2025-05-21 13:22:26'),
(17, 'El Mansouri', 'Karim', 'karim.elmansouri@uit.ac.ma', 'dd0c0fad8a01f57ca64be611ee45ebd59f23bf0e6c95027f89ad6aa29d779106', 'enseignant', '2025-05-21 13:22:26'),
(18, 'Bennani', 'Samira', 'samira.bennani@uit.ac.ma', '6c5880785e89e664a1fae85509501ef95cb3dac62b736c836dbf40837a55ee04', 'enseignant', '2025-05-21 13:22:26'),
(19, 'Bennani', 'Amine', 'amine.bennani@uit.ac.ma', 'e6d0761ef6eadba61aa067d0a5f5fce951a0140806e711ff19a81031149a01d7', 'etudiant', '2025-05-21 13:22:26'),
(20, 'Cherkaoui', 'Leila', 'leila.cherkaoui@uit.ac.ma', '076d0500a4c604ca6917fa0475a8bf34ad1bfccab491f23334457eeae9b58f88', 'etudiant', '2025-05-21 13:22:26'),
(21, 'El Amrani', 'Youssef', 'youssef.elamrani@uit.ac.ma', '965e3613df240275d2fec14341950dd40bff2a0916a39646c259bc192b9a6f81', 'etudiant', '2025-05-21 13:22:26'),
(22, 'Raji', 'Samira', 'samira.raji@uit.ac.ma', 'e8a747cfc09255cf14b257edef6ce14be4183123d23c14da3ec8c54b510066bd', 'etudiant', '2025-05-21 13:22:27'),
(23, 'Zeroual', 'Mehdi', 'mehdi.zeroual@uit.ac.ma', '74d24f88492d14f1cb9e7c6318a7a70709d6be3d6ccf4ce61d2109d77e075a36', 'etudiant', '2025-05-21 13:22:27'),
(24, 'El Khattabi', 'Karim', 'karim.elkhattabi@uit.ac.ma', '9d073bdb047bf2135fc714b4662ee178765c0f5f7e8fb6667a616e5a4c04c29e', 'etudiant', '2025-05-21 13:22:27'),
(25, 'Bouzidi', 'Fatima', 'fatima.bouzidi@uit.ac.ma', '30b111425a20b57e8fd8083b5cc3a4d2cb4b581a903003d0d51e8876be393fdb', 'etudiant', '2025-05-21 13:22:27'),
(26, 'El Fahsi', 'Adil', 'adil.elfahsi@uit.ac.ma', 'd18323e6f5a57100c2d6d8221f0e5af4d06bc3c8e64d451e1c15e11b881e85a5', 'etudiant', '2025-05-21 13:22:27'),
(27, 'Harrak', 'Nabil', 'nabil.harrak@uit.ac.ma', '8f2edcdc8a3d1e2db6eacd5668ea1c1c333775e5bc07714b6c745d8eb662478a', 'etudiant', '2025-05-21 13:22:27'),
(28, 'Saidi', 'Houda', 'houda.saidi@uit.ac.ma', 'a808890ecc853366598b0f95f94ae874fdc2bdb2a367691206ae5812cc624827', 'etudiant', '2025-05-21 13:22:27');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Index pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD PRIMARY KEY (`id_enseignant`);

--
-- Index pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`id_etudiant`),
  ADD UNIQUE KEY `cne` (`cne`);

--
-- Index pour la table `historique_projet`
--
ALTER TABLE `historique_projet`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `id_projet` (`id_projet`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id_like`),
  ADD UNIQUE KEY `id_projet` (`id_projet`,`id_utilisateur`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `livrable`
--
ALTER TABLE `livrable`
  ADD PRIMARY KEY (`id_livrable`),
  ADD KEY `id_projet` (`id_projet`);

--
-- Index pour la table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `expediteur_id` (`expediteur_id`),
  ADD KEY `destinataire_id` (`destinataire_id`);

--
-- Index pour la table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`id_module`);

--
-- Index pour la table `mot_cle`
--
ALTER TABLE `mot_cle`
  ADD PRIMARY KEY (`id_mot_cle`),
  ADD UNIQUE KEY `terme` (`terme`);

--
-- Index pour la table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id_post`),
  ADD KEY `idx_post_projet` (`id_projet`);

--
-- Index pour la table `projet`
--
ALTER TABLE `projet`
  ADD PRIMARY KEY (`id_projet`),
  ADD KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `id_enseignant` (`id_enseignant`);

--
-- Index pour la table `projet_module`
--
ALTER TABLE `projet_module`
  ADD PRIMARY KEY (`id_projet`,`id_module`),
  ADD KEY `id_module` (`id_module`);

--
-- Index pour la table `projet_mot_cle`
--
ALTER TABLE `projet_mot_cle`
  ADD PRIMARY KEY (`id_projet`,`id_mot_cle`),
  ADD KEY `id_mot_cle` (`id_mot_cle`);

--
-- Index pour la table `remarque`
--
ALTER TABLE `remarque`
  ADD PRIMARY KEY (`id_remarque`),
  ADD KEY `id_projet` (`id_projet`),
  ADD KEY `id_enseignant` (`id_enseignant`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_name`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `historique_projet`
--
ALTER TABLE `historique_projet`
  MODIFY `id_historique` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT pour la table `likes`
--
ALTER TABLE `likes`
  MODIFY `id_like` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `livrable`
--
ALTER TABLE `livrable`
  MODIFY `id_livrable` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `message`
--
ALTER TABLE `message`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `module`
--
ALTER TABLE `module`
  MODIFY `id_module` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `mot_cle`
--
ALTER TABLE `mot_cle`
  MODIFY `id_mot_cle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `post`
--
ALTER TABLE `post`
  MODIFY `id_post` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `projet`
--
ALTER TABLE `projet`
  MODIFY `id_projet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `remarque`
--
ALTER TABLE `remarque`
  MODIFY `id_remarque` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD CONSTRAINT `enseignant_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD CONSTRAINT `etudiant_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `historique_projet`
--
ALTER TABLE `historique_projet`
  ADD CONSTRAINT `historique_projet_ibfk_1` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`) ON DELETE CASCADE,
  ADD CONSTRAINT `historique_projet_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `livrable`
--
ALTER TABLE `livrable`
  ADD CONSTRAINT `livrable_ibfk_1` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateur` (`id_utilisateur`),
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Contraintes pour la table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`) ON DELETE CASCADE;

--
-- Contraintes pour la table `projet`
--
ALTER TABLE `projet`
  ADD CONSTRAINT `projet_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiant` (`id_etudiant`) ON DELETE SET NULL,
  ADD CONSTRAINT `projet_ibfk_2` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignant` (`id_enseignant`) ON DELETE SET NULL;

--
-- Contraintes pour la table `projet_module`
--
ALTER TABLE `projet_module`
  ADD CONSTRAINT `projet_module_ibfk_1` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`) ON DELETE CASCADE,
  ADD CONSTRAINT `projet_module_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `module` (`id_module`) ON DELETE CASCADE;

--
-- Contraintes pour la table `projet_mot_cle`
--
ALTER TABLE `projet_mot_cle`
  ADD CONSTRAINT `projet_mot_cle_ibfk_1` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`),
  ADD CONSTRAINT `projet_mot_cle_ibfk_2` FOREIGN KEY (`id_mot_cle`) REFERENCES `mot_cle` (`id_mot_cle`);

--
-- Contraintes pour la table `remarque`
--
ALTER TABLE `remarque`
  ADD CONSTRAINT `remarque_ibfk_1` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`) ON DELETE CASCADE,
  ADD CONSTRAINT `remarque_ibfk_2` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignant` (`id_enseignant`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
