document.addEventListener("DOMContentLoaded", function () {
    // Add event listener to the "Add Row" button
    const addRestockRowButton = document.getElementById("add-restock-row");
    addRestockRowButton.addEventListener("click", addRestockRow);

    // Add event listener to the "Order" button
    const orderButton = document.getElementById("order");
    orderButton.addEventListener("click", processOrder);
});


function addRestockRow() {
    const restockOrderTableBody = document.querySelector("#restock-order tbody");

    // Create a new row with input elements
    const newRow = document.createElement("tr");
    newRow.innerHTML = `
        <td><input type="text" name="restock_item_id[]" class="restock-item-id"></td>
        <td><input type="number" name="restock_qty[]" class="restock-qty"></td>
    `;

    // Append the new row to the restock order table body
    restockOrderTableBody.appendChild(newRow);
}

function processOrder() {
    const itemIds = document.querySelectorAll(".restock-item-id");
    const quantities = document.querySelectorAll(".restock-qty");

    // Prepare the data to send to the server
    const orderData = [];
    for (let i = 0; i < itemIds.length; i++) {
        const itemId = itemIds[i].value.trim();
        const qty = parseInt(quantities[i].value.trim(), 10);

        if (itemId !== "" && qty > 0) {
            orderData.push({ sku: itemId, quantity: qty });
        }
    }

    // Send the order data to the server using fetch API
    fetch("process_order.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(orderData),
    })
    .then(response => response.json())
    .then(data => {
        // Check the response from the server
        if (data.success) {
            alert(data.message);
            // If the order was successful, you might want to update the inventory table or display a success message
        } else {
            alert("Error: " + data.message);
            // If there was an error, display the error message sent by the server
        }
    })
    .catch(error => {
        console.error("Error occurred:", error);
        alert("An error occurred while processing the order.");
    });
}