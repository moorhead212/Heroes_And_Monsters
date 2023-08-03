<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Process Order Script is Executed";
exit();



// process_order.php

// Include the database connection file
require_once "db_connection.php";

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the order data from the request body
    $orderData = json_decode(file_get_contents("php://input"), true);

    // Validate and process the order data
    if (is_array($orderData) && !empty($orderData)) {
        $response = processOrder($orderData);
    } else {
        $response = array("success" => false, "message" => "Invalid order data.");
    }

    // Send the response back to the client as JSON
    header("Content-Type: application/json");
    echo json_encode($response);
}

function processOrder($orderData)
{
    global $conn;

    // Start a transaction for atomicity
    mysqli_begin_transaction($conn);

    try {
        // Loop through each order item
        foreach ($orderData as $orderItem) {
            // Extract SKU and quantity from the order item
            $sku = $orderItem['sku'];
            $quantity = $orderItem['quantity'];

            // Check if the warehouse has enough quantity for the SKU
            $query = "SELECT quantity FROM inventory WHERE sku = ? AND stored_at_id = ? AND stored_at_type = 'warehouse'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $sku, $_SESSION["store_id"]);
            $stmt->execute();
            $stmt->bind_result($warehouseQuantity);

            if ($stmt->fetch()) {
                // If the warehouse has enough quantity, update the inventory for both warehouse and store
                if ($warehouseQuantity >= $quantity) {
                    // Update warehouse inventory
                    $query = "UPDATE inventory SET quantity = quantity - ? WHERE sku = ? AND stored_at_id = ? AND stored_at_type = 'warehouse'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("isi", $quantity, $sku, $_SESSION["store_id"]);
                    $stmt->execute();

                    // Check if the item already exists in the store inventory
                    $query = "SELECT quantity FROM inventory WHERE sku = ? AND stored_at_id = ? AND stored_at_type = 'store'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("si", $sku, $_SESSION["store_id"]);
                    $stmt->execute();
                    $stmt->bind_result($storeQuantity);

                    if ($stmt->fetch()) {
                        // If the item exists in the store inventory, update the quantity
                        $query = "UPDATE inventory SET quantity = quantity + ? WHERE sku = ? AND stored_at_id = ? AND stored_at_type = 'store'";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("isi", $quantity, $sku, $_SESSION["store_id"]);
                        $stmt->execute();
                    } else {
                        // If the item does not exist in the store inventory, insert a new record
                        $query = "INSERT INTO inventory (sku, quantity, stored_at_id, stored_at_type) VALUES (?, ?, ?, 'store')";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sii", $sku, $quantity, $_SESSION["store_id"]);
                        $stmt->execute();
                    }

                    // Commit the transaction
                    mysqli_commit($conn);

                    // Set success response
                    $response["success"] = true;
                    $response["message"] = "Order processed successfully.";
                } else {
                    // If the warehouse does not have enough quantity, rollback the transaction
                    mysqli_rollback($conn);

                    $response["success"] = false;
                    $response["message"] = "Not enough quantity available in the warehouse for SKU: " . $sku;
                    return $response;
                }
            } else {
                // If the SKU does not exist in the warehouse inventory, rollback the transaction
                mysqli_rollback($conn);

                $response["success"] = false;
                $response["message"] = "Invalid SKU: " . $sku;
                return $response;
            }
        }
    } catch (Exception $e) {
        // If any exception occurs, rollback the transaction
        mysqli_rollback($conn);

        $response["success"] = false;
        $response["message"] = "An error occurred while processing the order.";
        return $response;
    }

    return $response;
}
?>
