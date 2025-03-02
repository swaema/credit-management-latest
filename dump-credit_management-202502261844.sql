-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: credit_management
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

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
-- Table structure for table `admin_fees`
--

DROP TABLE IF EXISTS `admin_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `collection_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','collected') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `admin_fees_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_fees`
--

LOCK TABLES `admin_fees` WRITE;
/*!40000 ALTER TABLE `admin_fees` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consoledatedfund`
--

DROP TABLE IF EXISTS `consoledatedfund`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consoledatedfund` (
  `consoledatedFundId` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `Amount` decimal(15,8) NOT NULL DEFAULT 0.00000000,
  `Earning` decimal(15,8) NOT NULL DEFAULT 0.00000000,
  PRIMARY KEY (`consoledatedFundId`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `consoledatedfund_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consoledatedfund`
--

LOCK TABLES `consoledatedfund` WRITE;
/*!40000 ALTER TABLE `consoledatedfund` DISABLE KEYS */;
/*!40000 ALTER TABLE `consoledatedfund` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('nic','utility_bills','salary_statements') NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lendercontribution`
--

DROP TABLE IF EXISTS `lendercontribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lendercontribution` (
  `lenderContributionId` int(11) NOT NULL AUTO_INCREMENT,
  `lenderId` int(11) NOT NULL,
  `loanId` int(11) NOT NULL,
  `LoanPercent` decimal(11,8) NOT NULL,
  `LoanAmount` decimal(15,2) NOT NULL,
  `RecoveredPrincipal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ReturnedInterest` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ExtraAmount` decimal(11,2) DEFAULT 0.00,
  `ExtraEarning` decimal(11,2) DEFAULT 0.00,
  PRIMARY KEY (`lenderContributionId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lendercontribution`
--

LOCK TABLES `lendercontribution` WRITE;
/*!40000 ALTER TABLE `lendercontribution` DISABLE KEYS */;
INSERT INTO `lendercontribution` VALUES (1,0,1,0.00000000,0.00,0.00,0.00,0.00,0.00),(2,114,1,60.00000000,30000.00,0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `lendercontribution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_processing_locks`
--

DROP TABLE IF EXISTS `loan_processing_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_processing_locks` (
  `loan_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`loan_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_processing_locks`
--

LOCK TABLES `loan_processing_locks` WRITE;
/*!40000 ALTER TABLE `loan_processing_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_processing_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_transfers`
--

DROP TABLE IF EXISTS `loan_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `stripe_transfer_id` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `loan_transfers_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_transfers`
--

LOCK TABLES `loan_transfers` WRITE;
/*!40000 ALTER TABLE `loan_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loaninstallments`
--

DROP TABLE IF EXISTS `loaninstallments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loaninstallments` (
  `loanInstallmentsId` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` text NOT NULL,
  `loan_id` text NOT NULL,
  `payable_amount` float NOT NULL,
  `pay_date` date NOT NULL,
  `principal` decimal(15,2) NOT NULL,
  `interest` decimal(15,2) NOT NULL,
  `admin_fee` decimal(15,2) NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`loanInstallmentsId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loaninstallments`
--

LOCK TABLES `loaninstallments` WRITE;
/*!40000 ALTER TABLE `loaninstallments` DISABLE KEYS */;
/*!40000 ALTER TABLE `loaninstallments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `noOfInstallments` int(11) DEFAULT NULL,
  `interstRate` int(11) DEFAULT NULL,
  `grade` text DEFAULT NULL,
  `AnnualIncome` int(11) NOT NULL,
  `loanAmount` int(11) NOT NULL,
  `loanPurpose` text NOT NULL,
  `employeementTenure` int(11) NOT NULL,
  `Accepted_Date` date DEFAULT NULL,
  `status` text NOT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `InstallmentAmount` decimal(11,2) DEFAULT NULL,
  `TotalLoan` int(11) NOT NULL,
  `stripe_account_id` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (1,112,2,10,'B',100000,50000,'debt_consolidation',2,NULL,'Accepted','2025-02-26 18:10:01',25916.50,51833,NULL,'2025-02-26 18:19:54');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,112,'Your loan application for $50,000.00 has been accepted. Risk Grade: B. Your loan is now visible to potential lenders and available for funding.','2025-02-26 18:19:54'),(2,112,'Dear Itachi Uchiha,\n\nYour loan application has been accepted!\n\nLoan Details:\n- Loan ID: 1\n- Amount: Rs 50,000.00\n- Interest Rate: 10%\n- Term: 2 months\n- Monthly Payment: Rs 25,916.50\n- Risk Grade: B\n\nNext Steps:\n1. Your loan is now visible to potential lenders\n2. Lenders can start contributing to your loan\n3. You\'ll be notified once your loan is fully funded\n4. Funds will be disbursed after full funding is achieved\n\nThank you for choosing our services.\n\nBest regards,\nYour Lending Team','2025-02-26 15:19:58'),(3,112,'New contribution received: $30,000.00 (60%)','2025-02-26 18:39:03');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_errors`
--

DROP TABLE IF EXISTS `payment_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_errors`
--

LOCK TABLES `payment_errors` WRITE;
/*!40000 ALTER TABLE `payment_errors` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_errors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_transfers`
--

DROP TABLE IF EXISTS `payment_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stripe_transfer_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_transfers`
--

LOCK TABLES `payment_transfers` WRITE;
/*!40000 ALTER TABLE `payment_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('repayment','investment','loan_fee','admin_fee') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('completed','pending','failed') DEFAULT 'pending',
  `reference_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,112,'loan_fee',50000.00,'2025-02-26 14:19:54','pending',1),(2,114,'investment',30000.00,'2025-02-26 14:39:03','completed',1);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('borrower','lender','admin') DEFAULT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','blocked','suspend') DEFAULT NULL,
  `user_verfied` text DEFAULT NULL,
  `reset_token` text DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stripe_account_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (51,'admin admin','admin@gmail.com','$2y$10$3Y6EpC5qGMrsNIAnxpJinufgRa9P2i1ojDfJarYKQ6RToQ1gOgVv2','admin','57908786','mru','','active','verified','0f248e77330ddb567b5184efbf57b8360a0cddabdd561452f5bc0c9a7c23aaac11f4d1bf0c990056a89579e815669a0b268a','2025-02-19 19:31:17','2024-12-07 16:48:57','2025-02-23 15:28:11',NULL),(112,'Itachi Uchiha','swaema04@gmail.com','$2y$10$h/zKWCBqmXVj5Myumw96S.PpTuKaFtri3i48ZgUitwXBag96fvboy','borrower','57908786','Cpe','../uploads/users/112/profile_image/1740577191_itachi uchiha.png','active','verified',NULL,NULL,'2025-02-24 11:16:57','2025-02-26 13:44:05',NULL),(113,'hinata hyuga','ema04hh@gmail.com','$2y$10$plENZddevQKIB7e8kvh1gOOJy.1Jb5dP/kjrMwDL8zfc2JfJIGnD6','lender','57862402','pm','uploads/users/113/profile_image/1740385958_hinataProfPic.png','active','verified',NULL,NULL,'2025-02-24 12:32:38','2025-02-24 08:33:18',NULL),(114,'rock lee','sbw.hosenbocus@gmail.com','$2y$10$hBRq4ZOafN4Eosp4l3nByu8R.CqUO/0h72tYjwO76HeEgmV6B.fTy','lender','58022375','port louis','uploads/users/114/profile_image/1740389760_rocklee.png','active','verified',NULL,NULL,'2025-02-24 13:36:00','2025-02-26 13:43:53',NULL),(115,'jhaanvi gokool','jhaanvigokool26@gmail.com','$2y$10$21Ib17du.uheHMK5hSJBXefJMsUB68Mt3.x8I831sKjKXvkkn4oVO','borrower','57726668','Pave road, Petit Raffray','','active','verified',NULL,NULL,'2025-02-24 13:50:02','2025-02-26 13:44:27',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'credit_management'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-02-26 18:44:09
