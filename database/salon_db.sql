-- MySQL dump 10.13  Distrib 8.3.0, for Win64 (x86_64)
--
-- Host: localhost    Database: unisex_salon_db
-- ------------------------------------------------------
-- Server version	8.3.0

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
-- Current Database: `unisex_salon_db`
--

/*!40000 DROP DATABASE IF EXISTS `unisex_salon_db`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `unisex_salon_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `unisex_salon_db`;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `service_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','approved','rejected','confirmed','in-progress','completed','cancelled') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_staff` (`staff_id`),
  KEY `idx_date` (`appointment_date`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (6,1,1,1,'2026-05-02','10:00:00','completed','Hair cut completed','2026-05-02 14:50:32','2026-05-02 14:50:32'),(7,2,3,3,'2026-05-02','11:00:00','completed','Beard shave completed','2026-05-02 14:50:32','2026-05-02 14:50:32'),(8,3,2,4,'2026-05-03','14:00:00','completed','','2026-05-02 14:50:32','2026-05-02 15:59:08'),(9,4,1,2,'2026-05-04','15:00:00','in-progress','','2026-05-02 14:50:32','2026-05-02 15:30:25'),(10,1,2,5,'2026-05-05','16:00:00','cancelled','','2026-05-02 14:50:32','2026-05-02 15:25:10'),(11,1,NULL,6,'2026-05-08','13:30:00','completed',NULL,'2026-05-02 15:06:32','2026-05-02 15:24:29'),(12,6,NULL,3,'2026-05-03','11:00:00','cancelled',NULL,'2026-05-02 15:20:15','2026-05-02 15:22:08'),(13,6,NULL,4,'2026-05-02','18:00:00','pending',NULL,'2026-05-02 15:22:26','2026-05-02 15:22:26');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bill_items`
--

DROP TABLE IF EXISTS `bill_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_id` int NOT NULL,
  `service_id` int NOT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bill_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bill_items`
--

LOCK TABLES `bill_items` WRITE;
/*!40000 ALTER TABLE `bill_items` DISABLE KEYS */;
INSERT INTO `bill_items` VALUES (1,1,1,'Hair Cut',1,450.00,450.00,'2026-05-02 14:50:55'),(2,2,3,'Beard Shave',1,150.00,150.00,'2026-05-02 14:50:55'),(3,3,6,'Hair Color',1,1200.00,1200.00,'2026-05-02 15:26:28'),(4,4,4,'Facial',1,1200.00,1200.00,'2026-05-02 15:59:08');
/*!40000 ALTER TABLE `bill_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int DEFAULT NULL,
  `customer_id` int NOT NULL,
  `bill_number` varchar(20) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `final_amount` decimal(10,2) NOT NULL,
  `status` enum('draft','final','pending','paid','cancelled') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`),
  KEY `appointment_id` (`appointment_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`bill_date`),
  CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bills`
--

LOCK TABLES `bills` WRITE;
/*!40000 ALTER TABLE `bills` DISABLE KEYS */;
INSERT INTO `bills` VALUES (1,6,1,'BILL001','2026-05-02',450.00,0.00,450.00,'final','Hair cut service','2026-05-02 14:50:55','2026-05-02 14:50:55'),(2,7,2,'BILL002','2026-05-02',150.00,0.00,150.00,'final','Beard shave service','2026-05-02 14:50:55','2026-05-02 14:50:55'),(3,11,1,'BILL20260502152628','2026-05-02',1200.00,0.00,1200.00,'final',NULL,'2026-05-02 15:26:28','2026-05-02 15:26:28'),(4,8,3,'BILL-2026-0001','2026-05-02',1200.00,0.00,1200.00,'paid','Auto-generated after completed appointment','2026-05-02 15:59:08','2026-05-02 16:37:50');
/*!40000 ALTER TABLE `bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_groups`
--

DROP TABLE IF EXISTS `booking_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(30) NOT NULL,
  `customer_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `total_duration` int NOT NULL DEFAULT '0',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `promo_code` varchar(50) DEFAULT NULL,
  `payment_method` enum('pay_at_salon','online_simulated','razorpay') DEFAULT 'pay_at_salon',
  `payment_status` enum('paid','unpaid') DEFAULT 'unpaid',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_code` (`booking_code`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date_time` (`appointment_date`,`appointment_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_groups`
--

LOCK TABLES `booking_groups` WRITE;
/*!40000 ALTER TABLE `booking_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text,
  `last_login` timestamp NULL DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Alice Brown','customer@salon.com','$2y$10$ljNFTtg1bX/cc/2FK9CJV.WexGJ5oB8AdFgqI2TJ1nrguj06wKFyq','9123456789','Female','1995-05-15','14 Rose Avenue, New York','2026-05-02 15:58:51','New York',NULL,'active','2026-05-02 14:50:05','2026-05-02 15:58:51'),(2,'Bob Wilson','bob@salon.com','$2y$10$ljNFTtg1bX/cc/2FK9CJV.WexGJ5oB8AdFgqI2TJ1nrguj06wKFyq','9234567890','Male','1990-03-20','22 Oak Market Road, Boston',NULL,'Boston',NULL,'active','2026-05-02 14:50:05','2026-05-02 14:55:15'),(3,'Carol Davis','carol@salon.com','$2y$10$ljNFTtg1bX/cc/2FK9CJV.WexGJ5oB8AdFgqI2TJ1nrguj06wKFyq','9345678901','Female','1992-07-10','8 Pine Residency, Chicago',NULL,'Chicago',NULL,'active','2026-05-02 14:50:05','2026-05-02 14:55:15'),(4,'David Martinez','david@salon.com','$2y$10$ljNFTtg1bX/cc/2FK9CJV.WexGJ5oB8AdFgqI2TJ1nrguj06wKFyq','9456789012','Male','1988-11-25','31 Elm Street, Houston',NULL,'Houston',NULL,'active','2026-05-02 14:50:05','2026-05-02 14:55:15'),(5,'Priya Sharma','priya@salon.com','$2y$10$ljNFTtg1bX/cc/2FK9CJV.WexGJ5oB8AdFgqI2TJ1nrguj06wKFyq','9567890123','Female','1998-01-14','55 Lake View Road, Bengaluru',NULL,'Bengaluru',NULL,'active','2026-05-02 14:58:51','2026-05-02 14:58:51'),(6,'Vageesh H','vageesh02h@gmail.com','$2y$10$GQheKog7HqhjZGxLlrqYeOnxIyXydGg/545Eg1XcEwvpBpnKCLDX.','9071599725','Male','2004-10-02','12 MG Road, Mysuru',NULL,'Mysuru',NULL,'active','2026-05-02 15:18:21','2026-05-02 15:18:21');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int DEFAULT NULL,
  `customer_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `comment` text,
  `feedback_date` date NOT NULL,
  `status` enum('new','reviewed') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `staff_id` (`staff_id`),
  KEY `service_id` (`service_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_date` (`feedback_date`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedback_ibfk_4` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedback_chk_1` CHECK (((`rating` >= 1) and (`rating` <= 5)))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,6,1,1,1,5,'Excellent service! Very satisfied with the haircut.','2026-05-02','reviewed','2026-05-02 14:50:55'),(2,7,2,3,3,4,'Good service. Professional staff.','2026-05-02','reviewed','2026-05-02 14:50:55');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) DEFAULT '0',
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_time` (`email`,`attempted_at`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','check','online_transfer','razorpay','manual','pending') DEFAULT 'pending',
  `payment_date` date NOT NULL,
  `status` enum('pending','completed','failed','paid') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,1,450.00,'cash','2026-05-02','completed',NULL,'Payment received','2026-05-02 14:50:55'),(2,2,2,150.00,'card','2026-05-02','completed',NULL,'Payment received','2026-05-02 14:50:55'),(3,4,3,1200.00,'manual','2026-05-02','paid',NULL,'Manual payment pending','2026-05-02 15:59:08');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `duration` int NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `gender_category` enum('Male','Female','Kids','Unisex') NOT NULL DEFAULT 'Unisex',
  `status` enum('active','inactive') DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Hair Cut','Professional hair cutting service',450.00,30,'Hair','Unisex','active',NULL,'2026-05-02 14:50:05','2026-05-02 14:50:05'),(2,'Hair Styling','Professional hair styling and treatment',700.00,45,'Hair','Unisex','active',NULL,'2026-05-02 14:50:05','2026-05-02 14:50:05'),(3,'Beard Shave','Professional beard shaving and grooming',150.00,20,'Shave','Male','active',NULL,'2026-05-02 14:50:05','2026-05-02 15:47:21'),(4,'Facial','Deep cleansing and rejuvenating facial',1200.00,50,'Skincare','Female','active',NULL,'2026-05-02 14:50:05','2026-05-02 15:47:21'),(5,'Makeup','Professional makeup application',2500.00,40,'Makeup','Female','active',NULL,'2026-05-02 14:50:05','2026-05-02 15:47:21'),(6,'Hair Color','Hair coloring and treatment',1200.00,60,'Hair','Unisex','active',NULL,'2026-05-02 14:50:05','2026-05-02 14:50:05'),(7,'Waxing','Full body waxing service',2200.00,45,'Waxing','Female','active',NULL,'2026-05-02 14:50:05','2026-05-02 15:47:21'),(8,'Massage','Relaxing body massage',1500.00,50,'Massage','Unisex','active',NULL,'2026-05-02 14:50:05','2026-05-02 14:50:05'),(9,'Men Hair Cut','Modern haircut and finishing for men',350.00,30,'Hair','Male','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(10,'Beard Trim','Sharp beard shaping, trimming, and line-up',180.00,20,'Shave','Male','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(11,'Men Facial','Deep cleansing facial tailored for men',1000.00,45,'Skincare','Male','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(12,'Head Massage','Relaxing oil head massage for stress relief',300.00,25,'Massage','Male','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(13,'Women Hair Cut','Layered haircut, trimming, and blow dry finish',700.00,40,'Hair','Female','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(14,'Hair Spa','Nourishing hair spa for smooth and healthy hair',1200.00,60,'Hair','Female','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(15,'Manicure','Nail shaping, cuticle care, and polish application',800.00,35,'Nails','Female','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(16,'Threading','Eyebrow and upper lip threading service',250.00,15,'Threading','Female','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(17,'Kids Hair Cut','Comfortable haircut service for kids',350.00,25,'Hair','Kids','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(18,'Kids Hair Styling','Fun styling for school events and celebrations',500.00,30,'Hair','Kids','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(19,'Kids Mini Facial','Gentle cleansing service for young skin',700.00,25,'Skincare','Kids','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(20,'Kids Nail Trim','Quick nail trimming and basic hand care',250.00,15,'Nails','Kids','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(21,'Hair Wash','Refreshing shampoo and conditioning service',300.00,20,'Hair','Unisex','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(22,'Pedicure','Foot soak, nail care, and relaxing scrub',900.00,40,'Nails','Unisex','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(23,'Detan Treatment','Brightening detan care for face, neck, and hands',1000.00,40,'Skincare','Unisex','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00'),(24,'Aromatherapy Massage','Calming aromatherapy massage with essential oils',1800.00,60,'Massage','Unisex','active',NULL,'2026-05-07 10:00:00','2026-05-07 10:00:00');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'salon_name','Fabulous Unisex Salon','2026-05-02 14:50:55','2026-05-02 14:50:55'),(2,'salon_address','Sno:12 Mahaveer Plaza, Tikare Road, Dharwad Karnataka 580001','2026-05-02 14:50:55','2026-05-02 14:50:55'),(3,'salon_phone','','2026-05-02 14:50:55','2026-05-02 14:50:55'),(4,'salon_email','','2026-05-02 14:50:55','2026-05-02 14:50:55'),(5,'business_hours','9:00 AM - 8:00 PM','2026-05-02 14:50:55','2026-05-02 14:50:55'),(6,'currency','INR','2026-05-02 14:50:55','2026-05-02 14:50:55'),(7,'time_slot_interval','30','2026-05-02 14:50:55','2026-05-02 14:50:55');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `availability_start` time DEFAULT NULL,
  `availability_end` time DEFAULT NULL,
  `days_working` varchar(50) DEFAULT NULL,
  `commission_percentage` decimal(5,2) DEFAULT '10.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,2,'Hair Styling','09:00:00','18:00:00','Mon,Tue,Wed,Thu,Fri,Sat',15.00,'2026-05-02 14:50:05'),(2,3,'Makeup & Skincare','10:00:00','19:00:00','Tue,Wed,Thu,Fri,Sat,Sun',12.00,'2026-05-02 14:50:05'),(3,4,'Hair Cut & Shave','09:00:00','17:00:00','Mon,Wed,Thu,Fri,Sat,Sun',10.00,'2026-05-02 14:50:05');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@salon.com','$2y$10$Blj4UvUo8W2mbayK74rLjOVMmkraSA3/V/ojFviHPpY7XEP9qEkM6','admin','active',NULL,'1234567890',NULL,'2026-05-02 16:37:50','2026-05-02 14:49:41','2026-05-02 16:37:50'),(2,'John Doe','staff@salon.com','$2y$10$0U9TKLPzqd5LhxFCSe2hGODXox2kmYevRwzo3bfWZXhs3xQg/Z1wK','staff','active',NULL,'9876543210',NULL,'2026-05-02 15:58:51','2026-05-02 14:50:05','2026-05-02 15:58:51'),(3,'Sarah Smith','sarah@salon.com','$2y$10$0U9TKLPzqd5LhxFCSe2hGODXox2kmYevRwzo3bfWZXhs3xQg/Z1wK','staff','active',NULL,'8765432109',NULL,NULL,'2026-05-02 14:50:05','2026-05-02 14:55:15'),(4,'Mike Johnson','mike@salon.com','$2y$10$0U9TKLPzqd5LhxFCSe2hGODXox2kmYevRwzo3bfWZXhs3xQg/Z1wK','staff','active',NULL,'7654321098',NULL,NULL,'2026-05-02 14:50:05','2026-05-02 14:55:15');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-02 22:08:54
