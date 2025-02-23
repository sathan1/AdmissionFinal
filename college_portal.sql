-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2025 at 01:44 PM
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
-- Database: `college_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic`
--

CREATE TABLE `academic` (
  `academicId` int(11) NOT NULL,
  `academicUserId` int(11) NOT NULL,
  `school_name` text NOT NULL,
  `yearOfPassing` int(4) NOT NULL,
  `tamilMarks` int(11) DEFAULT NULL,
  `englishMarks` int(11) NOT NULL,
  `mathsMarks` int(11) NOT NULL,
  `scienceMarks` int(11) NOT NULL,
  `socialScienceMarks` int(11) NOT NULL,
  `otherLanguageMarks` int(11) DEFAULT NULL,
  `totalMarks` int(11) GENERATED ALWAYS AS (coalesce(`tamilMarks`,0) + `englishMarks` + `mathsMarks` + `scienceMarks` + `socialScienceMarks` + coalesce(`otherLanguageMarks`,0)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic`
--

INSERT INTO `academic` (`academicId`, `academicUserId`, `school_name`, `yearOfPassing`, `tamilMarks`, `englishMarks`, `mathsMarks`, `scienceMarks`, `socialScienceMarks`, `otherLanguageMarks`) VALUES
(1, 13, 'sms', 3456, 45, 55, 65, 75, 85, 0);

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `documentId` int(11) NOT NULL,
  `documentUserId` int(11) NOT NULL,
  `documentType` varchar(100) NOT NULL,
  `documentName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document`
--

INSERT INTO `document` (`documentId`, `documentUserId`, `documentType`, `documentName`) VALUES
(1, 13, 'documents', '{\"aadhaar\":\"aadhaar_678ce42169d182.83076373.jpg\",\"marksheet\":\"marksheet_678ce4216bb288.46433269.jpg\",\"photo\":\"photo_678ce4216c96b9.32832375.jpg\"}');

-- --------------------------------------------------------

--
-- Table structure for table `preference`
--

CREATE TABLE `preference` (
  `preferenceId` int(11) NOT NULL,
  `preferenceUserId` int(11) NOT NULL,
  `preferenceOrder` enum('1','2') NOT NULL,
  `preferenceDepartment` varchar(200) NOT NULL,
  `preferenceStatus` enum('pending','rejected','success','reset') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `preference`
--

INSERT INTO `preference` (`preferenceId`, `preferenceUserId`, `preferenceOrder`, `preferenceDepartment`, `preferenceStatus`) VALUES
(1, 13, '1', 'Mechanical Engineering', 'rejected'),
(2, 13, '2', 'Electrical and Communication Engineering', 'success');

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
  `studentPhoneNumber` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentdetails`
--

INSERT INTO `studentdetails` (`studentId`, `studentUserId`, `studentFirstName`, `studentLastName`, `studentFatherName`, `studentMotherName`, `studentDateOfBirth`, `studentGender`, `studentCaste`, `studentCaste_2`, `studentReligion`, `studentMotherTongue`, `studentPhoneNumber`) VALUES
(1, 13, 'nishanth', 'nila', 'sf', 'fd', '2025-01-21', 'male', 'BC', 'kongu', 'Hindu', 'Tamil', '01234567890');

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
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `userName`, `userEmail`, `userPassword`, `userRole`, `createdAt`) VALUES
(0, 'nptc', 'sathancreator@gmail.com', 'Nptc@1957', 'admin', '2025-01-17 12:27:42'),
(13, 'sathan dhurkes', 'sathandhurkesdeivasikamani@gmail.com', '$2y$10$Ql0TpN.kibZcro3fWVQoI.CR2caLe2YU8IVIMYUWZrnM8I6cPDxwO', 'student', '2025-01-19 11:36:49');

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
  MODIFY `academicId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `documentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `preference`
--
ALTER TABLE `preference`
  MODIFY `preferenceId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `studentdetails`
--
ALTER TABLE `studentdetails`
  MODIFY `studentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
