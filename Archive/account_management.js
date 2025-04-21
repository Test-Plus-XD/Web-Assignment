// Logout Function
document.getElementById("logoutButton").addEventListener("click", function () {
    fetch("Class_account.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({ action: "logout" }),
    })
        .then(response => {
            //console.log("Raw response from server:", response.text());
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then(data => {
            console.log("Logout response:", data); // Debugging
            if (data.success) {
                alert(data.message);
                // Clear localStorage on successful logout
                localStorage.clear();
                localStorage.removeItem('cartItems');
                window.location.href = "login.php"; // Redirect to login page
            } else {
                console.error(data.message);
                alert("Logout failed: " + data.message);
            }
        })
        .catch(error => console.error("Error:", error));
});
// Delete Account Function
document.getElementById("deleteButton").addEventListener("click", function () {
    if (!confirm("Are you sure you want to delete your account?")) return;
    fetch("Class_account.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({ action: "delete" }),
    })
        .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then(data => {
            console.log("Delete response:", data); // Debugging
            if (data.success) {
                alert(data.message);
                localStorage.clear();
                localStorage.removeItem('cartItems');
                window.location.href = "registration.php"; // Redirect to registration page
            } else {
                console.error(data.message);
                alert("Account deletion failed: " + data.message);
            }
        })
        .catch(error => console.error("Error:", error));
});