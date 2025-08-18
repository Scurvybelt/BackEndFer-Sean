CREATE DATABASE boda_invitaciones;
use boda_invitaciones; 

CREATE TABLE IF NOT EXISTS invitados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arrivalDay VARCHAR(20),
    attendance VARCHAR(5) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    songSuggestion VARCHAR(255)
);