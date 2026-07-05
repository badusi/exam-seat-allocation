SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table structure for table `admins`
CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `exam_schedules`
CREATE TABLE `exam_schedules` (
  `id` int(11) NOT NULL,
  `hall_name` varchar(255) NOT NULL,
  `exam_period` enum('morning','afternoon','evening') NOT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `departments` text NOT NULL COMMENT 'JSON array of departments assigned to this hall for this period',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `department_levels` text DEFAULT NULL COMMENT 'JSON object mapping departments to their selected levels'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `halls`
CREATE TABLE `halls` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `venue` text NOT NULL,
  `total_seats` int(11) NOT NULL CHECK (`total_seats` > 0),
  `occupied_seats` int(11) DEFAULT 0 CHECK (`occupied_seats` >= 0),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `sessions`
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_type` enum('student','admin') NOT NULL,
  `user_id` int(11) NOT NULL,
  `data` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `students`
CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `matric_number` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'student',
  `department` varchar(100) NOT NULL,
  `level` enum('ND 1','ND 2','HND 1','HND 2') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `student_allocations`
CREATE TABLE `student_allocations` (
  `id` int(11) NOT NULL,
  `matric_number` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `level` enum('ND 1','ND 2','HND 1','HND 2') DEFAULT NULL,
  `hall_name` varchar(255) NOT NULL,
  `venue` text NOT NULL,
  `seat_number` int(11) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `exam_period` enum('morning','afternoon','evening') DEFAULT 'morning',
  `exam_date` date DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `student_searches`
CREATE TABLE `student_searches` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `search_term` varchar(255) NOT NULL,
  `search_type` enum('matric','name') NOT NULL,
  `search_result` text DEFAULT NULL COMMENT 'JSON result of the search',
  `searched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admins_email` (`email`);

ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_schedules_hall` (`hall_name`),
  ADD KEY `idx_exam_schedules_period` (`exam_period`),
  ADD KEY `idx_exam_schedules_date` (`exam_date`);

ALTER TABLE `halls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_halls_name` (`name`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sessions_expires` (`expires_at`);

ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matric_number` (`matric_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_students_matric` (`matric_number`),
  ADD KEY `idx_students_email` (`email`);

ALTER TABLE `student_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matric_number` (`matric_number`),
  ADD UNIQUE KEY `unique_hall_seat` (`hall_name`,`seat_number`),
  ADD KEY `idx_student_allocations_matric` (`matric_number`),
  ADD KEY `idx_student_allocations_hall` (`hall_name`),
  ADD KEY `idx_student_allocations_department` (`department`),
  ADD KEY `idx_allocations_period` (`exam_period`),
  ADD KEY `idx_allocations_date` (`exam_date`),
  ADD KEY `idx_allocations_schedule` (`schedule_id`);

ALTER TABLE `student_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_searches_admin` (`admin_id`),
  ADD KEY `idx_student_searches_term` (`search_term`),
  ADD KEY `idx_student_searches_date` (`searched_at`);

-- Auto-increment
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `exam_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `halls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `student_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `student_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign key constraints
ALTER TABLE `exam_schedules`
  ADD CONSTRAINT `fk_exam_schedules_hall` FOREIGN KEY (`hall_name`) REFERENCES `halls` (`name`) ON DELETE CASCADE;

ALTER TABLE `student_allocations`
  ADD CONSTRAINT `fk_allocations_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `exam_schedules` (`id`) ON DELETE SET NULL;

ALTER TABLE `student_searches`
  ADD CONSTRAINT `fk_student_searches_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

COMMIT;