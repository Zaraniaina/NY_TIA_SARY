-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 28 juil. 2026 à 14:03
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ny_tia_sary_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `authentification`
--

CREATE TABLE `authentification` (
  `ID_AUTH` bigint(4) NOT NULL,
  `EMAIL_AUTH` varchar(255) NOT NULL,
  `MDP_AUTH` varchar(128) NOT NULL,
  `ROLE_AUTH` enum('ADMIN','CLIENT') NOT NULL DEFAULT 'CLIENT'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `authentification`
--

INSERT INTO `authentification` (`ID_AUTH`, `EMAIL_AUTH`, `MDP_AUTH`, `ROLE_AUTH`) VALUES
(1, 'admin@gmail.com', '$2y$10$5P9sjdxN9ivYVykB1jtJNORE.FkJYFqmcg.ezE0D.uY3x1tipVDoK', 'ADMIN'),
(2, 'nomena@gmail.com', '$2y$10$8fmbC2IpKsH504F4D4J6Petx23G7JfWfarBmz1V1fEkh6huLA/4l6', 'CLIENT'),
(3, 'toky@gmail.com', '$2y$10$5pp5YKNGjgacIXWlNPNdUuKyqVQLbbnxKaQ7s0irsmDhlRggjJINe', 'CLIENT');

-- --------------------------------------------------------

--
-- Structure de la table `blog`
--

CREATE TABLE `blog` (
  `ID_BLOG` bigint(4) NOT NULL,
  `ID_TYPE_BLOG` bigint(4) NOT NULL,
  `TITRE_BLOG` varchar(128) NOT NULL,
  `CONTENU` varchar(255) NOT NULL,
  `IMAGE_COURVERTURE` varchar(255) NOT NULL,
  `DATE_PUBLICATION` date NOT NULL DEFAULT curdate(),
  `DATE_MODIFICATION` date NOT NULL DEFAULT curdate(),
  `STATUS_BLOG` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `ID_CATEGORIE` bigint(4) NOT NULL,
  `LIB_CATEGORIE` varchar(128) NOT NULL,
  `TARIF_CATEGORIE` int(11) NOT NULL,
  `ID_PRESTATION` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`ID_CATEGORIE`, `LIB_CATEGORIE`, `TARIF_CATEGORIE`, `ID_PRESTATION`) VALUES
(1, 'Equipe', 500000, 1),
(2, 'Portrait professionnel', 200000, 1),
(3, 'Entreprise', 600000, 1),
(5, 'Publicité', 100000, 1),
(6, 'Conférences', 600000, 2),
(7, 'Concert', 400000, 2),
(8, 'Festival', 900000, 2),
(9, 'Anniversaires', 650000, 2),
(10, 'Pré-mariage', 400000, 3),
(11, 'Mariage', 1000000, 3),
(12, 'Drone', 3000000, 3),
(13, 'Album', 200000, 3),
(14, 'Shooting studio', 200000, 4),
(15, 'Lookbook', 500000, 4),
(16, 'Fashion', 1000000, 4),
(17, 'E-commerce', 600000, 5),
(18, 'Catalogue', 100000, 5),
(19, 'Publicité', 50000, 5),
(20, 'Spot publicitaire', 200000, 6),
(21, 'Interview', 400000, 6),
(22, 'Film institutionnel', 100000, 6),
(23, 'Clip musical', 300000, 6),
(25, 'Vidéos aériennes', 900000, 7),
(26, 'Photos aériennes', 400000, 7);

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `ID_CLIENT` bigint(4) NOT NULL,
  `ID_AUTH` bigint(4) NOT NULL,
  `NOM_CLIENT` varchar(255) NOT NULL,
  `PRENOM_CLIENT` varchar(255) NOT NULL,
  `TEL_CLIENT` varchar(128) NOT NULL,
  `TYPE_CLIENT` varchar(128) NOT NULL,
  `PHOTO_CLIENT` varchar(255) NOT NULL DEFAULT 'assets/images/avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `client`
--

INSERT INTO `client` (`ID_CLIENT`, `ID_AUTH`, `NOM_CLIENT`, `PRENOM_CLIENT`, `TEL_CLIENT`, `TYPE_CLIENT`, `PHOTO_CLIENT`) VALUES
(1, 1, 'Rakoto', 'Bema', '+261341234567', 'Particulier', 'assets/uploads/avatars/9408e94c750e742dae9f2172bbb6c85f.jpg'),
(2, 2, 'Nomena', 'Sarika', '+261321234567', 'Particulier', 'assets/images/avatar.png'),
(3, 3, 'Toky', 'Natenanina', '+261381234578', 'Artistes', 'assets/images/avatar.png');

-- --------------------------------------------------------

--
-- Structure de la table `contact`
--

CREATE TABLE `contact` (
  `ID_CONTACT` bigint(4) NOT NULL,
  `ADRESSE_CONTACT` varchar(255) NOT NULL,
  `TEL_CONTACT` varchar(128) NOT NULL,
  `WHATSAPP_LIEN` varchar(255) NOT NULL,
  `MESSENGER_LIEN` varchar(255) NOT NULL,
  `EMAIL_CONTACT` varchar(100) NOT NULL,
  `HORAIRE_CONTACT` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contact`
--

INSERT INTO `contact` (`ID_CONTACT`, `ADRESSE_CONTACT`, `TEL_CONTACT`, `WHATSAPP_LIEN`, `MESSENGER_LIEN`, `EMAIL_CONTACT`, `HORAIRE_CONTACT`) VALUES
(2, 'Mangarano, Toamasina', '+261 34 1234567', 'https://web.whatsapp.com/', 'https://www.facebook.com', 'nytiasarycontact@gmail.com', 'Lundi -Samedi de 08:00 à 18:00');

-- --------------------------------------------------------

--
-- Structure de la table `contrat`
--

CREATE TABLE `contrat` (
  `ID_CONTRAT` bigint(4) NOT NULL,
  `ID_RESERVATION` bigint(4) NOT NULL,
  `STATUS_CONTRAT` varchar(128) NOT NULL,
  `DATE_CONTRAT` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contrat`
--

INSERT INTO `contrat` (`ID_CONTRAT`, `ID_RESERVATION`, `STATUS_CONTRAT`, `DATE_CONTRAT`) VALUES
(2, 2, 'CONFIRME', '2026-07-23'),
(3, 3, 'EN ATTENTE', '2026-07-23');

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

CREATE TABLE `devis` (
  `ID` bigint(4) NOT NULL,
  `NOM` varchar(255) NOT NULL,
  `PRENOMS` varchar(255) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `TELEPHONE` varchar(128) NOT NULL,
  `TYPE_VISITEUR` varchar(128) NOT NULL,
  `BUGET_ESTIMATIF` varchar(128) NOT NULL,
  `DATE_SOUHAITE` date NOT NULL,
  `DESCRIPTION` varchar(255) NOT NULL,
  `ID_PRESTATION` bigint(20) NOT NULL,
  `FICHIER_REPONSE` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `devis`
--

INSERT INTO `devis` (`ID`, `NOM`, `PRENOMS`, `EMAIL`, `TELEPHONE`, `TYPE_VISITEUR`, `BUGET_ESTIMATIF`, `DATE_SOUHAITE`, `DESCRIPTION`, `ID_PRESTATION`, `FICHIER_REPONSE`) VALUES
(9, 'BEZA', 'Bolo', 'bolo@gmail.com', '0320212121', 'Familles', '600000', '2026-07-18', 'no comment', 3, ''),
(10, 'RANDRIA', 'Sarika', 'sarika@gmail.com', '+261331234567', 'Particuliers', '100000', '2026-07-27', 'dfvsfds', 7, '');

-- --------------------------------------------------------

--
-- Structure de la table `devis_categories`
--

CREATE TABLE `devis_categories` (
  `ID_DEVIS` bigint(20) NOT NULL,
  `ID_CATEGORIE` bigint(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `devis_categories`
--

INSERT INTO `devis_categories` (`ID_DEVIS`, `ID_CATEGORIE`) VALUES
(9, 12),
(9, 13),
(10, 25);

-- --------------------------------------------------------

--
-- Structure de la table `facture`
--

CREATE TABLE `facture` (
  `ID_FACTURE` bigint(4) NOT NULL,
  `ID_CONTRAT` bigint(4) NOT NULL,
  `NUM_FACTURE` varchar(128) NOT NULL,
  `STATUS_FACTURE` varchar(128) NOT NULL,
  `MONTANT_FACTURE` bigint(4) NOT NULL,
  `DATE_FACTURE` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `facture`
--

INSERT INTO `facture` (`ID_FACTURE`, `ID_CONTRAT`, `NUM_FACTURE`, `STATUS_FACTURE`, `MONTANT_FACTURE`, `DATE_FACTURE`) VALUES
(2, 2, 'FAC-2026-0001', 'NON PAYEE', 200000, '2026-07-23');

-- --------------------------------------------------------

--
-- Structure de la table `media`
--

CREATE TABLE `media` (
  `ID_MEDIA` bigint(4) NOT NULL,
  `ID_RESERVATION` bigint(4) NOT NULL,
  `PATH_MEDIA` varchar(255) NOT NULL,
  `TYPE_MEDIA` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `ID_NOTIF` int(11) NOT NULL,
  `TYPE_NOTIF` varchar(110) NOT NULL,
  `ID_REF_NOTIF` int(11) NOT NULL,
  `TITRE_NOTIF` varchar(200) NOT NULL,
  `MESS_NOTIF` text NOT NULL,
  `LU_NOTIF` int(11) NOT NULL,
  `SUP_NOTIF` int(11) NOT NULL,
  `DATE_NOTIF` date NOT NULL DEFAULT current_timestamp(),
  `ID_CLIENT` bigint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `notification`
--

INSERT INTO `notification` (`ID_NOTIF`, `TYPE_NOTIF`, `ID_REF_NOTIF`, `TITRE_NOTIF`, `MESS_NOTIF`, `LU_NOTIF`, `SUP_NOTIF`, `DATE_NOTIF`, `ID_CLIENT`) VALUES
(6, 'devis', 9, 'Demande de devis', 'Notification de BEZA Bolo', 1, 0, '2026-07-13', NULL),
(7, 'Reservation', 2, 'Demande de reservation', 'Reservation de Nomena Sarika', 1, 0, '2026-07-14', NULL),
(8, 'devis', 10, 'Demande de devis', 'Notification de RANDRIA Sarika', 1, 0, '2026-07-23', NULL),
(9, 'client_facture', 2, 'Facture générée', 'Votre contrat a été validé et votre facture est disponible.', 1, 0, '2026-07-23', 2),
(10, 'Reservation', 3, 'Demande de reservation', 'Reservation de Nomena Sarika', 1, 0, '2026-07-23', NULL),
(11, 'client_contrat', 3, 'Votre contrat est prêt', 'Votre réservation du 31/07/2026 a été confirmée et votre contrat est disponible.', 1, 0, '2026-07-23', 2),
(12, 'client_contrat', 3, 'Votre contrat est prêt', 'Votre réservation du 31/07/2026 a été confirmée et votre contrat est disponible.', 0, 0, '2026-07-27', 2);

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `ID_PAIEMENT` bigint(4) NOT NULL,
  `ID_FACTURE` bigint(4) NOT NULL,
  `DATE_PAIEMENT` date NOT NULL DEFAULT curdate(),
  `MONTANT_PAIEMENT` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `partenaire`
--

CREATE TABLE `partenaire` (
  `ID_PARTENAIRE` bigint(4) NOT NULL,
  `PATH_LOGO` varchar(255) NOT NULL,
  `DESCRIPTIONS` varchar(255) NOT NULL,
  `LIEN_PARTENAIRE` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `partenaire`
--

INSERT INTO `partenaire` (`ID_PARTENAIRE`, `PATH_LOGO`, `DESCRIPTIONS`, `LIEN_PARTENAIRE`) VALUES
(2, 'assets/uploads/partenaires/53d7e7e838a46688d5bb8241dd74635e.png', 'the best partenaria', 'https://fr.wix.com/'),
(3, 'assets/uploads/partenaires/3541494de23939ca8c9163cd90cc4adb.jpg', 'king company', 'https://www.youtube.com/');

-- --------------------------------------------------------

--
-- Structure de la table `pieces_jointes`
--

CREATE TABLE `pieces_jointes` (
  `ID_PIECE` bigint(4) NOT NULL,
  `ID` bigint(4) NOT NULL,
  `PATH_PIECE` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pieces_jointes`
--

INSERT INTO `pieces_jointes` (`ID_PIECE`, `ID`, `PATH_PIECE`) VALUES
(6, 9, 'aucun'),
(7, 10, 'aucun');

-- --------------------------------------------------------

--
-- Structure de la table `prestations`
--

CREATE TABLE `prestations` (
  `ID_PRESTATION` bigint(4) NOT NULL,
  `LIB_PRESTATION` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `prestations`
--

INSERT INTO `prestations` (`ID_PRESTATION`, `LIB_PRESTATION`) VALUES
(1, 'photographie Corporate'),
(2, 'Photographie Evénementielle'),
(3, 'Mariage'),
(4, 'Mode'),
(5, 'Photographie produits'),
(6, 'Production Vidéo'),
(7, 'Drone');

-- --------------------------------------------------------

--
-- Structure de la table `reservation`
--

CREATE TABLE `reservation` (
  `ID_RESERVATION` bigint(4) NOT NULL,
  `ID_CLIENT` bigint(4) NOT NULL,
  `DATE_RESERVATION` date NOT NULL,
  `HEURE_RESERVATION` time NOT NULL,
  `LIEU_RESERVATION` varchar(255) NOT NULL,
  `COMME_RESERVATION` varchar(255) NOT NULL,
  `STATUS_RESERVATION` enum('EN ATTENTE','CONFIRMEE','ANNULEE','TERMINEE') NOT NULL DEFAULT 'EN ATTENTE',
  `ID_PRESTATION` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservation`
--

INSERT INTO `reservation` (`ID_RESERVATION`, `ID_CLIENT`, `DATE_RESERVATION`, `HEURE_RESERVATION`, `LIEU_RESERVATION`, `COMME_RESERVATION`, `STATUS_RESERVATION`, `ID_PRESTATION`) VALUES
(2, 2, '2026-07-18', '16:04:00', 'Mangarano', 'no comment', 'CONFIRMEE', 3),
(3, 2, '2026-07-31', '12:00:00', 'ddvdfv', 'dvgdsvsdvs', 'CONFIRMEE', 4);

-- --------------------------------------------------------

--
-- Structure de la table `reservation_categorie`
--

CREATE TABLE `reservation_categorie` (
  `ID_RESERVATION` bigint(20) NOT NULL,
  `ID_CATEGORIE` bigint(20) NOT NULL,
  `PRIX` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `reservation_categorie`
--

INSERT INTO `reservation_categorie` (`ID_RESERVATION`, `ID_CATEGORIE`, `PRIX`) VALUES
(2, 13, 200000),
(3, 15, 500000);

-- --------------------------------------------------------

--
-- Structure de la table `secutite`
--

CREATE TABLE `secutite` (
  `ID_SECURITE` bigint(4) NOT NULL,
  `ID_AUTH` bigint(4) NOT NULL,
  `QUESTION` varchar(128) NOT NULL,
  `REPONSE` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `secutite`
--

INSERT INTO `secutite` (`ID_SECURITE`, `ID_AUTH`, `QUESTION`, `REPONSE`) VALUES
(1, 1, 'Quel est votre plat préféré ?', '$2y$10$NaPQxgMMRECdtdFOO0sTweSVCyblPkwsxFhz7ZPfdlZv5u2khv7s.'),
(2, 2, 'Quel est votre plat préféré ?', '$2y$10$QKeoAV2HZeXtrhc.6MhSeedtKj1K4p4g1HDHzBntOI9k/0ZEye/YK'),
(3, 3, 'Quelle est votre ville de naissance ?', '$2y$10$x4iipIAM0FOSDEitP6rxfe6e2iu6A2F8nbRp8XsQaTGnR6bQDEDWK');

-- --------------------------------------------------------

--
-- Structure de la table `temoignage`
--

CREATE TABLE `temoignage` (
  `ID_TEMOIGNAGE` int(11) NOT NULL,
  `ID_RESERVATION` bigint(11) NOT NULL,
  `MESS_RESERVATION` text NOT NULL,
  `NOTE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `type_blog`
--

CREATE TABLE `type_blog` (
  `ID_TYPE_BLOG` bigint(4) NOT NULL,
  `LIB_TYPE_BLOG` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `authentification`
--
ALTER TABLE `authentification`
  ADD PRIMARY KEY (`ID_AUTH`),
  ADD UNIQUE KEY `EMAIL_AUTH` (`EMAIL_AUTH`);

--
-- Index pour la table `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`ID_BLOG`),
  ADD KEY `I_FK_BLOG_TYPE_BLOG` (`ID_TYPE_BLOG`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`ID_CATEGORIE`),
  ADD KEY `ID_PRESTATION` (`ID_PRESTATION`);

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`ID_CLIENT`),
  ADD UNIQUE KEY `TEL_CLIENT` (`TEL_CLIENT`),
  ADD UNIQUE KEY `I_FK_CLIENT_AUTHENTIFICATION` (`ID_AUTH`);

--
-- Index pour la table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`ID_CONTACT`);

--
-- Index pour la table `contrat`
--
ALTER TABLE `contrat`
  ADD PRIMARY KEY (`ID_CONTRAT`),
  ADD UNIQUE KEY `I_FK_CONTRAT_RESERVATION` (`ID_RESERVATION`);

--
-- Index pour la table `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_SERVICE` (`ID_PRESTATION`);

--
-- Index pour la table `devis_categories`
--
ALTER TABLE `devis_categories`
  ADD PRIMARY KEY (`ID_DEVIS`,`ID_CATEGORIE`),
  ADD KEY `ID` (`ID_DEVIS`),
  ADD KEY `ID_CATEGORIES` (`ID_CATEGORIE`);

--
-- Index pour la table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`ID_FACTURE`),
  ADD UNIQUE KEY `I_FK_FACTURE_CONTRAT` (`ID_CONTRAT`);

--
-- Index pour la table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`ID_MEDIA`),
  ADD KEY `I_FK_MEDIA_RESERVATION` (`ID_RESERVATION`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`ID_NOTIF`),
  ADD KEY `I_FK_NOTIFICATION_CLIENT` (`ID_CLIENT`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`ID_PAIEMENT`),
  ADD KEY `I_FK_PAIEMENT_FACTURE` (`ID_FACTURE`);

--
-- Index pour la table `partenaire`
--
ALTER TABLE `partenaire`
  ADD PRIMARY KEY (`ID_PARTENAIRE`);

--
-- Index pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD PRIMARY KEY (`ID_PIECE`),
  ADD KEY `I_FK_PIECES_JOINTES_DEVIS` (`ID`);

--
-- Index pour la table `prestations`
--
ALTER TABLE `prestations`
  ADD PRIMARY KEY (`ID_PRESTATION`);

--
-- Index pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`ID_RESERVATION`),
  ADD KEY `I_FK_RESERVATION_CLIENT` (`ID_CLIENT`),
  ADD KEY `ID_SERVICE` (`ID_PRESTATION`);

--
-- Index pour la table `reservation_categorie`
--
ALTER TABLE `reservation_categorie`
  ADD PRIMARY KEY (`ID_RESERVATION`,`ID_CATEGORIE`),
  ADD KEY `ID_RESERVATION` (`ID_RESERVATION`,`ID_CATEGORIE`),
  ADD KEY `categorie_res` (`ID_CATEGORIE`);

--
-- Index pour la table `secutite`
--
ALTER TABLE `secutite`
  ADD PRIMARY KEY (`ID_SECURITE`),
  ADD KEY `I_FK_SECUTITE_AUTHENTIFICATION` (`ID_AUTH`);

--
-- Index pour la table `temoignage`
--
ALTER TABLE `temoignage`
  ADD PRIMARY KEY (`ID_TEMOIGNAGE`),
  ADD KEY `ID_RESERVATION` (`ID_RESERVATION`);

--
-- Index pour la table `type_blog`
--
ALTER TABLE `type_blog`
  ADD PRIMARY KEY (`ID_TYPE_BLOG`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `authentification`
--
ALTER TABLE `authentification`
  MODIFY `ID_AUTH` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `blog`
--
ALTER TABLE `blog`
  MODIFY `ID_BLOG` bigint(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `ID_CATEGORIE` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `ID_CLIENT` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `contact`
--
ALTER TABLE `contact`
  MODIFY `ID_CONTACT` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `contrat`
--
ALTER TABLE `contrat`
  MODIFY `ID_CONTRAT` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `devis`
--
ALTER TABLE `devis`
  MODIFY `ID` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `facture`
--
ALTER TABLE `facture`
  MODIFY `ID_FACTURE` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `media`
--
ALTER TABLE `media`
  MODIFY `ID_MEDIA` bigint(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `ID_NOTIF` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `ID_PAIEMENT` bigint(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `partenaire`
--
ALTER TABLE `partenaire`
  MODIFY `ID_PARTENAIRE` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  MODIFY `ID_PIECE` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `prestations`
--
ALTER TABLE `prestations`
  MODIFY `ID_PRESTATION` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `ID_RESERVATION` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `secutite`
--
ALTER TABLE `secutite`
  MODIFY `ID_SECURITE` bigint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `temoignage`
--
ALTER TABLE `temoignage`
  MODIFY `ID_TEMOIGNAGE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `type_blog`
--
ALTER TABLE `type_blog`
  MODIFY `ID_TYPE_BLOG` bigint(4) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `blog`
--
ALTER TABLE `blog`
  ADD CONSTRAINT `FK_BLOG_TYPE_BLOG` FOREIGN KEY (`ID_TYPE_BLOG`) REFERENCES `type_blog` (`ID_TYPE_BLOG`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD CONSTRAINT `categories_prestation` FOREIGN KEY (`ID_PRESTATION`) REFERENCES `prestations` (`ID_PRESTATION`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `FK_CLIENT_AUTHENTIFICATION` FOREIGN KEY (`ID_AUTH`) REFERENCES `authentification` (`ID_AUTH`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `contrat`
--
ALTER TABLE `contrat`
  ADD CONSTRAINT `FK_CONTRAT_RESERVATION` FOREIGN KEY (`ID_RESERVATION`) REFERENCES `reservation` (`ID_RESERVATION`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `devi_service` FOREIGN KEY (`ID_PRESTATION`) REFERENCES `prestations` (`ID_PRESTATION`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `devis_categories`
--
ALTER TABLE `devis_categories`
  ADD CONSTRAINT `cate_devis` FOREIGN KEY (`ID_CATEGORIE`) REFERENCES `categorie` (`ID_CATEGORIE`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `devis_cate` FOREIGN KEY (`ID_DEVIS`) REFERENCES `devis` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `facture`
--
ALTER TABLE `facture`
  ADD CONSTRAINT `FK_FACTURE_CONTRAT` FOREIGN KEY (`ID_CONTRAT`) REFERENCES `contrat` (`ID_CONTRAT`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `FK_MEDIA_RESERVATION` FOREIGN KEY (`ID_RESERVATION`) REFERENCES `reservation` (`ID_RESERVATION`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `FK_NOTIFICATION_CLIENT` FOREIGN KEY (`ID_CLIENT`) REFERENCES `client` (`ID_CLIENT`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `FK_PAIEMENT_FACTURE` FOREIGN KEY (`ID_FACTURE`) REFERENCES `facture` (`ID_FACTURE`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD CONSTRAINT `FK_PIECES_JOINTES_DEVIS` FOREIGN KEY (`ID`) REFERENCES `devis` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `FK_RESERVATION_CLIENT` FOREIGN KEY (`ID_CLIENT`) REFERENCES `client` (`ID_CLIENT`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `service_reservation` FOREIGN KEY (`ID_PRESTATION`) REFERENCES `prestations` (`ID_PRESTATION`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reservation_categorie`
--
ALTER TABLE `reservation_categorie`
  ADD CONSTRAINT `categorie_res` FOREIGN KEY (`ID_CATEGORIE`) REFERENCES `categorie` (`ID_CATEGORIE`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `res_categorie` FOREIGN KEY (`ID_RESERVATION`) REFERENCES `reservation` (`ID_RESERVATION`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `secutite`
--
ALTER TABLE `secutite`
  ADD CONSTRAINT `FK_SECUTITE_AUTHENTIFICATION` FOREIGN KEY (`ID_AUTH`) REFERENCES `authentification` (`ID_AUTH`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `temoignage`
--
ALTER TABLE `temoignage`
  ADD CONSTRAINT `temoignage_client` FOREIGN KEY (`ID_RESERVATION`) REFERENCES `reservation` (`ID_RESERVATION`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
