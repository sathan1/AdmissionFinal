-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql103.infinityfree.com
-- Generation Time: Feb 21, 2025 at 11:05 AM
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

--
-- Dumping data for table `academic`
--

INSERT INTO `academic` (`academicId`, `academicUserId`, `school_name`, `yearOfPassing`, `tamilMarks`, `englishMarks`, `mathsMarks`, `scienceMarks`, `socialScienceMarks`, `otherLanguageMarks`, `emisNumber`) VALUES
(1, 9, 'ss', '0', 55, 55, 66, 77, 88, 0, NULL),
(2, 10, 'ss', '0', 77, 88, 99, 77, 77, 0, NULL),
(3, 11, 'PA International School', 'Apr-2022', 80, 67, 56, 78, 67, 56, NULL),
(4, 12, 'ss', 'Apr-2022', 44, 44, 44, 44, 44, 0, NULL),
(5, 13, 'Kandhasamy Matric Hr.Sec School', 'Apr-2022', 55, 55, 55, 66, 99, 0, '123456'),
(6, 14, 'AARB School', 'Apr-2022', 78, 76, 67, 45, 76, 0, ''),
(7, 15, 'RKR Grks School', 'Apr-2022', 87, 67, 67, 65, 56, 0, ''),
(8, 17, 'RGM HR SEC SCHOOL', 'apr-2022', 35, 66, 77, 88, 44, 0, '24353'),
(9, 18, 'ss', '0', 55, 44, 35, 55, 55, 0, '11'),
(12, 23, 'ss', 'Apr-2022', 55, 66, 77, 88, 99, 0, '44');

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

--
-- Dumping data for table `document`
--

INSERT INTO `document` (`documentId`, `documentUserId`, `documentType`, `documentName`) VALUES
(1, 9, 'aadhaar', 'aadhaar_67b752ca065913.26303435.png'),
(2, 9, 'marksheet', 'marksheet_67b752ca06a3a5.13217073.png'),
(3, 9, 'photo', 'photo_67b752ca06ea12.87466481.png'),
(4, 9, 'birthCertificate', 'birthCertificate_67b752ca0733a1.61085460.png'),
(5, 10, 'aadhaar', 'aadhaar_67b75990b98f40.41448734.png'),
(6, 10, 'marksheet', 'marksheet_67b75990b9e709.93082626.png'),
(7, 10, 'photo', 'photo_67b75990ba7590.26201132.png'),
(8, 10, 'birthCertificate', 'birthCertificate_67b75990bac769.48131361.png'),
(9, 12, 'aadhaar', 'aadhaar_67b75c5be14758.22549473.png'),
(10, 12, 'marksheet', 'marksheet_67b75c5be19497.45248236.png'),
(11, 12, 'photo', 'photo_67b75c5be1d1a1.95892001.png'),
(12, 12, 'birthCertificate', 'birthCertificate_67b75c5be21159.45954078.png'),
(13, 13, 'aadhaar', 'aadhaar_67b75f60ea79a3.58835218.jpg'),
(14, 13, 'marksheet', 'marksheet_67b75f60eaff22.40349059.jpg'),
(15, 13, 'photo', 'photo_67b75f60eb5039.75629790.jpg'),
(16, 13, 'birthCertificate', 'birthCertificate_67b75f60eba009.09521025.jpg'),
(17, 15, 'aadhaar', 'aadhaar_67b8146ad16de5.51163190.jpg'),
(18, 15, 'marksheet', 'marksheet_67b8146ad226e7.61808004.jpg'),
(19, 15, 'photo', 'photo_67b8146ad26a12.69816156.jpg'),
(20, 15, 'birthCertificate', 'birthCertificate_67b8146ad2bc27.90792981.jpg'),
(21, 17, 'aadhaar', 'aadhaar_67b85bb91b35d9.60026949.jpeg'),
(22, 17, 'marksheet', 'marksheet_67b85bb91bfee9.95846446.jpg'),
(23, 17, 'photo', 'photo_67b85bb91c5c02.14853658.jpg'),
(24, 17, 'birthCertificate', 'birthCertificate_67b85bb91cad48.32869291.jpg'),
(25, 23, 'aadhaar', 'aadhaar_67b88dc61a4c98.72245328.png'),
(26, 23, 'marksheet', 'marksheet_67b88dc61b66c0.67281324.png'),
(27, 23, 'photo', 'photo_67b88dc61c7e89.53958518.png'),
(28, 23, 'birthCertificate', 'birthCertificate_67b88dc61da042.17638963.png');

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

--
-- Dumping data for table `preference`
--

INSERT INTO `preference` (`preferenceId`, `preferenceUserId`, `preferenceOrder`, `preferenceDepartment`, `preferenceStatus`, `department_status`, `status_message`) VALUES
(1, 9, '1', 'Mechanical Engineering', 'success', 'MGMT', ''),
(2, 9, '2', 'Electrical and Electronics Engineering', 'reset', '', 'Nil'),
(3, 10, '1', 'Electrical and Electronics Engineering', 'pending', '', ''),
(4, 10, '2', 'Mechanical Engineering', 'pending', '', ''),
(5, 11, '1', 'Computer Technology', 'pending', '', ''),
(6, 11, '2', 'Electrical and Communication Engineering', 'pending', '', ''),
(7, 12, '1', 'Mechanical Engineering', 'pending', '', ''),
(8, 12, '2', 'Civil Engineering', 'pending', '', ''),
(9, 13, '1', 'Textile Technology', 'pending', '', ''),
(10, 13, '2', 'Automobile Engineering', 'pending', '', ''),
(11, 14, '1', 'Electrical and Communication Engineering', 'pending', '', ''),
(12, 14, '2', 'Electrical and Electronics Engineering', 'pending', '', ''),
(13, 15, '1', 'Automobile Engineering', 'success', 'MGMT', ''),
(14, 15, '2', 'Mechanical Engineering (R&AC)', 'rejected', '', 'Seat is not available'),
(15, 17, '1', 'Electrical and Electronics Engineering', 'success', 'MGMT', ''),
(16, 17, '2', 'Automobile Engineering', 'rejected', '', 'missing documents'),
(17, 18, '1', 'Mechanical Engineering', 'pending', '', ''),
(18, 18, '2', 'Textile Technology', 'pending', '', ''),
(23, 23, '1', 'Computer Technology', 'pending', '', ''),
(24, 23, '2', 'Printing Technology', 'pending', '', '');

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

--
-- Dumping data for table `studentdetails`
--

INSERT INTO `studentdetails` (`studentId`, `studentUserId`, `studentFirstName`, `studentLastName`, `studentFatherName`, `studentMotherName`, `studentDateOfBirth`, `studentGender`, `studentCaste`, `studentCaste_2`, `studentReligion`, `studentMotherTongue`, `studentPhoneNumber`, `studentAadharNumber`, `studentAddress`, `studentCity`, `studentState`, `studentPinCode`) VALUES
(1, 9, 'SATHAN DHURKES', 'ww', 'DEIVASIKAMANI', 'dd', '2010-02-20', 'male', 'ST', 'Boom Boom Mattukaran', 'Muslim', 'Kannada', '1233333333', 2147483647, 'ssd', 'Davanagere', 'Karnataka', '642103'),
(2, 10, 'SATHAN DHURKES', 'D', 'DEIVASIKAMANI', 'dd', '2006-06-17', 'male', 'ST', 'Kongu Vellalar', 'Hindu', 'Tamil', '1233333333', 2147483647, 'sss', 'Medininagar', 'Jharkhand', '642103'),
(3, 11, 'Akhaash ', 'A T', 'Thangavel', 'magudeswari', '2006-12-06', 'male', 'BC', 'Chettiar', 'Hindu', 'Tamil', '9876543210', 2147483647, '12/main street/pollachi', 'Coimbatore', 'Tamil Nadu', '642006'),
(4, 12, 'SATHAN DHURKES', 'D', 'DEIVASIKAMANI', 'dd', '2006-06-17', 'male', 'MBC', 'Boom Boom Mattukaran', 'Sikh', 'Sowrashtra', '2222222222', 2147483647, 'kkk', 'Sagar', 'Madhya Pradesh', '642103'),
(5, 13, 'SATHANDHURKES', 'D', 'sd', 'Sathiya ', '2006-02-20', 'male', 'BC', 'Kodikaal Vellalar', 'Christian', 'Sowrashtra', '9345556106', 2147483647, '3/15 A, NATRAJ GOUNDAR STREET, SUBBEGOUNDAN PUDHUR AMBARAMPALAYAM', 'POLLACHI', 'Tamil Nadu', '642103'),
(6, 14, 'Akhaash', 'A T', 'Thangavel', 'Magudeswari', '2006-11-22', 'male', 'BC', 'Chettiar', 'Hindu', 'Tamil', '9876543210', 2147483647, '32/main street/pollachi\r\n', 'Coimbatore', 'Tamil Nadu', '642003'),
(7, 16, 'harshat', 'p', 'prabhakaran', 'rajeswari', '2006-11-11', 'male', 'MBC', 'Vanniyar', 'Hindu', 'Tamil', '9514955236', 2147483647, 'no 3 /uthiyam nagar podanur /coimbatore', 'Coimbatore', 'Tamil Nadu', '641023'),
(8, 15, 'Bala Aadhityaa', 'K', 'Karthikeyan', 'rathipratha', '2006-11-11', 'male', 'BC', 'Kongu Vellalar', 'Hindu', 'Tamil', '9876543210', 2147483647, '13/main street/udumali', 'Tiruppur', 'Tamil Nadu', '642126'),
(9, 17, 'sathish', 's', 'suresh', 'rani', '2006-06-17', 'male', 'BC', 'Desikar', 'Jain', 'Tamil', '9876543210', 2147483647, 'kkk', 'Dharmapuri', 'Tamil Nadu', '641662'),
(10, 18, 'SATHAN DHURKES', 'D', 'DEIVASIKAMANI', 'SBDT', '2006-06-17', 'male', 'BC', 'Kongu Vellalar', 'Hindu', 'Tamil', '1233333333', 2147483647, 'nil', 'cbe', 'tamil nadu', '642103'),
(13, 23, 'SATHAN DHURKES', 'D', 'ee', 'dd', '2005-04-13', 'female', 'BC(M)', 'Boya (caste)', 'Muslim', 'Sowrashtra', '1233333333', 2147483647, 'dddd', 'Palakkad', 'Kerala', '642103');

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
(2, 'sathan', 'sathancreator@gmail.com', '123', 'admin', '2025-02-18 16:03:01'),
(10, 'wste_mgmt', 'deivasikamani8@gmail.com', '$2y$10$2yuYXpNHIaZuYdWNHpu7o.59Ov3xobZlmi8aIp.JA55sYvFONjtgC', 'student', '2025-02-20 16:12:42'),
(11, 'Akhaash A T', 'masterakhaash@gmail.com', '$2y$10$gNgI4qB8tz85gs3z0LSB7eXhBAOa1um0Wse1k3Rgwan928c10Fsey', 'student', '2025-02-20 16:35:35'),
(12, 'deivasiamani', 'sathandhurkesdeivasikamani@gmail.com', '$2y$10$NChPF0Brj7GQMFqoRSj1EunzxBT6F.7VqY0Iggh.4nve2ZZPTn/My', 'student', '2025-02-20 16:43:31'),
(14, 'Akhaash', 'mathavangonar@gmail.com', '$2y$10$V98eK/GMQTVEZ7m8QFfA/.Jq1wGVxeQ80Hl6Ye5uGoU25DvV9aumy', 'student', '2025-02-20 17:14:48'),
(15, 'Bala aadhityaa', 'balaaadhityaa@gmail.com', '$2y$10$XQjIfTjKeml3xFoNk3mkLO7cTGEst4NH5Aica/CFYSzrn.eTRxDFS', 'student', '2025-02-21 05:01:18'),
(16, 'harshath', 'harshathprabhu11@gmail.com', '$2y$10$1lYYFRBeOqk87tzIEN6S8OgCGr5L3UUImCYW1njVvbHOtpk6nJJ9a', 'student', '2025-02-21 05:40:23'),
(17, 'natheesh', 'nathesh06@gmail.com', '$2y$10$MNLMUzLFr0jzsN25toVXJOQsXcxrVbVX4s.PgDWLLx/xUsWFn8iT.', 'student', '2025-02-21 10:51:31'),
(18, 'sathandhurkes476', 'sathandhurkes@gmail.com', '$2y$10$MOiK0gcCvTZbi8i.Q80XwOHS/18th9ERexbYzVTs6kWo/SX2NWMQa', 'student', '2025-02-21 13:26:36'),
(23, 'sathandhurkesd890', 'sathandhurkes@outlook.com', '$2y$10$0cJ33bm0QM1OLlWh4mP1KuzcBXhLRkvuTWAb2Dqk1PklsLc6dgIQG', 'student', '2025-02-21 14:29:26');

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
  MODIFY `academicId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `bankingdetails`
--
ALTER TABLE `bankingdetails`
  MODIFY `bankingId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `documentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `preference`
--
ALTER TABLE `preference`
  MODIFY `preferenceId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `studentdetails`
--
ALTER TABLE `studentdetails`
  MODIFY `studentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
