<?php
session_start();

// Check if the customer is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once "db_connection.php";

// Function to get the cart items for the logged-in customer
function getCartItems($username)
{
    global $conn;

    $sql = "SELECT c.cart_id, c.username, c.store_id, it.sku, it.item_name, it.item_price, c.quantity
            FROM cart AS c
            JOIN item AS it ON c.sku = it.sku
            WHERE c.username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }
    }

    return $cart_items;
}

// Function to update the quantity of an item in the cart
function updateCartItemQuantity($cart_id, $quantity)
{
    global $conn;

    $sql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quantity, $cart_id);

    return $stmt->execute();
}

function addToCart($username, $sku, $store_id)
{
    global $conn;

    // Check if the item is in stock before adding to the cart
    $availableQuantity = getAvailableQuantity($sku);
    if ($availableQuantity > 0) {
        // Get the item price from the item table
        $sql_get_item_price = "SELECT item_price FROM item WHERE sku = ?";
        $stmt_get_item_price = $conn->prepare($sql_get_item_price);
        $stmt_get_item_price->bind_param("s", $sku);
        $stmt_get_item_price->execute();
        $result_get_item_price = $stmt_get_item_price->get_result();

        if ($result_get_item_price->num_rows > 0) {
            $row = $result_get_item_price->fetch_assoc();
            $item_price = $row['item_price'];

            $sql = "INSERT INTO cart (username, sku, quantity, store_id) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE quantity = quantity + 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssd", $username, $sku, $store_id);

            return $stmt->execute();
        }
    }

    return false;
}


function removeCartItem($cart_id)
{
    global $conn;

    $sql = "DELETE FROM cart WHERE cart_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cart_id);

    return $stmt->execute();
}

// Function to check if the item quantity is available in inventory
function isQuantityAvailable($sku, $quantity)
{
    global $conn;

    $sql = "SELECT SUM(quantity) AS total_quantity FROM inventory WHERE sku = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_quantity = $row['total_quantity'];

        return $total_quantity >= $quantity;
    }

    return false;
}

// Function to clear the cart for a customer
function clearCart($username)
{
    global $conn;

    $sql = "DELETE FROM cart WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);

    return $stmt->execute();
}

// Function to update the stock quantity after a successful checkout
function updateStockQuantity($sku, $quantity)
{
    global $conn;

    $sql = "UPDATE inventory SET quantity = quantity - ? WHERE sku = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $quantity, $sku);
    $stmt->execute();
}

// Handle cart updates (e.g., quantity change, remove item)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_quantity'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = $_POST['quantity'];

        // Update the quantity in the cart
        updateCartItemQuantity($cart_id, $quantity);
    } elseif (isset($_POST['add_to_cart'])) {
        $username = $_SESSION["username"];
        $sku = $_POST['sku'];

        // Add the item to the cart
        if (addToCart($username, $sku, $_SESSION['store_id'])) {
            // Redirect back to the store page after adding to cart
            header("Location: store.php");
            exit();
        } else {
            echo "Failed to add item to cart.";
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = $_POST['cart_id'];

        // Remove the item from the cart
        if (removeCartItem($cart_id)) {
            // Redirect back to the cart page after removing the item
            header("Location: cart.php");
            exit();
        } else {
            echo "Failed to remove item from cart.";
        }
    } elseif (isset($_POST['checkout'])) {
        // Check if all cart items have sufficient stock
        $checkoutAllowed = true;
        $itemsWithInsufficientStock = array();
        $cart_items = getCartItems($_SESSION["username"]);
        foreach ($cart_items as $item) {
            if (!isQuantityAvailable($item['sku'], $item['quantity'])) {
                $checkoutAllowed = false;
                $itemsWithInsufficientStock[] = $item['item_name'];
            }
        }

        if ($checkoutAllowed) {
            // Proceed with the checkout process
            // Deduct the purchased quantity from the stock for each cart item
            foreach ($cart_items as $item) {
                updateStockQuantity($item['sku'], $item['quantity']);
            }

            // Clear the cart for the customer by deleting all cart items associated with their username
            $username = $_SESSION["username"];
            clearCart($username);

            // Optionally, you can redirect the customer to a success/thank you page after checkout
            header("Location: checkout_success.php");
            exit();
        } else {
            // Display an error message indicating which items have insufficient stock
            $errorMessage = "Not enough stock available for the following items: " . implode(', ', $itemsWithInsufficientStock);
        }
    }
}

function getAvailableQuantity($sku)
{
    global $conn;

    $sql = "SELECT SUM(quantity) AS total_quantity FROM inventory WHERE sku = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_quantity = $row['total_quantity'];
        return $total_quantity;
    }

    return 0;
}

// Get the cart items for the logged-in customer
$username = $_SESSION["username"];
$cart_items = getCartItems($username);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Heroes & Monsters Magical Emporium</title>
    <link rel="stylesheet" href="./cart-style.css">
</head>

<body>
    <header>
        <div class="top-right">
            <a href="store.php" class="go-back-button">Go Back to Shopping</a>
        </div>
    </header>

    <main>
        <h1>Cart</h1>

        <table class="cart-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td>
                            <?php echo $item['item_name']; ?>
                        </td>
                        <td>$
                            <?php echo $item['item_price']; ?>
                        </td>
                        <td>
                            <form action="cart.php" method="post">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="submit" name="update_quantity">Update</button>
                            </form>
                        </td>
                        <td>$
                            <?php echo number_format($item['item_price'] * $item['quantity'], 2); ?>
                        </td>
                        <td>
                            <form action="cart.php" method="post">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" name="remove_item">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (isset($errorMessage)): ?>
            <div class="error-message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <div class="cart-summary">
            <div class="cart-totals">
                <?php
                $subtotal = 0;
                foreach ($cart_items as $item) {
                    $subtotal += $item['item_price'] * $item['quantity'];
                }
                ?>
                <div class="subtotal">Subtotal: $
                    <?php echo number_format($subtotal, 2); ?>
                </div>
                <!-- You can calculate and display tax, total, or any other summary information here -->
            </div>
            <form action="cart.php" method="post">
                <button type="submit" class="checkout-button" name="checkout">Check Out</button>
            </form>
        </div>
    </main>
</body>

</html>