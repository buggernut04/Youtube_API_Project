const CHANNEL_ID = document.getElementById("channelInfo").dataset.channelId;
let currentPage = 1;

async function loadVideos(page) {
  currentPage = page;
  const grid = document.getElementById("videoGrid");
  const errEl = document.getElementById("videoError");
  const pagEl = document.getElementById("pagination");
  const cntEl = document.getElementById("videoCount");

  hideEl(errEl);
  grid.innerHTML = '<div class="loading-row">Loading…</div>';
  pagEl.innerHTML = "";

  try {
    const res = await fetch(
      `../api/fetch_videos.php?channel_id=${encodeURIComponent(CHANNEL_ID)}&page=${page}`,
    );
    const data = await res.json();

    if (!data.success) {
      showError(errEl, data.error || "Failed to load videos.");
      grid.innerHTML = "";
      return;
    }

    cntEl.textContent =
      data.total + " video" + (data.total !== 1 ? "s" : "") + " saved";

    if (data.videos.length === 0) {
      grid.innerHTML =
        '<p class="empty-videos">No videos found for this channel.</p>';
      return;
    }

    grid.innerHTML = data.videos
      .map(
        (v) => `
        <a href="https://youtube.com/watch?v=${escHtml(v.video_id)}" target="_blank" rel="noopener" class="video-card">
          <div class="video-thumb-wrap">
            <img src="${escHtml(v.thumbnail_url)}" alt="${escHtml(v.title)}" class="video-thumb" loading="lazy">
            <span class="video-duration">${escHtml(v.duration_formatted)}</span>
          </div>
          <div class="video-meta">
            <h3 class="video-title">${escHtml(v.title)}</h3>
            <div class="video-stats">
              <span>${escHtml(v.view_count_formatted)} views</span>
              <span class="dot">·</span>
              <span>${formatDate(v.published_at)}</span>
            </div>
          </div>
        </a>
      `,
      )
      .join("");

    renderPagination(pagEl, data.page, data.total_pages);
  } catch (e) {
    showError(errEl, "Network error. Please try again.");
    grid.innerHTML = "";
  }
}

function renderPagination(el, page, total) {
  if (total <= 1) return;
  let html = "";
  if (page > 1)
    html += `<button class="page-btn" onclick="loadVideos(${page - 1})">← Prev</button>`;

  // Show up to 7 page buttons
  const start = Math.max(1, page - 3);
  const end = Math.min(total, page + 3);
  if (start > 1)
    html += `<button class="page-btn" onclick="loadVideos(1)">1</button><span class="page-ellipsis">…</span>`;
  for (let p = start; p <= end; p++) {
    html += `<button class="page-btn ${p === page ? "active" : ""}" onclick="loadVideos(${p})">${p}</button>`;
  }
  if (end < total)
    html += `<span class="page-ellipsis">…</span><button class="page-btn" onclick="loadVideos(${total})">${total}</button>`;
  if (page < total)
    html += `<button class="page-btn" onclick="loadVideos(${page + 1})">Next →</button>`;

  el.innerHTML = html;
}

loadVideos(1);
