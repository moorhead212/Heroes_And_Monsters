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

    $sql = "SELECT c.cart_id, c.username, c.store_id, i.sku, i.item_name, i.item_price, c.quantity
            FROM cart AS c
            JOIN item AS i ON c.sku = i.sku
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

// Function to add an item to the customer's cart
function addToCart($username, $sku, $store_id)
{
    global $conn;

    $sql = "INSERT INTO cart (username, sku, quantity, store_id) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE quantity = quantity + 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $username, $sku, $store_id);

    return $stmt->execute();
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
// Add the item to the cart
if (addToCart($username, $sku, $_SESSION['store_id'])) {
    // Redirect back to the store page after adding to cart
    header("Location: store.php");
    exit();
} else {
    echo "Failed to add item to cart.";
}

    }
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item) : ?>
                    <tr>
                        <td><?php echo $item['item_name']; ?></td>
                        <td>$<?php echo $item['item_price']; ?></td>
                        <td>
                            <form action="cart.php" method="post">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="submit" name="update_quantity">Update</button>
                            </form>
                        </td>
                        <td>$<?php echo number_format($item['item_price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="cart-totals">
                <?php
                $subtotal = 0;
                foreach ($cart_items as $item) {
                    $subtotal += $item['item_price'] * $item['quantity'];
                }
                ?>
                <div class="subtotal">Subtotal: $<?php echo number_format($subtotal, 2); ?></div>
                <!-- You can calculate and display tax, total, or any other summary information here -->
            </div>
            <button class="checkout-button">Check Out</button>
        </div>
    </main>
</body>

</html>
