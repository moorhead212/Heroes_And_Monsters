<?php
// filter_inventory.php
require_once "inventory.php";

// Get the selected filters from the query string
$selectedFilters = isset($_GET['filters']) ? explode(',', $_GET['filters']) : [];

// Filter the $combined_inventory array based on the selected filters
$filteredInventory = [];
foreach ($combined_inventory as $item) {
    if (in_array($item['item_type'], $selectedFilters)) {
        $filteredInventory[] = $item;
    }
}

// Generate the HTML for the filtered inventory
$filteredInventoryHtml = "";
foreach ($filteredInventory as $item) {
    $filteredInventoryHtml .= '<div class="grid-item">';
    $filteredInventoryHtml .= '<img src="path/to/item-image.jpg" alt="' . $item['item_name'] . '">';
    $filteredInventoryHtml .= '<div class="item-info">';
    $filteredInventoryHtml .= '<div class="item-name">' . $item['item_name'] . '</div>';
    $filteredInventoryHtml .= '<div class="item-price">$' . $item['item_price'] . '</div>';
    $filteredInventoryHtml .= '</div>';
    $filteredInventoryHtml .= '<button class="add-to-cart">Add to Cart</button>';
    $filteredInventoryHtml .= '</div>';
}

// Return the filtered inventory HTML
echo $filteredInventoryHtml;
?>
