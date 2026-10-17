(function () {
  const baseUrl = "../../php-backend/api/";
  const importKey = "autoline_job_cards_imported_v1";

  async function request(method = "GET", body, query = "") {
    const options = {
      method,
      credentials: "same-origin",
      headers: { "Accept": "application/json" }
    };
    if (body !== undefined) {
      options.headers["Content-Type"] = "application/json";
      options.body = JSON.stringify(body);
    }
    const response = await fetch(`${baseUrl}jobcards.php${query}`, options);
    const result = await response.json().catch(() => null);
    if (!response.ok || !result || !result.success) {
      const error = new Error(result?.message || "Job-card request failed.");
      error.status = response.status;
      throw error;
    }
    return result;
  }

  function legacyJobCards() {
    const data = DMS.get();
    const vehicles = new Map((data.vehicles || []).map(vehicle => [
      String(vehicle.registrationNumber || "").toLowerCase(), vehicle
    ]));
    return (Array.isArray(data.jobCards) ? data.jobCards : []).map(jobCard => {
      const details = jobCard.details || {};
      const vehicle = vehicles.get(String(jobCard.regNo || details.registrationNumber || "").toLowerCase()) || {};
      return {
        ...jobCard,
        jobCardNo: jobCard.jobCardNo || jobCard.id,
        registrationNumber: jobCard.registrationNumber || jobCard.regNo || details.registrationNumber,
        vehicleModel: jobCard.vehicleModel || jobCard.model || details.vehicleModel,
        customerName: jobCard.customerName || jobCard.customer || details.customerName || vehicle.customerName,
        phoneNumber: jobCard.phoneNumber || details.phoneNumber || vehicle.phoneNumber || "",
        customerAddress: jobCard.customerAddress || details.customerAddress || vehicle.customerAddress || "",
        purchaseDate: jobCard.purchaseDate || details.purchaseDate || vehicle.purchaseDate || "",
        dealerCode: jobCard.dealerCode || details.dealerCode || vehicle.dealerCode || "",
        dealerName: jobCard.dealerName || details.dealerName || vehicle.dealerName || "",
        customerLegacyKey: `${String(jobCard.customerName || jobCard.customer || details.customerName || vehicle.customerName || "").trim().toLowerCase()}|${String(jobCard.phoneNumber || details.phoneNumber || vehicle.phoneNumber || "").trim().toLowerCase()}|${String(jobCard.customerAddress || details.customerAddress || vehicle.customerAddress || "").trim().toLowerCase()}`,
        details: { ...details, phoneNumber: details.phoneNumber || vehicle.phoneNumber || "", customerAddress: details.customerAddress || vehicle.customerAddress || "", purchaseDate: details.purchaseDate || vehicle.purchaseDate || "" }
      };
    });
  }

  async function loadJobCards() {
    const fallback = legacyJobCards();
    try {
      let result = await request();
      if (fallback.length > 0 && !localStorage.getItem(importKey)) {
        await request("POST", { items: fallback });
        localStorage.setItem(importKey, "1");
        result = await request();
      }
      return { jobCards: Array.isArray(result.items) ? result.items : [], fromApi: true };
    } catch (error) {
      return { jobCards: fallback, fromApi: false, error };
    }
  }

  async function saveJobCard(jobCard) {
    return request("POST", jobCard);
  }

  async function getJobCard(id) {
    return request("GET", undefined, `?id=${encodeURIComponent(id)}`);
  }

  async function updateStatus(jobCardNo, status, cancelledFromStatus = "") {
    return request("PUT", { jobCardNo, status, cancelledFromStatus });
  }

  window.JobCardAPI = { request, loadJobCards, saveJobCard, getJobCard, updateStatus };
})();
