<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db_connection.php";

// Function to get the store inventory for the logged-in employee
function getStoreInventory($store_id)
{
    global $conn;

    // Adjusted SQL query to retrieve store inventory data with item_price and warehouse_id
    $stmt = $conn->prepare("SELECT i.sku, i.item_type, i.item_name, i.item_price, inv.quantity, s.stocks_from
                           FROM inventory AS inv
                           JOIN item AS i ON inv.sku = i.sku
                           JOIN store AS s ON inv.stored_at_id = s.store_id AND inv.stored_at_type = 'store'
                           WHERE inv.stored_at_id = ?");

    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $store_inventory = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Fetch the warehouse_id for each item from the stocks_from field
            $warehouse_id = $row['stocks_from'];
            $row['warehouse_id'] = $warehouse_id;

            $store_inventory[] = $row;
        }
    }

    return $store_inventory;
}



// Function to get the warehouse inventory for the logged-in employee's store
function getWarehouseInventory($warehouse_id)
{
    global $conn;

    // Adjusted SQL query to retrieve warehouse inventory data with item_price from the item table
    $stmt = $conn->prepare("SELECT i.sku, i.item_type, i.item_name, i.item_price, inv.quantity
                           FROM inventory AS inv
                           JOIN item AS i ON inv.sku = i.sku
                           WHERE inv.stored_at_id = ? AND inv.stored_at_type = 'warehouse'");

    $stmt->bind_param("i", $warehouse_id);
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

// Check if the employee is logged in
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Get the stored_at_id for the logged-in employee
$stored_at_id = $_SESSION["store_id"];

// Get the store inventory for the logged-in employee
$store_inventory = getStoreInventory($stored_at_id);

// Get the warehouse inventory for the logged-in employee's store
$warehouse_inventory = getWarehouseInventory($stored_at_id);

?>
