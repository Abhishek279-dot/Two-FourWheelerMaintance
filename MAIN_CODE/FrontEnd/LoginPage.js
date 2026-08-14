console.log("hello")

document.getElementById("loginForm").addEventListener("submit", function(e) {
  e.preventDefault(); // page reload rokne ke liye
  
  const userId = document.getElementById("userId").value.trim();
  const password = document.getElementById("password").value.trim();
  const errorMsg = document.getElementById("errorMsg");

  let role = document.getElementById("role").value;
  localStorage.setItem("userRole",role);

  // Abhi ke liye dummy check (baad me backend API se replace hoga)
  if (userId === "admin" && password === "admin123") {
    localStorage.setItem("userRole",role); 
    window.location.href = "HomePage.html";   // redirect

  }else if(userId === "manager" && password === "manager123"){
     localStorage.setItem("userRole",role); // role save kar rahe hai
    window.location.href = "HomePage.html";   // redirect
  
  } else {
    errorMsg.textContent = "Invalid User ID or Password";
    errorMsg.style.display = "block";
  }
});