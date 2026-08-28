/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: jevend_db
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `jevend_achats_publicites`
--

DROP TABLE IF EXISTS `jevend_achats_publicites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_achats_publicites` (
  `id_achat` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_annonce` int(11) DEFAULT NULL,
  `type_produit` enum('reguliere','premium','supreme') NOT NULL DEFAULT 'reguliere',
  `montant_paye` decimal(10,2) NOT NULL,
  `duree_jours` int(11) NOT NULL,
  `texte_banniere` varchar(120) NOT NULL,
  `date_achat` timestamp NOT NULL DEFAULT current_timestamp(),
  `stripe_checkout_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id_achat`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_achats_publicites`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_achats_publicites` WRITE;
/*!40000 ALTER TABLE `jevend_achats_publicites` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_achats_publicites` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_admin_ban`
--

DROP TABLE IF EXISTS `jevend_admin_ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_admin_ban` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `etat` enum('actif','inactif') DEFAULT 'inactif',
  `texte` text NOT NULL,
  `date_maj` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_admin_ban`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_admin_ban` WRITE;
/*!40000 ALTER TABLE `jevend_admin_ban` DISABLE KEYS */;
INSERT INTO `jevend_admin_ban` VALUES
(1,'inactif','📢 Bienvenue sur JeVend.com ! Suivez nos dernières nouveautés ici.','2026-07-26 14:57:27');
/*!40000 ALTER TABLE `jevend_admin_ban` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_annonces`
--

DROP TABLE IF EXISTS `jevend_annonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_annonces` (
  `id_annonces` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_categorie` int(11) NOT NULL,
  `titre_objet_nettoye` varchar(60) NOT NULL,
  `description_service` text DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `prix_promo` decimal(10,2) DEFAULT NULL,
  `date_fin_promo` datetime DEFAULT NULL,
  `image_courante` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_expiration` datetime NOT NULL,
  `statut` enum('actif','vendu','expire','inactif') NOT NULL DEFAULT 'actif',
  `statut_vente` varchar(20) NOT NULL DEFAULT 'disponible',
  `nb_vues_visiteurs` int(11) NOT NULL DEFAULT 0,
  `nb_vues_membres` int(11) NOT NULL DEFAULT 0,
  `nb_clics_contact` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_annonces`),
  KEY `fk_annonces_utilisateur` (`id_utilisateur`),
  KEY `fk_annonces_categorie` (`id_categorie`),
  CONSTRAINT `fk_annonces_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `jevend_categories` (`id_categorie`),
  CONSTRAINT `fk_annonces_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `jevend_utilisateurs` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1048 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_annonces`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_annonces` WRITE;
/*!40000 ALTER TABLE `jevend_annonces` DISABLE KEYS */;
INSERT INTO `jevend_annonces` VALUES
(1047,19,22,'Ensemble de batterie Gretsch Drums Energy','Grosse caisse 40,6 x 55,9 cm, toms suspendus 17,8 x 25,4 cm et 20,3 x 30,5 cm, tom sur pied 35,6 x 40,6 cm, caisse claire 12,7 x 35,6 cm\r\nMatériel : pédale de grosse caisse, support de charleston, support de caisse claire, support de cymbale droite, trône de batterie\r\nPack de cymbales : charleston de 33 cm, cymbale crash/ride de 38,1 cm\r\nAttaches classiques Full Range Gretsch\r\nLogo emblématique Gretsch sur la tête de grosse caisse',800.00,NULL,NULL,'img_6a6ce837131e96.68280387.jpg','2026-07-31 18:23:51','2026-08-10 14:23:51','actif','disponible',0,0,0);
/*!40000 ALTER TABLE `jevend_annonces` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_annonces_images`
--

DROP TABLE IF EXISTS `jevend_annonces_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_annonces_images` (
  `id_image` int(11) NOT NULL AUTO_INCREMENT,
  `id_annonces` int(11) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_image`),
  KEY `id_annonces` (`id_annonces`),
  CONSTRAINT `fk_images_annonces` FOREIGN KEY (`id_annonces`) REFERENCES `jevend_annonces` (`id_annonces`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_annonces_images`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_annonces_images` WRITE;
/*!40000 ALTER TABLE `jevend_annonces_images` DISABLE KEYS */;
INSERT INTO `jevend_annonces_images` VALUES
(47,1047,'img_6a6ce837131e96.68280387.jpg',1,'2026-07-31 18:23:51');
/*!40000 ALTER TABLE `jevend_annonces_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_bannieres_actives`
--

DROP TABLE IF EXISTS `jevend_bannieres_actives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_bannieres_actives` (
  `id_banniere` int(11) NOT NULL AUTO_INCREMENT,
  `id_annonce` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `type_banniere` enum('reguliere') NOT NULL DEFAULT 'reguliere',
  `texte_banniere` varchar(120) NOT NULL,
  `duree_jours` int(11) NOT NULL,
  `date_enregistrement` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_debut_activation` datetime DEFAULT NULL,
  `statut_affichage` enum('en_attente','active') NOT NULL DEFAULT 'en_attente',
  `nb_vues` int(11) NOT NULL DEFAULT 0,
  `nb_clics` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_banniere`),
  UNIQUE KEY `unique_annonce_banniere` (`id_annonce`),
  KEY `id_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_bannieres_actives_annonces` FOREIGN KEY (`id_annonce`) REFERENCES `jevend_annonces` (`id_annonces`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_bannieres_actives`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_bannieres_actives` WRITE;
/*!40000 ALTER TABLE `jevend_bannieres_actives` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_bannieres_actives` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_bannieres_actives_pro`
--

DROP TABLE IF EXISTS `jevend_bannieres_actives_pro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_bannieres_actives_pro` (
  `id_banniere_pro` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `type_banniere` enum('supreme','premium') NOT NULL,
  `duree_mois` int(11) NOT NULL,
  `prix_paye` decimal(10,2) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `texte_banniere` varchar(255) DEFAULT NULL,
  `url_redirection` varchar(255) DEFAULT NULL,
  `nb_clics` int(11) NOT NULL DEFAULT 0,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `date_butoir_renouvellement` datetime NOT NULL,
  `statut_affichage` enum('active','en_attente','expiree') DEFAULT 'active',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_banniere_pro`),
  KEY `fk_bann_pro_user` (`id_utilisateur`),
  CONSTRAINT `fk_bann_pro_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `jevend_utilisateurs` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_bannieres_actives_pro`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_bannieres_actives_pro` WRITE;
/*!40000 ALTER TABLE `jevend_bannieres_actives_pro` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_bannieres_actives_pro` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_categories`
--

DROP TABLE IF EXISTS `jevend_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_categories` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `nom_fr` varchar(100) NOT NULL,
  `nom_en` varchar(100) NOT NULL,
  PRIMARY KEY (`id_categorie`),
  KEY `fk_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `jevend_categories` (`id_categorie`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_categories` WRITE;
/*!40000 ALTER TABLE `jevend_categories` DISABLE KEYS */;
INSERT INTO `jevend_categories` VALUES
(1,NULL,'Véhicules et Pièces','Vehicles and Parts'),
(2,NULL,'Maison et Électroménager','Home and Appliances'),
(3,NULL,'Outils et Matériaux','Tools and Materials'),
(4,NULL,'Électronique et Informatique','Electronics and Computers'),
(5,NULL,'Loisirs, Sport et Plein air','Leisure, Sport and Outdoors'),
(6,NULL,'Articles pour bébés et enfants','Baby and Children Items'),
(7,NULL,'Vêtements et Accessoires','Clothing and Accessories'),
(8,NULL,'Animaux et Accessoires','Pets and Accessories'),
(9,NULL,'Immobilier (Locations/Ventes)','Real Estate (Rentals/Sales)'),
(10,1,'Pièces et Accessoires auto','Parts & Accessories'),
(11,2,'Meubles','Furniture'),
(12,2,'Électroménagers','Appliances'),
(13,3,'Scies et Scies rondes','Saws & Circular Saws'),
(14,3,'Outils à main','Hand Tools'),
(15,NULL,'Services professionnels et cours','Professional Services and Courses'),
(16,NULL,'Services à domicile et Entretien','Home Services and Maintenance'),
(17,NULL,'Soin des animaux (Toilettage/Garde)','Pet Care (Grooming/Sitting)'),
(18,NULL,'Emplois et Petits boulots','Jobs and Side Gigs'),
(19,NULL,'Divers et Brocante','Miscellaneous and Flea Market'),
(20,2,'Bungalow','Bungalow'),
(21,NULL,'Instrument Musicals','Musical Instruments'),
(22,21,'Batterie','Drums'),
(24,21,'Guitare','Guitar'),
(25,21,'Clavier','Keyboard'),
(27,21,'Autres','Others'),
(28,NULL,'Proposition d\'affaire','Business Proposals'),
(29,NULL,'Marché aux Puces','Flea Market'),
(30,NULL,'Marché aux Puces','Flea Market'),
(31,NULL,'Annonces','Announcement'),
(32,NULL,'Lunettes','Glasses'),
(33,32,'Autres','Others');
/*!40000 ALTER TABLE `jevend_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_configuration_pub`
--

DROP TABLE IF EXISTS `jevend_configuration_pub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_configuration_pub` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `emplacement` enum('HAUT','COTE') NOT NULL,
  `prix_par_jour` decimal(10,2) NOT NULL,
  `stripe_price_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `emplacement` (`emplacement`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_configuration_pub`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_configuration_pub` WRITE;
/*!40000 ALTER TABLE `jevend_configuration_pub` DISABLE KEYS */;
INSERT INTO `jevend_configuration_pub` VALUES
(1,'HAUT',2.50,'price_HAUT_placeholder'),
(2,'COTE',1.50,'price_COTE_placeholder');
/*!40000 ALTER TABLE `jevend_configuration_pub` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_faq`
--

DROP TABLE IF EXISTS `jevend_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `reponse` text NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_faq`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_faq` WRITE;
/*!40000 ALTER TABLE `jevend_faq` DISABLE KEYS */;
INSERT INTO `jevend_faq` VALUES
(1,'Comment acheter de l\'affichage ou une bannière ?','Vous pouvez réserver vos emplacements publicitaires directement depuis votre espace membre dans la section dédiée.',6,1,'2026-07-26 18:16:26'),
(2,'Combien de temps reste en ligne une annonce ?','Les annonces restent en ligne jusqu\'à leur date d\'échéance automatique. Une purge automatique supprime les annonces expirées.',4,1,'2026-07-26 18:16:26'),
(4,'Comment créer un compte sur JeVend.com ?','Pour créer un compte gratuitement sur JeVend.com :\r\n\r\n1. Cliquez sur le bouton \"S\'inscrire\" dans la barre de navigation.\r\n\r\n2. Entrez votre nom et votre adresse courriel.\r\n\r\n3. Un code de vérification vous sera immédiatement envoyé par courriel.\r\n\r\n4. Saisissez ce code sur le site pour valider votre accès (aucun mot de passe à retenir !).\r\n\r\n5. Vous êtes automatiquement connecté à votre espace membre et prêt à publier.',2,1,'2026-07-26 18:48:47'),
(5,'Quel est l\'avantage d\'\'afficher une bannière publicitaire sur JeVend.com ?','Offrez une visibilité maximale à votre offre pour seulement 1 $ par jour !\r\n\r\nPositionnées stratégiquement dans la zone supérieure de la page d\'accueil, nos bannières bénéficient d\'un système de rotation dynamique : la position de votre visuel change à chaque chargement de page.\r\n\r\nLes avantages pour vous :\r\n• Visibilité garantie : Peu importe le nombre d\'annonces sur le site, votre bannière capte l\'attention des visiteurs dès leur arrivée.\r\n• Équité absolue : Tous les annonceurs profitent d\'\'un affichage haut de page à tour de rôle.\r\n• Impact immédiat : Idéal pour vendre plus vite, promouvoir un commerce ou mettre en valeur une offre spéciale.\r\n\r\nRéservez votre emplacement dès aujourd\'hui pour 10, 20 ou 30 jours et boostez vos ventes !',5,1,'2026-07-27 13:04:37'),
(6,'Je suis un commerçant ou membre PRO : quelles sont les formules de bannières disponibles ?','Assurez une présence continue et dominante à votre entreprise grâce à nos formules mensuelles récurrentes !\r\n\r\nPour capturer l\'attention des acheteurs locaux au quotidien, JeVend.com met à disposition deux emplacements professionnels d\'exception :\r\n\r\n• Bannière SUPRÊME (89 $ / mois — soit seulement 2,33 $ / jour) :\r\n\r\nLa visibilité ultime. Votre marque occupe l\'emplacement le plus prestigieux et le plus convoité du site pour un impact maximal et une crédibilité totale.\r\n\r\n• Bannière PREMIUM (55 $ / mois — soit seulement 1,83 $ / jour) :\r\n\r\nL\'équilibre parfait entre performance et budget. Un positionnement prioritaire haut de page pour faire rouler vos offres toute l\'année.\r\n\r\nPourquoi choisir une formule PRO mensuelle ?\r\n\r\n- Affichage continu 24/7 durant tout le mois (renouvelable).\r\n\r\n- Redirection directe vers votre Espace Marchand ou vos annonces.\r\n\r\n- Tarif au jour dérisoire pour un impact local massif.\r\n\r\nDémarquez-vous de la concurrence : choisissez votre formule PRO dès aujourd\'hui !',7,1,'2026-07-27 13:22:24'),
(7,'Comment fonctionne le « Plan de Vente » pour accélérer la vente de mon objet ?','Transformez l\'intérêt des acheteurs en vente concrète grâce à notre système exclusif de Vente Flash !\r\n\r\nSur JeVend.com, le bouton Cœur ❤️ n\'est pas un simple coup de cœur : c\'est un signal de négociation. Lorsqu\'un visiteur ajoute votre vitrine à sa Liste d\'Envie, il vous envoie un message silencieux : « Je suis intéressé, es-tu prêt à revoir ton prix ? »\r\n\r\nComment prendre la main et forcer la vente :\r\n\r\n1. Rendez-vous dans votre Espace Membre ➔ Onglet « 🚀 Bon Plan de Vente ».\r\n\r\n2. Consultez le nombre de prospects qui surveillent votre annonce.\r\n\r\n3. Activez un Prix Spécial Flash en baissant votre prix pour une durée limitée (24h, 48h ou 72h).\r\n\r\nL\'effet choc sur le marché :\r\n\r\n• Urgence maximale : Un badge 🔥 VENTE FLASH avec compte à rebours s\'affiche immédiatement sur votre annonce sur la page d\'accueil, sur votre fiche détaillée et dans la Liste d\'Envie de vos prospects.\r\n\r\n• Compétition en direct : La règle est stricte — Premier arrivé, premier servi ! Les acheteurs indécis reçoivent une poussée d\'urgence pour acheter avant qu\'un autre ne leur souffle l\'aubaine.\r\n\r\n• Zéro gestion : Dès que le chrono arrive à zéro, votre prix régulier se réinstalle automatiquement.\r\n\r\nDéclenchez votre premier Plan de Vente dès aujourd\'hui et transformez vos curieux en acheteurs payants !',9,1,'2026-07-27 14:47:28'),
(8,'Comment JeVend.com protège ma tranquillité une fois mon objet vendu ?','Fini les messages intempestifs « Est-ce toujours disponible ? » des semaines après votre vente !\r\n\r\nContrairement aux autres réseaux sociaux et sites d\'annonces où vos coordonnées continuent de circuler à l\'infini, JeVend.com a été conçu pour respecter la paix d\'esprit des vendeurs.\r\n\r\nDeux protections exclusives pour votre tranquillité :\r\n\r\n• Le Bouton de Tranquillité (Marquer comme vendu) : Dès que votre transaction est conclue, un simple clic dans votre Espace Membre désactive immédiatement les options d\'appels et de SMS.\r\n\r\n• La Purge Automatique : Aucune annonce fantôme sur notre plateforme. Une fois l\'échéance atteinte, tout s\'efface proprement du site sans conserver vos informations personnelles.\r\n\r\nVendez rapidement, en toute sécurité, et retrouvez votre tranquillité instantanément !',8,1,'2026-07-27 14:49:32'),
(9,'En quoi une bannière régulière profite-t-elle à mes annonces ?','Sur jevend.com, la visibilité locale est la clé de voûte de vos ventes. Si le catalogue principal et le module « Je Cherche » permettent des échanges organiques extraordinaires, l\'utilisation d\'une bannière publicitaire régulière agit comme un véritable accélérateur de trafic.\r\n\r\nVoici en détail pourquoi investir dans l\'affichage publicitaire change radicalement la donne pour vos affaires :\r\n\r\n1. Une visibilité maximale et ciblée dans votre région\r\nContrairement aux réseaux sociaux où vos publications se noient en quelques minutes dans un fil d\'actualité infini, une bannière sur jevend.com s\'affiche directement aux endroits stratégiques du site fréquentés par les acheteurs locaux de votre secteur. Elle attire l\'œil instantanément et capte l\'attention dès les premières secondes de navigation.\r\n\r\n2. Le pouvoir de « ressusciter » une ancienne annonce\r\nC\'est l\'un des secrets les plus puissants de notre régie publicitaire :\r\n\r\nAvec le temps, une annonce standard finit par descendre naturellement dans la hiérarchie de l\'index général, supplantée par les nouveautés du jour.\r\n\r\nAssocier une bannière à une annonce plus ancienne permet de la ressusciter instantanément. Elle ne se contente pas de reprendre des couleurs sur l\'index principal : elle propulse à nouveau votre objet sur le devant de la scène auprès de toute la communauté locale, effaçant l\'effet de l\'ancienneté.\r\n\r\n3. La pôle position absolue dans le moteur de recherche\r\nLorsqu\'un acheteur utilise le moteur interne du site pour trouver un article, les algorithmes de la plateforme accordent une priorité suprême aux annonces soutenues par une bannière.\r\n\r\nElles sont listées en tout premier, tout en haut des résultats de recherche, peu importe les mots-clés saisis.\r\n\r\nMême si l\'acheteur cherche un terme large ou spécifique, votre bannière et votre annonce associée s\'imposent en tête de gondole, maximisant de façon spectaculaire vos chances de conclure une vente rapide.\r\n\r\nEn résumé\r\nAcheter de l\'affichage publicitaire sur jevend.com, ce n\'est pas seulement « faire de la pub », c\'est s\'assurer un levier de conversion direct. C\'est l\'outil parfait pour dynamiser un inventaire qui dort, écouler rapidement des biens ou asseoir la notoriété de votre commerce local face à la concurrence.',1,1,'2026-07-28 20:21:30'),
(10,'Sur jevend, vous pouvez demander ce que vous n\'avez pas (L\'art de trouver l\'introuvable)','Le module « Je Cherche » de jevend.com renverse complètement la logique des petites annonces traditionnelles. Au lieu d\'attendre passivement qu\'un vendeur publie l\'objet que vous convoitez, c\'est vous, l\'acheteur, qui exprimez votre besoin, et ce sont les vendeurs locaux qui viennent à vous !\r\nVoici tout ce que vous devez savoir pour comprendre, anticiper et exploiter pleinement ce puissant outil de proximité.\r\n1. Qu\'est-ce que le module « Je Cherche » et à quoi ça sert ?\r\nC\'est un babillard inversé et ultra-vivant. Si vous cherchez une pièce de voiture introuvable, un instrument de musique rare, un outil spécifique pour vos travaux ou un meuble aux dimensions précises qui ne figure dans aucun catalogue public, vous n\'avez plus besoin de fouiner pendant des heures : vous le demandez.\r\nCe module sert à créer une passerelle directe entre un besoin réel de la communauté et les stocks cachés des résidents ou des marchands locaux.\r\n2. Comment ça fonctionne concrètement ?\r\nPublier une demande d\'achat se fait en quelques clics :\r\nLe Titre et la Description : Vous décrivez précisément l\'objet recherché (ex: « Tondeuse à gazon fonctionnelle » ou « Cardan pour Kia 2010 ») en y ajoutant vos exigences de marque ou d\'état.\r\nLa Localisation et la Catégorie : Vous sélectionnez votre municipalité et la catégorie d\'articles pour cibler directement les bons interlocuteurs dans votre secteur.\r\nLe Budget : Vous indiquez un budget maximal ou laissez le champ ouvert pour initier la négociation.\r\nLa Photo de référence : Vous pouvez joindre une image d\'exemple (qui est automatiquement traitée, recadrée et compressée proprement par le système).\r\nLa Durée de vie : Votre demande reste active, visible en flux direct dans la zone dédiée pour une durée par défaut de 30 jours.\r\n3. Que peut-on attendre de « Je Cherche » une fois publié ?\r\nDès que votre besoin est en ligne, il s\'intègre au flux chronologique de la Zone \"Je Cherche\" et défile dans le ticker du site. C\'est là que la magie opère :\r\nDes propositions multiples : Un vendeur qui possède l\'objet dans son atelier peut vous répondre directement. Vous pouvez d\'ailleurs recevoir plusieurs propositions de la part de différents vendeurs pour une seule et même demande.\r\nLa vente de gré à gré : Vous consultez les offres reçues dans votre espace personnel (Mes recherches), vous échangez avec les vendeurs, et vous concluez la transaction rapidement et en personne dans votre région.\r\n4. Pourquoi ce module stimule vos trouvailles ?\r\nRévéler le marché dormant : Des milliers d\'objets utiles dorment dans les garages et les armoires de votre municipalité sans que leurs propriétaires n\'aient l\'énergie de créer une annonce classique. Votre demande leur donne une raison immédiate de les proposer.\r\nUne autonomie totale : Vous sortez du cadre rigide des catalogues figés. Vous pilotez vos achats en fonction de vos projets réels.\r\nLa force du réseau local : En combinant la recherche ciblée et la proximité géographique, vous obtenez des résultats fulgurants, souvent conclus en quelques heures tout près de chez vous.',3,1,'2026-07-28 20:30:26');
/*!40000 ALTER TABLE `jevend_faq` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_favoris`
--

DROP TABLE IF EXISTS `jevend_favoris`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_favoris` (
  `id_favori` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_annonces` int(11) NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_favori`),
  UNIQUE KEY `unique_utilisateur_annonce` (`id_utilisateur`,`id_annonces`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_annonces` (`id_annonces`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_favoris`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_favoris` WRITE;
/*!40000 ALTER TABLE `jevend_favoris` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_favoris` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_langues`
--

DROP TABLE IF EXISTS `jevend_langues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_langues` (
  `code_langue` varchar(2) NOT NULL,
  PRIMARY KEY (`code_langue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_langues`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_langues` WRITE;
/*!40000 ALTER TABLE `jevend_langues` DISABLE KEYS */;
INSERT INTO `jevend_langues` VALUES
('EN'),
('FR');
/*!40000 ALTER TABLE `jevend_langues` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_listes_envie`
--

DROP TABLE IF EXISTS `jevend_listes_envie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_listes_envie` (
  `id_envie` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_annonce` int(11) NOT NULL,
  `date_ajout` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_envie`),
  UNIQUE KEY `unique_utilisateur_annonce` (`id_utilisateur`,`id_annonce`),
  KEY `fk_envie_annonce` (`id_annonce`),
  CONSTRAINT `fk_envie_annonce` FOREIGN KEY (`id_annonce`) REFERENCES `jevend_annonces` (`id_annonces`) ON DELETE CASCADE,
  CONSTRAINT `fk_envie_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `jevend_utilisateurs` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_listes_envie`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_listes_envie` WRITE;
/*!40000 ALTER TABLE `jevend_listes_envie` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_listes_envie` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_parametres`
--

DROP TABLE IF EXISTS `jevend_parametres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_parametres` (
  `id_parametre` int(11) NOT NULL AUTO_INCREMENT,
  `cle_parametre` varchar(100) NOT NULL,
  `valeur_parametre` varchar(255) NOT NULL,
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_parametre`),
  UNIQUE KEY `cle_parametre` (`cle_parametre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_parametres`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_parametres` WRITE;
/*!40000 ALTER TABLE `jevend_parametres` DISABLE KEYS */;
INSERT INTO `jevend_parametres` VALUES
(1,'limite_annonces','1000','2026-07-29 14:25:56'),
(2,'limite_recherches','200','2026-07-30 14:52:59');
/*!40000 ALTER TABLE `jevend_parametres` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_pays`
--

DROP TABLE IF EXISTS `jevend_pays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_pays` (
  `id_pays` int(11) NOT NULL AUTO_INCREMENT,
  `nom_pays_fr` varchar(100) NOT NULL,
  `nom_pays_en` varchar(100) NOT NULL,
  PRIMARY KEY (`id_pays`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_pays`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_pays` WRITE;
/*!40000 ALTER TABLE `jevend_pays` DISABLE KEYS */;
INSERT INTO `jevend_pays` VALUES
(1,'Canada','Canada');
/*!40000 ALTER TABLE `jevend_pays` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_photos`
--

DROP TABLE IF EXISTS `jevend_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_photos` (
  `id_photo` int(11) NOT NULL AUTO_INCREMENT,
  `id_annonce` int(11) NOT NULL,
  `chemin_fichier` varchar(255) NOT NULL,
  `ordre` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_photo`),
  KEY `fk_photos_annonce` (`id_annonce`),
  CONSTRAINT `fk_photos_annonce` FOREIGN KEY (`id_annonce`) REFERENCES `jevend_annonces` (`id_annonces`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_photos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_photos` WRITE;
/*!40000 ALTER TABLE `jevend_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_photos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_preuve_dachat`
--

DROP TABLE IF EXISTS `jevend_preuve_dachat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_preuve_dachat` (
  `id_preuve` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `type_client` enum('pro','regulier') DEFAULT 'pro',
  `type_banniere` varchar(50) NOT NULL,
  `no_transaction` varchar(50) NOT NULL,
  `description_achat` text DEFAULT NULL,
  `prix_paye` decimal(10,2) NOT NULL,
  `duree_mois` int(11) NOT NULL,
  `date_achat` datetime NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `statut_paiement` varchar(30) DEFAULT 'Payé',
  PRIMARY KEY (`id_preuve`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `type_client` (`type_client`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_preuve_dachat`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_preuve_dachat` WRITE;
/*!40000 ALTER TABLE `jevend_preuve_dachat` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_preuve_dachat` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_publicites`
--

DROP TABLE IF EXISTS `jevend_publicites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_publicites` (
  `id_pub` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `nom_annonceur` varchar(100) NOT NULL,
  `emplacement` enum('HAUT','COTE') NOT NULL,
  `texte_pub_fr` varchar(60) DEFAULT NULL,
  `texte_pub_en` varchar(60) DEFAULT NULL,
  `statut_paiement` enum('En attente','Paye','Expire') NOT NULL DEFAULT 'En attente',
  `stripe_session_id` varchar(255) NOT NULL,
  `cout_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `chemin_image` varchar(255) DEFAULT NULL,
  `url_redirection` varchar(255) NOT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `nb_clics` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_pub`),
  KEY `fk_publicites_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_publicites_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `jevend_utilisateurs` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_publicites`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_publicites` WRITE;
/*!40000 ALTER TABLE `jevend_publicites` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_publicites` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_recherches`
--

DROP TABLE IF EXISTS `jevend_recherches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_recherches` (
  `id_recherche` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_categorie` int(11) NOT NULL,
  `id_ville` int(11) NOT NULL,
  `titre_recherche` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `budget_max` decimal(10,2) DEFAULT NULL,
  `image_reference` varchar(255) DEFAULT NULL,
  `statut` enum('actif','trouve','expire') DEFAULT 'actif',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_expiration` datetime NOT NULL,
  PRIMARY KEY (`id_recherche`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_ville` (`id_ville`),
  CONSTRAINT `jevend_recherches_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `jevend_utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `jevend_recherches_ibfk_2` FOREIGN KEY (`id_ville`) REFERENCES `jevend_villes` (`id_ville`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_recherches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_recherches` WRITE;
/*!40000 ALTER TABLE `jevend_recherches` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_recherches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_regions`
--

DROP TABLE IF EXISTS `jevend_regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_regions` (
  `id_region` int(11) NOT NULL AUTO_INCREMENT,
  `id_pays` int(11) NOT NULL,
  `nom_region_fr` varchar(100) NOT NULL,
  `nom_region_en` varchar(100) NOT NULL,
  PRIMARY KEY (`id_region`),
  KEY `fk_regions_pays` (`id_pays`),
  CONSTRAINT `fk_regions_pays` FOREIGN KEY (`id_pays`) REFERENCES `jevend_pays` (`id_pays`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_regions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_regions` WRITE;
/*!40000 ALTER TABLE `jevend_regions` DISABLE KEYS */;
INSERT INTO `jevend_regions` VALUES
(1,1,'Chaudière-Appalaches','Chaudiere-Appalaches'),
(2,1,'Bas-Saint-Laurent','Lower St. Lawrence'),
(3,1,'Matanie et de la Haute-Gaspésie','Matanie and Haute-Gaspesie'),
(4,1,'Côte-de-Gaspé','Gaspe Coast');
/*!40000 ALTER TABLE `jevend_regions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_reponses_recherche`
--

DROP TABLE IF EXISTS `jevend_reponses_recherche`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_reponses_recherche` (
  `id_reponse` int(11) NOT NULL AUTO_INCREMENT,
  `id_recherche` int(11) NOT NULL,
  `id_vendeur` int(11) NOT NULL,
  `id_annonce_associee` int(11) DEFAULT NULL,
  `message_vendeur` text DEFAULT NULL,
  `date_reponse` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reponse`),
  KEY `id_recherche` (`id_recherche`),
  KEY `id_vendeur` (`id_vendeur`),
  CONSTRAINT `jevend_reponses_recherche_ibfk_1` FOREIGN KEY (`id_recherche`) REFERENCES `jevend_recherches` (`id_recherche`) ON DELETE CASCADE,
  CONSTRAINT `jevend_reponses_recherche_ibfk_2` FOREIGN KEY (`id_vendeur`) REFERENCES `jevend_utilisateurs` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_reponses_recherche`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_reponses_recherche` WRITE;
/*!40000 ALTER TABLE `jevend_reponses_recherche` DISABLE KEYS */;
/*!40000 ALTER TABLE `jevend_reponses_recherche` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_stats_connect`
--

DROP TABLE IF EXISTS `jevend_stats_connect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_stats_connect` (
  `id_stat` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `type_appareil` varchar(20) NOT NULL,
  `date_connexion` datetime NOT NULL,
  PRIMARY KEY (`id_stat`),
  KEY `id_utilisateur` (`id_utilisateur`),
  CONSTRAINT `jevend_stats_connect_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `jevend_utilisateurs` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_stats_connect`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_stats_connect` WRITE;
/*!40000 ALTER TABLE `jevend_stats_connect` DISABLE KEYS */;
INSERT INTO `jevend_stats_connect` VALUES
(150,18,'ordinateur','2026-07-31 14:12:26'),
(151,19,'ordinateur','2026-07-31 14:21:46');
/*!40000 ALTER TABLE `jevend_stats_connect` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_tarifs_pro`
--

DROP TABLE IF EXISTS `jevend_tarifs_pro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_tarifs_pro` (
  `id_tarif_pro` int(11) NOT NULL AUTO_INCREMENT,
  `type_forfait` enum('supreme','premium') NOT NULL,
  `nom_forfait` varchar(100) NOT NULL,
  `prix_mensuel` decimal(10,2) NOT NULL,
  `duree_max_mois` int(11) NOT NULL DEFAULT 3,
  `description` text DEFAULT NULL,
  `date_mise_a_jour` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tarif_pro`),
  UNIQUE KEY `type_forfait` (`type_forfait`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_tarifs_pro`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_tarifs_pro` WRITE;
/*!40000 ALTER TABLE `jevend_tarifs_pro` DISABLE KEYS */;
INSERT INTO `jevend_tarifs_pro` VALUES
(1,'supreme','Forfait Suprême (En-tête Carrousel)',89.00,3,'3 slots maximum en circulation','2026-07-24 20:02:27'),
(2,'premium','Forfait Premium (Grille Pavés)',55.00,6,'20 slots maximum en circulation','2026-07-24 20:02:27');
/*!40000 ALTER TABLE `jevend_tarifs_pro` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_tarifs_publicites`
--

DROP TABLE IF EXISTS `jevend_tarifs_publicites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_tarifs_publicites` (
  `id_tarif` int(11) NOT NULL AUTO_INCREMENT,
  `type_produit` enum('reguliere','premium','supreme') NOT NULL,
  `prix_par_jour` decimal(10,2) NOT NULL DEFAULT 1.00,
  `duree_min_jours` int(11) NOT NULL DEFAULT 5,
  `date_mise_a_jour` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tarif`),
  UNIQUE KEY `type_produit` (`type_produit`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_tarifs_publicites`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_tarifs_publicites` WRITE;
/*!40000 ALTER TABLE `jevend_tarifs_publicites` DISABLE KEYS */;
INSERT INTO `jevend_tarifs_publicites` VALUES
(1,'reguliere',1.00,10,'2026-07-24 13:17:01'),
(2,'premium',5.00,5,'2026-07-24 12:53:10'),
(3,'supreme',10.00,5,'2026-07-24 12:53:10');
/*!40000 ALTER TABLE `jevend_tarifs_publicites` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_utilisateurs`
--

DROP TABLE IF EXISTS `jevend_utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_utilisateurs` (
  `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `courriel` varchar(150) NOT NULL,
  `cellulaire` varchar(20) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `id_ville` int(11) NOT NULL,
  `description_magasin` text DEFAULT NULL,
  `filtre_region_defaut` enum('SEULE','TOUTES') NOT NULL DEFAULT 'SEULE',
  `role` enum('membre','admin') NOT NULL DEFAULT 'membre',
  `statut` enum('actif','bloque') NOT NULL DEFAULT 'actif',
  `jeton_connexion` varchar(64) DEFAULT NULL,
  `jeton_expiration` datetime DEFAULT NULL,
  `date_inscription` timestamp NULL DEFAULT current_timestamp(),
  `type_compte` enum('particulier','pro') DEFAULT 'particulier',
  `nom_entreprise` varchar(150) DEFAULT NULL,
  `neq` varchar(50) DEFAULT NULL,
  `telephone_pro` varchar(30) DEFAULT NULL,
  `adresse_pro` varchar(255) DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `logo_pro` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `courriel` (`courriel`),
  KEY `fk_utilisateurs_ville` (`id_ville`),
  CONSTRAINT `fk_utilisateurs_ville` FOREIGN KEY (`id_ville`) REFERENCES `jevend_villes` (`id_ville`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_utilisateurs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_utilisateurs` WRITE;
/*!40000 ALTER TABLE `jevend_utilisateurs` DISABLE KEYS */;
INSERT INTO `jevend_utilisateurs` VALUES
(18,'Administrateur','douimet61@gmail.com','418-429-9029','$2y$12$Wg8b1jcF0upCCglWiRMK4u7YVadqJaTKvfyDjzjym0lHO/vfQzJW.',1,NULL,'TOUTES','admin','actif',NULL,NULL,'2026-07-31 18:11:54','pro','Jevend.com',NULL,NULL,NULL,NULL,NULL),
(19,'Jean Tremblay','jean@gmail.com','418-222-6666','$2y$12$t2.QgBH4/T2gWmXbpBxATOU4WvKWam/2.i7gF2tied/GENtvrO7Ee',8,NULL,'SEULE','membre','actif',NULL,NULL,'2026-07-31 18:21:32','particulier',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `jevend_utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jevend_villes`
--

DROP TABLE IF EXISTS `jevend_villes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jevend_villes` (
  `id_ville` int(11) NOT NULL AUTO_INCREMENT,
  `id_region` int(11) NOT NULL,
  `nom_ville` varchar(100) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id_ville`),
  KEY `fk_villes_region` (`id_region`),
  CONSTRAINT `fk_villes_region` FOREIGN KEY (`id_region`) REFERENCES `jevend_regions` (`id_region`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jevend_villes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jevend_villes` WRITE;
/*!40000 ALTER TABLE `jevend_villes` DISABLE KEYS */;
INSERT INTO `jevend_villes` VALUES
(1,2,'Matane',48.84900000,-67.52550000),
(2,2,'Amqui',48.47640000,-67.43000000),
(3,2,'Rimouski',48.44750000,-68.52380000),
(4,2,'Mont-Joli',48.58610000,-68.18830000),
(5,2,'Sayabec',48.56670000,-67.68330000),
(6,2,'Causapscal',48.35560000,-67.22750000),
(7,2,'Rivière-du-Loup',47.83580000,-69.53640000),
(8,4,'Gaspé',48.83110000,-64.48560000),
(9,3,'Sainte-Anne-des-Monts',49.12390000,-66.49220000),
(41,4,'Percé',48.52260000,-64.21450000),
(42,4,'Chandler',48.34820000,-64.67780000),
(43,4,'Bonaventure',48.04440000,-65.49500000),
(44,4,'New Richmond',48.16670000,-65.86670000),
(45,4,'Carleton-sur-Mer',48.10000000,-66.13330000),
(46,4,'Nouvelle',48.13330000,-66.48330000),
(47,4,'Paspébiac',48.03330000,-65.25000000),
(48,3,'Carleton',48.10000000,-66.13330000),
(49,3,'Cap-Chat',49.08330000,-66.68330000),
(50,3,'Grande-Vallée',49.22380000,-65.12280000),
(51,2,'Pointe-à-la-Croix',48.01670000,-66.70000000),
(52,2,'Matapédia',47.97330000,-66.93880000),
(53,2,'Val-Brillant',48.53330000,-67.55000000),
(54,2,'Lac-au-Saumon',48.42060000,-67.34640000),
(55,2,'Sainte-Luce',48.55000000,-68.38330000),
(56,2,'Bic',48.36670000,-68.70000000),
(57,2,'Trois-Pistoles',48.11670000,-69.18330000),
(58,2,'Saint-Fabien',48.30000000,-68.86670000),
(59,2,'Pohénégamook',47.46670000,-69.21670000),
(60,2,'La Pocatière',47.36670000,-70.03330000),
(61,2,'Saint-Pascal',47.53330000,-69.80000000),
(62,1,'Montmagny',46.98330000,-70.55000000),
(63,1,'Saint-Jean-Port-Joli',47.21670000,-70.26670000),
(64,1,'L\'Islet',47.13330000,-70.36670000),
(65,1,'Lévis',46.80330000,-71.17780000),
(66,1,'Saint-Romuald',46.75400000,-71.24200000),
(67,1,'Saint-Nicolas',46.70000000,-71.40000000),
(68,1,'Québec',46.81390000,-71.20820000),
(69,1,'Sainte-Foy',46.78700000,-71.29100000),
(70,1,'Beauport',46.85800000,-71.18900000),
(71,1,'Charlesbourg',46.86300000,-71.26700000);
/*!40000 ALTER TABLE `jevend_villes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-07-31 14:33:31
