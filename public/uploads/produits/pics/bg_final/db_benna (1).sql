-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 16 mai 2026 à 19:07
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
-- Base de données : `db_benna`
--

-- --------------------------------------------------------

--
-- Structure de la table `alertes_nutritionnelles`
--

CREATE TABLE `alertes_nutritionnelles` (
  `id` int(11) NOT NULL,
  `nutritionniste_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `gravite` enum('info','attention','urgent') DEFAULT 'info',
  `lu` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `alertes_nutritionnelles`
--

INSERT INTO `alertes_nutritionnelles` (`id`, `nutritionniste_id`, `client_id`, `titre`, `message`, `gravite`, `lu`, `created_at`) VALUES
(1, 2, 5, 'Consultation nutritionnelle', 'AHAHAHAH', 'info', 0, '2026-04-27 15:04:09');

-- --------------------------------------------------------

--
-- Structure de la table `allergenes`
--

CREATE TABLE `allergenes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `icone` varchar(10) DEFAULT '⚠️'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `allergenes`
--

INSERT INTO `allergenes` (`id`, `nom`, `icone`) VALUES
(1, 'Gluten', '🌾'),
(2, 'Lactose', '🥛'),
(3, 'Arachides', '🥜'),
(4, 'Soja', '🫘'),
(5, 'Oeufs', '🥚'),
(6, 'Noix', '🌰'),
(7, 'Sesame', '🫙'),
(8, 'Moutarde', '🟡');

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `note` tinyint(4) DEFAULT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `valide` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(10) DEFAULT '?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `icone`) VALUES
(1, 'Boissons', 'Jus, infusions & boissons naturelles', '🥤'),
(2, 'Snacks & biscuits', 'Biscuits artisanaux et snacks sains', '🍪'),
(3, 'Fruits secs & snacks salés', 'Fruits secs, noix et en-cas salés', '🥜'),
(4, 'Pain & boulangerie', 'Pains au levain, galettes & viennoiseries', '🍞'),
(5, 'Plats healthy', 'Plats équilibrés prêts à consommer', '🥗'),
(6, 'Produits sportifs', 'Barres, protéines & nutrition sportive', '💪'),
(7, 'Petit déjeuner', 'Céréales, granolas & produits du matin', '🌅'),
(8, 'Pâtisserie healthy', 'Gâteaux et desserts allégés', '🧁'),
(9, 'Préparations maison', 'Mélanges et bases pour cuisiner chez soi', '🏠'),
(10, 'Produits végétaux', 'Alternatives végétales & produits vegan', '🌱');

-- --------------------------------------------------------

--
-- Structure de la table `chatbot_messages`
--

CREATE TABLE `chatbot_messages` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `reponse` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,3) NOT NULL,
  `statut` enum('en_attente','confirmee','en_preparation','expedie','en_livraison','livre','annulee') DEFAULT 'en_attente',
  `adresse_livraison` text NOT NULL,
  `note_client` text DEFAULT NULL,
  `paiement_statut` enum('en_attente','paye','rembourse') DEFAULT 'en_attente',
  `paiement_methode` enum('carte','virement','cash') DEFAULT 'cash',
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_livraison_estimee` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `user_id`, `total`, `statut`, `adresse_livraison`, `note_client`, `paiement_statut`, `paiement_methode`, `date_commande`, `date_livraison_estimee`) VALUES
(1, 5, 72.300, 'en_attente', 'sousse', '', 'en_attente', 'cash', '2026-04-25 23:19:42', '2026-04-29'),
(2, 5, 109.700, 'en_attente', 'rue all', '', 'en_attente', 'cash', '2026-04-26 17:51:43', '2026-04-29'),
(3, 4, 25.800, 'confirmee', 'souusz', '', 'paye', 'cash', '2026-04-27 21:54:15', '2026-04-30'),
(4, 5, 64.200, 'annulee', 'sousse', 'vite', 'paye', 'carte', '2026-04-28 15:58:56', '2026-05-01'),
(5, 5, 13.500, 'en_attente', 'SOUUSE', '', 'en_attente', 'cash', '2026-04-28 16:04:29', '2026-05-01'),
(6, 5, 13.500, 'confirmee', 'souuse', '', 'paye', 'virement', '2026-04-28 16:04:56', '2026-05-01'),
(7, 7, 38.000, 'confirmee', 'Rue Abdelaziz Darraa', '', 'paye', 'cash', '2026-04-28 16:36:56', '2026-05-01'),
(8, 7, 90.800, 'en_livraison', 'Rue Abdelaziz Darraa', '', 'en_attente', 'cash', '2026-04-28 16:37:46', '2026-05-01'),
(9, 7, 36.400, 'confirmee', 'Rue Abdelaziz Darraa', '', 'paye', 'cash', '2026-05-02 21:04:23', '2026-05-05'),
(10, 5, 34.700, 'en_livraison', 'msaken', '', 'paye', 'carte', '2026-05-05 16:29:50', '2026-05-08'),
(11, 5, 9.900, 'annulee', 'msaken', '', 'paye', 'virement', '2026-05-05 16:36:12', '2026-05-08');

-- --------------------------------------------------------

--
-- Structure de la table `commande_details`
--

CREATE TABLE `commande_details` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commande_details`
--

INSERT INTO `commande_details` (`id`, `commande_id`, `produit_id`, `quantite`, `prix_unitaire`) VALUES
(2, 4, 13, 3, 4.200),
(3, 4, 14, 3, 5.500),
(4, 4, 15, 1, 5.200),
(5, 4, 17, 3, 3.800),
(6, 4, 5, 1, 18.500),
(7, 5, 6, 1, 13.500),
(8, 6, 6, 1, 13.500),
(9, 7, 6, 2, 13.500),
(10, 7, 13, 1, 4.200),
(11, 7, 29, 1, 6.800),
(12, 8, 12, 1, 12.500),
(13, 8, 9, 1, 19.900),
(14, 8, 8, 1, 11.900),
(15, 8, 2, 1, 14.500),
(16, 8, 5, 1, 18.500),
(17, 8, 6, 1, 13.500),
(18, 9, 9, 1, 19.900),
(19, 9, 23, 1, 7.500),
(20, 9, 30, 1, 5.500),
(21, 9, 32, 1, 3.500),
(22, 10, 7, 1, 9.900),
(23, 10, 18, 4, 6.200),
(24, 11, 7, 1, 9.900);

-- --------------------------------------------------------

--
-- Structure de la table `conseils`
--

CREATE TABLE `conseils` (
  `id` int(11) NOT NULL,
  `nutritionniste_id` int(11) NOT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `titre` varchar(200) NOT NULL,
  `contenu` text NOT NULL,
  `type` enum('conseil','recette','recommandation','plan_alimentaire') DEFAULT 'conseil',
  `public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consultations`
--

CREATE TABLE `consultations` (
  `id` int(11) NOT NULL,
  `nutritionniste_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL DEFAULT 'Consultation nutritionnelle',
  `date_heure` datetime NOT NULL,
  `duree_min` int(11) DEFAULT 30,
  `type` enum('chat','visio') DEFAULT 'visio',
  `statut` enum('planifiee','en_cours','terminee','annulee') DEFAULT 'planifiee',
  `lien_visio` varchar(500) DEFAULT NULL,
  `notes_avant` text DEFAULT NULL,
  `notes_apres` text DEFAULT NULL,
  `objectifs` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `consultations`
--

INSERT INTO `consultations` (`id`, `nutritionniste_id`, `client_id`, `titre`, `date_heure`, `duree_min`, `type`, `statut`, `lien_visio`, `notes_avant`, `notes_apres`, `objectifs`, `created_at`) VALUES
(1, 2, 5, 'Consultation nutritionnelle', '2026-04-21 19:30:00', 15, 'visio', 'planifiee', 'https://meet.jit.si/Benna-consultation-nutritionnelle-6929', 'hello', NULL, 'suivi', '2026-04-21 18:31:18'),
(2, 2, 5, 'Consultation nutritionnelle', '2026-04-24 13:12:00', 15, 'visio', 'terminee', 'https://meet.jit.si/Benna-2-24159', '', 'continuer dans ton regime ', 'suivi', '2026-04-24 12:10:49'),
(3, 2, 5, 'Consultation nutritionnelle', '2026-05-16 17:36:00', 30, 'visio', 'terminee', 'https://meet.jit.si/Benna-consultation-nutritionnelle-4029', '', 'trés bien passée', '', '2026-05-16 16:37:07');

-- --------------------------------------------------------

--
-- Structure de la table `livraisons`
--

CREATE TABLE `livraisons` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `livreur_id` int(11) DEFAULT NULL,
  `statut` enum('assignee','acceptee','en_cours','livree','echec') DEFAULT 'assignee',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `note_livreur` text DEFAULT NULL,
  `probleme` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `livraisons`
--

INSERT INTO `livraisons` (`id`, `commande_id`, `livreur_id`, `statut`, `latitude`, `longitude`, `note_livreur`, `probleme`, `updated_at`) VALUES
(1, 8, 4, 'livree', NULL, NULL, NULL, NULL, '2026-05-01 19:27:08'),
(2, 10, 4, 'en_cours', NULL, NULL, NULL, NULL, '2026-05-05 16:41:11');

-- --------------------------------------------------------

--
-- Structure de la table `ordres_production`
--

CREATE TABLE `ordres_production` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `statut` enum('demande','en_cours','termine','annule') DEFAULT 'demande',
  `demande_par` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `termine_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ordres_production`
--

INSERT INTO `ordres_production` (`id`, `produit_id`, `quantite`, `statut`, `demande_par`, `created_at`, `termine_at`) VALUES
(1, 12, 50, 'demande', 3, '2026-04-25 05:40:35', NULL),
(2, 7, 20, 'annule', 3, '2026-04-25 07:29:28', NULL),
(3, 8, 10, 'termine', 3, '2026-04-25 07:44:41', '2026-04-28 16:50:53'),
(4, 11, 40, 'termine', 3, '2026-04-25 07:45:20', '2026-04-25 10:39:49'),
(5, 6, 50, 'annule', 3, '2026-04-25 07:45:49', NULL),
(7, 7, 40, 'termine', 3, '2026-04-25 07:51:07', '2026-04-25 10:37:48'),
(8, 6, 50, 'termine', 3, '2026-04-25 08:05:15', '2026-04-25 10:37:48'),
(9, 2, 54, 'annule', 3, '2026-04-25 08:18:30', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `panier`
--

INSERT INTO `panier` (`id`, `user_id`, `produit_id`, `quantite`) VALUES
(7, 1, 14, 1),
(8, 1, 19, 1),
(9, 1, 35, 1),
(10, 1, 52, 1),
(11, 1, 11, 1),
(12, 1, 12, 1),
(117, 7, 36, 2),
(118, 7, 32, 2),
(119, 7, 53, 2),
(120, 7, 66, 2),
(121, 7, 64, 2),
(122, 7, 67, 2),
(151, 5, 99, 1),
(152, 5, 100, 1),
(153, 5, 101, 2),
(166, 5, 13, 1),
(167, 5, 102, 1),
(168, 5, 8, 1);

-- --------------------------------------------------------

--
-- Structure de la table `plans_alimentaires`
--

CREATE TABLE `plans_alimentaires` (
  `id` int(11) NOT NULL,
  `nutritionniste_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `objectif` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `plan_repas`
--

CREATE TABLE `plan_repas` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `jour` varchar(20) NOT NULL,
  `moment` enum('matin','midi','soir','collation') NOT NULL,
  `description` text NOT NULL,
  `calories` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,3) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `regime` varchar(200) DEFAULT NULL,
  `calories` int(11) DEFAULT 0,
  `proteines` decimal(5,2) DEFAULT 0.00,
  `glucides` decimal(5,2) DEFAULT 0.00,
  `lipides` decimal(5,2) DEFAULT 0.00,
  `note_moyenne` decimal(3,2) DEFAULT 0.00,
  `nb_avis` int(11) DEFAULT 0,
  `est_actif` tinyint(1) DEFAULT 1,
  `est_valide` tinyint(1) DEFAULT 0,
  `est_nouveau` tinyint(1) DEFAULT 0,
  `est_bestseller` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `description`, `prix`, `stock`, `image`, `categorie_id`, `regime`, `calories`, `proteines`, `glucides`, `lipides`, `note_moyenne`, `nb_avis`, `est_actif`, `est_valide`, `est_nouveau`, `est_bestseller`, `created_at`) VALUES
(2, 'Crackers', 'Extra virgin olive oil, fresh rosemary from Zaghouan, cracked black pepper.', 14.500, 34, 'pics/product-olive-rosemary.jpg', 3, 'sans-lactose,bio,vegan', 280, 4.80, 35.00, 16.00, 0.00, 0, 1, 1, 0, 0, '2026-04-21 14:04:25'),
(5, 'Date & Almond Bread', 'Pain artisanal aux dattes de Kébili, amandes, levain naturel — sans additifs.', 18.500, 11, 'pics/gallery-1.jpg', 4, 'sans-lactose,bio', 350, 9.00, 50.00, 12.50, 0.00, 0, 1, 1, 1, 0, '2026-04-21 14:04:25'),
(6, 'Zaatar Granola Bowl', 'Granola artisanal au zaatar tunisien, graines et miel de montagne.', 13.500, 75, 'pics/gallery-2.jpg', 7, 'bio,vegan', 260, 5.10, 30.00, 15.00, 0.00, 0, 1, 1, 0, 0, '2026-04-21 14:04:25'),
(7, 'Green Detox Smoothie', 'Mélange épinards, concombre, gingembre et menthe fraîche — boisson détoxifiante.', 9.900, 63, 'pics/gallery-3.jpg', 1, 'vegan,bio', 85, 2.10, 18.00, 0.50, 0.00, 0, 1, 1, 1, 0, '2026-04-21 14:04:25'),
(8, 'Protein Almond Bar', 'Barre protéinée aux amandes, whey bio et miel — idéale après l\'effort.', 11.900, 54, 'pics/gallery-4.jpg', 6, 'sans-gluten', 280, 18.00, 22.00, 9.00, 0.00, 0, 1, 1, 0, 0, '2026-04-21 14:04:25'),
(9, 'Baklawa Healthy', 'Pâtisserie tunisienne revisitée — sans sucre ajouté, au sirop de datte.', 19.900, 18, 'pics/product-honey-sesame.jpg', 8, 'sans-sucre', 310, 6.00, 35.00, 16.00, 0.00, 0, 1, 1, 0, 1, '2026-04-21 14:04:25'),
(10, 'Harissa Veggie Wrap', 'Base de wrap végétalien maison avec harissa douce et légumes grillés.', 14.900, 18, 'pics/1.jpg', 5, 'vegan,bio', 220, 8.00, 28.00, 7.00, 0.00, 0, 1, 1, 1, 0, '2026-04-21 14:04:25'),
(11, 'Couscous Bio', 'Mélange semoule bio et épices tunisiennes — prêt en 10 minutes.', 8.500, 100, 'pics/product-olive-rosemary.jpg', 9, 'bio,vegan', 180, 5.50, 38.00, 1.50, 0.00, 0, 1, 1, 0, 0, '2026-04-21 14:04:25'),
(12, 'Lait de Coco 1L\r\n', 'Boisson végétale à la noix de coco fraîche, sans conservateurs ni additifs.', 12.500, 39, 'pics/gallery-1.jpg', 10, 'sans-lactose,vegan,bio', 150, 1.50, 8.00, 13.00, 0.00, 0, 1, 1, 1, 0, '2026-04-21 14:04:25'),
(13, 'Lait sans lactose 1L', 'Lait digestif', 4.200, 46, 'uploads/produits/img_69f9198ed3cd8.jpg', 1, 'sans-lactose', 120, 8.00, 10.00, 4.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(14, 'Lait amande 1L', 'Boisson végétale', 5.500, 37, 'pics/lait_amande.jpg', 1, 'vegan', 110, 3.00, 12.00, 5.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(15, 'Lait avoine 1L', 'Riche fibres', 5.200, 34, 'pics/lait_avoine.jpg', 1, 'vegan,high-fiber', 130, 4.00, 18.00, 4.50, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(16, 'Lait soja 1L', 'Protéines végétales', 4.800, 45, 'pics/lait_soja.jpg', 1, 'vegan,protein-rich', 125, 7.00, 9.00, 4.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(17, 'Jus orange naturel', '100% frais', 3.800, 57, 'pics/jus_orange.png', 1, 'natural,vegan', 90, 1.20, 20.00, 0.50, 0.00, 0, 1, 1, 0, 1, '2026-04-26 16:49:11'),
(18, 'Smoothie fruits rouges', 'Antioxydants', 6.200, 21, 'pics/smoothie_fruits_rouges.jpg', 1, 'vegan,natural', 150, 2.00, 30.00, 1.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(19, 'Eau citronnée detox', 'Boisson detox', 2.500, 70, 'pics/eau_citronnee.jpg', 1, 'vegan,low-calorie', 10, 0.50, 2.00, 0.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(20, 'Jus carotte naturel', '100% frais', 3.900, 50, 'pics/jus_carotte.jpg', 1, 'natural,vegan', 80, 1.00, 18.00, 0.20, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(21, 'Smoothie protéiné banane', 'Après sport', 6.500, 30, 'pics/smoothie_proteine.jpg', 1, 'protein-rich,low-calorie', 180, 15.00, 25.00, 2.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(22, 'Cookies sans gluten ', 'Healthy cookies', 6.900, 50, 'pics/22.jpg', 2, 'sans-gluten', 280, 5.00, 35.00, 12.00, 0.00, 0, 1, 1, 0, 1, '2026-04-26 16:49:11'),
(23, 'Cookies sans sucre', 'Adapté diabète', 7.500, 44, 'pics/cookies_sans_sucre.jpg', 2, 'sans-sucre', 260, 4.50, 30.00, 10.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(24, 'Cookies orange\r\n', 'Snack sain', 5.800, 60, 'pics/cookies_avoine.jpg', 2, 'vegan,high-fiber,natural', 250, 4.00, 38.00, 8.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(25, 'graines de lin', '100% naturelle ', 6.500, 50, 'pics/33.jpg', 10, 'natural', 270, 5.00, 32.00, 11.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(26, 'biscuit coco vegan', 'Sans produits animaux', 6.200, 55, 'pics/bis_coco.jpg', 2, 'vegan', 260, 3.00, 28.00, 14.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(27, 'Cookies dattes tunisiennes', 'Énergie naturelle', 5.900, 60, 'pics/cookies_dattes.jpg', 2, 'vegan,natural', 240, 3.00, 40.00, 7.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(28, 'Yaourt végétal coco', 'Sans lactose', 4.200, 40, 'pics/yaourt_coco.jpg', 2, 'vegan,sans-lactose,natural', 150, 3.00, 10.00, 6.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(29, 'thé glacé', 'boisson fraiche', 6.800, 19, 'pics/thee.jpg', 1, 'maison', 300, 6.00, 30.00, 15.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(30, 'Muffin sans sucre', 'Healthy snack', 5.500, 29, 'pics/muffin.jpg', 2, 'sans-sucre,low-calorie', 240, 5.00, 28.00, 8.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(31, 'Pancakes sans gluten', 'Petit déjeuner', 6.200, 25, 'pics/pancakes.jpg', 2, 'sans-gluten,vegetarian', 280, 6.00, 35.00, 9.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(32, 'Compote pomme sans sucre', 'Dessert naturel', 3.500, 39, 'pics/compote_pomme.jpg', 2, 'sans-sucre,natural', 100, 0.50, 25.00, 0.20, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(33, 'Yaourt grec light', 'Faible calories', 4.500, 40, 'pics/yaourt_grec.jpg', 2, 'low-calorie,protein-rich', 120, 10.00, 6.00, 2.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(34, 'Makroud healthy', 'Dessert premium', 6.900, 20, 'pics/makroud.jpg', 2, 'traditional,gourmet,sans-sucre', 220, 4.00, 35.00, 8.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(35, 'Barre énergie dattes', 'Snack naturel', 3.200, 70, 'pics/barre_dattes.jpg', 3, 'vegan,natural', 180, 3.00, 30.00, 6.00, 0.00, 0, 1, 1, 0, 1, '2026-04-26 16:49:11'),
(36, 'Barre chocolat noir', 'Énergie rapide', 3.900, 60, 'pics/barre_choco.jpg', 3, 'gourmet', 200, 3.00, 25.00, 10.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(37, 'Barre amande noisette', 'Riche protéines', 4.500, 50, 'pics/barre_amande.jpg', 3, 'protein-rich', 210, 7.00, 18.00, 12.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(38, 'Chips patate douce', 'Snack sain', 3.500, 60, 'pics/chips_patate_douce.jpg', 3, 'vegan,low-calorie', 200, 2.00, 25.00, 8.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(40, 'Mix noix grillées', 'Énergie naturelle', 5.200, 50, 'pics/mix_noix.jpg', 3, 'protein-rich', 300, 10.00, 15.00, 22.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(41, 'Chips patate douce spicy', 'Snack sain épicé', 3.500, 0, 'pics/chips_spicy.jpg', 3, 'vegan,low-calorie', 200, 2.00, 25.00, 8.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(42, 'Salade quinoa bio', 'Repas équilibré', 8.500, 30, 'pics/salade_quinoa.jpg', 3, 'vegan,vegetarian,bio,high-fiber', 320, 10.00, 45.00, 9.00, 0.00, 0, 1, 1, 0, 1, '2026-04-26 16:49:11'),
(43, 'Wrap falafel maison', 'Street food healthy', 7.800, 25, 'pics/wrap_falafel.jpg', 3, 'vegan,homemade,protein-rich', 400, 12.00, 50.00, 10.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(44, 'Soupe légumes maison', 'Recette simple', 4.800, 35, 'pics/soupe_legumes.jpg', 3, 'homemade,natural,low-calorie', 120, 4.00, 20.00, 2.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(45, 'Bol lentilles healthy', 'Riche fibres', 7.200, 30, 'pics/bol_lentilles.jpg', 3, 'vegan,high-fiber,protein-rich', 350, 15.00, 50.00, 5.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(46, 'Lablabi healthy', 'Recette tunisienne revisitée', 5.800, 40, 'pics/lablabi.jpg', 3, 'traditional,vegan,high-fiber', 300, 12.00, 55.00, 4.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(47, 'Ojja végétarienne', 'Plat tunisien', 7.500, 35, 'pics/ojja.jpg', 3, 'traditional,vegetarian', 250, 10.00, 20.00, 12.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(48, 'Salade concombre light', 'Très léger', 3.800, 50, 'pics/salade_concombre.jpg', 3, 'low-calorie,vegan', 60, 2.00, 10.00, 1.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(49, 'Energy bowl complet', 'Repas complet healthy', 9.500, 20, 'pics/energy_bowl.jpg', 3, 'vegan,high-fiber,protein-rich,natural', 450, 18.00, 60.00, 12.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(50, 'Snack detox premium', 'Healthy gourmet', 6.700, 25, 'pics/snack_detox.jpg', 3, 'gourmet,low-calorie,natural', 150, 4.00, 25.00, 3.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(51, 'Energy balls dattes & noix', 'Snack naturel', 5.500, 40, 'pics/energy_balls.jpg', 5, 'natural,high-fiber', 190, 5.00, 28.00, 7.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(52, 'Pain complet bio', 'Artisanal', 3.500, 40, 'pics/pain_complet.jpg', 4, 'bio', 220, 7.00, 40.00, 3.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(53, 'Pain sans gluten', 'Alternative saine', 4.800, 30, 'pics/pain_sans_gluten.jpg', 4, 'sans-gluten', 210, 6.00, 38.00, 3.50, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(54, 'Pain avoine', 'Digestion facile', 3.700, 40, 'pics/pain_avoine.jpg', 4, 'vegan,high-fiber', 230, 8.00, 42.00, 3.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(55, 'beurre noisette', 'Équilibré', 20.000, 35, 'pics/beurre_nois.jpg', 4, 'bio,high-fiber', 600, 12.00, 40.00, 55.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(56, 'beurre pistache ', 'Riche protéines', 4.900, 25, 'pics/pain_quinoa.jpg', 4, 'bio,vegetarian,high-protein', 560, 20.00, 35.00, 20.00, 0.00, 0, 1, 1, NULL, 0, '2026-04-26 16:49:11'),
(57, 'Shake protéiné chocolat', 'Sport nutrition', 7.900, 40, 'pics/shake_proteine.jpg', 5, 'protein-rich', 220, 25.00, 15.00, 6.00, 0.00, 0, 1, 1, 0, 1, '2026-04-26 16:49:11'),
(58, 'Shake protéiné vanille', 'Musculation', 7.800, 40, 'pics/shake_vanille.jpg', 5, 'protein-rich', 210, 25.00, 14.00, 5.50, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(59, 'Granola fitness', 'Énergie matin', 5.900, 50, 'pics/granola.jpg', 5, 'high-fiber', 300, 10.00, 40.00, 8.00, 0.00, 0, 1, 1, 1, 0, '2026-04-26 16:49:11'),
(62, 'Granola quinoa vegan', 'Petit déjeuner healthy', 7.200, 12, 'pics/granola_quinoa.jpg', 5, 'vegan,sans-gluten,high-fiber', 320, 8.00, 45.00, 9.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(64, 'Préparation fricassé', 'Mix cuisson healthy', 6.500, 60, 'pics/prep_fricassee.jpg', 6, 'homemade', 300, 8.00, 40.00, 10.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(65, 'Préparation fricassé léger', 'Moins gras', 6.200, 65, 'pics/prep_fricassee_light.jpg', 6, 'low-calorie', 250, 8.00, 35.00, 6.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(66, 'Préparation cookies maison', 'Mix cookies', 7.200, 80, 'pics/prep_cookies.jpg', 6, 'homemade', 350, 5.00, 50.00, 15.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(67, 'Préparation cookies sans sucre', 'Diabète friendly', 7.500, 70, 'pics/prep_cookies_ss.jpg', 6, 'sans-sucre', 300, 6.00, 40.00, 12.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(68, 'Préparation brownies', 'Chocolat maison', 8.500, 60, 'pics/prep_brownies.jpg', 6, 'homemade,gourmet', 400, 6.00, 45.00, 20.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(69, 'Préparation brownies sans sucre', 'Healthy dessert', 8.900, 55, 'pics/prep_brownies_ss.jpg', 6, 'sans-sucre,low-calorie', 320, 7.00, 35.00, 15.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(70, 'Préparation mlewi', 'Farine complète', 5.800, 90, 'pics/prep_mlewi.jpg', 6, 'homemade', 280, 7.00, 45.00, 8.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(71, 'Préparation mlewi sans gluten', 'Alternative saine', 6.900, 50, 'pics/prep_mlewi_sg.jpg', 6, 'sans-gluten', 270, 6.00, 40.00, 9.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(72, 'Préparation mlewi avoine', 'Riche fibres', 6.300, 60, 'pics/prep_mlewi_avoine.jpg', 6, 'vegan,high-fiber', 280, 8.00, 42.00, 7.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(92, 'graines de chia', '100% naturelle ', 6.500, 50, 'pics/26.jpg', 10, 'natural', 270, 5.00, 32.00, 11.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(93, 'farine amande', 'poudre fine obtenue en broyant des amandes décortiquées.', 5.800, 90, 'pics/prep_mlewi.jpg', 8, 'sans gluten', 280, 7.00, 45.00, 8.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(96, 'frik tchich', 'Plat traditionnel tunisien, nourrissant et riche en fibres.', 5.000, 50, 'pics/94.jpg', 9, 'Traditional', 270, 5.00, 32.00, 11.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(97, 'spiruline', '100% naturelle ', 30.000, 50, 'pics/95.jpg', 10, 'natural', 270, 5.00, 32.00, 11.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(98, 'semoule ', 'Semoule fabriquée à partir de céréales ou alternatives (maïs, riz…) sans gluten, adaptée aux personnes intolérantes.', 5.800, 90, 'pics/prep_mlewi.jpg', 8, 'sans gluten', 280, 7.00, 45.00, 8.00, 0.00, 0, 1, 1, 0, 0, '2026-04-26 16:49:11'),
(99, 'Box Bio — Petite', 'box', 12.900, 9999, NULL, NULL, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0, 1, 0, 0, 0, '2026-05-15 23:06:07'),
(100, 'Box Bio — Grande', 'box', 34.900, 9999, NULL, NULL, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0, 1, 0, 0, 0, '2026-05-15 23:06:17'),
(101, 'Box Bio — Moyenne', 'box', 22.900, 9999, NULL, NULL, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0, 1, 0, 0, 0, '2026-05-15 23:06:21'),
(102, 'Boîte Mystère  Moyenne', 'box', 22.900, 9999, NULL, NULL, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0, 1, 0, 0, 0, '2026-05-16 01:49:37');

-- --------------------------------------------------------

--
-- Structure de la table `produit_allergenes`
--

CREATE TABLE `produit_allergenes` (
  `produit_id` int(11) NOT NULL,
  `allergene_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produit_allergenes`
--

INSERT INTO `produit_allergenes` (`produit_id`, `allergene_id`) VALUES
(2, 7),
(8, 6);

-- --------------------------------------------------------

--
-- Structure de la table `reclamations`
--

CREATE TABLE `reclamations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `commande_id` int(11) DEFAULT NULL,
  `sujet` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `statut` enum('ouverte','en_cours','transmise_usine','resolue','rejetee') DEFAULT 'ouverte',
  `reponse` text DEFAULT NULL,
  `repondu_par` int(11) DEFAULT NULL,
  `transmis_usine` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reclamations`
--

INSERT INTO `reclamations` (`id`, `user_id`, `commande_id`, `sujet`, `message`, `statut`, `reponse`, `repondu_par`, `transmis_usine`, `created_at`) VALUES
(1, 5, 2, 'mwasltnish', 'HAHAH', 'en_cours', 'en cours ', 1, 1, '2026-04-27 17:05:16'),
(2, 5, 11, 'annulation', 'pourquoi annulé', 'ouverte', NULL, NULL, 0, '2026-05-05 16:42:29');

-- --------------------------------------------------------

--
-- Structure de la table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) DEFAULT 0,
  `seuil_alerte` int(11) DEFAULT 20,
  `en_production` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock`
--

INSERT INTO `stock` (`id`, `produit_id`, `quantite`, `seuil_alerte`, `en_production`, `updated_at`) VALUES
(2, 2, 34, 10, 0, '2026-04-28 16:37:46'),
(5, 5, 11, 8, 0, '2026-04-28 16:37:46'),
(6, 6, 75, 10, 0, '2026-04-28 16:37:46'),
(7, 7, 63, 10, 0, '2026-05-05 16:36:12'),
(8, 8, 54, 15, 0, '2026-04-28 16:50:53'),
(9, 9, 18, 8, 0, '2026-05-02 21:04:23'),
(10, 10, 18, 8, 0, '2026-04-21 14:04:25'),
(11, 11, 100, 20, 0, '2026-04-25 10:39:49'),
(12, 12, 39, 12, 0, '2026-04-28 16:37:46');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','admin','nutritionniste','usine','livreur') DEFAULT 'client',
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password`, `role`, `telephone`, `adresse`, `avatar`, `actif`, `created_at`) VALUES
(1, 'Admin Benna', 'admin@benna.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, 1, '2026-04-21 14:04:25'),
(2, 'Dr. Sana Ben Ali', 'nutri@benna.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nutritionniste', NULL, NULL, NULL, 1, '2026-04-21 14:04:25'),
(3, 'Usine Sousse', 'usine@benna.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usine', NULL, NULL, NULL, 1, '2026-04-21 14:04:25'),
(4, 'Ahmed Livreur', 'livreur@benna.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'livreur', NULL, NULL, NULL, 1, '2026-04-21 14:04:25'),
(5, 'Client Test', 'client@benna.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', NULL, NULL, NULL, 1, '2026-04-21 14:04:25'),
(6, 'amine belabed', 'amine@gmail.com', '$2y$10$OG6SF9miuvumVOpv7csIv.BdnNWW105e64hy5ZT6BV5dcQwNbNKRW', 'client', '78945612', '', NULL, 1, '2026-04-25 10:10:20'),
(7, 'islem taher', 'islem@gmail.com', '$2y$10$B.KlMef9c1HXAwtkOz3gf.VjXDQNvC6M7d8EZytrsZ5EZtXbeb5ia', 'client', '24122302', 'Rue Abdelaziz Darraa', NULL, 1, '2026-04-28 16:08:26'),
(8, 'selim livreur', 'selimll@gmail.com', '$2y$10$9zDxmFUThDGMPhr8KfSm4u7bpSVNCTbcvND6p/Iz68XzUGOsIJx4u', 'livreur', '25122358', '', NULL, 0, '2026-05-01 18:51:01');

-- --------------------------------------------------------

--
-- Structure de la table `vip_abonnements`
--

CREATE TABLE `vip_abonnements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `niveau` enum('basic','premium','elite') NOT NULL,
  `prix_mensuel` decimal(8,3) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `renouvellement` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vip_abonnements`
--

INSERT INTO `vip_abonnements` (`id`, `user_id`, `niveau`, `prix_mensuel`, `date_debut`, `date_fin`, `actif`, `renouvellement`, `created_at`) VALUES
(1, 5, 'premium', 35.000, '2026-04-21', '2026-05-21', 1, 1, '2026-04-21 15:14:37'),
(2, 7, 'basic', 13.000, '2026-05-02', '2026-06-01', 0, 1, '2026-05-02 21:10:53');

-- --------------------------------------------------------

--
-- Structure de la table `vip_messages`
--

CREATE TABLE `vip_messages` (
  `id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `consultation_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vip_messages`
--

INSERT INTO `vip_messages` (`id`, `expediteur_id`, `destinataire_id`, `contenu`, `lu`, `consultation_id`, `created_at`) VALUES
(1, 7, 2, 'bonjour', 0, NULL, '2026-05-02 21:11:01'),
(2, 7, 2, 'je veux une consultation', 0, NULL, '2026-05-02 21:15:13');

-- --------------------------------------------------------

--
-- Structure de la table `vip_objectifs`
--

CREATE TABLE `vip_objectifs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `nutri_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `valeur_cible` decimal(8,2) DEFAULT NULL,
  `valeur_actuelle` decimal(8,2) DEFAULT NULL,
  `unite` varchar(20) DEFAULT 'kg',
  `deadline` date DEFAULT NULL,
  `atteint` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `vip_paiements`
--

CREATE TABLE `vip_paiements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `abonnement_id` int(11) NOT NULL,
  `montant` decimal(8,3) NOT NULL,
  `methode` enum('carte','virement','cash') DEFAULT 'carte',
  `statut` enum('en_attente','paye','echoue') DEFAULT 'en_attente',
  `reference` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vip_paiements`
--

INSERT INTO `vip_paiements` (`id`, `user_id`, `abonnement_id`, `montant`, `methode`, `statut`, `reference`, `created_at`) VALUES
(1, 5, 1, 35.000, 'carte', 'paye', 'VIP-69E7945DBEDAD', '2026-04-21 15:14:37'),
(2, 7, 2, 13.000, 'carte', 'paye', 'VIP-69F6685D7A2F8', '2026-05-02 21:10:53');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `alertes_nutritionnelles`
--
ALTER TABLE `alertes_nutritionnelles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutritionniste_id` (`nutritionniste_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `allergenes`
--
ALTER TABLE `allergenes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `un_avis` (`user_id`,`produit_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `conseils`
--
ALTER TABLE `conseils`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutritionniste_id` (`nutritionniste_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutritionniste_id` (`nutritionniste_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `commande_id` (`commande_id`),
  ADD KEY `livreur_id` (`livreur_id`);

--
-- Index pour la table `ordres_production`
--
ALTER TABLE `ordres_production`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `demande_par` (`demande_par`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item` (`user_id`,`produit_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `plans_alimentaires`
--
ALTER TABLE `plans_alimentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutritionniste_id` (`nutritionniste_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `plan_repas`
--
ALTER TABLE `plan_repas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_name` (`nom`),
  ADD UNIQUE KEY `nom` (`nom`),
  ADD KEY `categorie_id` (`categorie_id`);

--
-- Index pour la table `produit_allergenes`
--
ALTER TABLE `produit_allergenes`
  ADD PRIMARY KEY (`produit_id`,`allergene_id`),
  ADD KEY `allergene_id` (`allergene_id`);

--
-- Index pour la table `reclamations`
--
ALTER TABLE `reclamations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `repondu_par` (`repondu_par`);

--
-- Index pour la table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produit_id` (`produit_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `vip_abonnements`
--
ALTER TABLE `vip_abonnements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Index pour la table `vip_messages`
--
ALTER TABLE `vip_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expediteur_id` (`expediteur_id`),
  ADD KEY `destinataire_id` (`destinataire_id`),
  ADD KEY `consultation_id` (`consultation_id`);

--
-- Index pour la table `vip_objectifs`
--
ALTER TABLE `vip_objectifs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `nutri_id` (`nutri_id`);

--
-- Index pour la table `vip_paiements`
--
ALTER TABLE `vip_paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `abonnement_id` (`abonnement_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `alertes_nutritionnelles`
--
ALTER TABLE `alertes_nutritionnelles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `allergenes`
--
ALTER TABLE `allergenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `commande_details`
--
ALTER TABLE `commande_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `conseils`
--
ALTER TABLE `conseils`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `livraisons`
--
ALTER TABLE `livraisons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `ordres_production`
--
ALTER TABLE `ordres_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT pour la table `plans_alimentaires`
--
ALTER TABLE `plans_alimentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `plan_repas`
--
ALTER TABLE `plan_repas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT pour la table `reclamations`
--
ALTER TABLE `reclamations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `vip_abonnements`
--
ALTER TABLE `vip_abonnements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `vip_messages`
--
ALTER TABLE `vip_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `vip_objectifs`
--
ALTER TABLE `vip_objectifs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `vip_paiements`
--
ALTER TABLE `vip_paiements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `alertes_nutritionnelles`
--
ALTER TABLE `alertes_nutritionnelles`
  ADD CONSTRAINT `alertes_nutritionnelles_ibfk_1` FOREIGN KEY (`nutritionniste_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alertes_nutritionnelles_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD CONSTRAINT `chatbot_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD CONSTRAINT `commande_details_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commande_details_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conseils`
--
ALTER TABLE `conseils`
  ADD CONSTRAINT `conseils_ibfk_1` FOREIGN KEY (`nutritionniste_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conseils_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`nutritionniste_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultations_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD CONSTRAINT `livraisons_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `livraisons_ibfk_2` FOREIGN KEY (`livreur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `ordres_production`
--
ALTER TABLE `ordres_production`
  ADD CONSTRAINT `ordres_production_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ordres_production_ibfk_2` FOREIGN KEY (`demande_par`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `plans_alimentaires`
--
ALTER TABLE `plans_alimentaires`
  ADD CONSTRAINT `plans_alimentaires_ibfk_1` FOREIGN KEY (`nutritionniste_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plans_alimentaires_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `plan_repas`
--
ALTER TABLE `plan_repas`
  ADD CONSTRAINT `plan_repas_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans_alimentaires` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `produit_allergenes`
--
ALTER TABLE `produit_allergenes`
  ADD CONSTRAINT `produit_allergenes_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produit_allergenes_ibfk_2` FOREIGN KEY (`allergene_id`) REFERENCES `allergenes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reclamations`
--
ALTER TABLE `reclamations`
  ADD CONSTRAINT `reclamations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_ibfk_2` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reclamations_ibfk_3` FOREIGN KEY (`repondu_par`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `vip_abonnements`
--
ALTER TABLE `vip_abonnements`
  ADD CONSTRAINT `vip_abonnements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `vip_messages`
--
ALTER TABLE `vip_messages`
  ADD CONSTRAINT `vip_messages_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vip_messages_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vip_messages_ibfk_3` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `vip_objectifs`
--
ALTER TABLE `vip_objectifs`
  ADD CONSTRAINT `vip_objectifs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vip_objectifs_ibfk_2` FOREIGN KEY (`nutri_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `vip_paiements`
--
ALTER TABLE `vip_paiements`
  ADD CONSTRAINT `vip_paiements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vip_paiements_ibfk_2` FOREIGN KEY (`abonnement_id`) REFERENCES `vip_abonnements` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
