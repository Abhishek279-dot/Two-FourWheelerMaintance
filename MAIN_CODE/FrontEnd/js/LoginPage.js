document.getElementById("loginForm").addEventListener("submit", async function(e) {
  e.preventDefault();

  const userId = document.getElementById("userId").value.trim();
  const password = document.getElementById("password").value;
  const branch = document.getElementById("branch").value;
  const errorMsg = document.getElementById("errorMsg");
  const submitButton = document.getElementById("sendOtpBtn");

  errorMsg.textContent = "";
  errorMsg.style.display = "none";
  submitButton.disabled = true;

  try {
    const response = await fetch("../../php-backend/api/login.php", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      body: JSON.stringify({
        username: userId,
        password: password
      })
    });

    const result = await response.json().catch(() => null);

    if (!response.ok || !result || !result.success || !result.user) {
      throw new Error(result?.message || "Unable to sign in.");
    }

    localStorage.setItem("userRole", result.user.role);
    localStorage.setItem("userBranch", branch);
    localStorage.setItem("userId", result.user.username);
    window.location.href = "HomePage.html";
  } catch (error) {
    errorMsg.textContent = error.message === "Invalid username or passworddd."
      ? error.message
      : "Unable to sign in. Please try again.";
    errorMsg.style.display = "block";
    submitButton.disabled = false;
  }
});
