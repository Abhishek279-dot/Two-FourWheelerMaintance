/* ============ AutoLine DMS - shared app logic ============ */

const DMS = (() => {
  const LS_KEY = "autoline_dms_data_v1";

  function seedVehicles() {
    return [
      { registrationNumber: "MH12AB1234", vehicleModel: "TVS Jupiter 110 OBD2E", customerName: "Ramesh Patil", phoneNumber: "9876543210", customerAddress: "Pune, Maharashtra", purchaseDate: "2024-04-15", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12CD5566", vehicleModel: "TVS Activa 6G", customerName: "Sunita Kale", phoneNumber: "9876543211", customerAddress: "Nashik, Maharashtra", purchaseDate: "2023-11-20", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12EF7788", vehicleModel: "TVS Ntorq 125", customerName: "Amit Deshmukh", phoneNumber: "9876543212", customerAddress: "Kolhapur, Maharashtra", purchaseDate: "2024-01-08", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12GH9900", vehicleModel: "TVS iQube", customerName: "Priya Shinde", phoneNumber: "9876543213", customerAddress: "Satara, Maharashtra", purchaseDate: "2024-06-12", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12IJ2211", vehicleModel: "TVS Jupiter Classic", customerName: "Nikhil Gaikwad", phoneNumber: "9876543214", customerAddress: "Mumbai, Maharashtra", purchaseDate: "2023-08-25", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12KL3344", vehicleModel: "TVS Jupiter 110 DR", customerName: "Swapnil More", phoneNumber: "9876543215", customerAddress: "Pimpri, Maharashtra", purchaseDate: "2023-05-17", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12MN5566", vehicleModel: "TVS RONIN 1CH BASE", customerName: "Rohit Jadhav", phoneNumber: "9876543216", customerAddress: "Baramati, Maharashtra", purchaseDate: "2024-02-22", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12OP7788", vehicleModel: "TVS Jupiter 125", customerName: "Sneha Kulkarni", phoneNumber: "9876543217", customerAddress: "Pune, Maharashtra", purchaseDate: "2024-07-09", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12QR9900", vehicleModel: "TVS NTORQ 125", customerName: "Vikram Pawar", phoneNumber: "9876543218", customerAddress: "Lonavala, Maharashtra", purchaseDate: "2023-12-14", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" },
      { registrationNumber: "MH12ST1122", vehicleModel: "TVS WEGO 110", customerName: "Neha Jagtap", phoneNumber: "9876543219", customerAddress: "Shirur, Maharashtra", purchaseDate: "2024-03-30", dealerCode: "62380", dealerName: "YASH AUTOMOTORS" }
    ];
  }

  function seedMechanics() {
    return ["Abhishek Swami", "Sumit Bhise", "Advait Salvi", "Kartik Kale"];
  }

  function seedServiceAdvisors() {
    return ["Vilas Hire", "Sonu Chavan", "Sidharam Jirole", "Ganesh Pawar"];
  }

  function seedInventory() {
    return [
      { id: "INV-001", image: "", partName: "Brake Pad", partCode: "BP-101", category: "Braking", compatibleWith: "All Models", quantity: 25, minStock: 10, price: 450, status: "In Stock" },
      { id: "INV-002", image: "", partName: "Engine Oil 1L", partCode: "EO-103", category: "Lubricants", compatibleWith: "All Models", quantity: 40, minStock: 15, price: 350, status: "In Stock" },
      { id: "INV-003", image: "", partName: "Air Filter", partCode: "AF-102", category: "Filters", compatibleWith: "Jupiter, Ntorq", quantity: 8, minStock: 10, price: 220, status: "Low Stock" },
      { id: "INV-004", image: "", partName: "Drive Belt", partCode: "DB-105", category: "Transmission", compatibleWith: "Ntorq, Raider", quantity: 5, minStock: 10, price: 600, status: "Low Stock" },
      { id: "INV-005", image: "", partName: "Rear Mirror", partCode: "RM-104", category: "Body Parts", compatibleWith: "All Models", quantity: 18, minStock: 5, price: 320, status: "In Stock" }
    ];
  }

  function seedJobCards() {
    const jobCards = [
      { id: "JC-3101", frameNo: "MD626EG52T1C18618", regNo: "MH12AB1234", customer: "Ramesh Patil", model: "TVS Jupiter 110 OBD2E", jobType: "2nd Free Service", status: "Allocated", date: "2026-08-01", mechanic: "Abhishek Swami", vehicleAllocation: "Abhishek Swami", labourTotal: 118, partsTotal: 675 },
      { id: "JC-3098", frameNo: "MD626BG44G1G21074", regNo: "MH12CD5566", customer: "Sunita Kale", model: "JUPITER", jobType: "Paid Service", status: "Job Completed", date: "2026-07-31", mechanic: "Sumit Bhise", vehicleAllocation: "Sumit Bhise", labourTotal: 250, partsTotal: 1420 },
      { id: "JC-3090", frameNo: "MD626EG56T1B08784", regNo: "MH12EF7788", customer: "Amit Deshmukh", model: "TVS Jupiter110 DR", jobType: "Running Repair", status: "Closed", date: "2026-07-29", mechanic: "Advait Salvi", vehicleAllocation: "Advait Salvi", labourTotal: 340, partsTotal: 2100 },
      { id: "JC-3087", frameNo: "MD626EG51S1B12014", regNo: "MH12GH9900", customer: "Priya Shinde", model: "TVS Jupiter110 DR", jobType: "Running Repair", status: "Closed", date: "2026-07-28", mechanic: "Kartik Kale", vehicleAllocation: "Kartik Kale", labourTotal: 180, partsTotal: 560 },
      { id: "JC-3080", frameNo: "MD626CG58N1F43734", regNo: "MH12IJ2211", customer: "Nikhil Gaikwad", model: "JUPITER ZX DISC SXC", jobType: "Running Repair", status: "Closed", date: "2026-07-26", mechanic: "Abhishek Swami", vehicleAllocation: "Abhishek Swami", labourTotal: 220, partsTotal: 890 },
      { id: "JC-3075", frameNo: "MD626EG59S1L01488", regNo: "MH12KL3344", customer: "Swapnil More", model: "TVS Jupiter110 DR", jobType: "3rd Free Service", status: "Closed", date: "2026-07-24", mechanic: "Sumit Bhise", vehicleAllocation: "Sumit Bhise", labourTotal: 0, partsTotal: 145 },
      { id: "JC-3070", frameNo: "MD637AN17S2C02885", regNo: "MH12MN5566", customer: "Rohit Jadhav", model: "TVS RONIN 1CH BASE", jobType: "Running Repair", status: "Closed", date: "2026-07-22", mechanic: "Advait Salvi", vehicleAllocation: "Advait Salvi", labourTotal: 410, partsTotal: 3200 },
      { id: "JC-3064", frameNo: "MD626EG53R1D10121", regNo: "MH12OP7788", customer: "Sneha Kulkarni", model: "TVS Jupiter 125", jobType: "Free Service", status: "Closed", date: "2026-07-19", mechanic: "Kartik Kale", vehicleAllocation: "Kartik Kale", labourTotal: 120, partsTotal: 430 },
      { id: "JC-3059", frameNo: "MD637AN19T2C04312", regNo: "MH12QR9900", customer: "Vikram Pawar", model: "TVS NTORQ 125", jobType: "Paid Service", status: "Closed", date: "2026-07-16", mechanic: "Abhishek Swami", vehicleAllocation: "Abhishek Swami", labourTotal: 280, partsTotal: 1170 },
      { id: "JC-3052", frameNo: "MD626BG48P1H20773", regNo: "MH12ST1122", customer: "Neha Jagtap", model: "TVS WEGO 110", jobType: "Running Repair", status: "Closed", date: "2026-07-12", mechanic: "Sumit Bhise", vehicleAllocation: "Sumit Bhise", labourTotal: 190, partsTotal: 820 }
    ];

    const receivedTimes = ["10:30", "11:15", "12:00", "09:45", "14:20"];
    const takingTimes = ["11:30", "12:15", "13:00", "10:45", "15:20"];
    const deliveryTimes = ["17:30", "18:15", "19:00", "16:45", "20:20"];
    const serviceAdvisors = seedServiceAdvisors();

    return jobCards.map((jobCard, index) => ({
      ...jobCard,
      details: {
        registrationNumber: jobCard.regNo,
        vehicleModel: jobCard.model,
        customerName: jobCard.customer,
        jobType: jobCard.jobType,
        receivedDate: jobCard.date,
        receivedTime: receivedTimes[index % receivedTimes.length],
        takingDate: jobCard.date,
        takingTime: takingTimes[index % takingTimes.length],
        deliveryDate: jobCard.date,
        deliveryTime: deliveryTimes[index % deliveryTimes.length],
        vehicleAllocation: jobCard.vehicleAllocation,
        serviceAdvisor: serviceAdvisors[index % serviceAdvisors.length],
        vehiclePicked: "Yes",
        complaints: [{ name: index % 2 ? "Suspension Setting" : "Engine Noise", price: index % 2 ? 150 : 100 }],
        damages: [index % 2 ? "Scratches" : "Side Fender Dent"]
      }
    }));
  }

  function seedData() {
    return {
      session: { dealerCode: "62380", role: "ADMIN", branch: "YASH AUTOMOTORS", userId: "admin", loggedIn: false },
      theme: "light",
      vehicles: seedVehicles(),
      notifications: [
        { title: "Service reminder due", detail: "18 vehicles due for free service this week", time: "10 min ago" },
        { title: "Low stock alert", detail: "TVS TRU4 Engine Oil 900ML - only 6 left", time: "1 hr ago" },
        { title: "Job card closed", detail: "JC-2131 closed by Sidharam Jirole", time: "3 hrs ago" },
        { title: "New enquiry", detail: "Walk-in enquiry logged for NTORQ 125", time: "5 hrs ago" }
      ],
      jobCards: seedJobCards(),
      inventory: seedInventory(),
      parts: {
        "NTORQ 125": ["Battery & Battery Box Assembly", "Frame Assembly", "Side Panel LH RH Assembly", "Front Wheel Assembly", "Rear Wheel Assembly", "Handle Assembly"],
        "JUPITER 125": ["Rear Wheel Assembly", "Front Wheel Assembly", "Engine Assembly", "Frame Assembly", "Seat Assembly"],
        "IQUBE": ["Battery Pack Assembly", "Motor Assembly", "Charger Unit", "Frame Assembly"],
        "WEGO 110": ["Frame Assembly", "Front Wheel Assembly", "Rear Wheel Assembly"],
        "ORBITER": ["Battery & Battery Box Assembly", "Frame Assembly", "Side Panel LH RH Assembly"]
      },
      partsList: [
        { ref: "1", part: "K6111690", name: "WHEEL ASSY REAR DRUM TUBELESS", ndp: 6776.05, mrp: 10055, moq: 1, remark: "" },
        { ref: "1A", part: "K6111190", name: "WHEEL ASSY REAR JUPITER", ndp: 6145.95, mrp: 9120, moq: 1, remark: "" },
        { ref: "1B", part: "K6111840", name: "WHEEL ASSY REAR", ndp: 3227.97, mrp: 4790, moq: 1, remark: "SMW" },
        { ref: "2", part: "K6111680", name: "ALLOY WHEEL 12 215 5 DRUM REAR", ndp: 4427.51, mrp: 6570, moq: 1, remark: "FR. DISC VER." },
        { ref: "2A", part: "K61108805D", name: "ALLOY WHEEL REAR 2.15*12 - JUPITER", ndp: 3086.46, mrp: 4580, moq: 1, remark: "MAT. BLACK" },
        { ref: "4", part: "N9112880", name: "VALVE TUBELESS TYRE", ndp: 33.69, mrp: 50, moq: 8, remark: "" },
        { ref: "5", part: "1110020", name: "SHOE SET BRAKE 130 DIA/28MM WIDTH", ndp: 192.06, mrp: 285, moq: 200, remark: "" },
        { ref: "6", part: "S1200910", name: "FLANGE U NUT M16X1.5", ndp: 40.43, mrp: 60, moq: 10, remark: "" },
        { ref: "10", part: "K3110690", name: "SPRING BRAKE CABLE REAR", ndp: 6.74, mrp: 10, moq: 40, remark: "DISC" },
        { ref: "11", part: "K6110840", name: "BRAKE CAM REAR - WEGO", ndp: 77.50, mrp: 115, moq: 3, remark: "" }
      ],
      mechanics: seedMechanics(),
      serviceAdvisors: seedServiceAdvisors(),
      cart: []
    };
  }

  function load() {
    let raw = localStorage.getItem(LS_KEY);
    if (!raw) {
      const seed = seedData();
      localStorage.setItem(LS_KEY, JSON.stringify(seed));
      return seed;
    }
    try {
      const data = JSON.parse(raw);
      const seededVehicles = seedVehicles();
      const seededJobCards = seedJobCards();
      const seededMechanics = seedMechanics();
      const seededServiceAdvisors = seedServiceAdvisors();
      let changed = false;
      const hasValue = value => value !== "" && value !== null && value !== undefined && (!Array.isArray(value) || value.length > 0);
      if (!Array.isArray(data.vehicles) || data.vehicles.length === 0) {
        data.vehicles = seededVehicles;
        changed = true;
      } else {
        seededVehicles.forEach(seed => {
          const existing = data.vehicles.find(vehicle =>
            String(vehicle.registrationNumber || "").toLowerCase() === seed.registrationNumber.toLowerCase()
          );
          if (!existing) {
            if (data.vehicles.length >= seededVehicles.length) return;
            data.vehicles.push(seed);
            changed = true;
            return;
          }
          ["vehicleModel", "customerName", "phoneNumber", "customerAddress", "purchaseDate", "dealerCode", "dealerName"].forEach(field => {
            if (!existing[field]) {
              existing[field] = seed[field] || (field === "dealerCode" ? data.session?.dealerCode : field === "dealerName" ? data.session?.branch : "");
              changed = true;
            }
          });
        });
      }
      if (!Array.isArray(data.jobCards)) {
        data.jobCards = seededJobCards;
        changed = true;
      } else {
        seededJobCards.forEach(seed => {
          const existing = data.jobCards.find(jobCard => jobCard.id === seed.id);
          if (!existing && data.jobCards.length < seededJobCards.length) {
            data.jobCards.push(seed);
            changed = true;
            return;
          }
          if (!existing) return;

          const details = (existing.details && typeof existing.details === "object") ? existing.details : {};
          Object.entries(seed.details || {}).forEach(([field, value]) => {
            if (!hasValue(details[field]) && !hasValue(existing[field])) {
              details[field] = value;
              changed = true;
            }
          });
          if (existing.details !== details) {
            existing.details = details;
          }
        });
      }
      const dealerCode = data.session?.dealerCode || "62380";
      const dealerName = data.session?.branch || "YASH AUTOMOTORS";
      data.vehicles.forEach(vehicle => {
        if (!vehicle.dealerCode) {
          vehicle.dealerCode = dealerCode;
          changed = true;
        }
        if (!vehicle.dealerName) {
          vehicle.dealerName = dealerName;
          changed = true;
        }
      });
      if (!Array.isArray(data.mechanics) || JSON.stringify(data.mechanics) !== JSON.stringify(seededMechanics)) {
        data.mechanics = seededMechanics;
        changed = true;
      }
      if (!Array.isArray(data.serviceAdvisors) || data.serviceAdvisors.length === 0) {
        data.serviceAdvisors = seededServiceAdvisors;
        changed = true;
      }
      if (!Array.isArray(data.inventory)) {
        data.inventory = seedInventory();
        changed = true;
      }
      (data.jobCards || []).forEach((jobCard, index) => {
        const existingMechanic = jobCard.details?.vehicleAllocation || jobCard.vehicleAllocation || jobCard.mechanic;
        if (!seededMechanics.includes(existingMechanic)) {
          const assignedMechanic = seededMechanics[index % seededMechanics.length];
          jobCard.mechanic = assignedMechanic;
          jobCard.vehicleAllocation = assignedMechanic;
          changed = true;
        } else if (jobCard.vehicleAllocation !== existingMechanic || jobCard.mechanic !== existingMechanic) {
          jobCard.mechanic = existingMechanic;
          jobCard.vehicleAllocation = existingMechanic;
          changed = true;
        }
      });
      if (changed) save(data);
      return data;
    } catch (e) {
      const seed = seedData();
      localStorage.setItem(LS_KEY, JSON.stringify(seed));
      return seed;
    }
  }

  function save(data) { localStorage.setItem(LS_KEY, JSON.stringify(data)); }

  function get() { return load(); }

  function update(fn) {
    const data = load();
    fn(data);
    save(data);
    return data;
  }

  function upsertVehicle(data, vehicle) {
    if (!Array.isArray(data.vehicles)) data.vehicles = [];

    const registrationNumber = String(vehicle.registrationNumber || "").trim();
    if (!registrationNumber) return null;

    const existing = data.vehicles.find(item =>
      String(item.registrationNumber || "").trim().toLowerCase() === registrationNumber.toLowerCase()
    );
    if (existing) {
      Object.assign(existing, vehicle, { registrationNumber });
      return existing;
    }

    const newVehicle = { ...vehicle, registrationNumber };
    data.vehicles.push(newVehicle);
    return newVehicle;
  }

  return { get, update, seedData, seedVehicles, seedJobCards, seedMechanics, seedServiceAdvisors, seedInventory, upsertVehicle };
})();

/* ---------- Theme ---------- */
function applyTheme() {
  const data = DMS.get();
  document.documentElement.setAttribute("data-theme", data.theme || "light");
}
function toggleTheme() {
  DMS.update(d => { d.theme = (d.theme === "dark") ? "light" : "dark"; });
  applyTheme();
}

/* ---------- Toast ---------- */
function showToast(message, type = "default") {
  let wrap = document.querySelector(".toast-wrap");
  if (!wrap) {
    wrap = document.createElement("div");
    wrap.className = "toast-wrap";
    document.body.appendChild(wrap);
  }
  const t = document.createElement("div");
  t.className = "toast" + (type !== "default" ? " " + type : "");
  t.innerHTML = message;
  wrap.appendChild(t);
  setTimeout(() => { t.style.opacity = "0"; t.style.transition = "opacity .3s"; setTimeout(() => t.remove(), 300); }, 2800);
}

/* ---------- Sidebar + Topbar builder ---------- */
const NAV_ITEMS = [
  { group: "Overview", items: [
    { href: "dashboard.html", icon: "📊", label: "Dashboard" }
  ]},
  { group: "Service", items: [
    { href: "jobcard-list.html", icon: "🧾", label: "Service History" },
    { href: "jobcard-create.html", icon: "🛠️", label: "Job Card" }
  ]},
  { group: "Parts", items: [
    { href: "parts-catalog.html", icon: "⚙️", label: "Parts E-Catalog" }
  ]}
];

function buildShell(activeHref, pageTitle, pageSub) {
  const sidebar = document.getElementById("sidebar");
  const role = DMS.get().session.role;
  let navHtml = "";
  NAV_ITEMS.forEach(g => {
    navHtml += `<div class="nav-group-label">${g.group}</div>`;
    g.items.forEach(it => {
      const active = it.href === activeHref ? "active" : "";
      navHtml += `<a href="${it.href}" class="${active}"><span class="ic">${it.icon}</span>${it.label}</a>`;
    });
  });
  sidebar.innerHTML = `
    <div class="sidebar-brand">
      <div class="logo-mark">AL</div>
      <div class="brand-text"><b>AutoLine DMS</b><span>DEALERSHIP MGMT SYSTEM</span></div>
    </div>
    <div class="side-nav">${navHtml}</div>
    <div class="sidebar-foot">Branch: YASH AUTOMOTORS<br>v2.4 — Student Demo Build</div>
  `;

  const topbar = document.getElementById("topbar");
  const data = DMS.get();
  const initials = (data.session.userId || "AD").slice(0, 2).toUpperCase();
  topbar.innerHTML = `
    <div class="hamburger icon-btn" id="hamburgerBtn">☰</div>
    <div>
      <div class="page-title">${pageTitle}</div>
      <div class="page-sub">${pageSub || ""}</div>
    </div>
    <div class="search-box"><span>🔍</span><input placeholder="Search job card, frame no, part no..." id="globalSearch"></div>
    <div class="top-actions">
      <div style="position:relative;">
        <div class="icon-btn" id="themeBtn" title="Toggle theme">${data.theme === "dark" ? "☀️" : "🌙"}</div>
      </div>
      <div style="position:relative;">
        <div class="icon-btn" id="notifBtn">🔔<span class="badge-dot">${data.notifications.length}</span></div>
        <div class="dropdown-panel" id="notifPanel">
          <div class="dp-head">Notifications</div>
          ${data.notifications.map(n => `<div class="dp-item"><b>${n.title}</b><span>${n.detail} · ${n.time}</span></div>`).join("")}
        </div>
      </div>
      <div style="position:relative;">
        <div class="user-chip" id="userChip">
          <div class="avatar">${initials}</div>
          <div class="who"><b>${data.session.userId}</b><span>${data.session.role}</span></div>
        </div>
        <div class="dropdown-panel" id="userPanel" style="width:220px;">
          <div class="dp-item"><b>Switch Role (demo)</b>
            <select id="roleSwitch" style="width:100%;margin-top:6px;padding:6px;border-radius:6px;border:1px solid var(--border);background:var(--bg);color:var(--text);">
              <option ${data.session.role==='ADMIN'?'selected':''}>ADMIN</option>
              <option ${data.session.role==='MANAGER'?'selected':''}>MANAGER</option>
              <option ${data.session.role==='MECHANIC'?'selected':''}>MECHANIC</option>
            </select>
          </div>
          <div class="dp-item" id="logoutBtn" style="cursor:pointer;color:var(--danger);font-weight:700;">⎋ Logout</div>
        </div>
      </div>
    </div>
  `;

  document.getElementById("themeBtn").onclick = () => { toggleTheme(); location.reload(); };
  document.getElementById("notifBtn").onclick = (e) => { e.stopPropagation(); togglePanel("notifPanel"); };
  document.getElementById("userChip").onclick = (e) => { e.stopPropagation(); togglePanel("userPanel"); };
  document.getElementById("logoutBtn").onclick = () => {
    DMS.update(d => d.session.loggedIn = false);
    window.location.href = "index.html";
  };
  document.getElementById("roleSwitch").onchange = (e) => {
    DMS.update(d => d.session.role = e.target.value);
    showToast(`Role switched to ${e.target.value}`, "success");
    setTimeout(()=>location.reload(), 600);
  };
  document.getElementById("hamburgerBtn").onclick = () => {
    document.getElementById("sidebar").classList.toggle("open");
    document.getElementById("overlayBg").classList.toggle("show");
  };
  document.getElementById("overlayBg").onclick = () => {
    document.getElementById("sidebar").classList.remove("open");
    document.getElementById("overlayBg").classList.remove("show");
  };
  document.getElementById("globalSearch").addEventListener("keydown", (e) => {
    if (e.key === "Enter" && e.target.value.trim()) {
      window.location.href = "jobcard-list.html?q=" + encodeURIComponent(e.target.value.trim());
    }
  });

  document.addEventListener("click", () => {
    document.querySelectorAll(".dropdown-panel").forEach(p => p.classList.remove("open"));
  });
}

function togglePanel(id) {
  document.querySelectorAll(".dropdown-panel").forEach(p => { if (p.id !== id) p.classList.remove("open"); });
  document.getElementById(id).classList.toggle("open");
}

function requireLogin() {
  const data = DMS.get();
  if (!data.session.loggedIn) window.location.href = "index.html";
}

function exportTableToCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  let csv = [];
  table.querySelectorAll("tr").forEach(row => {
    const cols = Array.from(row.querySelectorAll("th,td")).map(c => `"${c.innerText.replace(/"/g,'""')}"`);
    csv.push(cols.join(","));
  });
  const blob = new Blob([csv.join("\n")], { type: "text/csv" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = filename || "export.csv";
  a.click();
}

function statusPill(status) {
  const map = {
    "Allocated": "amber", "Job Completed": "blue", "Closed": "green",
    "Unallocated": "gray", "Job Created": "gray", "Open": "red"
  };
  return `<span class="pill ${map[status] || 'gray'}">${status}</span>`;
}

document.addEventListener("DOMContentLoaded", applyTheme);
