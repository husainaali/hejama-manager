document.addEventListener("DOMContentLoaded", () => {
  const intakeBtn = document.getElementById("new-patient-btn");
  if (intakeBtn) {
    intakeBtn.addEventListener("click", () => {
      window.location.href = "intake.html";
    });
  }

  // Fetch Appointments from API
  fetchAppointments();

  // Simulation of the Reminder System Logic
  const triggerSyncBtn = document.querySelector(".sidebar-right .btn-primary");
  if (triggerSyncBtn) {
    triggerSyncBtn.addEventListener("click", () => {
      const originalText = triggerSyncBtn.innerText;
      triggerSyncBtn.innerText = "Syncing...";
      triggerSyncBtn.disabled = true;

      setTimeout(() => {
        triggerSyncBtn.innerText = "Sync Complete!";
        triggerSyncBtn.style.background = "var(--success)";

        setTimeout(() => {
          triggerSyncBtn.innerText = originalText;
          triggerSyncBtn.disabled = false;
          triggerSyncBtn.style.background = "var(--primary)";
        }, 2000);
      }, 1500);
    });
  }

  // Mobile Sidebar Toggle
  const menuToggle = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.createElement("div");
  overlay.className = "sidebar-overlay";
  document.body.appendChild(overlay);

  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active");
      overlay.classList.toggle("active");
    });

    overlay.addEventListener("click", () => {
      sidebar.classList.remove("active");
      overlay.classList.remove("active");
    });
  }
});

async function fetchAppointments() {
  try {
    const response = await fetch("api.php?action=get_appointments");
    const appointments = await response.json();

    // Render Dashboard Lists
    renderWaitingRoom(appointments.filter((a) => a.status === "Waiting"));
    renderScheduledList(appointments.filter((a) => a.status === "Scheduled"));

    // Update Stats
    updateStats(appointments);
  } catch (error) {
    console.error("Error fetching dashboard data:", error);
  }
}

function renderWaitingRoom(waiting) {
  const container = document.querySelector(".waiting-grid");
  if (!container) return;

  const tabItem = document.querySelector(".tab-item:nth-child(1)");
  if (tabItem) tabItem.innerText = `Waiting Room (${waiting.length})`;

  if (waiting.length === 0) {
    container.innerHTML =
      '<p style="padding: 2rem; color: #888; text-align: center;">No patients in waiting room.</p>';
    return;
  }

  container.innerHTML = waiting
    .map(
      (app) => `
        <div class="waiting-card">
            <div class="waiting-info">
                <h4>${app.patient_name}</h4>
                <span>Service: Hejama</span><br>
                <div class="pulse-timer">Arrived: ${new Date(app.appointment_date).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}</div>
            </div>
            <button class="btn btn-primary" style="padding: 8px 15px; font-size: 0.8rem;" onclick="assignSpecialist(${app.id})">
                Assign
            </button>
        </div>
    `,
    )
    .join("");
}

function renderScheduledList(scheduled) {
  const tableBody = document.querySelector("#scheduled-content tbody");
  if (!tableBody) return;

  if (scheduled.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No appointments scheduled for today.</td></tr>';
    return;
  }

  tableBody.innerHTML = scheduled
    .map(
      (app) => `
        <tr>
            <td style="font-weight: 600;">${app.patient_name}</td>
            <td>${new Date(app.appointment_date).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}</td>
            <td>${app.specialist_name || "TBD"}</td>
            <td><span class="status-badge status-confirmed">Confirmed</span></td>
            <td><button class="btn" style="padding: 5px 10px; font-size: 0.7rem;" onclick="checkIn(${app.id})">Check In</button></td>
        </tr>
    `,
    )
    .join("");
}

function updateStats(appointments) {
  const todayCount = document.querySelector(
    ".stat-card:nth-child(1) .stat-value",
  );
  const waitingCount = document.querySelector(
    ".stat-card:nth-child(2) .stat-value",
  );

  if (todayCount) todayCount.innerText = appointments.length;
  if (waitingCount)
    waitingCount.innerText = appointments.filter(
      (a) => a.status === "Waiting",
    ).length;
}

// Tab Switching Logic
function switchTab(tabId) {
  const tabs = document.querySelectorAll(".tab-item");
  const waitingContent = document.getElementById("waiting-content");
  const scheduledContent = document.getElementById("scheduled-content");

  tabs.forEach((tab) => tab.classList.remove("active"));

  if (tabId === "waiting") {
    tabs[0].classList.add("active");
    waitingContent.style.display = "block";
    scheduledContent.style.display = "none";
  } else {
    tabs[1].classList.add("active");
    waitingContent.style.display = "none";
    scheduledContent.style.display = "block";
  }
}
