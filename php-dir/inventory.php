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
    header("Location: ./login.php");
    exit();
}

// Get the stored_at_id for the logged-in employee
$stored_at_id = $_SESSION["store_id"];

// Get the store inventory for the logged-in employee
$store_inventory = getStoreInventory($stored_at_id);

// Get the warehouse inventory for the logged-in employee's store
$warehouse_inventory = getWarehouseInventory($stored_at_id);

// Function to generate HTML for store inventory table
function getStoreInventoryHtml($inventory)
{
    $html = "";
    foreach ($inventory as $item) {
        $html .= "<tr>";
        $html .= "<td>" . $item['sku'] . "</td>";
        $html .= "<td>" . $item['item_type'] . "</td>";
        $html .= "<td>" . $item['item_name'] . "</td>";
        $html .= "<td>" . $item['quantity'] . "</td>";
        $html .= "</tr>";
    }
    return $html;
}

// Function to generate HTML for warehouse inventory table
function getWarehouseInventoryHtml($inventory)
{
    $html = "";
    foreach ($inventory as $item) {
        $html .= "<tr>";
        $html .= "<td>" . $item['sku'] . "</td>";
        $html .= "<td>" . $item['item_type'] . "</td>";
        $html .= "<td>" . $item['item_name'] . "</td>";
        $html .= "<td>" . $item['quantity'] . "</td>";
        $html .= "</tr>";
    }
    return $html;
}

// Include the HTML template (inventory.html) and replace the placeholders with the dynamic inventory data
$store_inventory_html = getStoreInventoryHtml($store_inventory);
$warehouse_inventory_html = getWarehouseInventoryHtml($warehouse_inventory);

?>
<!DOCTYPE html>
<html lang="en">
    <?php
    // Fetch store_id from the PHP session and assign it to a variable
    $store_id = $_SESSION["store_id"];
    ?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store# <?= $store_id ?> Inventory</title>
    <link rel="stylesheet" href="../styles/inventory-style.css">
    <script src="../scripts/inventory-script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>
    <div id="titlebox">Store # <?= $store_id ?> Inventory</div>


    <div id="inventory-container">
        <table class="inventory-table" id="store-inventory">
            <caption>Store# <?= $store_id ?> Current Stock</caption>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($store_inventory as $item): ?>
                <tr>
                    <td>
                        <?= $item['sku'] ?>
                    </td>
                    <td>
                        <?= $item['item_type'] ?>
                    </td>
                    <td>
                        <?= $item['item_name'] ?>
                    </td>
                    <td>
                        <?= $item['quantity'] ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="inventory-table" id="warehouse-inventory">
            <caption>Warehouse Current Stock</caption>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($warehouse_inventory as $item): ?>
                <tr>
                    <td>
                        <?= $item['sku'] ?>
                    </td>
                    <td>
                        <?= $item['item_type'] ?>
                    </td>
                    <td>
                        <?= $item['item_name'] ?>
                    </td>
                    <td>
                        <?= $item['quantity'] ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>

<!-- Restock Order Table -->
<!-- Restock Order Table -->
<table class="inventory-table" id="restock-order">
    <caption>Restock Order</caption>
    <thead>
        <tr>
            <th>Item ID</th>
            <th>Qty</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><input type="text" name="restock_item_id[]" class="restock-item-id"></td>
            <td><input type="number" name="restock_qty[]" class="restock-qty"></td>
        </tr>
    </tbody>
</table>

</div>

<div id="order-button">
    <button id="add-restock-row">Add Row</button>
    <!-- Add an ID to the form element for easy access -->
    <form id="order-form" action="../php-dir/process_order.php" method="POST">
        <button type="submit" id="order">Order</button>
    </form>
</div>

    


</body>

</html>
