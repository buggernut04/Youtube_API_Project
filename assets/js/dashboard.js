["openSyncModal", "openSyncModal2"].forEach((id) => {
  const el = document.getElementById(id);
  if (el) el.addEventListener("click", () => openModal("syncModal"));
});

function resetSyncModal() {
  document.getElementById("channelIdInput").value = "";
  hideEl(document.getElementById("syncError"));
  hideEl(document.getElementById("syncSuccess"));
  document.getElementById("syncError").className = "alert alert-error";
  closeModal("syncModal");
}

document
  .getElementById("closeSyncModal")
  .addEventListener("click", resetSyncModal);
document
  .getElementById("cancelSync")
  .addEventListener("click", resetSyncModal);

document.getElementById("doSync").addEventListener("click", async () => {
  const channelId = document.getElementById("channelIdInput").value.trim();
  const errEl = document.getElementById("syncError");
  const okEl = document.getElementById("syncSuccess");
  const label = document.getElementById("syncLabel");

  hideEl(errEl);
  hideEl(okEl);
  if (!channelId) {
    showError(errEl, "Please enter a Channel ID.");
    return;
  }

  label.textContent = "Syncing…";
  document.getElementById("doSync").disabled = true;

  try {
    const res = await fetch("../api/fetch_channel.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ channel_id: channelId }),
    });
    const data = await res.json();

    if (data.success) {
      showSuccess(okEl, data.message);
      setTimeout(() => location.reload(), 1500);
    } else if (data.already_exists) {
      errEl.className = "alert alert-warning";
      showError(errEl, "⚠️ " + data.error);
    } else {
      errEl.className = "alert alert-error";
      showError(errEl, data.error || "Sync failed.");
    }
  } catch (e) {
    showError(errEl, "Network error. Please try again.");
  } finally {
    label.textContent = "Sync";
    document.getElementById("doSync").disabled = false;
  }
});
