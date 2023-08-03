<?php
require_once "db_connection.php";

session_start();

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the restock item IDs and quantities from the form data
    $itemIds = $_POST['restock_item_id'];
    $quantities = $_POST['restock_qty'];

    // Validate and process the order data
    if (is_array($itemIds) && is_array($quantities) && !empty($itemIds) && !empty($quantities)) {
        $orderData = [];
        foreach ($itemIds as $index => $itemId) {
            $quantity = (int) $quantities[$index];
            if ($itemId !== "" && $quantity > 0) {
                $orderData[] = ['sku' => $itemId, 'quantity' => $quantity];
            }
        }

        if (!empty($orderData)) {
            $response = processOrder($orderData);
        } else {
            $response = array("success" => false, "message" => "Invalid order data.");
        }
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
            $stmt->fetch();
            $stmt->close(); // Close the prepared statement to avoid the "Commands out of sync" error

            if ($warehouseQuantity !== null) {
                // If the warehouse has enough quantity, update the inventory for both warehouse and store
                if ($warehouseQuantity >= $quantity) {
                    // Update warehouse inventory
                    $query = "UPDATE inventory SET quantity = quantity - ? WHERE sku = ? AND stored_at_id = ? AND stored_at_type = 'warehouse'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("isi", $quantity, $sku, $_SESSION["store_id"]);
                    $stmt->execute();
                    $stmt->close(); // Close the prepared statement

                    // Check if the item already exists in the store inventory
                    $query = "SELECT quantity FROM inventory WHERE sku = ? AND stored_at_id = ? AND stored_at_type = 'store'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("si", $sku, $_SESSION["store_id"]);
                    $stmt->execute();
                    $stmt->bind_result($storeQuantity);
                    $stmt->fetch();
                    $stmt->close(); // Close the prepared statement

                    if ($storeQuantity !== null) {
                        // If the item exists in the store inventory, update the quantity
                        $query = "UPDATE inventory SET quantity = quantity + ? WHERE sku = ? AND stored_at_id = ? AND stored_at_type = 'store'";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("isi", $quantity, $sku, $_SESSION["store_id"]);
                        $stmt->execute();
                        $stmt->close(); // Close the prepared statement
                    } else {
                        // If the item does not exist in the store inventory, insert a new record
                        $query = "INSERT INTO inventory (sku, quantity, stored_at_id, stored_at_type) VALUES (?, ?, ?, 'store')";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sii", $sku, $quantity, $_SESSION["store_id"]);
                        $stmt->execute();
                        $stmt->close(); // Close the prepared statement
                    }

                    // Set success response
                    $response["success"] = true;
                    $response["message"] = "Order processed successfully.";
                    header("Location: inventory.php");
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

        // Commit the transaction after processing all order items
        mysqli_commit($conn);

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
