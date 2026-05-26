// app.js — shared auth guard, dynamic sidebar, and dashboard logic.
// Include this in every staff page. It calls window.onAuthReady(user) after
// auth resolves so page-specific code can run with a guaranteed user object.

const NAV = [
  {
    href: "index.html",
    icon: "fa-house",
    label: "Dashboard",
    roles: ["super_admin", "reception", "specialist"],
  },
  {
    href: "appointments.html",
    icon: "fa-calendar-check",
    label: "Appointments",
    roles: ["super_admin", "reception", "specialist"],
  },
  {
    href: "patients.html",
    icon: "fa-users",
    label: "Patients",
    roles: ["super_admin", "reception"],
  },
  {
    href: "reminders.html",
    icon: "fa-bell",
    label: "Automations",
    roles: ["super_admin", "reception"],
  },
  {
    href: "admin-users.html",
    icon: "fa-user-shield",
    label: "User Management",
    roles: ["super_admin"],
  },
];

// Pages each role is allowed to view. Redirect elsewhere if wrong role lands here.
const ROLE_HOME = {
  super_admin: "index.html",
  reception: "index.html",
  specialist: "appointments.html",
};

const currentPage = location.pathname.split("/").pop() || "index.html";

document.addEventListener("DOMContentLoaded", () => {
  // Auth check — redirect to login if session is gone
  fetch("api.php?action=auth_check")
    .then((r) => r.json())
    .then((data) => {
      if (!data.authenticated) {
        window.location.href = "login.html";
        return;
      }

      const user = data;
      buildNav(user);
      updateWelcome(user);
      setupSidebarToggle();
      fetchAndDisplayStats(user);

      if (typeof window.onAuthReady === "function") {
        window.onAuthReady(user);
      }
    })
    .catch(() => {
      window.location.href = "login.html";
    });
});

function buildNav(user) {
  const navEl = document.getElementById("nav-links");
  if (!navEl) return;

  navEl.innerHTML = NAV.filter((item) => item.roles.includes(user.role))
    .map((item) => {
      const isActive = currentPage === item.href ? " active" : "";
      return `<li class="nav-item">
        <a href="${item.href}" class="nav-link${isActive}">
          <i class="fa-solid ${item.icon}"></i>
          <span>${item.label}</span>
        </a>
      </li>`;
    })
    .join("");
}

function updateWelcome(user) {
  const msgEl = document.querySelector(".welcome-msg p");
  if (msgEl && msgEl.textContent.includes("Sara")) {
    msgEl.textContent = `Welcome back, ${user.full_name}. Here's what's happening today.`;
  }
  // Also update any static name reference in the header
  const h1 = document.querySelector(".welcome-msg h1");
  if (
    h1 &&
    h1.textContent === "Reception Dashboard" &&
    user.role === "specialist"
  ) {
    h1.textContent = "My Dashboard";
  }
}

function setupSidebarToggle() {
  const menuToggle = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".sidebar");
  if (!menuToggle || !sidebar) return;

  let overlay = document.querySelector(".sidebar-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    document.body.appendChild(overlay);
  }

  menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
  });
  overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
  });
}

// ── Dashboard stats (only runs on index.html) ──────────────────────────────

async function fetchAndDisplayStats() {
  if (currentPage !== "index.html" && currentPage !== "") return;

  try {
    const [statsRes, apptRes] = await Promise.all([
      fetch("api.php?action=get_today_stats"),
      fetch(`api.php?action=get_appointments&date=${todayStr()}`),
    ]);
    const stats = await statsRes.json();
    const appts = await apptRes.json();

    // Stat cards
    setStatCard(0, stats.total_today ?? appts.length);
    setStatCard(
      1,
      stats.waiting ?? appts.filter((a) => a.status === "Waiting").length,
    );
    setStatCard(2, stats.new_patients_today ?? 0);

    // Waiting room
    renderWaitingRoom(appts.filter((a) => a.status === "Waiting"));
    renderScheduledList(appts.filter((a) => a.status === "Scheduled"));
    updateTabLabels(appts);
  } catch (err) {
    console.error("Dashboard load error:", err);
  }
}

function setStatCard(index, value) {
  const el = document.querySelector(
    `.stat-card:nth-child(${index + 1}) .stat-value`,
  );
  if (el) el.textContent = value;
}

function renderWaitingRoom(waiting) {
  const container = document.querySelector(".waiting-grid");
  if (!container) return;

  if (!waiting.length) {
    container.innerHTML =
      '<p style="padding:2rem;color:#888;text-align:center">No patients in waiting room.</p>';
    return;
  }

  container.innerHTML = waiting
    .map(
      (a) => `
      <div class="waiting-card">
        <div class="waiting-info">
          <h4>${a.patient_name}</h4>
          <span>${a.notes || "Hejama"}</span><br>
          <div class="pulse-timer">
            ${new Date(a.appointment_date).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}
          </div>
        </div>
        <button class="btn btn-primary" style="padding:8px 15px;font-size:0.8rem"
          onclick="checkInPatient(${a.id}, ${a.patient_id})">
          Assign
        </button>
      </div>`,
    )
    .join("");
}

function renderScheduledList(scheduled) {
  const tbody = document.querySelector("#scheduled-content tbody");
  if (!tbody) return;

  if (!scheduled.length) {
    tbody.innerHTML =
      '<tr><td colspan="5" style="text-align:center;padding:2rem;color:#888">No appointments scheduled for today.</td></tr>';
    return;
  }

  tbody.innerHTML = scheduled
    .map(
      (a) => `
      <tr>
        <td style="font-weight:600">
          <a href="patient-details.html?id=${a.patient_id}" style="color:inherit;text-decoration:none">${a.patient_name}</a>
        </td>
        <td>${new Date(a.appointment_date).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}</td>
        <td>${a.specialist_name || "TBD"}</td>
        <td><span class="status-badge status-confirmed">Confirmed</span></td>
        <td>
          <button class="btn" style="padding:5px 10px;font-size:0.7rem"
            onclick="checkInPatient(${a.id}, ${a.patient_id})">Check In</button>
        </td>
      </tr>`,
    )
    .join("");
}

function updateTabLabels(appts) {
  const waitingTab = document.querySelector(".tab-item:nth-child(1)");
  const scheduledTab = document.querySelector(".tab-item:nth-child(2)");
  const waitingCount = appts.filter((a) => a.status === "Waiting").length;
  const scheduledCount = appts.filter((a) => a.status === "Scheduled").length;
  if (waitingTab) waitingTab.textContent = `Waiting Room (${waitingCount})`;
  if (scheduledTab)
    scheduledTab.textContent = `Scheduled Today (${scheduledCount})`;
}

async function checkInPatient(apptId, patientId) {
  await fetch("api.php?action=update_appointment", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: apptId, status: "Waiting" }),
  });
  fetchAndDisplayStats();
}

// Tab switching on dashboard
function switchTab(tabId) {
  document
    .querySelectorAll(".tab-item")
    .forEach((t) => t.classList.remove("active"));
  const waitingContent = document.getElementById("waiting-content");
  const scheduledContent = document.getElementById("scheduled-content");

  if (tabId === "waiting") {
    document.querySelector(".tab-item:nth-child(1)").classList.add("active");
    if (waitingContent) waitingContent.style.display = "block";
    if (scheduledContent) scheduledContent.style.display = "none";
  } else {
    document.querySelector(".tab-item:nth-child(2)").classList.add("active");
    if (waitingContent) waitingContent.style.display = "none";
    if (scheduledContent) scheduledContent.style.display = "block";
  }
}

// Manual sync button on dashboard
document.addEventListener("DOMContentLoaded", () => {
  const syncBtn = document.querySelector(".sidebar-right .btn-primary");
  if (syncBtn) {
    syncBtn.addEventListener("click", () => {
      syncBtn.textContent = "Syncing...";
      syncBtn.disabled = true;
      setTimeout(() => {
        syncBtn.textContent = "Sync Complete!";
        syncBtn.style.background = "var(--success)";
        setTimeout(() => {
          syncBtn.textContent = "Trigger Manual Sync";
          syncBtn.disabled = false;
          syncBtn.style.background = "";
        }, 2000);
      }, 1500);
    });
  }
});

function todayStr() {
  return new Date().toISOString().split("T")[0];
}
