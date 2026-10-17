const express = require("express");
const cors = require("cors");
const path = require("path");
const pool = require("./db");

const app = express();
const PORT = Number(process.env.PORT || 5000);
const FRONTEND_DIR = path.resolve(__dirname, "..", "FrontEnd");

// Middleware
app.use(cors());              // frontend ko backend se baat karne dega
app.use(express.json());      // JSON data ko samajhne ke liye
app.use(express.static(FRONTEND_DIR));

// Redirect to the login page so relative page links resolve from /pages/.
app.get("/", (req, res) => {
  res.redirect("/pages/LoginPage.html");
});

app.get("/api/health", async (req, res) => {
  try {
    await pool.query("SELECT 1");
    res.json({ ok: true, database: "connected" });
  } catch (error) {
    res.status(503).json({ ok: false, database: "disconnected" });
  }
});

function nullable(value) {
  return value === undefined || value === "" ? null : value;
}

function numberOrZero(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

function makeJobCardNumber() {
  return `JC-${Date.now()}`;
}

app.post("/api/job-cards", async (req, res) => {
  const body = req.body || {};
  const requiredFields = [
    "registrationNumber",
    "vehicleModel",
    "customerName",
    "jobType"
  ];
  const missingField = requiredFields.find(field => !String(body[field] || "").trim());

  if (missingField || !(body.phoneNumber || body.mobileNumber)) {
    return res.status(400).json({
      message: `${missingField || "phoneNumber"} is required`
    });
  }

  const complaints = Array.isArray(body.complaints) ? body.complaints : [];
  const damages = Array.isArray(body.damages) ? body.damages : [];
  const labour = Array.isArray(body.labour) ? body.labour : [];
  const parts = Array.isArray(body.parts) ? body.parts : [];
  const checks = Array.isArray(body.checks) ? body.checks : [];
  const complaintTotal = complaints.reduce((total, item) => total + numberOrZero(item && item.price), 0);
  const labourTotal = numberOrZero(body.labourTotal);
  const partsTotal = numberOrZero(body.partsTotal);
  const gstTotal = numberOrZero(body.gstTotal);
  const grandTotal = numberOrZero(body.grandTotal);
  const connection = await pool.getConnection();

  try {
    await connection.beginTransaction();

    const [jobCardResult] = await connection.execute(
      `INSERT INTO job_cards
        (job_card_no, job_card_date, frame_number, registration_number, vehicle_model,
         customer_name, phone_number, customer_address, purchase_date, job_type,
         kms_run, service_mode, received_date, received_time, taking_date, taking_time,
         delivery_date, delivery_time, service_advisor, mechanic_name, bay, supervisor,
         water_wash, vehicle_picked, complaint_total, labour_total, parts_total,
         gst_total, grand_total, payment_mode, amount_received, payment_reference,
         received_by)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        makeJobCardNumber(),
        nullable(body.jobCardDate || body.date),
        nullable(body.frameNo),
        String(body.registrationNumber).trim(),
        String(body.vehicleModel).trim(),
        String(body.customerName).trim(),
        String(body.phoneNumber || body.mobileNumber).trim(),
        nullable(body.customerAddress),
        nullable(body.purchaseDate),
        String(body.jobType).trim(),
        nullable(body.kmsRun) === null ? null : numberOrZero(body.kmsRun),
        nullable(body.serviceMode),
        nullable(body.receivedDate),
        nullable(body.receivedTime),
        nullable(body.takingDate),
        nullable(body.takingTime),
        nullable(body.deliveryDate),
        nullable(body.deliveryTime),
        nullable(body.serviceAdvisor || body.mechanic),
        nullable(body.mechanic),
        nullable(body.bay),
        nullable(body.supervisor),
        nullable(body.waterWash),
        nullable(body.vehiclePicked),
        complaintTotal,
        labourTotal,
        partsTotal,
        gstTotal,
        grandTotal,
        nullable(body.paymentMode),
        numberOrZero(body.amountReceived),
        nullable(body.paymentReference),
        nullable(body.receivedBy)
      ]
    );

    for (const complaint of complaints) {
      if (!complaint || !String(complaint.name || complaint.text || "").trim()) continue;
      await connection.execute(
        "INSERT INTO job_card_complaints (job_card_id, complaint_name, price) VALUES (?, ?, ?)",
        [jobCardResult.insertId, String(complaint.name || complaint.text).trim(), numberOrZero(complaint.price)]
      );
    }

    for (const damage of damages) {
      if (!String(damage || "").trim()) continue;
      await connection.execute(
        "INSERT INTO job_card_damages (job_card_id, damage_description) VALUES (?, ?)",
        [jobCardResult.insertId, String(damage).trim()]
      );
    }

    for (const item of labour) {
      if (!item || !String(item.description || item.desc || "").trim()) continue;
      await connection.execute(
        `INSERT INTO job_card_labour
          (job_card_id, labour_code, description, category, quantity, rate, charge)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [
          jobCardResult.insertId,
          nullable(item.code),
          String(item.description || item.desc).trim(),
          nullable(item.category || item.cat),
          numberOrZero(item.quantity || item.qty || 1),
          numberOrZero(item.rate),
          numberOrZero(item.charge)
        ]
      );
    }

    for (const item of parts) {
      if (!item || !String(item.description || item.desc || "").trim()) continue;
      await connection.execute(
        `INSERT INTO job_card_parts
          (job_card_id, part_number, description, issue_mode, quantity, unit_price, mrp, total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          jobCardResult.insertId,
          nullable(item.partNumber || item.part),
          String(item.description || item.desc).trim(),
          nullable(item.issueMode || item.issue),
          numberOrZero(item.quantity || item.qty || 1),
          numberOrZero(item.unitPrice || item.unit),
          numberOrZero(item.mrp),
          numberOrZero(item.total || (item.qty * item.unit))
        ]
      );
    }

    for (const item of checks) {
      if (!item || !String(item.name || "").trim()) continue;
      await connection.execute(
        "INSERT INTO job_card_checks (job_card_id, check_name, is_checked) VALUES (?, ?, ?)",
        [jobCardResult.insertId, String(item.name).trim(), Boolean(item.checked)]
      );
    }

    await connection.commit();
    res.status(201).json({
      message: "Job card saved successfully",
      jobCardId: jobCardResult.insertId
    });
  } catch (error) {
    await connection.rollback();
    console.error("Job card save failed:", error.message);
    res.status(500).json({ message: "Job card save nahi ho paya" });
  } finally {
    connection.release();
  }
});

app.get("/api/job-cards", async (req, res) => {
  try {
    const [rows] = await pool.execute(
      `SELECT id, job_card_no AS jobCardNo, frame_number AS frameNo,
              registration_number AS regNo,
              customer_name AS customer, vehicle_model AS model, job_type AS jobType,
              COALESCE(mechanic_name, service_advisor) AS mechanic, status,
              DATE_FORMAT(COALESCE(job_card_date, created_at), '%Y-%m-%d') AS date,
              parts_total AS partsTotal, labour_total AS labourTotal
       FROM job_cards
       ORDER BY created_at DESC`
    );
    res.json(rows);
  } catch (error) {
    console.error("Job cards fetch failed:", error.message);
    res.status(500).json({ message: "Job cards load nahi ho paye" });
  }
});

if (require.main === module) {
  app.listen(PORT, async () => {
    console.log(`Server running on http://localhost:${PORT}`);
    try {
      await pool.query("SELECT 1");
      console.log("MySQL database connected");
    } catch (error) {
      console.warn("MySQL connection pending:", error.message);
    }
  });
}

module.exports = app;
