// Admin Panel JavaScript Functions

function saveOrder(event) {
    event.preventDefault();
    let formData = new FormData(document.getElementById("orderForm"));
    formData.append("action", "save");

    fetch("admin.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data.message, data.status === "success" ? "success" : "error");
        if (data.status === "success") {
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    });
}

function confirmDelete(id) {
    if (confirm("❌ Apakah Anda yakin ingin menghapus order ini?")) {
        let formData = new FormData();
        formData.append("action", "delete");
        formData.append("id", id);

        fetch("admin.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showMessage(data.message, data.status === "success" ? "success" : "error");
            if (data.status === "success") {
                document.getElementById("order-" + id).style.opacity = "0";
                setTimeout(() => {
                    document.getElementById("order-" + id).remove();
                    checkEmptyOrders();
                }, 300);
            }
        });
    }
}

function editOrder(id, nama, orderNo, iccid) {
    document.getElementById("edit_id").value = id;
    document.getElementById("nama").value = nama;
    document.getElementById("orderNo").value = orderNo;
    document.getElementById("iccid").value = iccid;
    document.getElementById("formTitle").innerText = "✏️ Edit Order";
    document.getElementById("submitBtn").innerText = "Update";
    
    // Scroll to form
    document.getElementById("orderForm").scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById("orderForm").reset();
    document.getElementById("edit_id").value = "";
    document.getElementById("formTitle").innerText = "➕ Tambah Order Baru";
    document.getElementById("submitBtn").innerText = "Simpan";
}

function copyToken(token) {
    navigator.clipboard.writeText(token).then(() => {
        showMessage("✅ Token berhasil disalin!", "success");
    });
}

function showMessage(message, type) {
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${type}`;
    messageDiv.innerText = message;
    
    const container = document.querySelector(".container");
    const form = document.getElementById("orderForm");
    
    container.insertBefore(messageDiv, form);
    
    setTimeout(() => {
        messageDiv.style.opacity = "0";
        setTimeout(() => {
            messageDiv.remove();
        }, 300);
    }, 3000);
}

function checkEmptyOrders() {
    const orderItems = document.querySelectorAll(".order-item");
    const orderList = document.querySelector(".order-list");
    
    if (orderItems.length === 0) {
        const emptyState = document.createElement("div");
        emptyState.className = "empty-state";
        emptyState.innerHTML = `
            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" alt="No Orders">
            <h3>Belum ada order</h3>
            <p>Tambahkan order baru dengan mengisi form di atas</p>
        `;
        orderList.appendChild(emptyState);
    }
}