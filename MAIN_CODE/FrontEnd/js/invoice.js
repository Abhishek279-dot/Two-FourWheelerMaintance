const catalogBody = document.getElementById("catalogBody");
const catalogSearch = document.getElementById("catalogSearch");
const recordCount = document.getElementById("recordCount");
const emptyState = document.getElementById("emptyState");

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function getInvoiceRows() {
  const data = DMS.get();
  const vehicles = new Map((data.vehicles || []).map(vehicle => [
    String(vehicle.registrationNumber || "").toLowerCase(), vehicle
  ]));
  const query = catalogSearch.value.trim().toLowerCase();

  return (data.jobCards || [])
    .map(jobCard => {
      const vehicle = vehicles.get(String(jobCard.regNo || "").toLowerCase()) || {};
      return {
        ...jobCard,
        phone: jobCard.details?.phoneNumber || vehicle.phoneNumber || "-",
        customer: jobCard.customer || vehicle.customerName || "-",
        model: jobCard.model || vehicle.vehicleModel || "-"
      };
    })
    .filter(jobCard => {
      if (!query) return true;
      return [jobCard.id, jobCard.regNo, jobCard.customer, jobCard.phone, jobCard.model]
        .some(value => String(value || "").toLowerCase().includes(query));
    });
}

function renderInvoiceCatalog() {
  const rows = getInvoiceRows();
  recordCount.textContent = `${rows.length} ${rows.length === 1 ? "record" : "records"}`;
  emptyState.hidden = rows.length > 0;

  catalogBody.innerHTML = rows.map((jobCard, index) => `
    <tr>
      <td class="serial-number">${index + 1}</td>
      <td class="job-card-number">
        ${escapeHtml(jobCard.id)}
        ${jobCard.status === "Cancelled" ? '<span class="cancelled-label">Cancelled</span>' : ""}
      </td>
      <td class="vehicle-number">${escapeHtml(jobCard.regNo)}</td>
      <td>${escapeHtml(jobCard.customer)}</td>
      <td>${escapeHtml(jobCard.date)}</td>
      <td>${escapeHtml(jobCard.phone)}</td>
      <td class="model-cell">${escapeHtml(jobCard.model)}</td>
      <td><button class="create-invoice-button" type="button" data-invoice="${escapeHtml(jobCard.id)}">Create Invoice</button></td>
    </tr>`).join("");
}

catalogBody.addEventListener("click", event => {
  const invoiceButton = event.target.closest("[data-invoice]");
  if (invoiceButton) {
    window.alert(`Invoice creation started for ${invoiceButton.dataset.invoice}.`);
  }
});

catalogSearch.addEventListener("input", renderInvoiceCatalog);
window.addEventListener("pageshow", renderInvoiceCatalog);
window.addEventListener("storage", event => {
  if (event.key === "autoline_dms_data_v1") renderInvoiceCatalog();
});

const session = DMS.get().session || {};
document.getElementById("userRole").textContent = localStorage.getItem("userRole") || session.role || "Admin";
document.getElementById("userBranch").textContent = localStorage.getItem("userBranch") || session.branch || "Swami Service Center";
renderInvoiceCatalog();
