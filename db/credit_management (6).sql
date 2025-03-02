-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2025 at 12:31 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `credit_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `consoledatedfund`
--

CREATE TABLE `consoledatedfund` (
  `consoledatedFundId` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `Amount` int(11) NOT NULL,
  `Earning` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consoledatedfund`
--

INSERT INTO `consoledatedfund` (`consoledatedFundId`, `user_id`, `Amount`, `Earning`) VALUES
(4, 103, 104515, 3909),
(5, 104, 105118, 4377),
(6, 106, 1268917, -58879325);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('nic','utility_bills','salary_statements') NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `type`, `path`, `uploaded_at`) VALUES
(16, 102, 'nic', 'uploads/users/102/nic/NIC2.png', '2025-01-17 09:43:23'),
(17, 102, 'utility_bills', 'uploads/users/102/utility_bills/Utility2.png', '2025-01-17 09:43:23'),
(18, 102, 'salary_statements', 'uploads/users/102/salary_statements/salary2.png', '2025-01-17 09:43:23'),
(19, 103, 'nic', 'uploads/users/103/nic/NIC2.png', '2025-01-17 09:45:47'),
(20, 103, 'utility_bills', 'uploads/users/103/utility_bills/utility bill.jpg', '2025-01-17 09:45:47'),
(22, 104, 'nic', 'uploads/users/104/nic/NIC2.png', '2025-01-17 10:39:35'),
(23, 104, 'utility_bills', 'uploads/users/104/utility_bills/NIC.png', '2025-01-17 10:39:35'),
(24, 104, 'salary_statements', 'uploads/users/104/salary_statements/salary2.png', '2025-01-17 10:39:35'),
(25, 103, 'salary_statements', 'uploads/users/103/salary_statements/1737112517_salary (1).pdf', '2025-01-17 11:15:17'),
(26, 105, 'nic', 'uploads/users/105/nic/NIC2 (1).png', '2025-01-20 07:17:14'),
(27, 105, 'utility_bills', 'uploads/users/105/utility_bills/girlIcon.png', '2025-01-20 07:17:14'),
(28, 105, 'salary_statements', 'uploads/users/105/salary_statements/NIC2 (1).png', '2025-01-20 07:17:14'),
(29, 106, 'nic', 'uploads/users/106/nic/NIC2 (1).png', '2025-01-20 07:21:12'),
(30, 106, 'utility_bills', 'uploads/users/106/utility_bills/NIC2 (1).png', '2025-01-20 07:21:12'),
(31, 106, 'salary_statements', 'uploads/users/106/salary_statements/NIC2 (1).png', '2025-01-20 07:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `lendercontribution`
--

CREATE TABLE `lendercontribution` (
  `lenderContributionId` int(11) NOT NULL,
  `lenderId` int(11) NOT NULL,
  `loanId` int(11) NOT NULL,
  `LoanPercent` decimal(11,2) NOT NULL,
  `LoanAmount` int(11) NOT NULL,
  `RecoveredPrincipal` int(11) NOT NULL DEFAULT 0,
  `ReturnedInterest` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lendercontribution`
--

INSERT INTO `lendercontribution` (`lenderContributionId`, `lenderId`, `loanId`, `LoanPercent`, `LoanAmount`, `RecoveredPrincipal`, `ReturnedInterest`) VALUES
(30, 103, 39, 50.00, 25000, 408, 203),
(31, 104, 39, 50.00, 25000, 175, 87),
(32, 103, 40, 70.00, 24500, 408, 203),
(33, 104, 40, 30.00, 10500, 175, 87),
(34, 106, 41, 20.00, 2000, 8328, 248259),
(35, 104, 41, 80.00, 8000, 0, 0),
(36, 106, 45, 100.00, 50000, 8328, 248259);

-- --------------------------------------------------------

--
-- Table structure for table `loaninstallments`
--

CREATE TABLE `loaninstallments` (
  `loanInstallmentsId` int(11) NOT NULL,
  `user_id` text NOT NULL,
  `loan_id` text NOT NULL,
  `payable_amount` float NOT NULL,
  `pay_date` date NOT NULL,
  `principal` int(11) NOT NULL,
  `interest` int(11) NOT NULL,
  `admin_fee` int(11) NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loaninstallments`
--

INSERT INTO `loaninstallments` (`loanInstallmentsId`, `user_id`, `loan_id`, `payable_amount`, `pay_date`, `principal`, `interest`, `admin_fee`, `status`) VALUES
(110, '102', '45', 1875, '2025-01-20', 1388, 458, 27, 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
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
  `TotalLoan` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `noOfInstallments`, `interstRate`, `grade`, `AnnualIncome`, `loanAmount`, `loanPurpose`, `employeementTenure`, `Accepted_Date`, `status`, `requested_at`, `InstallmentAmount`, `TotalLoan`) VALUES
(39, 102, 36, 11, 'B', 35000, 50000, 'home_improvement', 3, '2025-01-18', 'approved', '2025-01-18 22:52:54', 1875.00, 67500),
(40, 102, 60, 10, 'B', 35000, 35000, 'medical', 1, '0000-00-00', 'approved', '0000-00-00 00:00:00', 886.67, 53200),
(41, 105, 4, 5, 'A', 10000, 10000, 'other', 1, NULL, 'approved', '2025-01-20 11:27:08', NULL, 10366),
(42, 102, 5, 5, 'A', 5000, 5000, 'credit_card', 1, NULL, 'Accepted', '2025-01-20 12:36:49', NULL, 5204),
(43, 102, 36, 6, 'A', 50000, 50000, 'other', 1, '2025-01-20', 'Accepted', '2025-01-20 13:00:45', 5388.89, 60000),
(44, 102, 36, 11, 'B', 35000, 50000, 'home_improvement', 3, '2025-01-20', 'Accepted', '2025-01-20 13:04:34', 7888.89, 67500),
(45, 102, 36, 11, 'B', 35000, 50000, 'home_improvement', 3, '2025-01-20', 'approved', '2025-01-20 13:09:07', 1875.00, 67500),
(46, 102, 36, 11, 'B', 35000, 50000, 'other', 3, NULL, 'Pending', '2025-01-20 14:57:42', NULL, 67500);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `created_at`) VALUES
(28, 102, 'Dear swaema hosenbocus, your loan request for amount 35000 for the purpose of credit_card has been accepted. The number of installments is 60.', '2025-01-17 11:25:11'),
(29, 102, 'Dear swaema hosenbocus, your loan request for amount 50000 for the purpose of debt_consolidation has been accepted. The number of installments is 36.', '2025-01-18 14:27:20'),
(30, 102, 'Dear swaema hosenbocus, your loan request for amount 50000 for the purpose of debt_consolidation has been accepted. The number of installments is 36.', '2025-01-18 15:12:11'),
(31, 102, 'Dear swaema hosenbocus, your installment for paydate 2025-01-18 has been paid .', '2025-01-18 15:52:43'),
(32, 102, 'Dear swaema hosenbocus, your loan request for amount 50000 for the purpose of home_improvement has been accepted. The number of installments is 36.', '2025-01-18 19:13:37'),
(33, 102, 'Dear swaema hosenbocus, your installment for paydate 2025-01-18 has been paid .', '2025-01-18 19:14:27'),
(34, 102, 'Dear swaema hosenbocus, your installment for paydate 2025-01-18 has been paid .', '2025-01-18 19:53:47'),
(35, 102, 'Dear swaema hosenbocus, your loan request for amount 35000 for the purpose of medical has been accepted. The number of installments is 60.', '2025-01-18 20:08:42'),
(36, 102, 'Dear swaema hosenbocus, your installment for paydate 2025-01-18 has been paid .', '2025-01-18 20:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('repayment','investment','loan_fee','admin_fee') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('completed','pending','failed') DEFAULT 'pending',
  `reference_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `mobile`, `address`, `image`, `status`, `user_verfied`, `reset_token`, `reset_token_expiry`, `created_at`, `updated_at`) VALUES
(51, 'admin admin', 'admin@gmail.com', '$2y$10$c9cRP6zVhrSmYhlhzSWy7udFapplpRfoB4eHaRaoDWZqxm8ZOPFCS', 'admin', '57908786', 'mru', 'uploads/users/51/profile_image/1737098889_mark cuban quote.jpg', 'active', 'verified', '6e5aa9c51cd64ef89d9e01dc3822505f6c8af21c524c33a9ac0803164b05260883a3286de1e0d3427c51747614cc703a1390', '2025-01-14 23:59:21', '2024-12-07 16:48:57', '2025-01-17 07:28:09'),
(102, 'swaema hosenbocus', 'swaema04@gmail.com', '$2y$10$6gR9yDi01X6yD1OiTXXK7ObmugeYGSTPGnd/dKlikrFQJubMC82rC', 'borrower', '57862402', 'cemetery road', 'uploads/users/102/profile_image/1737107003_girlIcon.png', 'active', 'verified', NULL, NULL, '2025-01-17 13:43:23', '2025-01-17 09:44:20'),
(103, 'len len', 'sbw.hosenbocus@gmail.com', '$2y$10$lqPI75u1OSERSdJsDAwhMe5DPbpJ4pcXV5kydoTaQtqiaU8yA4fEG', 'lender', '57908786', 'pm', '', 'active', 'verified', NULL, NULL, '2025-01-17 13:45:46', '2025-01-17 11:15:17'),
(104, 'coquille', 'dissertationsafefund@gmail.com', '$2y$10$70O59kxpXekj6vI3jV1c6uRVwGl6zMYdhGHvK1Tk2CubT5.cirS5O', 'lender', '58022375', 'xyz', 'uploads/users/104/profile_image/1737110375_Screenshot_2025-01-06_205844-removebg-preview.png', 'active', 'verified', NULL, NULL, '2025-01-17 14:39:35', '2025-01-17 10:40:12'),
(105, 'test', 'ema04hh@gmail.com', '$2y$10$2l108BvY8VeeMIKSJ/dRm.h2jqqBYOR4uw02iNNqqZz0IJqZtBE.a', 'borrower', '57862403', 'text', 'uploads/users/105/profile_image/1737357434_girlIcon.png', 'active', 'verified', NULL, NULL, '2025-01-20 11:17:14', '2025-01-20 07:18:58'),
(106, 'len lender', 'fatema.mounjir@gmail.com', '$2y$10$smi8E/5DpG2sBSf3uFdFL.ZriFdxeI.BESNrorzTVM72aeqWHlxQG', 'lender', '57908786', 'lenders address xyz', 'uploads/users/106/profile_image/1737357672_girlIcon.png', 'active', 'verified', NULL, NULL, '2025-01-20 11:21:12', '2025-01-20 07:21:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consoledatedfund`
--
ALTER TABLE `consoledatedfund`
  ADD PRIMARY KEY (`consoledatedFundId`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lendercontribution`
--
ALTER TABLE `lendercontribution`
  ADD PRIMARY KEY (`lenderContributionId`);

--
-- Indexes for table `loaninstallments`
--
ALTER TABLE `loaninstallments`
  ADD PRIMARY KEY (`loanInstallmentsId`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consoledatedfund`
--
ALTER TABLE `consoledatedfund`
  MODIFY `consoledatedFundId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `lendercontribution`
--
ALTER TABLE `lendercontribution`
  MODIFY `lenderContributionId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `loaninstallments`
--
ALTER TABLE `loaninstallments`
  MODIFY `loanInstallmentsId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
