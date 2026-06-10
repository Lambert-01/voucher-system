-- MySQL dump 10.13  Distrib 8.0.44, for macos11.7 (x86_64)
--
-- Host: localhost    Database: nhonest_voucher_system
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `nhonest_voucher_system`
--

/*!40000 DROP DATABASE IF EXISTS `nhonest_voucher_system`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `nhonest_voucher_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `nhonest_voucher_system`;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'login','User logged in','127.0.0.1','2026-06-09 06:48:20'),(2,2,'login','User logged in','127.0.0.1','2026-06-09 07:15:35'),(3,1,'login','User logged in','127.0.0.1','2026-06-09 08:00:10'),(4,1,'logout','User logged out','127.0.0.1','2026-06-09 08:00:25'),(5,3,'login','User logged in','127.0.0.1','2026-06-09 08:00:33'),(6,2,'login','User logged in','127.0.0.1','2026-06-09 18:52:40'),(7,1,'login','User logged in','127.0.0.1','2026-06-09 18:55:30'),(8,1,'logout','User logged out','127.0.0.1','2026-06-09 18:56:09'),(9,3,'login','User logged in','127.0.0.1','2026-06-09 18:56:22'),(13,3,'logout','User logged out','127.0.0.1','2026-06-09 19:42:46'),(14,2,'login','User logged in','127.0.0.1','2026-06-09 19:42:50'),(15,2,'login','User logged in','127.0.0.1','2026-06-09 19:43:34'),(17,2,'logout','User logged out','127.0.0.1','2026-06-09 19:47:20'),(18,2,'login','User logged in','127.0.0.1','2026-06-09 19:47:38'),(19,2,'login','User logged in','127.0.0.1','2026-06-09 19:55:19'),(21,2,'logout','User logged out','127.0.0.1','2026-06-09 19:57:14'),(22,2,'login','User logged in','127.0.0.1','2026-06-09 19:57:24'),(23,2,'batch_create','Created batch: IOM-2026-10','127.0.0.1','2026-06-09 19:57:57'),(24,2,'voucher_import','Imported 19 vouchers from xlsx file to batch ID 6','127.0.0.1','2026-06-09 19:58:52'),(25,2,'login','User logged in','127.0.0.1','2026-06-09 21:32:20'),(26,2,'login','User logged in','127.0.0.1','2026-06-09 21:43:14'),(27,2,'login','User logged in','127.0.0.1','2026-06-09 21:59:23'),(28,2,'login','User logged in','127.0.0.1','2026-06-09 22:10:35'),(29,2,'logout','User logged out','127.0.0.1','2026-06-09 22:47:35'),(30,1,'login','User logged in','127.0.0.1','2026-06-09 22:47:42'),(31,3,'login','User logged in','127.0.0.1','2026-06-10 09:38:16'),(32,2,'login','User logged in','127.0.0.1','2026-06-10 09:38:55'),(33,3,'voucher_redeem','Redeemed voucher ID 1 for 50,000 RWF','127.0.0.1','2026-06-10 09:39:56'),(34,3,'login','User logged in','127.0.0.1','2026-06-10 15:06:49'),(35,2,'login','User logged in','127.0.0.1','2026-06-10 15:09:07'),(36,2,'logout','User logged out','127.0.0.1','2026-06-10 16:26:35'),(37,1,'login','User logged in','127.0.0.1','2026-06-10 16:26:39'),(38,2,'login','User logged in','127.0.0.1','2026-06-10 16:42:36'),(40,3,'login','User logged in','127.0.0.1','2026-06-10 16:55:58'),(41,3,'login','User logged in','127.0.0.1','2026-06-10 17:16:18'),(42,3,'logout','User logged out','127.0.0.1','2026-06-10 17:16:33'),(43,1,'login','User logged in','127.0.0.1','2026-06-10 17:16:37'),(44,1,'logout','User logged out','127.0.0.1','2026-06-10 17:16:50'),(45,2,'login','User logged in','127.0.0.1','2026-06-10 17:16:56'),(46,2,'user_create','Created user: Lambert','127.0.0.1','2026-06-10 17:17:58');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'International Organization for Migration (IOM)','John Doe','0788111222','iom@contact.rw','Kigali, Rwanda','active','2026-06-08 23:20:46');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('boss','admin','cashier') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Boss Account','boss','boss@honestsupermarket.com','0788633739','$2y$10$xKLMnA703p0KvoJLOxv0SepbKHxX32wCDPOYB63hbSpeRgs22lV4i','boss','active','2026-06-10 19:16:37','2026-06-08 23:20:46'),(2,'Admin Account','admin','admin@honestsupermarket.com','0788633740','$2y$10$xKLMnA703p0KvoJLOxv0SepbKHxX32wCDPOYB63hbSpeRgs22lV4i','admin','active','2026-06-10 19:16:56','2026-06-08 23:20:46'),(3,'Cashier Account','cashier','cashier@honestsupermarket.com','0788633741','$2y$10$xKLMnA703p0KvoJLOxv0SepbKHxX32wCDPOYB63hbSpeRgs22lV4i','cashier','active','2026-06-10 19:16:18','2026-06-08 23:20:46'),(5,'Lambert NDACYAYISABA','Lambert','nlambert833@gmail.com','0790311401','$2y$10$wjR/oP4Iwv1DbED22leb4.okRiZP4banKAc/3CHiut.O2QWTb7jFS','admin','active',NULL,'2026-06-10 17:17:58');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_batches`
--

DROP TABLE IF EXISTS `voucher_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_batches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `batch_code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_month` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_vouchers` int DEFAULT '0',
  `total_amount` decimal(14,2) DEFAULT '0.00',
  `payment_status` enum('pending','paid','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_reference` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_code` (`batch_code`),
  KEY `company_id` (`company_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `voucher_batches_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `voucher_batches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_batches`
--

LOCK TABLES `voucher_batches` WRITE;
/*!40000 ALTER TABLE `voucher_batches` DISABLE KEYS */;
INSERT INTO `voucher_batches` VALUES (6,1,'IOM-2026-10','IOM October 2026 Vouchers','2026-10',19,8299200.00,'paid','122','',2,'2026-06-09 19:57:57');
/*!40000 ALTER TABLE `voucher_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_transactions`
--

DROP TABLE IF EXISTS `voucher_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voucher_id` int NOT NULL,
  `receipt_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_balance` decimal(14,2) NOT NULL,
  `amount_used` decimal(14,2) NOT NULL,
  `new_balance` decimal(14,2) NOT NULL,
  `cashier_id` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `voucher_id` (`voucher_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_cashier_id` (`cashier_id`),
  CONSTRAINT `voucher_transactions_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `voucher_transactions_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_transactions`
--

LOCK TABLES `voucher_transactions` WRITE;
/*!40000 ALTER TABLE `voucher_transactions` DISABLE KEYS */;
INSERT INTO `voucher_transactions` VALUES (1,1,'238899773',436800.00,50000.00,386800.00,3,'2','2026-06-10 09:39:56');
/*!40000 ALTER TABLE `voucher_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vouchers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `batch_id` int NOT NULL,
  `voucher_no` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `eva_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_amount` decimal(14,2) NOT NULL,
  `balance` decimal(14,2) NOT NULL,
  `status` enum('active','partially_used','used','blocked','expired','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `qr_code_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_token` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_no` (`voucher_no`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `batch_id` (`batch_id`),
  KEY `idx_voucher_no` (`voucher_no`),
  KEY `idx_status` (`status`),
  CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `voucher_batches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vouchers`
--

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` VALUES (1,6,'HSV-2026-0001','ALLELUIA ALAIN','PX-Q3-2026-CB80',436800.00,386800.00,'partially_used','/assets/qrcodes/HSV-2026-0001.png',NULL,NULL,'2026-06-09 19:58:34'),(2,6,'HSV-2026-0002','BAHIGIRORA JEAN DE LA CROIX','PX-Q3-2026-CB70',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0002.png',NULL,NULL,'2026-06-09 19:58:35'),(3,6,'HSV-2026-0003','BRENDA MUTONI','PX-Q3-2026-CB85',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0003.png',NULL,NULL,'2026-06-09 19:58:36'),(4,6,'HSV-2026-0004','DERRICK KWIZERA','PX-Q3-2026-CB82',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0004.png',NULL,NULL,'2026-06-09 19:58:37'),(5,6,'HSV-2026-0005','DIDIER NSENGIMANA','PX-Q3-2026-CB84',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0005.png',NULL,NULL,'2026-06-09 19:58:38'),(6,6,'HSV-2026-0006','GASANA BRUCE','PX-Q3-2026-CB73',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0006.png',NULL,NULL,'2026-06-09 19:58:39'),(7,6,'HSV-2026-0007','HERVE KAREKEZI','PX-Q3-2026-CB81',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0007.png',NULL,NULL,'2026-06-09 19:58:40'),(8,6,'HSV-2026-0008','ISINGIZWE ARISTIDE','PX-Q3-2026-CB71',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0008.png',NULL,NULL,'2026-06-09 19:58:41'),(9,6,'HSV-2026-0009','KAGAME ERIC','PX-Q3-2026-CB74',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0009.png',NULL,NULL,'2026-06-09 19:58:42'),(10,6,'HSV-2026-0010','MIZERO JEAN FRANCIS','PX-Q3-2026-CB76',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0010.png',NULL,NULL,'2026-06-09 19:58:43'),(11,6,'HSV-2026-0011','MOISE NTAWIHA','PX-Q3-2026-CB86',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0011.png',NULL,NULL,'2026-06-09 19:58:44'),(12,6,'HSV-2026-0012','MUGISHA SABIN','PX-Q3-2026-CB75',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0012.png',NULL,NULL,'2026-06-09 19:58:44'),(13,6,'HSV-2026-0013','PAMELA UHOZABE','7',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0013.png',NULL,NULL,'2026-06-09 19:58:45'),(14,6,'HSV-2026-0014','PRINCE MUGISHA','PX-Q3-2026-CB83',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0014.png',NULL,NULL,'2026-06-09 19:58:46'),(15,6,'HSV-2026-0015','RWAKAZAYIRE SWAIB','7',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0015.png',NULL,NULL,'2026-06-09 19:58:47'),(16,6,'HSV-2026-0016','TUYISABE AIME JACKSON','PX-Q3-2026-CB72',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0016.png',NULL,NULL,'2026-06-09 19:58:48'),(17,6,'HSV-2026-0017','UWASE ROSE','PX-Q3-2026-CB79',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0017.png',NULL,NULL,'2026-06-09 19:58:49'),(18,6,'HSV-2026-0018','UWIZEYE LAMBERT','PX-Q3-2026-CB77',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0018.png',NULL,NULL,'2026-06-09 19:58:50'),(19,6,'HSV-2026-0019','UWIZEYIMANA CYNTHIA','PX-Q3-2026-CB78',436800.00,436800.00,'active','/assets/qrcodes/HSV-2026-0019.png',NULL,NULL,'2026-06-09 19:58:51');
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'nhonest_voucher_system'
--

--
-- Dumping routines for database 'nhonest_voucher_system'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-10 19:19:32
