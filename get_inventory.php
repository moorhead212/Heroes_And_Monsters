<?php
// get_inventory.php

// Include the database connection file
require_once "db_connection.php";

// Function to get the store inventory for a specific store
function getStoreInventory($store_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT i.sku, i.item_type, i.item_name, inv.quantity 
                           FROM inventory AS inv
                           JOIN item AS i ON inv.sku = i.sku
                           WHERE inv.stored_at_id = ? AND inv.stored_at_type = 'store'");

    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $store_inventory = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $store_inventory[] = $row;
        }
    }

    return $store_inventory;
}

// Function to get the warehouse inventory for a specific store
function getWarehouseInventory($store_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT i.sku, i.item_type, i.item_name, inv.quantity, w.warehouse_name, w.address
                           FROM inventory AS inv
                           JOIN item AS i ON inv.sku = i.sku
                           JOIN warehouse AS w ON inv.stored_at_id = w.warehouse_id
                           WHERE inv.stored_at_type = 'warehouse'
                           AND inv.stored_at_id IN (SELECT warehouse_id FROM warehouse_store_relation WHERE store_id = ?)");

    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $warehouse_inventory = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $warehouse_inventory[] = $row;
        }
    }

    return $warehouse_inventory;
}

// Check if the request method is GET and the store_id parameter is set
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["store_id"])) {
    $store_id = $_GET["store_id"];

    // Get the store inventory and warehouse inventory for the selected store
    $store_inventory = getStoreInventory($store_id);
    $warehouse_inventory = getWarehouseInventory($store_id);

    // Combine the store and warehouse inventories into a single response array
    $response = array("store_inventory" => $store_inventory, "warehouse_inventory" => $warehouse_inventory);

    // Send the response back to the client as JSON
    header("Content-Type: application/json");
    echo json_encode($response);
}
?>
