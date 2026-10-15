-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2025 at 11:32 AM
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
-- Database: `eras`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_code` varchar(10) NOT NULL,
  `course_title` varchar(100) NOT NULL,
  `program_name` varchar(50) NOT NULL,
  `semester_no` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_code`, `course_title`, `program_name`, `semester_no`) VALUES
('ACC201', 'Financial Accounting', 'BIM', 3),
('ACC202', 'Cost and Management Accounting', 'BIM', 4),
('CAAC152', 'Financial Accounting', 'BCA', 2),
('CACO102', 'Society and Technology', 'BCA', 1),
('CACS101', 'Computer Fundamentals & Applications', 'BCA', 1),
('CACS105', 'Digital Logic', 'BCA', 1),
('CACS151', 'C Programming', 'BCA', 2),
('CACS155', 'Microprocessor and Computer Architecture', 'BCA', 2),
('CACS201', 'Data Structures and Algorithms', 'BCA', 3),
('CACS203', 'System Analysis and Design', 'BCA', 3),
('CACS204', 'OOP in Java', 'BCA', 3),
('CACS205', 'Web Technology', 'BCA', 3),
('CACS251', 'Operating System', 'BCA', 4),
('CACS252', 'Numerical Methods', 'BCA', 4),
('CACS253', 'Software Engineering', 'BCA', 4),
('CACS254', 'Scripting Language', 'BCA', 4),
('CACS255', 'Database Management System', 'BCA', 4),
('CACS301', 'MIS and E-Business', 'BCA', 5),
('CACS302', 'DotNet Technology', 'BCA', 5),
('CACS303', 'Computer Networking', 'BCA', 5),
('CACS305', 'Computer Graphics and Animation', 'BCA', 5),
('CACS351', 'Mobile Programming', 'BCA', 6),
('CACS352', 'Distributed System', 'BCA', 6),
('CACS354', 'Advanced Java Programming', 'BCA', 6),
('CACS355', 'Network Programming', 'BCA', 6),
('CACS401', 'Cyber Law and Professional Ethics', 'BCA', 7),
('CACS402', 'Cloud Computing', 'BCA', 7),
('CACS404', 'Image Processing', 'BCA', 7),
('CACS405', 'Database Administration', 'BCA', 7),
('CACS406', 'Network Administration', 'BCA', 7),
('CACS408', 'Advanced Dot Net Technology', 'BCA', 7),
('CACS409', 'E-Governance', 'BCA', 7),
('CACS410', 'Artificial Intelligence', 'BCA', 7),
('CACS453', 'Database Programming', 'BCA', 8),
('CACS454', 'Geographical Information System', 'BCA', 8),
('CACS455', 'Data Analysis and Visualization', 'BCA', 8),
('CACS456', 'Machine Learning', 'BCA', 8),
('CACS457', 'Multimedia System', 'BCA', 8),
('CACS458', 'Knowledge Engineering', 'BCA', 8),
('CACS459', 'Information Security', 'BCA', 8),
('CACS460', 'Internet of Things', 'BCA', 8),
('CAEC353', 'Applied Economics', 'BCA', 6),
('CAEN103', 'English I', 'BCA', 1),
('CAEN153', 'English II', 'BCA', 2),
('CAIN403', 'Internship', 'BCA', 7),
('CAMG304', 'Introduction to Management', 'BCA', 5),
('CAMT104', 'Mathematics I', 'BCA', 1),
('CAMT154', 'Mathematics II', 'BCA', 2),
('CAOR451', 'Operations Research', 'BCA', 8),
('CAPJ256', 'Project I', 'BCA', 4),
('CAPJ356', 'Project II', 'BCA', 6),
('CAPJ452', 'Project III', 'BCA', 8),
('CAST202', 'Probability and Statistics', 'BCA', 3),
('CSC109', 'Introduction to Information Technology', 'CSIT', 1),
('CSC110', 'C Programming', 'CSIT', 1),
('CSC111', 'Digital Logic', 'CSIT', 1),
('CSC160', 'Discrete Structure', 'CSIT', 2),
('CSC161', 'Object Oriented Programming', 'CSIT', 2),
('CSC162', 'Microprocessor', 'CSIT', 2),
('CSC206', 'Data Structure and Algorithms', 'CSIT', 3),
('CSC207', 'Numerical Method', 'CSIT', 3),
('CSC208', 'Computer Architecture', 'CSIT', 3),
('CSC209', 'Computer Graphics', 'CSIT', 3),
('CSC257', 'Theory of Computation', 'CSIT', 4),
('CSC258', 'Computer Networks', 'CSIT', 4),
('CSC259', 'Operating Systems', 'CSIT', 4),
('CSC260', 'Database Management System', 'CSIT', 4),
('CSC261', 'Artificial Intelligence', 'CSIT', 4),
('CSC314', 'Design and Analysis of Algorithms', 'CSIT', 5),
('CSC315', 'System Analysis and Design', 'CSIT', 5),
('CSC316', 'Cryptography', 'CSIT', 5),
('CSC317', 'Simulation and Modeling', 'CSIT', 5),
('CSC318', 'Web Technology', 'CSIT', 5),
('CSC319', 'Multimedia Computing', 'CSIT', 5),
('CSC320', 'Wireless Networking', 'CSIT', 5),
('CSC321', 'Image Processing', 'CSIT', 5),
('CSC322', 'Knowledge Management', 'CSIT', 5),
('CSC323', 'Society and Ethics in Information Technology', 'CSIT', 5),
('CSC324', 'Microprocessor Based Design', 'CSIT', 5),
('CSC364', 'Software Engineering', 'CSIT', 6),
('CSC365', 'Complier Design and Construction', 'CSIT', 6),
('CSC366', 'E-Governance', 'CSIT', 6),
('CSC367', 'NET Centric Computing', 'CSIT', 6),
('CSC368', 'Technical Writing', 'CSIT', 6),
('CSC369', 'Applied Logic', 'CSIT', 6),
('CSC370', 'E-commerce', 'CSIT', 6),
('CSC371', 'Automation and Robotics', 'CSIT', 6),
('CSC372', 'Neural Networks', 'CSIT', 6),
('CSC373', 'Computer Hardware Design', 'CSIT', 6),
('CSC374', 'Cognitive Science', 'CSIT', 6),
('CSC409', 'Advanced Java Programming', 'CSIT', 7),
('CSC410', 'Data Warehousing and Data Mining', 'CSIT', 7),
('CSC412', 'Project Work', 'CSIT', 7),
('CSC413', 'Information Retrieval', 'CSIT', 7),
('CSC414', 'Database Administration', 'CSIT', 7),
('CSC415', 'Software Project Management', 'CSIT', 7),
('CSC416', 'Network Security', 'CSIT', 7),
('CSC417', 'Digital System Design', 'CSIT', 7),
('CSC461', 'Advanced Database', 'CSIT', 8),
('CSC462', 'Internship', 'CSIT', 8),
('CSC463', 'Advanced Networking with IPV6', 'CSIT', 8),
('CSC464', 'Distributed Networking', 'CSIT', 8),
('CSC465', 'Game Technology', 'CSIT', 8),
('CSC466', 'Distributed and Object Oriented Database', 'CSIT', 8),
('CSC467', 'Introduction to Cloud Computing', 'CSIT', 8),
('CSC468', 'Geographical Information System', 'CSIT', 8),
('CSC469', 'Decision Support System and Expert System', 'CSIT', 8),
('CSC470', 'Mobile Application Development', 'CSIT', 8),
('CSC471', 'Real Time Systems', 'CSIT', 8),
('CSC472', 'Network and System Administration', 'CSIT', 8),
('CSC473', 'Embedded Systems Programming', 'CSIT', 8),
('ECO206', 'Economics for Business', 'BIM', 4),
('ENG203', 'Business Communications', 'BIM', 2),
('ENG206', 'English I', 'BIM', 1),
('FIN229', 'Fundamentals of Corporate Finance', 'BIM', 6),
('IT220', 'Database Management System', 'BIM', 4),
('IT228', 'Artificial Intelligence', 'BIM', 5),
('IT231', 'Foundation of Information Technology', 'BIM', 1),
('IT232', 'C Programming', 'BIM', 1),
('IT233', 'Digital Logic', 'BIM', 2),
('IT234', 'Object Oriented Programming with Java', 'BIM', 2),
('IT235', 'Discrete Structure', 'BIM', 2),
('IT236', 'Microprocessor and Computer Architecture', 'BIM', 3),
('IT237', 'Web Technology I', 'BIM', 3),
('IT238', 'Data Structure and Algorithms', 'BIM', 3),
('IT239', 'Web Technology II', 'BIM', 4),
('IT240', 'Business Data Communication and Networking', 'BIM', 4),
('IT241', 'Operating System', 'BIM', 4),
('IT242', 'Software Design and Development', 'BIM', 5),
('IT243', 'Programming with Python', 'BIM', 5),
('IT244', 'Information Security', 'BIM', 5),
('IT245', 'Business Information Systems', 'BIM', 6),
('IT246', 'IT Ethics and Cybersecurity', 'BIM', 6),
('IT247', 'E-Commerce and Internet Marketing', 'BIM', 7),
('IT248', 'IT Entrepreneurship and Management', 'BIM', 8),
('IT249', 'Business Intelligence', 'BIM', 8),
('IT250', 'Digital Economy', 'BIM', 8),
('IT271', 'Networking and System Administration', 'BIM', 7),
('IT272', 'Mobile Application Development', 'BIM', 7),
('IT273', 'Multimedia System Application', 'BIM', 7),
('IT274', 'Data Warehousing and Data Mining', 'BIM', 7),
('IT275', '.NET Programming', 'BIM', 8),
('IT276', 'Database Administration', 'BIM', 8),
('IT277', 'Cloud Computing', 'BIM', 8),
('IT278', 'Big Data and Analytics', 'BIM', 8),
('IT350', 'Internship', 'BIM', 8),
('IT352', 'Project', 'BIM', 6),
('MGT205', 'Operations Management', 'BIM', 7),
('MGT231', 'Foundation of Business Management', 'BIM', 1),
('MGT236', 'Business Environment', 'BIM', 6),
('MGT240', 'Strategic Management', 'BIM', 7),
('MGT241', 'Organizational Behavior & Human Resource Management', 'BIM', 2),
('MGT411', 'Principles of Management', 'CSIT', 7),
('MGT418', 'International Marketing', 'CSIT', 7),
('MGT474', 'International Business Management', 'CSIT', 8),
('MKT201', 'Fundamentals of Marketing', 'BIM', 5),
('MTH112', 'Mathematics I', 'CSIT', 1),
('MTH163', 'Mathematics II', 'CSIT', 2),
('MTH204', 'Basic Mathematics', 'BIM', 1),
('PHY113', 'Physics', 'CSIT', 1),
('RCH201', 'Business Research Methods', 'BIM', 6),
('SOC203', 'Sociology for Business Management', 'BIM', 7),
('STA164', 'Statistics I', 'CSIT', 2),
('STA210', 'Statistics II', 'CSIT', 3),
('STT201', 'Business Statistics', 'BIM', 3);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `course_code` varchar(10) NOT NULL,
  `section` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `student_name`, `course_code`, `section`) VALUES
(1, '2025-CSIT-SDNT-1', 'Aarav Sharma', 'CSC109', 'A'),
(2, '2025-CSIT-SDNT-1', 'Aarav Sharma', 'CSC110', 'A'),
(3, '2025-CSIT-SDNT-1', 'Aarav Sharma', 'CSC111', 'A'),
(4, '2025-CSIT-SDNT-1', 'Aarav Sharma', 'MTH112', 'A'),
(5, '2025-CSIT-SDNT-1', 'Aarav Sharma', 'PHY113', 'A'),
(6, '2025-CSIT-SDNT-2', 'Aanya Pradhan', 'CSC109', 'A'),
(7, '2025-CSIT-SDNT-2', 'Aanya Pradhan', 'CSC110', 'A'),
(8, '2025-CSIT-SDNT-2', 'Aanya Pradhan', 'CSC111', 'A'),
(9, '2025-CSIT-SDNT-2', 'Aanya Pradhan', 'MTH112', 'A'),
(10, '2025-CSIT-SDNT-2', 'Aanya Pradhan', 'PHY113', 'A'),
(11, '2025-CSIT-SDNT-3', 'Aditya Shrestha', 'CSC109', 'A'),
(12, '2025-CSIT-SDNT-3', 'Aditya Shrestha', 'CSC110', 'A'),
(13, '2025-CSIT-SDNT-3', 'Aditya Shrestha', 'CSC111', 'A'),
(14, '2025-CSIT-SDNT-3', 'Aditya Shrestha', 'MTH112', 'A'),
(15, '2025-CSIT-SDNT-3', 'Aditya Shrestha', 'PHY113', 'A'),
(16, '2025-CSIT-SDNT-4', 'Anisha Thapa', 'CSC109', 'A'),
(17, '2025-CSIT-SDNT-4', 'Anisha Thapa', 'CSC110', 'A'),
(18, '2025-CSIT-SDNT-4', 'Anisha Thapa', 'CSC111', 'A'),
(19, '2025-CSIT-SDNT-4', 'Anisha Thapa', 'MTH112', 'A'),
(20, '2025-CSIT-SDNT-4', 'Anisha Thapa', 'PHY113', 'A'),
(21, '2025-CSIT-SDNT-5', 'Arjun Gurung', 'CSC109', 'A'),
(22, '2025-CSIT-SDNT-5', 'Arjun Gurung', 'CSC110', 'A'),
(23, '2025-CSIT-SDNT-5', 'Arjun Gurung', 'CSC111', 'A'),
(24, '2025-CSIT-SDNT-5', 'Arjun Gurung', 'MTH112', 'A'),
(25, '2025-CSIT-SDNT-5', 'Arjun Gurung', 'PHY113', 'A'),
(26, '2025-CSIT-SDNT-6', 'Arpita Magar', 'CSC109', 'A'),
(27, '2025-CSIT-SDNT-6', 'Arpita Magar', 'CSC110', 'A'),
(28, '2025-CSIT-SDNT-6', 'Arpita Magar', 'CSC111', 'A'),
(29, '2025-CSIT-SDNT-6', 'Arpita Magar', 'MTH112', 'A'),
(30, '2025-CSIT-SDNT-6', 'Arpita Magar', 'PHY113', 'A'),
(31, '2025-CSIT-SDNT-7', 'Ashish Tamang', 'CSC109', 'A'),
(32, '2025-CSIT-SDNT-7', 'Ashish Tamang', 'CSC110', 'A'),
(33, '2025-CSIT-SDNT-7', 'Ashish Tamang', 'CSC111', 'A'),
(34, '2025-CSIT-SDNT-7', 'Ashish Tamang', 'MTH112', 'A'),
(35, '2025-CSIT-SDNT-7', 'Ashish Tamang', 'PHY113', 'A'),
(36, '2025-CSIT-SDNT-8', 'Ayush Rai', 'CSC109', 'A'),
(37, '2025-CSIT-SDNT-8', 'Ayush Rai', 'CSC110', 'A'),
(38, '2025-CSIT-SDNT-8', 'Ayush Rai', 'CSC111', 'A'),
(39, '2025-CSIT-SDNT-8', 'Ayush Rai', 'MTH112', 'A'),
(40, '2025-CSIT-SDNT-8', 'Ayush Rai', 'PHY113', 'A'),
(41, '2025-CSIT-SDNT-9', 'Bijay Limbu', 'CSC109', 'A'),
(42, '2025-CSIT-SDNT-9', 'Bijay Limbu', 'CSC110', 'A'),
(43, '2025-CSIT-SDNT-9', 'Bijay Limbu', 'CSC111', 'A'),
(44, '2025-CSIT-SDNT-9', 'Bijay Limbu', 'MTH112', 'A'),
(45, '2025-CSIT-SDNT-9', 'Bijay Limbu', 'PHY113', 'A'),
(46, '2025-CSIT-SDNT-10', 'Binita Chettri', 'CSC109', 'A'),
(47, '2025-CSIT-SDNT-10', 'Binita Chettri', 'CSC110', 'A'),
(48, '2025-CSIT-SDNT-10', 'Binita Chettri', 'CSC111', 'A'),
(49, '2025-CSIT-SDNT-10', 'Binita Chettri', 'MTH112', 'A'),
(50, '2025-CSIT-SDNT-10', 'Binita Chettri', 'PHY113', 'A'),
(51, '2025-CSIT-SDNT-11', 'Deepak Karki', 'CSC109', 'A'),
(52, '2025-CSIT-SDNT-11', 'Deepak Karki', 'CSC110', 'A'),
(53, '2025-CSIT-SDNT-11', 'Deepak Karki', 'CSC111', 'A'),
(54, '2025-CSIT-SDNT-11', 'Deepak Karki', 'MTH112', 'A'),
(55, '2025-CSIT-SDNT-11', 'Deepak Karki', 'PHY113', 'A'),
(56, '2025-CSIT-SDNT-12', 'Dipika Adhikari', 'CSC109', 'A'),
(57, '2025-CSIT-SDNT-12', 'Dipika Adhikari', 'CSC110', 'A'),
(58, '2025-CSIT-SDNT-12', 'Dipika Adhikari', 'CSC111', 'A'),
(59, '2025-CSIT-SDNT-12', 'Dipika Adhikari', 'MTH112', 'A'),
(60, '2025-CSIT-SDNT-12', 'Dipika Adhikari', 'PHY113', 'A'),
(61, '2025-CSIT-SDNT-13', 'Ganesh Bhattarai', 'CSC109', 'A'),
(62, '2025-CSIT-SDNT-13', 'Ganesh Bhattarai', 'CSC110', 'A'),
(63, '2025-CSIT-SDNT-13', 'Ganesh Bhattarai', 'CSC111', 'A'),
(64, '2025-CSIT-SDNT-13', 'Ganesh Bhattarai', 'MTH112', 'A'),
(65, '2025-CSIT-SDNT-13', 'Ganesh Bhattarai', 'PHY113', 'A'),
(66, '2025-CSIT-SDNT-14', 'Gita Khadka', 'CSC109', 'A'),
(67, '2025-CSIT-SDNT-14', 'Gita Khadka', 'CSC110', 'A'),
(68, '2025-CSIT-SDNT-14', 'Gita Khadka', 'CSC111', 'A'),
(69, '2025-CSIT-SDNT-14', 'Gita Khadka', 'MTH112', 'A'),
(70, '2025-CSIT-SDNT-14', 'Gita Khadka', 'PHY113', 'A'),
(71, '2025-CSIT-SDNT-15', 'Hari Poudel', 'CSC109', 'A'),
(72, '2025-CSIT-SDNT-15', 'Hari Poudel', 'CSC110', 'A'),
(73, '2025-CSIT-SDNT-15', 'Hari Poudel', 'CSC111', 'A'),
(74, '2025-CSIT-SDNT-15', 'Hari Poudel', 'MTH112', 'A'),
(75, '2025-CSIT-SDNT-15', 'Hari Poudel', 'PHY113', 'A'),
(76, '2025-CSIT-SDNT-16', 'Indira Bhandari', 'CSC109', 'A'),
(77, '2025-CSIT-SDNT-16', 'Indira Bhandari', 'CSC110', 'A'),
(78, '2025-CSIT-SDNT-16', 'Indira Bhandari', 'CSC111', 'A'),
(79, '2025-CSIT-SDNT-16', 'Indira Bhandari', 'MTH112', 'A'),
(80, '2025-CSIT-SDNT-16', 'Indira Bhandari', 'PHY113', 'A'),
(81, '2025-CSIT-SDNT-17', 'Jeevan Regmi', 'CSC109', 'A'),
(82, '2025-CSIT-SDNT-17', 'Jeevan Regmi', 'CSC110', 'A'),
(83, '2025-CSIT-SDNT-17', 'Jeevan Regmi', 'CSC111', 'A'),
(84, '2025-CSIT-SDNT-17', 'Jeevan Regmi', 'MTH112', 'A'),
(85, '2025-CSIT-SDNT-17', 'Jeevan Regmi', 'PHY113', 'A'),
(86, '2025-CSIT-SDNT-18', 'Kabita Subedi', 'CSC109', 'A'),
(87, '2025-CSIT-SDNT-18', 'Kabita Subedi', 'CSC110', 'A'),
(88, '2025-CSIT-SDNT-18', 'Kabita Subedi', 'CSC111', 'A'),
(89, '2025-CSIT-SDNT-18', 'Kabita Subedi', 'MTH112', 'A'),
(90, '2025-CSIT-SDNT-18', 'Kabita Subedi', 'PHY113', 'A'),
(91, '2025-CSIT-SDNT-19', 'Kiran Basnet', 'CSC109', 'A'),
(92, '2025-CSIT-SDNT-19', 'Kiran Basnet', 'CSC110', 'A'),
(93, '2025-CSIT-SDNT-19', 'Kiran Basnet', 'CSC111', 'A'),
(94, '2025-CSIT-SDNT-19', 'Kiran Basnet', 'MTH112', 'A'),
(95, '2025-CSIT-SDNT-19', 'Kiran Basnet', 'PHY113', 'A'),
(96, '2025-CSIT-SDNT-20', 'Kriti Neupane', 'CSC109', 'A'),
(97, '2025-CSIT-SDNT-20', 'Kriti Neupane', 'CSC110', 'A'),
(98, '2025-CSIT-SDNT-20', 'Kriti Neupane', 'CSC111', 'A'),
(99, '2025-CSIT-SDNT-20', 'Kriti Neupane', 'MTH112', 'A'),
(100, '2025-CSIT-SDNT-20', 'Kriti Neupane', 'PHY113', 'A'),
(101, '2025-CSIT-SDNT-21', 'Lakpa Sherpa', 'CSC109', 'A'),
(102, '2025-CSIT-SDNT-21', 'Lakpa Sherpa', 'CSC110', 'A'),
(103, '2025-CSIT-SDNT-21', 'Lakpa Sherpa', 'CSC111', 'A'),
(104, '2025-CSIT-SDNT-21', 'Lakpa Sherpa', 'MTH112', 'A'),
(105, '2025-CSIT-SDNT-21', 'Lakpa Sherpa', 'PHY113', 'A'),
(106, '2025-CSIT-SDNT-22', 'Laxmi Maharjan', 'CSC109', 'A'),
(107, '2025-CSIT-SDNT-22', 'Laxmi Maharjan', 'CSC110', 'A'),
(108, '2025-CSIT-SDNT-22', 'Laxmi Maharjan', 'CSC111', 'A'),
(109, '2025-CSIT-SDNT-22', 'Laxmi Maharjan', 'MTH112', 'A'),
(110, '2025-CSIT-SDNT-22', 'Laxmi Maharjan', 'PHY113', 'A'),
(111, '2025-CSIT-SDNT-23', 'Manish Pariyar', 'CSC109', 'A'),
(112, '2025-CSIT-SDNT-23', 'Manish Pariyar', 'CSC110', 'A'),
(113, '2025-CSIT-SDNT-23', 'Manish Pariyar', 'CSC111', 'A'),
(114, '2025-CSIT-SDNT-23', 'Manish Pariyar', 'MTH112', 'A'),
(115, '2025-CSIT-SDNT-23', 'Manish Pariyar', 'PHY113', 'A'),
(116, '2025-CSIT-SDNT-24', 'Manisha Khatri', 'CSC109', 'A'),
(117, '2025-CSIT-SDNT-24', 'Manisha Khatri', 'CSC110', 'A'),
(118, '2025-CSIT-SDNT-24', 'Manisha Khatri', 'CSC111', 'A'),
(119, '2025-CSIT-SDNT-24', 'Manisha Khatri', 'MTH112', 'A'),
(120, '2025-CSIT-SDNT-24', 'Manisha Khatri', 'PHY113', 'A'),
(121, '2025-CSIT-SDNT-25', 'Nabin Shahi', 'CSC109', 'A'),
(122, '2025-CSIT-SDNT-25', 'Nabin Shahi', 'CSC110', 'A'),
(123, '2025-CSIT-SDNT-25', 'Nabin Shahi', 'CSC111', 'A'),
(124, '2025-CSIT-SDNT-25', 'Nabin Shahi', 'MTH112', 'A'),
(125, '2025-CSIT-SDNT-25', 'Nabin Shahi', 'PHY113', 'A'),
(126, '2025-CSIT-SDNT-26', 'Namrata Koirala', 'CSC109', 'A'),
(127, '2025-CSIT-SDNT-26', 'Namrata Koirala', 'CSC110', 'A'),
(128, '2025-CSIT-SDNT-26', 'Namrata Koirala', 'CSC111', 'A'),
(129, '2025-CSIT-SDNT-26', 'Namrata Koirala', 'MTH112', 'A'),
(130, '2025-CSIT-SDNT-26', 'Namrata Koirala', 'PHY113', 'A'),
(131, '2025-CSIT-SDNT-27', 'Niraj Thakuri', 'CSC109', 'A'),
(132, '2025-CSIT-SDNT-27', 'Niraj Thakuri', 'CSC110', 'A'),
(133, '2025-CSIT-SDNT-27', 'Niraj Thakuri', 'CSC111', 'A'),
(134, '2025-CSIT-SDNT-27', 'Niraj Thakuri', 'MTH112', 'A'),
(135, '2025-CSIT-SDNT-27', 'Niraj Thakuri', 'PHY113', 'A'),
(136, '2025-CSIT-SDNT-28', 'Nisha Sapkota', 'CSC109', 'A'),
(137, '2025-CSIT-SDNT-28', 'Nisha Sapkota', 'CSC110', 'A'),
(138, '2025-CSIT-SDNT-28', 'Nisha Sapkota', 'CSC111', 'A'),
(139, '2025-CSIT-SDNT-28', 'Nisha Sapkota', 'MTH112', 'A'),
(140, '2025-CSIT-SDNT-28', 'Nisha Sapkota', 'PHY113', 'A'),
(141, '2025-CSIT-SDNT-29', 'Om Prakash Tharu', 'CSC109', 'A'),
(142, '2025-CSIT-SDNT-29', 'Om Prakash Tharu', 'CSC110', 'A'),
(143, '2025-CSIT-SDNT-29', 'Om Prakash Tharu', 'CSC111', 'A'),
(144, '2025-CSIT-SDNT-29', 'Om Prakash Tharu', 'MTH112', 'A'),
(145, '2025-CSIT-SDNT-29', 'Om Prakash Tharu', 'PHY113', 'A'),
(146, '2025-CSIT-SDNT-30', 'Parbati Bishwakarma', 'CSC109', 'A'),
(147, '2025-CSIT-SDNT-30', 'Parbati Bishwakarma', 'CSC110', 'A'),
(148, '2025-CSIT-SDNT-30', 'Parbati Bishwakarma', 'CSC111', 'A'),
(149, '2025-CSIT-SDNT-30', 'Parbati Bishwakarma', 'MTH112', 'A'),
(150, '2025-CSIT-SDNT-30', 'Parbati Bishwakarma', 'PHY113', 'A'),
(151, '2025-CSIT-SDNT-31', 'Prakash Dahal', 'CSC109', 'A'),
(152, '2025-CSIT-SDNT-31', 'Prakash Dahal', 'CSC110', 'A'),
(153, '2025-CSIT-SDNT-31', 'Prakash Dahal', 'CSC111', 'A'),
(154, '2025-CSIT-SDNT-31', 'Prakash Dahal', 'MTH112', 'A'),
(155, '2025-CSIT-SDNT-31', 'Prakash Dahal', 'PHY113', 'A'),
(156, '2025-CSIT-SDNT-32', 'Pratima Giri', 'CSC109', 'A'),
(157, '2025-CSIT-SDNT-32', 'Pratima Giri', 'CSC110', 'A'),
(158, '2025-CSIT-SDNT-32', 'Pratima Giri', 'CSC111', 'A'),
(159, '2025-CSIT-SDNT-32', 'Pratima Giri', 'MTH112', 'A'),
(160, '2025-CSIT-SDNT-32', 'Pratima Giri', 'PHY113', 'A'),
(161, '2025-CSIT-SDNT-33', 'Prem Bahadur KC', 'CSC109', 'A'),
(162, '2025-CSIT-SDNT-33', 'Prem Bahadur KC', 'CSC110', 'A'),
(163, '2025-CSIT-SDNT-33', 'Prem Bahadur KC', 'CSC111', 'A'),
(164, '2025-CSIT-SDNT-33', 'Prem Bahadur KC', 'MTH112', 'A'),
(165, '2025-CSIT-SDNT-33', 'Prem Bahadur KC', 'PHY113', 'A'),
(166, '2025-CSIT-SDNT-34', 'Priyanka Raut', 'CSC109', 'A'),
(167, '2025-CSIT-SDNT-34', 'Priyanka Raut', 'CSC110', 'A'),
(168, '2025-CSIT-SDNT-34', 'Priyanka Raut', 'CSC111', 'A'),
(169, '2025-CSIT-SDNT-34', 'Priyanka Raut', 'MTH112', 'A'),
(170, '2025-CSIT-SDNT-34', 'Priyanka Raut', 'PHY113', 'A'),
(171, '2025-CSIT-SDNT-35', 'Rabina Malla', 'CSC109', 'A'),
(172, '2025-CSIT-SDNT-35', 'Rabina Malla', 'CSC110', 'A'),
(173, '2025-CSIT-SDNT-35', 'Rabina Malla', 'CSC111', 'A'),
(174, '2025-CSIT-SDNT-35', 'Rabina Malla', 'MTH112', 'A'),
(175, '2025-CSIT-SDNT-35', 'Rabina Malla', 'PHY113', 'A'),
(176, '2025-CSIT-SDNT-36', 'Rahul Lama', 'CSC109', 'A'),
(177, '2025-CSIT-SDNT-36', 'Rahul Lama', 'CSC110', 'A'),
(178, '2025-CSIT-SDNT-36', 'Rahul Lama', 'CSC111', 'A'),
(179, '2025-CSIT-SDNT-36', 'Rahul Lama', 'MTH112', 'A'),
(180, '2025-CSIT-SDNT-36', 'Rahul Lama', 'PHY113', 'A'),
(181, '2025-CSIT-SDNT-37', 'Rajendra Hamal', 'CSC109', 'B'),
(182, '2025-CSIT-SDNT-37', 'Rajendra Hamal', 'CSC110', 'B'),
(183, '2025-CSIT-SDNT-37', 'Rajendra Hamal', 'CSC111', 'B'),
(184, '2025-CSIT-SDNT-37', 'Rajendra Hamal', 'MTH112', 'B'),
(185, '2025-CSIT-SDNT-37', 'Rajendra Hamal', 'PHY113', 'B'),
(186, '2025-CSIT-SDNT-38', 'Rama Pun', 'CSC109', 'B'),
(187, '2025-CSIT-SDNT-38', 'Rama Pun', 'CSC110', 'B'),
(188, '2025-CSIT-SDNT-38', 'Rama Pun', 'CSC111', 'B'),
(189, '2025-CSIT-SDNT-38', 'Rama Pun', 'MTH112', 'B'),
(190, '2025-CSIT-SDNT-38', 'Rama Pun', 'PHY113', 'B'),
(191, '2025-CSIT-SDNT-39', 'Ramesh Dhami', 'CSC109', 'B'),
(192, '2025-CSIT-SDNT-39', 'Ramesh Dhami', 'CSC110', 'B'),
(193, '2025-CSIT-SDNT-39', 'Ramesh Dhami', 'CSC111', 'B'),
(194, '2025-CSIT-SDNT-39', 'Ramesh Dhami', 'MTH112', 'B'),
(195, '2025-CSIT-SDNT-39', 'Ramesh Dhami', 'PHY113', 'B'),
(196, '2025-CSIT-SDNT-40', 'Ranju Oli', 'CSC109', 'B'),
(197, '2025-CSIT-SDNT-40', 'Ranju Oli', 'CSC110', 'B'),
(198, '2025-CSIT-SDNT-40', 'Ranju Oli', 'CSC111', 'B'),
(199, '2025-CSIT-SDNT-40', 'Ranju Oli', 'MTH112', 'B'),
(200, '2025-CSIT-SDNT-40', 'Ranju Oli', 'PHY113', 'B'),
(201, '2025-CSIT-SDNT-41', 'Ravi Jung Rayamajhi', 'CSC109', 'B'),
(202, '2025-CSIT-SDNT-41', 'Ravi Jung Rayamajhi', 'CSC110', 'B'),
(203, '2025-CSIT-SDNT-41', 'Ravi Jung Rayamajhi', 'CSC111', 'B'),
(204, '2025-CSIT-SDNT-41', 'Ravi Jung Rayamajhi', 'MTH112', 'B'),
(205, '2025-CSIT-SDNT-41', 'Ravi Jung Rayamajhi', 'PHY113', 'B'),
(206, '2025-CSIT-SDNT-42', 'Rekha Bista', 'CSC109', 'B'),
(207, '2025-CSIT-SDNT-42', 'Rekha Bista', 'CSC110', 'B'),
(208, '2025-CSIT-SDNT-42', 'Rekha Bista', 'CSC111', 'B'),
(209, '2025-CSIT-SDNT-42', 'Rekha Bista', 'MTH112', 'B'),
(210, '2025-CSIT-SDNT-42', 'Rekha Bista', 'PHY113', 'B'),
(211, '2025-CSIT-SDNT-43', 'Rohan Prajapati', 'CSC109', 'B'),
(212, '2025-CSIT-SDNT-43', 'Rohan Prajapati', 'CSC110', 'B'),
(213, '2025-CSIT-SDNT-43', 'Rohan Prajapati', 'CSC111', 'B'),
(214, '2025-CSIT-SDNT-43', 'Rohan Prajapati', 'MTH112', 'B'),
(215, '2025-CSIT-SDNT-43', 'Rohan Prajapati', 'PHY113', 'B'),
(216, '2025-CSIT-SDNT-44', 'Rojina Shah', 'CSC109', 'B'),
(217, '2025-CSIT-SDNT-44', 'Rojina Shah', 'CSC110', 'B'),
(218, '2025-CSIT-SDNT-44', 'Rojina Shah', 'CSC111', 'B'),
(219, '2025-CSIT-SDNT-44', 'Rojina Shah', 'MTH112', 'B'),
(220, '2025-CSIT-SDNT-44', 'Rojina Shah', 'PHY113', 'B'),
(221, '2025-CSIT-SDNT-45', 'Sabin Pokharel', 'CSC109', 'B'),
(222, '2025-CSIT-SDNT-45', 'Sabin Pokharel', 'CSC110', 'B'),
(223, '2025-CSIT-SDNT-45', 'Sabin Pokharel', 'CSC111', 'B'),
(224, '2025-CSIT-SDNT-45', 'Sabin Pokharel', 'MTH112', 'B'),
(225, '2025-CSIT-SDNT-45', 'Sabin Pokharel', 'PHY113', 'B'),
(226, '2025-CSIT-SDNT-46', 'Sabina Lamichhane', 'CSC109', 'B'),
(227, '2025-CSIT-SDNT-46', 'Sabina Lamichhane', 'CSC110', 'B'),
(228, '2025-CSIT-SDNT-46', 'Sabina Lamichhane', 'CSC111', 'B'),
(229, '2025-CSIT-SDNT-46', 'Sabina Lamichhane', 'MTH112', 'B'),
(230, '2025-CSIT-SDNT-46', 'Sabina Lamichhane', 'PHY113', 'B'),
(231, '2025-CSIT-SDNT-47', 'Sachin Budhathoki', 'CSC109', 'B'),
(232, '2025-CSIT-SDNT-47', 'Sachin Budhathoki', 'CSC110', 'B'),
(233, '2025-CSIT-SDNT-47', 'Sachin Budhathoki', 'CSC111', 'B'),
(234, '2025-CSIT-SDNT-47', 'Sachin Budhathoki', 'MTH112', 'B'),
(235, '2025-CSIT-SDNT-47', 'Sachin Budhathoki', 'PHY113', 'B'),
(236, '2025-CSIT-SDNT-48', 'Samikshya Pandey', 'CSC109', 'B'),
(237, '2025-CSIT-SDNT-48', 'Samikshya Pandey', 'CSC110', 'B'),
(238, '2025-CSIT-SDNT-48', 'Samikshya Pandey', 'CSC111', 'B'),
(239, '2025-CSIT-SDNT-48', 'Samikshya Pandey', 'MTH112', 'B'),
(240, '2025-CSIT-SDNT-48', 'Samikshya Pandey', 'PHY113', 'B'),
(241, '2025-CSIT-SDNT-49', 'Sandip Bajracharya', 'CSC109', 'B'),
(242, '2025-CSIT-SDNT-49', 'Sandip Bajracharya', 'CSC110', 'B'),
(243, '2025-CSIT-SDNT-49', 'Sandip Bajracharya', 'CSC111', 'B'),
(244, '2025-CSIT-SDNT-49', 'Sandip Bajracharya', 'MTH112', 'B'),
(245, '2025-CSIT-SDNT-49', 'Sandip Bajracharya', 'PHY113', 'B'),
(246, '2025-CSIT-SDNT-50', 'Sangita Khatiwada', 'CSC109', 'B'),
(247, '2025-CSIT-SDNT-50', 'Sangita Khatiwada', 'CSC110', 'B'),
(248, '2025-CSIT-SDNT-50', 'Sangita Khatiwada', 'CSC111', 'B'),
(249, '2025-CSIT-SDNT-50', 'Sangita Khatiwada', 'MTH112', 'B'),
(250, '2025-CSIT-SDNT-50', 'Sangita Khatiwada', 'PHY113', 'B'),
(251, '2025-CSIT-SDNT-51', 'Santosh Devkota', 'CSC109', 'B'),
(252, '2025-CSIT-SDNT-51', 'Santosh Devkota', 'CSC110', 'B'),
(253, '2025-CSIT-SDNT-51', 'Santosh Devkota', 'CSC111', 'B'),
(254, '2025-CSIT-SDNT-51', 'Santosh Devkota', 'MTH112', 'B'),
(255, '2025-CSIT-SDNT-51', 'Santosh Devkota', 'PHY113', 'B'),
(256, '2025-CSIT-SDNT-52', 'Saraswati Acharya', 'CSC109', 'B'),
(257, '2025-CSIT-SDNT-52', 'Saraswati Acharya', 'CSC110', 'B'),
(258, '2025-CSIT-SDNT-52', 'Saraswati Acharya', 'CSC111', 'B'),
(259, '2025-CSIT-SDNT-52', 'Saraswati Acharya', 'MTH112', 'B'),
(260, '2025-CSIT-SDNT-52', 'Saraswati Acharya', 'PHY113', 'B'),
(261, '2025-CSIT-SDNT-53', 'Saugat Kunwar', 'CSC109', 'B'),
(262, '2025-CSIT-SDNT-53', 'Saugat Kunwar', 'CSC110', 'B'),
(263, '2025-CSIT-SDNT-53', 'Saugat Kunwar', 'CSC111', 'B'),
(264, '2025-CSIT-SDNT-53', 'Saugat Kunwar', 'MTH112', 'B'),
(265, '2025-CSIT-SDNT-53', 'Saugat Kunwar', 'PHY113', 'B'),
(266, '2025-CSIT-SDNT-54', 'Shanti Gautam', 'CSC109', 'B'),
(267, '2025-CSIT-SDNT-54', 'Shanti Gautam', 'CSC110', 'B'),
(268, '2025-CSIT-SDNT-54', 'Shanti Gautam', 'CSC111', 'B'),
(269, '2025-CSIT-SDNT-54', 'Shanti Gautam', 'MTH112', 'B'),
(270, '2025-CSIT-SDNT-54', 'Shanti Gautam', 'PHY113', 'B'),
(271, '2025-CSIT-SDNT-55', 'Shiva Rijal', 'CSC109', 'B'),
(272, '2025-CSIT-SDNT-55', 'Shiva Rijal', 'CSC110', 'B'),
(273, '2025-CSIT-SDNT-55', 'Shiva Rijal', 'CSC111', 'B'),
(274, '2025-CSIT-SDNT-55', 'Shiva Rijal', 'MTH112', 'B'),
(275, '2025-CSIT-SDNT-55', 'Shiva Rijal', 'PHY113', 'B'),
(276, '2025-CSIT-SDNT-56', 'Shristi Bogati', 'CSC109', 'B'),
(277, '2025-CSIT-SDNT-56', 'Shristi Bogati', 'CSC110', 'B'),
(278, '2025-CSIT-SDNT-56', 'Shristi Bogati', 'CSC111', 'B'),
(279, '2025-CSIT-SDNT-56', 'Shristi Bogati', 'MTH112', 'B'),
(280, '2025-CSIT-SDNT-56', 'Shristi Bogati', 'PHY113', 'B'),
(281, '2025-CSIT-SDNT-57', 'Sita Dangol', 'CSC109', 'B'),
(282, '2025-CSIT-SDNT-57', 'Sita Dangol', 'CSC110', 'B'),
(283, '2025-CSIT-SDNT-57', 'Sita Dangol', 'CSC111', 'B'),
(284, '2025-CSIT-SDNT-57', 'Sita Dangol', 'MTH112', 'B'),
(285, '2025-CSIT-SDNT-57', 'Sita Dangol', 'PHY113', 'B'),
(286, '2025-CSIT-SDNT-58', 'Subash Pathak', 'CSC109', 'B'),
(287, '2025-CSIT-SDNT-58', 'Subash Pathak', 'CSC110', 'B'),
(288, '2025-CSIT-SDNT-58', 'Subash Pathak', 'CSC111', 'B'),
(289, '2025-CSIT-SDNT-58', 'Subash Pathak', 'MTH112', 'B'),
(290, '2025-CSIT-SDNT-58', 'Subash Pathak', 'PHY113', 'B'),
(291, '2025-CSIT-SDNT-59', 'Sudip Chand', 'CSC109', 'B'),
(292, '2025-CSIT-SDNT-59', 'Sudip Chand', 'CSC110', 'B'),
(293, '2025-CSIT-SDNT-59', 'Sudip Chand', 'CSC111', 'B'),
(294, '2025-CSIT-SDNT-59', 'Sudip Chand', 'MTH112', 'B'),
(295, '2025-CSIT-SDNT-59', 'Sudip Chand', 'PHY113', 'B'),
(296, '2025-CSIT-SDNT-60', 'Sunita Shrestha', 'CSC109', 'B'),
(297, '2025-CSIT-SDNT-60', 'Sunita Shrestha', 'CSC110', 'B'),
(298, '2025-CSIT-SDNT-60', 'Sunita Shrestha', 'CSC111', 'B'),
(299, '2025-CSIT-SDNT-60', 'Sunita Shrestha', 'MTH112', 'B'),
(300, '2025-CSIT-SDNT-60', 'Sunita Shrestha', 'PHY113', 'B'),
(301, '2025-CSIT-SDNT-61', 'Suraj Bhujel', 'CSC109', 'B'),
(302, '2025-CSIT-SDNT-61', 'Suraj Bhujel', 'CSC110', 'B'),
(303, '2025-CSIT-SDNT-61', 'Suraj Bhujel', 'CSC111', 'B'),
(304, '2025-CSIT-SDNT-61', 'Suraj Bhujel', 'MTH112', 'B'),
(305, '2025-CSIT-SDNT-61', 'Suraj Bhujel', 'PHY113', 'B'),
(306, '2025-CSIT-SDNT-62', 'Sushila Tiwari', 'CSC109', 'B'),
(307, '2025-CSIT-SDNT-62', 'Sushila Tiwari', 'CSC110', 'B'),
(308, '2025-CSIT-SDNT-62', 'Sushila Tiwari', 'CSC111', 'B'),
(309, '2025-CSIT-SDNT-62', 'Sushila Tiwari', 'MTH112', 'B'),
(310, '2025-CSIT-SDNT-62', 'Sushila Tiwari', 'PHY113', 'B'),
(311, '2025-CSIT-SDNT-63', 'Tek Bahadur Chaudhary', 'CSC109', 'B'),
(312, '2025-CSIT-SDNT-63', 'Tek Bahadur Chaudhary', 'CSC110', 'B'),
(313, '2025-CSIT-SDNT-63', 'Tek Bahadur Chaudhary', 'CSC111', 'B'),
(314, '2025-CSIT-SDNT-63', 'Tek Bahadur Chaudhary', 'MTH112', 'B'),
(315, '2025-CSIT-SDNT-63', 'Tek Bahadur Chaudhary', 'PHY113', 'B'),
(316, '2025-CSIT-SDNT-64', 'Uma Gyawali', 'CSC109', 'B'),
(317, '2025-CSIT-SDNT-64', 'Uma Gyawali', 'CSC110', 'B'),
(318, '2025-CSIT-SDNT-64', 'Uma Gyawali', 'CSC111', 'B'),
(319, '2025-CSIT-SDNT-64', 'Uma Gyawali', 'MTH112', 'B'),
(320, '2025-CSIT-SDNT-64', 'Uma Gyawali', 'PHY113', 'B'),
(321, '2025-CSIT-SDNT-65', 'Umesh Yadav', 'CSC109', 'B'),
(322, '2025-CSIT-SDNT-65', 'Umesh Yadav', 'CSC110', 'B'),
(323, '2025-CSIT-SDNT-65', 'Umesh Yadav', 'CSC111', 'B'),
(324, '2025-CSIT-SDNT-65', 'Umesh Yadav', 'MTH112', 'B'),
(325, '2025-CSIT-SDNT-65', 'Umesh Yadav', 'PHY113', 'B'),
(326, '2025-CSIT-SDNT-66', 'Usha Khadgi', 'CSC109', 'B'),
(327, '2025-CSIT-SDNT-66', 'Usha Khadgi', 'CSC110', 'B'),
(328, '2025-CSIT-SDNT-66', 'Usha Khadgi', 'CSC111', 'B'),
(329, '2025-CSIT-SDNT-66', 'Usha Khadgi', 'MTH112', 'B'),
(330, '2025-CSIT-SDNT-66', 'Usha Khadgi', 'PHY113', 'B'),
(331, '2025-CSIT-SDNT-67', 'Vivek Puri', 'CSC109', 'B'),
(332, '2025-CSIT-SDNT-67', 'Vivek Puri', 'CSC110', 'B'),
(333, '2025-CSIT-SDNT-67', 'Vivek Puri', 'CSC111', 'B'),
(334, '2025-CSIT-SDNT-67', 'Vivek Puri', 'MTH112', 'B'),
(335, '2025-CSIT-SDNT-67', 'Vivek Puri', 'PHY113', 'B'),
(336, '2025-CSIT-SDNT-68', 'Yamuna Joshi', 'CSC109', 'B'),
(337, '2025-CSIT-SDNT-68', 'Yamuna Joshi', 'CSC110', 'B'),
(338, '2025-CSIT-SDNT-68', 'Yamuna Joshi', 'CSC111', 'B'),
(339, '2025-CSIT-SDNT-68', 'Yamuna Joshi', 'MTH112', 'B'),
(340, '2025-CSIT-SDNT-68', 'Yamuna Joshi', 'PHY113', 'B'),
(341, '2025-CSIT-SDNT-69', 'Yubraj Singh', 'CSC109', 'B'),
(342, '2025-CSIT-SDNT-69', 'Yubraj Singh', 'CSC110', 'B'),
(343, '2025-CSIT-SDNT-69', 'Yubraj Singh', 'CSC111', 'B'),
(344, '2025-CSIT-SDNT-69', 'Yubraj Singh', 'MTH112', 'B'),
(345, '2025-CSIT-SDNT-69', 'Yubraj Singh', 'PHY113', 'B'),
(346, '2025-CSIT-SDNT-70', 'Yunisha Baniya', 'CSC109', 'B'),
(347, '2025-CSIT-SDNT-70', 'Yunisha Baniya', 'CSC110', 'B'),
(348, '2025-CSIT-SDNT-70', 'Yunisha Baniya', 'CSC111', 'B'),
(349, '2025-CSIT-SDNT-70', 'Yunisha Baniya', 'MTH112', 'B'),
(350, '2025-CSIT-SDNT-70', 'Yunisha Baniya', 'PHY113', 'B'),
(351, '2025-CSIT-SDNT-71', 'Zenisha Rana', 'CSC109', 'B'),
(352, '2025-CSIT-SDNT-71', 'Zenisha Rana', 'CSC110', 'B'),
(353, '2025-CSIT-SDNT-71', 'Zenisha Rana', 'CSC111', 'B'),
(354, '2025-CSIT-SDNT-71', 'Zenisha Rana', 'MTH112', 'B'),
(355, '2025-CSIT-SDNT-71', 'Zenisha Rana', 'PHY113', 'B'),
(356, '2025-CSIT-SDNT-72', 'Zenit Ghimire', 'CSC109', 'B'),
(357, '2025-CSIT-SDNT-72', 'Zenit Ghimire', 'CSC110', 'B'),
(358, '2025-CSIT-SDNT-72', 'Zenit Ghimire', 'CSC111', 'B'),
(359, '2025-CSIT-SDNT-72', 'Zenit Ghimire', 'MTH112', 'B'),
(360, '2025-CSIT-SDNT-72', 'Zenit Ghimire', 'PHY113', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `course_code` varchar(10) NOT NULL,
  `section` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_id`, `teacher_name`, `course_code`, `section`) VALUES
(1, '2025-TCHR-1', 'Ramesh Kumar Poudel', 'CSC109', 'A'),
(2, '2025-TCHR-1', 'Ramesh Kumar Poudel', 'CSC109', 'B'),
(3, '2025-TCHR-1', 'Ramesh Kumar Poudel', 'CSC110', 'A'),
(4, '2025-TCHR-1', 'Ramesh Kumar Poudel', 'CSC110', 'B'),
(5, '2025-TCHR-2', 'Naresh Bhattarai', 'CSC111', 'A'),
(6, '2025-TCHR-2', 'Naresh Bhattarai', 'CSC111', 'B'),
(7, '2025-TCHR-3', 'Govinda Raj Shrestha', 'MTH112', 'A'),
(8, '2025-TCHR-3', 'Govinda Raj Shrestha', 'MTH112', 'B'),
(9, '2025-TCHR-3', 'Govinda Raj Shrestha', 'PHY113', 'A'),
(10, '2025-TCHR-3', 'Govinda Raj Shrestha', 'PHY113', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uuid` varchar(50) NOT NULL,
  `hashed_secret` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`uuid`, `hashed_secret`, `role`) VALUES
('2025-ADMIN-0', '$2y$10$lolJGjMN04kyvVrEg46W8eMY5aGpPNc8RWK473xzfMuxK.MoAHZSq', 'admin'),
('2025-TCHR-1', '$2y$10$mdFmjFO/8hLnpHgGj8mYMeLYAFN/EHXCjZFCS7sgsxDnfs8BFD2g.', 'teacher'),
('2025-TCHR-2', '$2y$10$wq/QinhbIP6lQL3IAUBp9.CCSrm0sJSkgUMF.iuhZSBxhju0B2I9m', 'teacher'),
('2025-TCHR-3', '$2y$10$OygKdMGXoL7vQABaRDerxuvlr6KjJMNDfSYXv9FymUelhsRmY62sq', 'teacher');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_code`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uuid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=361;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON UPDATE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
