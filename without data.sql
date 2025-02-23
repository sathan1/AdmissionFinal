-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql103.infinityfree.com
-- Generation Time: Feb 21, 2025 at 11:00 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38130235_college_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic`
--

CREATE TABLE `academic` (
  `academicId` int(11) NOT NULL,
  `academicUserId` int(11) NOT NULL,
  `school_name` text NOT NULL,
  `yearOfPassing` text NOT NULL,
  `tamilMarks` int(11) DEFAULT NULL,
  `englishMarks` int(11) NOT NULL,
  `mathsMarks` int(11) NOT NULL,
  `scienceMarks` int(11) NOT NULL,
  `socialScienceMarks` int(11) NOT NULL,
  `otherLanguageMarks` int(11) DEFAULT NULL,
  `totalMarks` int(11) GENERATED ALWAYS AS (coalesce(`tamilMarks`,0) + `englishMarks` + `mathsMarks` + `scienceMarks` + `socialScienceMarks` + coalesce(`otherLanguageMarks`,0)) STORED,
  `emisNumber` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bankingdetails`
--

CREATE TABLE `bankingdetails` (
  `bankingId` int(11) NOT NULL,
  `bankingUserId` int(11) DEFAULT NULL,
  `accountNumber` varchar(15) DEFAULT NULL,
  `bankName` varchar(100) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `ifsc` varchar(11) DEFAULT NULL,
  `panNumber` varchar(10) DEFAULT NULL,
  `drivingLicenseNumber` varchar(15) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `documentId` int(11) NOT NULL,
  `documentUserId` int(11) NOT NULL,
  `documentType` varchar(50) NOT NULL,
  `documentName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `preference`
--

CREATE TABLE `preference` (
  `preferenceId` int(11) NOT NULL,
  `preferenceUserId` int(11) NOT NULL,
  `preferenceOrder` enum('1','2') NOT NULL,
  `preferenceDepartment` varchar(200) NOT NULL,
  `preferenceStatus` enum('pending','rejected','success','reset') NOT NULL,
  `department_status` text NOT NULL,
  `status_message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studentdetails`
--

CREATE TABLE `studentdetails` (
  `studentId` int(11) NOT NULL,
  `studentUserId` int(11) NOT NULL,
  `studentFirstName` varchar(200) NOT NULL,
  `studentLastName` varchar(200) NOT NULL,
  `studentFatherName` varchar(200) NOT NULL,
  `studentMotherName` varchar(200) NOT NULL,
  `studentDateOfBirth` date NOT NULL,
  `studentGender` enum('male','female','other') NOT NULL,
  `studentCaste` varchar(100) NOT NULL,
  `studentCaste_2` varchar(100) NOT NULL,
  `studentReligion` varchar(100) NOT NULL,
  `studentMotherTongue` varchar(100) NOT NULL,
  `studentPhoneNumber` varchar(15) NOT NULL,
  `studentAadharNumber` int(15) NOT NULL,
  `studentAddress` text NOT NULL,
  `studentCity` text NOT NULL,
  `studentState` text NOT NULL,
  `studentPinCode` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `userName` varchar(200) NOT NULL,
  `userEmail` varchar(200) NOT NULL,
  `userPassword` varchar(200) NOT NULL,
  `userRole` enum('student','admin') NOT NULL DEFAULT 'student',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic`
--
ALTER TABLE `academic`
  ADD PRIMARY KEY (`academicId`),
  ADD KEY `academicUserId` (`academicUserId`);

--
-- Indexes for table `bankingdetails`
--
ALTER TABLE `bankingdetails`
  ADD PRIMARY KEY (`bankingId`),
  ADD KEY `bankingUserId` (`bankingUserId`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`documentId`),
  ADD KEY `documentUserId` (`documentUserId`);

--
-- Indexes for table `preference`
--
ALTER TABLE `preference`
  ADD PRIMARY KEY (`preferenceId`),
  ADD KEY `preferenceUserId` (`preferenceUserId`);

--
-- Indexes for table `studentdetails`
--
ALTER TABLE `studentdetails`
  ADD PRIMARY KEY (`studentId`),
  ADD KEY `studentUserId` (`studentUserId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `userName` (`userName`),
  ADD UNIQUE KEY `userEmail` (`userEmail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic`
--
ALTER TABLE `academic`
  MODIFY `academicId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bankingdetails`
--
ALTER TABLE `bankingdetails`
  MODIFY `bankingId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `documentId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `preference`
--
ALTER TABLE `preference`
  MODIFY `preferenceId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studentdetails`
--
ALTER TABLE `studentdetails`
  MODIFY `studentId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic`
--
ALTER TABLE `academic`
  ADD CONSTRAINT `academic_academicUserId_users_userId` FOREIGN KEY (`academicUserId`) REFERENCES `users` (`userId`);

--
-- Constraints for table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `document_documentUserId_users_userId` FOREIGN KEY (`documentUserId`) REFERENCES `users` (`userId`);

--
-- Constraints for table `preference`
--
ALTER TABLE `preference`
  ADD CONSTRAINT `preference_preferenceUserId_users_userId` FOREIGN KEY (`preferenceUserId`) REFERENCES `users` (`userId`);

--
-- Constraints for table `studentdetails`
--
ALTER TABLE `studentdetails`
  ADD CONSTRAINT `studentDetails_studentUserId_users_userId` FOREIGN KEY (`studentUserId`) REFERENCES `users` (`userId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
