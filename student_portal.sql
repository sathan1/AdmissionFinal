-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2025 at 12:14 PM
-- Server version: 10.4.6-MariaDB
-- PHP Version: 8.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic`
--

CREATE TABLE `academic` (
  `academicId` int(11) NOT NULL,
  `academicUserId` int(11) NOT NULL,
  `academicSSLCName` varchar(200) NOT NULL,
  `academicSSLCMark` int(11) NOT NULL,
  `academicHSLCName` varchar(200) NOT NULL,
  `academicHSLCMark` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `documentId` int(11) NOT NULL,
  `documentUserId` int(11) NOT NULL,
  `documentType` varchar(100) NOT NULL,
  `documentName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `preference`
--

CREATE TABLE `preference` (
  `preferenceId` int(11) NOT NULL,
  `preferenceUserId` int(11) NOT NULL,
  `preferenceOrder` enum('1','2') NOT NULL,
  `preferenceDepartment` varchar(200) NOT NULL,
  `preferenceStatus` enum('pending','rejected','success') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `studentDetails`
--

CREATE TABLE `studentDetails` (
  `studentId` int(11) NOT NULL,
  `studentUserId` int(11) NOT NULL,
  `studentFirstName` varchar(200) NOT NULL,
  `studentLastName` varchar(200) NOT NULL,
  `studentFatherName` varchar(200) NOT NULL,
  `studentMotherName` varchar(200) NOT NULL,
  `studentDateOfBirth` date NOT NULL,
  `studentGender` enum('male','female','other') NOT NULL,
  `studentCaste` varchar(100) NOT NULL,
  `studentPhoneNumber` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `userName`, `userEmail`, `userPassword`, `userRole`, `createdAt`) VALUES
(1, 'blk', 'black@black.in', '$2y$10$VMoNgcNWw9oCeA3JKQI0X.16no.FXeRaLKG292cz1b6P7kCycc/zG', 'admin', '2025-01-10 16:01:46');

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
-- Indexes for table `studentDetails`
--
ALTER TABLE `studentDetails`
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
-- AUTO_INCREMENT for table `studentDetails`
--
ALTER TABLE `studentDetails`
  MODIFY `studentId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `studentDetails`
--
ALTER TABLE `studentDetails`
  ADD CONSTRAINT `studentDetails_studentUserId_users_userId` FOREIGN KEY (`studentUserId`) REFERENCES `users` (`userId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
