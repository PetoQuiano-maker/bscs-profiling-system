CREATE DATABASE IF NOT EXISTS bscs_profiling;

USE bscs_profiling;

CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(10) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    extension_name VARCHAR(10),
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    year_level INT NOT NULL,
    permanent_address TEXT NOT NULL,
    birthday DATE NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    citizenship VARCHAR(50) NOT NULL,
    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed') NOT NULL
);
