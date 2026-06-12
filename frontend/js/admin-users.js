/**
 * admin-users.js
 * Handles: user listing, search/filter, pagination,
 *          create drawer (POST), edit drawer (PUT),
 *          delete modal, resend verification modal, CSV export
 */

let currentPage = 1;
let currentLimit = 10;
let currentSearch = "";
let currentVerified = null;
let totalPages = 1;

let pendingDeleteId = null;
let pendingResendId = null;
let drawerMode = "create"; // 'create' | 'edit'

/* ── Toast ────────────────────────────────────────────────── */

function showToast(message, type = "success") {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.className = `admin-toast admin-toast--${type} admin-toast--visible`;
  clearTimeout(toast._timer);
  toast._timer = setTimeout(
    () => toast.classList.remove("admin-toast--visible"),
    3500,
  );
}

/* ── Load users ───────────────────────────────────────────── */

async function loadUsers() {
  const url = new URL("/api/admin/users", window.location.origin);
  url.searchParams.set("page", currentPage);
  url.searchParams.set("limit", currentLimit);
  if (currentSearch) url.searchParams.set("search", currentSearch);
  if (currentVerified !== null)
    url.searchParams.set("verified", currentVerified);

  setTableLoading();

  try {
    const res = await fetch(url);
    const data = await res.json();

    if (data.status === "success") {
      renderUsers(data.data.users);
      totalPages = data.data.total_pages;
      updateMeta(data.data.total, data.data.users.length);
      updatePagination();
    } else {
      showToast("Failed to load users", "error");
      setTableEmpty("Could not load users. Please try again.");
    }
  } catch (err) {
    console.error(err);
    showToast("Network error", "error");
    setTableEmpty("Network error. Please check your connection.");
  }

  applyViewerRestrictions();
}

function setTableLoading() {
  document.getElementById("users-table-body").innerHTML = `
    <tr>
      <td colspan="6" class="admin-table__loading">
        <i class="fa-solid fa-circle-notch fa-spin"></i> Loading users…
      </td>
    </tr>`;
}

function setTableEmpty(msg) {
  document.getElementById("users-table-body").innerHTML = `
    <tr><td colspan="6" class="admin-table__empty">${escapeHtml(msg)}</td></tr>`;
}

/* ── Render ───────────────────────────────────────────────── */

function renderUsers(users) {
  const tbody = document.getElementById("users-table-body");

  if (!users || users.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="admin-table__empty">
          <i class="fa-solid fa-users" style="font-size:24px;margin-bottom:8px;display:block;opacity:.3"></i>
          No users found
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = users
    .map((u) => {
      const initials = getInitials(u.name);
      const verified = u.email_verified
        ? `<span class="badge badge--green"><i class="fa-solid fa-circle-check" style="font-size:9px"></i> Verified</span>`
        : `<span class="badge badge--amber"><i class="fa-solid fa-clock" style="font-size:9px"></i> Pending</span>`;

      const date = new Date(u.created_at).toLocaleDateString(undefined, {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });

      const resendBtn = !u.email_verified
        ? `<button class="table-btn js-resend-btn admin-action-btn" data-id="${u.id}" data-email="${escapeHtml(u.email)}">
           <i class="fa-solid fa-paper-plane" style="font-size:10px"></i> Resend
         </button>`
        : "";

      return `
      <tr>
        <td style="color:var(--admin-text3);font-size:12px">${u.id}</td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="user-avatar">${initials}</div>
            <span class="cell-primary">${escapeHtml(u.name)}</span>
          </div>
        </td>
        <td style="color:var(--admin-text2);font-size:13px">${escapeHtml(u.email)}</td>
        <td>${verified}</td>
        <td style="color:var(--admin-text3);font-size:12px;white-space:nowrap">${date}</td>
        <td class="cell-actions">
          <button
            class="table-btn js-edit-btn admin-action-btn"
            data-id="${u.id}"
            data-name="${escapeHtml(u.name)}"
            data-email="${escapeHtml(u.email)}"
            data-verified="${u.email_verified ? "1" : "0"}"
          >
            <i class="fa-solid fa-pen" style="font-size:10px"></i> Edit
          </button>
          ${resendBtn}
          <button
            class="table-btn table-btn--danger js-delete-btn admin-action-btn"
            data-id="${u.id}"
            data-name="${escapeHtml(u.name)}"
          >
            <i class="fa-solid fa-trash" style="font-size:10px"></i> Delete
          </button>
        </td>
      </tr>`;
    })
    .join("");
}

function getInitials(name) {
  if (!name) return "?";
  const parts = name.trim().split(/\s+/);
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.substring(0, 2).toUpperCase();
}

/* ── Pagination & meta ────────────────────────────────────── */

function updatePagination() {
  document.getElementById("prev-page").disabled = currentPage <= 1;
  document.getElementById("next-page").disabled = currentPage >= totalPages;
  document.getElementById("page-info").textContent =
    `Page ${currentPage} of ${totalPages}`;
}

function updateMeta(total, shown) {
  const meta = document.getElementById("results-meta");
  if (!meta || total === undefined) return;
  const start = (currentPage - 1) * currentLimit + 1;
  const end = start + shown - 1;
  meta.textContent =
    total > 0
      ? `Showing ${start}–${end} of ${total} user${total !== 1 ? "s" : ""}`
      : "0 users";
}

/* ── Drawer (shared for create + edit) ───────────────────── */

function clearDrawer() {
  document.getElementById("edit-user-id").value = "";
  document.getElementById("edit-name").value = "";
  document.getElementById("edit-email").value = "";
  document.getElementById("edit-password").value = "";
  document.getElementById("edit-verified").checked = false;
  ["err-edit-name", "err-edit-email", "err-edit-password"].forEach((id) => {
    document.getElementById(id).textContent = "";
  });
  const alert = document.getElementById("drawer-alert");
  alert.style.display = "none";
  alert.textContent = "";
}

function openDrawer() {
  document.getElementById("edit-drawer").classList.add("user-drawer--open");
  document
    .getElementById("edit-overlay")
    .classList.add("user-drawer-overlay--visible");
  document.body.style.overflow = "hidden";
}

function closeDrawer() {
  document.getElementById("edit-drawer").classList.remove("user-drawer--open");
  document
    .getElementById("edit-overlay")
    .classList.remove("user-drawer-overlay--visible");
  document.body.style.overflow = "";
}

function openCreateDrawer() {
  drawerMode = "create";
  clearDrawer();

  document.getElementById("drawer-avatar").textContent = "+";
  document.getElementById("drawer-title").textContent = "New user";
  document.getElementById("drawer-subtitle").textContent =
    "Fill in the details below";
  document.getElementById("password-field").style.display = "block";
  document.getElementById("edit-save-btn").textContent = "Create user";

  openDrawer();
  setTimeout(() => document.getElementById("edit-name").focus(), 150);
}

function openEditDrawer(id, name, email, verified) {
  drawerMode = "edit";
  clearDrawer();

  document.getElementById("edit-user-id").value = id;
  document.getElementById("edit-name").value = name;
  document.getElementById("edit-email").value = email;
  document.getElementById("edit-verified").checked =
    verified === "1" || verified === true;
  document.getElementById("drawer-avatar").textContent = getInitials(name);
  document.getElementById("drawer-title").textContent = name;
  document.getElementById("drawer-subtitle").textContent = email;
  document.getElementById("password-field").style.display = "none";
  document.getElementById("edit-save-btn").textContent = "Save changes";

  openDrawer();
  setTimeout(() => document.getElementById("edit-name").focus(), 150);
}

/* ── Drawer form submit ───────────────────────────────────── */

document
  .getElementById("edit-user-form")
  .addEventListener("submit", async (e) => {
    e.preventDefault();

    ["err-edit-name", "err-edit-email", "err-edit-password"].forEach((id) => {
      document.getElementById(id).textContent = "";
    });

    const id = document.getElementById("edit-user-id").value;
    const name = document.getElementById("edit-name").value.trim();
    const email = document.getElementById("edit-email").value.trim();
    const password = document.getElementById("edit-password").value;
    const verified = document.getElementById("edit-verified").checked;

    let valid = true;
    if (!name) {
      document.getElementById("err-edit-name").textContent = "Name is required";
      valid = false;
    }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      document.getElementById("err-edit-email").textContent =
        "A valid email is required";
      valid = false;
    }
    if (drawerMode === "create" && password.length < 8) {
      document.getElementById("err-edit-password").textContent =
        "Password must be at least 8 characters";
      valid = false;
    }
    if (!valid) return;

    const btn = document.getElementById("edit-save-btn");
    btn.disabled = true;
    btn.innerHTML =
      '<i class="fa-solid fa-circle-notch fa-spin" style="font-size:11px"></i> Saving…';

    const isCreate = drawerMode === "create";
    const url = isCreate ? "/api/admin/users" : `/api/admin/users/${id}`;
    const method = isCreate ? "POST" : "PUT";
    const payload = isCreate
      ? { name, email, password, email_verified: verified }
      : { name, email, email_verified: verified };

    try {
      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (res.ok) {
        showToast(
          isCreate ? "User created successfully" : "User updated successfully",
          "success",
        );
        closeDrawer();
        loadUsers();
      } else {
        const alertEl = document.getElementById("drawer-alert");
        alertEl.textContent = data.message || "Something went wrong";
        alertEl.className = "profile-alert profile-alert--error";
        alertEl.style.display = "block";
      }
    } catch (err) {
      console.error(err);
      const alertEl = document.getElementById("drawer-alert");
      alertEl.textContent = "Network error. Please try again.";
      alertEl.className = "profile-alert profile-alert--error";
      alertEl.style.display = "block";
    } finally {
      btn.disabled = false;
      btn.textContent = isCreate ? "Create user" : "Save changes";
    }
  });

/* ── Delete modal ─────────────────────────────────────────── */

function openDeleteModal(id, name) {
  pendingDeleteId = id;
  document.getElementById("delete-modal-body").textContent =
    `"${name}" and all associated data will be permanently removed. This action cannot be undone.`;
  document.getElementById("delete-modal").style.display = "flex";
}

function closeDeleteModal() {
  pendingDeleteId = null;
  document.getElementById("delete-modal").style.display = "none";
}

async function confirmDelete() {
  if (!pendingDeleteId) return;

  const btn = document.getElementById("delete-confirm");
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Deleting…';

  try {
    const res = await fetch(`/api/admin/users/${pendingDeleteId}`, {
      method: "DELETE",
    });
    const data = await res.json();

    if (res.ok) {
      showToast("User deleted successfully", "success");
      closeDeleteModal();
      if (currentPage > 1) currentPage--;
      loadUsers();
    } else {
      showToast(data.message || "Delete failed", "error");
    }
  } catch (err) {
    console.error(err);
    showToast("Network error", "error");
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-trash"></i> Delete';
  }
}

/* ── Resend verification modal ────────────────────────────── */

function openResendModal(id, email) {
  pendingResendId = id;
  document.getElementById("resend-modal-body").textContent =
    `A new verification email will be sent to ${email}.`;
  document.getElementById("resend-modal").style.display = "flex";
}

function closeResendModal() {
  pendingResendId = null;
  document.getElementById("resend-modal").style.display = "none";
}

async function confirmResend() {
  if (!pendingResendId) return;

  const btn = document.getElementById("resend-confirm");
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending…';

  try {
    const res = await fetch(
      `/api/admin/users/${pendingResendId}/resend-verification`,
      { method: "POST" },
    );
    const data = await res.json();

    if (res.ok) {
      showToast("Verification email sent successfully", "success");
      closeResendModal();
    } else {
      showToast(data.message || "Failed to resend", "error");
    }
  } catch (err) {
    console.error(err);
    showToast("Network error", "error");
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send email';
  }
}

/* ── Event delegation ─────────────────────────────────────── */

document.addEventListener("click", (e) => {
  const editBtn = e.target.closest(".js-edit-btn");
  if (editBtn) {
    openEditDrawer(
      editBtn.dataset.id,
      editBtn.dataset.name,
      editBtn.dataset.email,
      editBtn.dataset.verified,
    );
    return;
  }
  const deleteBtn = e.target.closest(".js-delete-btn");
  if (deleteBtn) {
    openDeleteModal(deleteBtn.dataset.id, deleteBtn.dataset.name);
    return;
  }
  const resendBtn = e.target.closest(".js-resend-btn");
  if (resendBtn) {
    openResendModal(resendBtn.dataset.id, resendBtn.dataset.email);
  }
});

// New user button
document
  .getElementById("new-user-btn")
  .addEventListener("click", openCreateDrawer);

// Drawer close controls
document.getElementById("edit-close").addEventListener("click", closeDrawer);
document
  .getElementById("edit-cancel-btn")
  .addEventListener("click", closeDrawer);
document.getElementById("edit-overlay").addEventListener("click", closeDrawer);

// Delete modal
document
  .getElementById("delete-cancel")
  .addEventListener("click", closeDeleteModal);
document
  .getElementById("delete-confirm")
  .addEventListener("click", confirmDelete);
document.getElementById("delete-modal").addEventListener("click", (e) => {
  if (e.target === e.currentTarget) closeDeleteModal();
});

// Resend modal
document
  .getElementById("resend-cancel")
  .addEventListener("click", closeResendModal);
document
  .getElementById("resend-confirm")
  .addEventListener("click", confirmResend);
document.getElementById("resend-modal").addEventListener("click", (e) => {
  if (e.target === e.currentTarget) closeResendModal();
});

// Password visibility toggle
document.querySelectorAll(".profile-toggle-pw").forEach((btn) => {
  btn.addEventListener("click", () => {
    const input = document.getElementById(btn.dataset.target);
    const icon = btn.querySelector("i");
    const hide = input.type === "password";
    input.type = hide ? "text" : "password";
    icon.classList.toggle("fa-eye", !hide);
    icon.classList.toggle("fa-eye-slash", hide);
  });
});

// Escape closes everything
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeDrawer();
    closeDeleteModal();
    closeResendModal();
  }
});

/* ── Filters & search ─────────────────────────────────────── */

let searchTimer;
document.getElementById("search-input").addEventListener("input", (e) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentSearch = e.target.value.trim();
    currentPage = 1;
    loadUsers();
  }, 300);
});

document.getElementById("verified-filter").addEventListener("change", (e) => {
  const val = e.target.value;
  currentVerified =
    val === "verified" ? true : val === "unverified" ? false : null;
  currentPage = 1;
  loadUsers();
});

/* ── Pagination ───────────────────────────────────────────── */

document.getElementById("prev-page").addEventListener("click", () => {
  if (currentPage > 1) {
    currentPage--;
    loadUsers();
  }
});
document.getElementById("next-page").addEventListener("click", () => {
  if (currentPage < totalPages) {
    currentPage++;
    loadUsers();
  }
});

/* ── Export ───────────────────────────────────────────────── */

document.getElementById("export-csv").addEventListener("click", () => {
  window.location.href = "/api/admin/users/export";
});

/* ── Helpers ──────────────────────────────────────────────── */

function escapeHtml(str) {
  if (str == null) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

/* ── Init ─────────────────────────────────────────────────── */

loadUsers();

// ─── Role-Based UI Restrictions ───────────────────────────────────────────

/**
 * Hides all write-action elements from viewer-role users.
 * Safe to call multiple times (idempotent).
 */
function applyViewerRestrictions() {
  if (window.adminRole === undefined) return;
  if (window.adminRole === "admin") return; // admins see everything

  // Page header — "New User" button
  const newUserBtn = document.getElementById("new-user-btn");
  if (newUserBtn) newUserBtn.style.display = "none";

  // Table rows — Edit, Delete, Resend Verification buttons
  document
    .querySelectorAll(".js-edit-btn, .js-delete-btn, .js-resend-btn")
    .forEach((btn) => (btn.style.display = "none"));
}

// Apply when role is known
document.addEventListener("roleLoaded", applyViewerRestrictions);
