/* ── DOM Helpers ── */
 
function hideEl(el) {
  if (el) el.hidden = true;
}
 
function showEl(el) {
  if (el) el.hidden = false;
}
 
function showError(el, msg) {
  if (!el) return;
  el.textContent = msg;
  el.hidden = false;
}
 
function showSuccess(el, msg) {
  if (!el) return;
  el.textContent = msg;
  el.hidden = false;
}
 
/* ── String Helpers ── */
 
/**
 * Escape HTML special characters to prevent XSS when
 * inserting untrusted text into innerHTML.
 */
function escHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
 
/**
 * Format an ISO date string to a readable short date.
 * e.g. "2023-07-15 00:00:00" → "Jul 15, 2023"
 */
function formatDate(dateStr) {
  if (!dateStr) return '';
  try {
    const d = new Date(dateStr.replace(' ', 'T'));
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  } catch (e) {
    return dateStr;
  }
}
 
/* ── Modal Helpers ── */
 
function openModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.hidden = false;
    // Focus the first input inside the modal, if any
    const input = el.querySelector('input');
    if (input) setTimeout(() => input.focus(), 50);
  }
}
 
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.hidden = true;
}
 
// Close modal when clicking on the overlay (outside the modal box)
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.hidden = true;
    e.target.querySelectorAll('input').forEach(el => el.value = '');
    e.target.querySelectorAll('.alert').forEach(el => el.hidden = true);
    e.target.hidden = true;
  }
});
 
// Close modal with Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay:not([hidden])').forEach(el => {
      el.hidden = true;
    });
  }
});