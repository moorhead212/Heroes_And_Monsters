<?php
require_once "db_connection.php";

// Function to register a new employee
function registerEmployee($employee_id, $fname, $lname, $works_at, $email, $phone_num, $bdate, $password, $username) {
    global $conn;

    // Hash the password using password_hash() before storing in the database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and execute the SQL query to insert the employee data
    $stmt = $conn->prepare("INSERT INTO employee (employee_id, fname, lname, works_at, email, phone_num, bdate, password, username)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $employee_id, $fname, $lname, $works_at, $email, $phone_num, $bdate, $hashedPassword, $username);
    
    if ($stmt->execute()) {
        return true; // Registration successful
    } else {
        return false; // Registration failed
    }
}

// Handle form submission and register the employee
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employee_id = $_POST["employee_id"];
    $fname = $_POST["fname"];
    $lname = $_POST["lname"];
    $works_at = $_POST["works_at"];
    $email = $_POST["email"];
    $phone_num = $_POST["phone_num"];
    $bdate = $_POST["bdate"];
    $password = $_POST["password"];
    $username = $_POST["username"];

    // Call the registerEmployee function to insert the data into the database
    if (registerEmployee($employee_id, $fname, $lname, $works_at, $email, $phone_num, $bdate, $password, $username)) {
        // Registration successful, redirect to login page
        header("Location: index.html");
        exit();
    } else {
        // Registration failed, show an error message
        $registrationError = "Registration failed. Please try again.";
    }
}
?>
