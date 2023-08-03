<?php
require_once "db_connection.php";

// Function to verify customer login
function verifyCustomerLogin($username, $password) {
    global $conn;

    // Check if the username exists in the customers table
    $sql = "SELECT * FROM customer WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashedPassword = $row['password'];

        // Verify the password using password_verify()
        if (password_verify($password, $hashedPassword)) {
            return "customer"; // Login successful for customers
        }
    }

    return false; // Login failed
}

// Function to verify employee login
function verifyEmployeeLogin($username, $password) {
    global $conn;

    // Check if the username exists in the employees table
    $sql = "SELECT * FROM employee WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashedPassword = $row['password'];

        // Verify the password using password_verify()
        if (password_verify($password, $hashedPassword)) {
            return "employee"; // Login successful for employees
        }
    }

    return false; // Login failed
}

// Function to get the store_id for the logged-in employee
function getStoreIdForEmployee($username) {
    global $conn;

    // Check if the employee exists in the employees table and get the store_id
    $sql = "SELECT works_at FROM employee WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['works_at']; // Return the store_id for the employee
    }

    return null; // Store_id not found
}

// Function to get the store_id for the logged-in employee
function getStoreIdForCustomer($username) {
    global $conn;

    // Check if the employee exists in the employees table and get the store_id
    $sql = "SELECT store_choice FROM customer WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['store_choice']; // Return the store_id for the employee
    }

    return null; // Store_id not found
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $loginAs = isset($_POST["employee"]) ? "employee" : "customer";

    if (($loginAs === "customer" && verifyCustomerLogin($username, $password)) || ($loginAs === "employee" && verifyEmployeeLogin($username, $password))) {
        // Start a session and store the username, login type (customer or employee), and store_id in it
        session_start();
        $_SESSION["username"] = $username;
        $_SESSION["login_type"] = $loginAs;

        if ($loginAs === "customer") {
            // Redirect customers to the store page after successful login
            $store_choice = getStoreIdForCustomer($username);
            $_SESSION["store_id"] = $store_choice;

            header("Location: store.php");
            exit();
         } elseif ($loginAs === "employee") {
                // Get the store_id for the logged-in employee
                $store_id = getStoreIdForEmployee($username);
                $_SESSION["store_id"] = $store_id; // Store the store_id in the session
            
                // Redirect employees to the inventory page after successful login
                header("Location: inventory.php");
                exit();
            }
    } else {
        // Login failed, show an error message
        $loginError = "Invalid username or password.";
    }
}
?>
