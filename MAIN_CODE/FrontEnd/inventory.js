(function () {
  const data = DMS.get();
  const state = { items: Array.isArray(data.inventory) ? data.inventory : [], page: 1, pageSize: 5, query: "", category: "", status: "" };
  const $ = id => document.getElementById(id);

  const fields = ["partName", "partCode", "category", "compatibleWith", "quantity", "minStock", "price", "status", "image"];

  function escapeHtml(value) {
    return String(value ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }

  function save() {
    DMS.update(current => { current.inventory = state.items; });
  }

  function formatCurrency(value) {
    return Number(value || 0).toLocaleString("en-IN", { maximumFractionDigits: 2 });
  }

  function statusClass(status) {
    return String(status || "").toLowerCase().replace(/\s+/g, "-");
  }

  function filteredItems() {
    const query = state.query.toLowerCase();
    return state.items.filter(item => {
      const searchable = [item.partName, item.partCode, item.category, item.compatibleWith].join(" ").toLowerCase();
      return (!query || searchable.includes(query)) && (!state.category || item.category === state.category) && (!state.status || item.status === state.status);
    });
  }

  function imageMarkup(item) {
    if (!item.image) return '<span class="part-image"><i class="fa-regular fa-image"></i></span>';
    return `<span class="part-image"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.partName)}"></span>`;
  }

  function bindImageErrors() {
    document.querySelectorAll(".part-image img, #imagePreview img").forEach(image => {
      image.addEventListener("error", () => {
        const holder = image.parentElement;
        holder.innerHTML = '<i class="fa-regular fa-image"></i><small>Image unavailable</small>';
      }, { once: true });
    });
  }

  function renderSummary() {
    $("totalParts").textContent = state.items.length;
    $("inStockCount").textContent = state.items.filter(item => item.status === "In Stock").length;
    $("lowStockCount").textContent = state.items.filter(item => item.status === "Low Stock").length;
    $("inventoryValue").textContent = `₹${formatCurrency(state.items.reduce((total, item) => total + Number(item.quantity || 0) * Number(item.price || 0), 0))}`;
  }

  function renderCategories() {
    const categories = [...new Set(state.items.map(item => item.category).filter(Boolean))].sort();
    const select = $("categoryFilter");
    select.innerHTML = '<option value="">All Categories</option>' + categories.map(category => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`).join("");
    select.value = state.category;
  }

  function renderPagination(totalPages) {
    const pagination = $("pagination");
    pagination.innerHTML = "";
    const previous = document.createElement("button");
    previous.className = "page-button";
    previous.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    previous.disabled = state.page === 1;
    previous.addEventListener("click", () => { state.page -= 1; render(); });
    pagination.appendChild(previous);
    for (let page = 1; page <= totalPages; page += 1) {
      const button = document.createElement("button");
      button.className = `page-button${page === state.page ? " active" : ""}`;
      button.textContent = page;
      button.addEventListener("click", () => { state.page = page; render(); });
      pagination.appendChild(button);
    }
    const next = document.createElement("button");
    next.className = "page-button";
    next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    next.disabled = state.page === totalPages;
    next.addEventListener("click", () => { state.page += 1; render(); });
    pagination.appendChild(next);
  }

  function render() {
    renderSummary();
    renderCategories();
    const items = filteredItems();
    const totalPages = Math.max(1, Math.ceil(items.length / state.pageSize));
    state.page = Math.min(state.page, totalPages);
    const start = (state.page - 1) * state.pageSize;
    const visibleItems = items.slice(start, start + state.pageSize);
    $("inventoryBody").innerHTML = visibleItems.map((item, index) => `<tr>
      <td>${start + index + 1}</td>
      <td>${imageMarkup(item)}</td>
      <td class="part-name">${escapeHtml(item.partName)}</td>
      <td class="part-code">${escapeHtml(item.partCode)}</td>
      <td>${escapeHtml(item.category)}</td>
      <td>${escapeHtml(item.compatibleWith)}</td>
      <td class="number-cell">${Number(item.quantity || 0)}</td>
      <td class="number-cell">${Number(item.minStock || 0)}</td>
      <td class="number-cell">${formatCurrency(item.price)}</td>
      <td><span class="status-pill ${statusClass(item.status)}">${escapeHtml(item.status)}</span></td>
      <td><div class="actions"><button class="action-button edit" data-action="edit" data-id="${escapeHtml(item.id)}" type="button" title="Edit part"><i class="fa-solid fa-pen-to-square"></i></button><button class="action-button copy" data-action="copy" data-id="${escapeHtml(item.id)}" type="button" title="Duplicate part"><i class="fa-regular fa-copy"></i></button><button class="action-button delete" data-action="delete" data-id="${escapeHtml(item.id)}" type="button" title="Delete part"><i class="fa-solid fa-trash-can"></i></button></div></td>
    </tr>`).join("");
    $("emptyState").hidden = visibleItems.length > 0;
    const end = Math.min(start + visibleItems.length, items.length);
    $("showingText").textContent = items.length ? `Showing ${start + 1} to ${end} of ${items.length} entries` : "Showing 0 entries";
    renderPagination(totalPages);
    $("filterNote").hidden = !(state.query || state.category || state.status);
    bindImageErrors();
  }

  function resetForm(item) {
    $("partForm").reset();
    $("partId").value = item?.id || "";
    fields.forEach(field => { if (item && $(field)) $(field).value = item[field] ?? ""; });
    $("modalTitle").textContent = item ? "Edit Inventory Part" : "Add Inventory Part";
    updatePreview();
  }

  function openModal(item) {
    resetForm(item);
    $("partModal").hidden = false;
    document.body.style.overflow = "hidden";
    $("partName").focus();
  }

  function closeModal() {
    $("partModal").hidden = true;
    document.body.style.overflow = "";
  }

  function updatePreview() {
    const url = $("image").value.trim();
    $("imagePreview").innerHTML = url ? `<img src="${escapeHtml(url)}" alt="Part preview">` : '<i class="fa-regular fa-image"></i><small>No image link added</small>';
    bindImageErrors();
  }

  function showToast(message) {
    const toast = $("toast");
    toast.textContent = message;
    toast.classList.add("show");
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => toast.classList.remove("show"), 2400);
  }

  $("inventoryBody").addEventListener("click", event => {
    const button = event.target.closest("[data-action]");
    if (!button) return;
    const item = state.items.find(entry => entry.id === button.dataset.id);
    if (!item) return;
    if (button.dataset.action === "edit") openModal(item);
    if (button.dataset.action === "copy") {
      const copy = { ...item, id: `INV-${Date.now()}`, partCode: `${item.partCode}-COPY`, partName: `${item.partName} Copy` };
      state.items.push(copy); save(); render(); showToast("Part duplicated");
    }
    if (button.dataset.action === "delete" && window.confirm(`Delete ${item.partName}?`)) {
      state.items = state.items.filter(entry => entry.id !== item.id); save(); render(); showToast("Part deleted");
    }
  });

  $("partForm").addEventListener("submit", event => {
    event.preventDefault();
    const item = Object.fromEntries(new FormData(event.target).entries());
    item.id = $("partId").value || `INV-${Date.now()}`;
    ["quantity", "minStock", "price"].forEach(field => { item[field] = Number(item[field] || 0); });
    const index = state.items.findIndex(entry => entry.id === item.id);
    if (index >= 0) state.items[index] = item; else state.items.push(item);
    save(); closeModal(); render(); showToast(index >= 0 ? "Part updated successfully" : "Part added successfully");
  });

  $("addPartButton").addEventListener("click", () => openModal());
  $("closeModal").addEventListener("click", closeModal);
  $("cancelModal").addEventListener("click", closeModal);
  $("partModal").addEventListener("click", event => { if (event.target === $("partModal")) closeModal(); });
  $("image").addEventListener("input", updatePreview);
  $("searchInput").addEventListener("input", event => { state.query = event.target.value.trim(); state.page = 1; render(); });
  $("categoryFilter").addEventListener("change", event => { state.category = event.target.value; state.page = 1; render(); });
  $("statusFilter").addEventListener("change", event => { state.status = event.target.value; state.page = 1; render(); });
  $("pageSize").addEventListener("change", event => { state.pageSize = Number(event.target.value); state.page = 1; render(); });
  $("filterButton").addEventListener("click", () => { $("categoryFilter").focus(); $("filterButton").classList.toggle("active"); });
  document.addEventListener("keydown", event => { if (event.key === "Escape" && !$("partModal").hidden) closeModal(); });

  const session = data.session || {};
  $("userRole").textContent = localStorage.getItem("userRole") || session.role || "Admin";
  $("userBranch").textContent = localStorage.getItem("userBranch") || session.branch || "Swami Service Center";
  render();
})();
