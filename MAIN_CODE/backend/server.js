const express = require("express");
const cors = require("cors");

const app = express();
const PORT = 5000;

// Middleware
app.use(cors());              // frontend ko backend se baat karne dega
app.use(express.json());      // JSON data ko samajhne ke liye

// Test route
app.get("/", (req, res) => {
  res.send("Backend server chal raha hai!");
});

// Server start
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});