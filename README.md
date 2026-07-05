# Exam Seat Allocation System

An automated web-based solution designed to streamline the seating arrangement process for examinations. This system helps educational institutions automatically assign seats to students based on hall capacities, prevent examination malpractice by mixing different courses, and eliminate double-booking conflicts.

## 🚀 Features
* **Student & Course Management:** Easily import and manage student enrollment records and exam schedules.
* **Exam Hall Configuration:** Set up exam halls with specific row, column, and total seating capacities.
* **Automated Allocation Algorithm:** Smart seating generation that ensures students from the same course do not sit directly next to each other.
* **Seat Plan Generation:** Clear, exportable seating charts and attendance lists for invigilators and students.

## 🛠️ Tech Stack
* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP
* **Database:** MySQL (via XAMPP phpMyAdmin)

## 💻 How to Run the Project
1. Download or clone this repository as a `.zip` file.
2. Extract the folder and move it into your XAMPP installation directory: `C:\xampp\htdocs\`
3. Open the **XAMPP Control Panel** and start both **Apache** and **MySQL**.
4. Open your browser and go to `http://localhost/phpmyadmin/` to create a new database.
5. Import your project's `.sql` database file (if included) into phpMyAdmin.
6. Open a new tab in your browser and visit: `http://localhost/exam-seat-allocation/`

## 📄 License
This project is licensed under the MIT License - see the LICENSE file for details.
