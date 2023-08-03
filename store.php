<?php
// store.php

// Include the inventory.php file to access its functions
require_once "get_inventory.php";

// Check if the customer is logged in
if (!isset($_SESSION['username'])) {
    echo "Username not set.";
    exit();
}

if (!isset($_SESSION['login_type'])) {
    echo "Login type not set.";
    exit();
}

if ($_SESSION['login_type'] !== 'customer') {
    echo "Not logged in as a customer.";
    exit();
}

if (!isset($_SESSION['store_id'])) {
    echo "Store choice not set.";
    exit();
}

// Get the stored_at_id for the logged-in customer
$store_id = $_SESSION["store_id"];

// Get the store inventory for the logged-in customer
$store_inventory = getStoreInventory($store_id);

// Get the warehouse_id for the customer's store
$warehouse_id = $store_inventory[0]["warehouse_id"]; // Get the warehouse_id from the first item in the store inventory

// Get the warehouse inventory for the customer's store
$warehouse_inventory = getWarehouseInventory($warehouse_id);

// Combine the store and warehouse inventory arrays
$combined_inventory = array_merge($store_inventory, $warehouse_inventory);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heroes & Monsters Magical Emporium Store# <?php echo $_SESSION["store_id"]; ?></title>
    <link rel="stylesheet" href="./store-style.css">
</head>

<body>
    <header>
        <div class="store-title"><?php echo $_SESSION["username"]; ?>, Welcome to Heroes & Monsters Magical Emporium Store# <?php echo $_SESSION["store_id"]; ?> </div>
        <div class="top-right">
            <img src="path/to/cart-image.png" alt="Cart" class="cart-image">
            <button class="cart-button" onclick="window.location.href='cart.php'">Cart</button>
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <button class="search-button">Search</button>
            </div>
        </div>
    </header>

    <main>
        <div class="filter-section">
            <h2>Filter By:</h2>
            <label><input type="checkbox" name="minis" onchange="filterItems()">Minis</label>
            <label><input type="checkbox" name="books" onchange="filterItems()">Books</label>
            <label><input type="checkbox" name="boardgames" onchange="filterItems()">Boardgames</label>
            <label><input type="checkbox" name="other" onchange="filterItems()">Other</label>
        </div>

        <div class="item-grid" id="itemGrid">
    <?php
    foreach ($combined_inventory as $item) {
        echo '<div class="grid-item" data-item-type="' . $item['item_type'] . '">';
        echo '<img src="path/to/item-image.jpg" alt="' . $item['item_name'] . '">';
        echo '<div class="item-info">';
        echo '<div class="item-name">' . $item['sku'] . '</div>';
        echo '<div class="item-type" style="display: none;">' . $item['item_type'] . '</div>';
        echo '<div class="item-price">$' . $item['item_price'] . '</div>';
        echo '</div>';
        echo '<form action="cart.php" method="post">'; // Form to submit the item to the cart
        echo '<input type="hidden" name="sku" value="' . $item['sku'] . '">';
        echo '<button type="submit" name="add_to_cart">Add to Cart</button>'; // Add to Cart button
        echo '</form>';
        echo '</div>';
    }
    ?>
</div>
    </main>

    <script>
        function filterItems() {
            const checkboxes = document.querySelectorAll("input[name='minis'], input[name='books'], input[name='boardgames'], input[name='other']");
            const itemGrid = document.getElementById("itemGrid");
            
            const itemsToShow = new Set();
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const itemType = checkbox.name;
                    itemsToShow.add(itemType);
                }
            });

            const items = itemGrid.children;
            for (const item of items) {
                const itemTypeName = item.getAttribute('data-item-type');
                if (itemsToShow.size === 0 || itemsToShow.has(itemTypeName)) {
                    item.style.display = "block";
                } else {
                    item.style.display = "none";
                }
            }
        }
    </script>
</body>

</html>
