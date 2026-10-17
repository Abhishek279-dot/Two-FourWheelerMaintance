(function () {
  const baseUrl = "../../php-backend/api/";
  const importKey = "autoline_customer_vehicle_imported_v1";

  async function request(path, method = "GET", body) {
    const options = {
      method,
      credentials: "same-origin",
      headers: { "Accept": "application/json" }
    };

    if (body !== undefined) {
      options.headers["Content-Type"] = "application/json";
      options.body = JSON.stringify(body);
    }

    const response = await fetch(baseUrl + path, options);
    const result = await response.json().catch(() => null);
    if (!response.ok || !result || !result.success) {
      const error = new Error(result?.message || "Customer and vehicle request failed.");
      error.status = response.status;
      throw error;
    }
    return result;
  }

  function legacyKey(vehicle) {
    const phone = String(vehicle.phoneNumber || "").trim().toLowerCase();
    const name = String(vehicle.customerName || "").trim().toLowerCase();
    const address = String(vehicle.customerAddress || "").trim().toLowerCase();
    return `${name}|${phone}|${address}`;
  }

  function legacyVehicles() {
    const data = DMS.get();
    return Array.isArray(data.vehicles) ? data.vehicles : [];
  }

  function importPayload(vehicle) {
    return {
      registrationNumber: vehicle.registrationNumber,
      vehicleModel: vehicle.vehicleModel,
      customerName: vehicle.customerName,
      phoneNumber: vehicle.phoneNumber,
      customerAddress: vehicle.customerAddress,
      purchaseDate: vehicle.purchaseDate,
      dealerCode: vehicle.dealerCode,
      dealerName: vehicle.dealerName,
      customerLegacyKey: legacyKey(vehicle)
    };
  }

  async function loadVehicles() {
    const fallback = legacyVehicles();
    try {
      let result = await request("vehicles.php");
      if (fallback.length > 0 && !localStorage.getItem(importKey)) {
        await request("vehicles.php", "POST", { items: fallback.map(importPayload) });
        localStorage.setItem(importKey, "1");
        result = await request("vehicles.php");
      }
      return { vehicles: Array.isArray(result.items) ? result.items : [], fromApi: true };
    } catch (error) {
      return { vehicles: fallback, fromApi: false, error };
    }
  }

  async function saveVehicle(vehicle) {
    return request("vehicles.php", "POST", vehicle);
  }

  window.CustomerVehicleAPI = { request, loadVehicles, saveVehicle, legacyVehicles };
})();
