<?php
require_once "db_connection.php";

// Function to register a new user
function registerUser($username, $fname, $lname, $address, $email, $bdate, $phone_num, $password, $store_choice) {
    global $conn;

    // Check if the username already exists in the database
    $usernameExists = doesUsernameExist($username);
    if ($usernameExists) {
        return "Username already exists.";
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database
    $sql = "INSERT INTO customer (username, fname, lname, address, email_address, bdate, phone_num, password, store_choice)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $username, $fname, $lname, $address, $email, $bdate, $phone_num, $hashedPassword, $store_choice);

    if ($stmt->execute()) {
        return "Registration successful.";
    } else {
        return "Registration failed. Error: " . $conn->error;
    }
}

// Function to check if the username already exists in the database
function doesUsernameExist($username) {
    global $conn;

    $sql = "SELECT * FROM customer WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $fname = $_POST["fname"];
    $lname = $_POST["lname"];
    $address = $_POST["address"];
    $email = $_POST["email_address"];
    $bdate = $_POST["bdate"];
    $phone_num = $_POST["phone_num"];
    $password = $_POST["password"];
    $store_choice = $_POST["store_choice"];
    echo "Store Choice: " . $store_choice; // Debugging statement

    if (registerUser($username, $fname, $lname, $address, $email, $bdate, $phone_num, $password, $store_choice)) {
        // Registration successful, redirect to login page
        header("Location: index.html");
        exit();
    } else {
        // Registration failed, show an error message
        $registrationError = "Registration failed. Please try again.";
    }
}

?>