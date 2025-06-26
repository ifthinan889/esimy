// Admin Panel JavaScript Functions

function saveOrder(event) {
    event.preventDefault();
    let formData = new FormData(document.getElementById("orderForm"));
    formData.append("action", "save");

    fetch("esim.php", {
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
    })
    .catch(error => {
        console.error("Error:", error);
        showMessage("Terjadi kesalahan: " + error.message, "error");
    });
}

function confirmDelete(id) {
    if (confirm("❌ Apakah Anda yakin ingin menghapus eSIM ini?")) {
        let formData = new FormData();
        formData.append("action", "delete_order");
        formData.append("order_id", id);

        fetch("orders.php", {
            method: "POST",
            body: formData
        })
        .then(response => {
            try {
                return response.json();
            } catch (e) {
                return { status: "success", message: "eSIM berhasil dihapus!" };
            }
        })
        .then(data => {
            location.reload();
        })
        .catch(error => {
            console.error("Error:", error);
            // If there's an error, still reload the page as the delete might have succeeded
            location.reload();
        });
    }
}

function editOrder(id, nama, orderNo, iccid) {
    document.getElementById("edit_id").value = id;
    document.getElementById("nama").value = nama;
    document.getElementById("orderNo").value = orderNo;
    document.getElementById("iccid").value = iccid;
    document.getElementById("formTitle").innerText = "✏️ Edit eSIM";
    document.getElementById("submitBtn").innerText = "Update";
    
    // Scroll to form
    document.getElementById("orderForm").scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById("orderForm").reset();
    document.getElementById("edit_id").value = "";
    document.getElementById("formTitle").innerText = "➕ Tambah eSIM Baru";
    document.getElementById("submitBtn").innerText = "Simpan";
}

function copyToken(token) {
    navigator.clipboard.writeText(token).then(() => {
        showMessage("✅ Token berhasil disalin!", "success");
    }).catch(err => {
        // }).catch(err => {
        // Fallback for browsers that don't support clipboard API
        const textArea = document.createElement("textarea");
        textArea.value = token;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        showMessage("✅ Token berhasil disalin!", "success");
    });
}

function showMessage(message, type) {
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${type}`;
    messageDiv.innerText = message;
    
    // Find the container - could be .container or .main-content
    const container = document.querySelector(".main-content") || document.querySelector(".container");
    const header = document.querySelector(".dashboard-header") || document.querySelector("h1");
    
    if (container && header) {
        container.insertBefore(messageDiv, header.nextSibling);
        
        setTimeout(() => {
            messageDiv.style.opacity = "0";
            setTimeout(() => {
                messageDiv.remove();
            }, 300);
        }, 3000);
    }
}

function checkEmptyOrders() {
    const orderItems = document.querySelectorAll(".order-item");
    const orderList = document.querySelector(".order-list");
    
    if (orderItems.length === 0 && orderList) {
        const emptyState = document.createElement("div");
        emptyState.className = "empty-state";
        emptyState.innerHTML = `
            <img src="assets/images/no-orders.png" alt="No Orders">
            <h3>Belum ada eSIM</h3>
            <p>Tambahkan eSIM baru dengan mengisi form di atas</p>
        `;
        orderList.appendChild(emptyState);
    }
}

function searchPackages() {
    const countryName = document.getElementById('countrySearch').value.trim();
    if (!countryName) {
        alert('Silakan masukkan nama negara terlebih dahulu!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'search_packages');
    formData.append('country_name', countryName);
    
    // Tampilkan loading
    document.getElementById('searchResults').innerHTML = '<div class="loading">Mencari paket...</div>';
    
    fetch('esim.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        try {
            return response.json();
        } catch (e) {
            throw new Error('Invalid JSON response');
        }
    })
    .then(data => {
        const resultsDiv = document.getElementById('searchResults');
        
        if (data.success && data.obj && data.obj.packageList && data.obj.packageList.length > 0) {
            let html = '<h4>Hasil Pencarian untuk: ' + countryName + '</h4>';
            
            data.obj.packageList.forEach(package => {
                html += `
                <div class="package-item">
                    <h4>${package.name}</h4>
                    <p><strong>Kode:</strong> ${package.packageCode} (${package.slug})</p>
                    <p><strong>Harga:</strong> ${package.currencyCode} ${(package.price/10000).toFixed(2)}</p>
                    <p><strong>Data:</strong> ${formatBytes(package.volume)}</p>
                    <p><strong>Masa Aktif:</strong> ${package.duration} ${package.durationUnit.toLowerCase()}(s)</p>
                    <p><strong>Lokasi:</strong> ${package.location}</p>
                    <p><strong>Deskripsi:</strong> ${package.description}</p>
                    <button class="order-btn" onclick="openOrderModal('${package.packageCode}', '${package.name}', '${(package.price/10000).toFixed(2)}')">🛒 Pesan eSIM</button>
                </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
        } else {
            resultsDiv.innerHTML = `
            <div class="empty-state">
                <h3>Tidak ada paket ditemukan</h3>
                <p>Tidak ada paket yang tersedia untuk negara "${countryName}" atau kode negara tidak valid.</p>
                <p>Gunakan kode negara 2 huruf (contoh: ID untuk Indonesia, SG untuk Singapura)</p>
            </div>
            `;
        }
    })
    .catch(error => {
        document.getElementById('searchResults').innerHTML = `
        <div class="empty-state">
            <h3>Terjadi Kesalahan</h3>
            <p>Gagal mencari paket: ${error.message}</p>
        </div>
        `;
    });
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Close modal if user clicks outside
window.onclick = function(event) {
    const modal = document.getElementById('orderModal') || document.getElementById('statusModal');
    if (modal && event.target == modal) {
        closeModal();
    }
}

function closeModal() {
    const orderModal = document.getElementById('orderModal');
    const statusModal = document.getElementById('statusModal');
    
    if (orderModal) {
        orderModal.style.display = 'none';
    }
    
    if (statusModal) {
        statusModal.style.display = 'none';
    }
}