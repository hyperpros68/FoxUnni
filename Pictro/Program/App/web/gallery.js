// ==========================================================================
// Pictro - My Gallery & Storage Dashboard Client Logic
// ==========================================================================

const API_BASE = '/pictro-api/api/v1';
const API_KEY = localStorage.getItem('pictro_api_key') || 'FOXUNNI-PARTNER-MASTER-KEY-2026';

function formatImgUrl(url) {
  if (!url) return '';
  if (url.startsWith('http://') || url.startsWith('https://')) return url;
  if (url.startsWith('/pictro-api/')) return url;
  if (url.startsWith('/files/')) return '/pictro-api' + url;
  if (url.startsWith('files/')) return '/pictro-api/' + url;
  return url;
}

const state = {
  isTrash: false,
  keyword: '',
  items: [],
  selectedIds: new Set(),
  dashboard: null
};

// DOM Elements
const el = {
  companyName: document.getElementById('companyName'),
  planName: document.getElementById('planName'),
  usedHuman: document.getElementById('usedHuman'),
  maxHuman: document.getElementById('maxHuman'),
  pctBadge: document.getElementById('pctBadge'),
  progressBar: document.getElementById('storageProgressBar'),
  warningTip: document.getElementById('storageWarningTip'),
  tabActive: document.getElementById('tabActive'),
  tabTrash: document.getElementById('tabTrash'),
  activeCount: document.getElementById('activeCount'),
  trashCount: document.getElementById('trashCount'),
  searchInput: document.getElementById('searchInput'),
  btnSearch: document.getElementById('btnSearch'),
  btnExportCsv: document.getElementById('btnExportCsv'),
  btnBatchUploadModal: document.getElementById('btnBatchUploadModal'),
  galleryGrid: document.getElementById('galleryGrid'),
  batchBar: document.getElementById('batchBar'),
  selectAllCheckbox: document.getElementById('selectAllCheckbox'),
  selectedCountLabel: document.getElementById('selectedCountLabel'),
  btnBatchTrash: document.getElementById('btnBatchTrash'),
  btnBatchRestore: document.getElementById('btnBatchRestore'),
  btnBatchPurge: document.getElementById('btnBatchPurge'),
  detailModal: document.getElementById('detailModal'),
  btnCloseDetail: document.getElementById('btnCloseDetail'),
  detailOrigImg: document.getElementById('detailOrigImg'),
  detailVisualImg: document.getElementById('detailVisualImg'),
  detailCleanImg: document.getElementById('detailCleanImg'),
  detailTransImg: document.getElementById('detailTransImg'),
  detailLangLabel: document.getElementById('detailLangLabel'),
  detailSummary: document.getElementById('detailSummary'),
  detailColor: document.getElementById('detailColor'),
  detailSize: document.getElementById('detailSize'),
  btnDownloadOrig: document.getElementById('btnDownloadOrig'),
  btnDownloadTrans: document.getElementById('btnDownloadTrans'),
  uploadModal: document.getElementById('uploadModal'),
  btnCloseUpload: document.getElementById('btnCloseUpload'),
  btnCancelUpload: document.getElementById('btnCancelUpload'),
  batchDropzone: document.getElementById('batchDropzone'),
  batchFileInput: document.getElementById('batchFileInput'),
  batchTargetLang: document.getElementById('batchTargetLang'),
  btnStartBatchUpload: document.getElementById('btnStartBatchUpload'),
  uploadProgressWrap: document.getElementById('uploadProgressWrap'),
  uploadProgressBar: document.getElementById('uploadProgressBar'),
  uploadStatusText: document.getElementById('uploadStatusText'),
  uploadStatusPct: document.getElementById('uploadStatusPct'),
  queueFileList: document.getElementById('queueFileList')
};

// Headers with API Key
function getAuthHeaders() {
  return { 'X-API-Key': API_KEY };
}

// 1. Load Dashboard
async function loadDashboard() {
  try {
    const res = await fetch(`${API_BASE}/gallery/dashboard`, { headers: getAuthHeaders() });
    const data = await res.json();
    if (!data.success) return;

    state.dashboard = data;
    el.companyName.textContent = data.company_name;
    el.planName.textContent = data.plan_name;

    const s = data.storage;
    el.usedHuman.textContent = s.used_human;
    el.maxHuman.textContent = s.max_human;
    el.pctBadge.textContent = `${s.usage_percent}%`;
    el.progressBar.style.width = `${Math.min(100, s.usage_percent)}%`;

    if (s.is_warning) {
      el.progressBar.classList.add('warning');
      el.warningTip.textContent = '⚠️ 스토리지 용량이 85%를 초과했습니다. 플랜 업그레이드를 권장합니다.';
      el.warningTip.style.color = '#ef4444';
    } else {
      el.progressBar.classList.remove('warning');
      el.warningTip.textContent = '💡 여유 공간이 충분합니다.';
      el.warningTip.style.color = '#94a3b8';
    }

    el.activeCount.textContent = data.stats.active_count;
    el.trashCount.textContent = data.stats.trash_count;
  } catch (err) {
    console.error('Failed to load dashboard:', err);
  }
}

// 2. Load Gallery Items
async function loadGalleryItems() {
  el.galleryGrid.innerHTML = `
    <div class="gallery-loading">
      <div class="spinner"></div>
      <p>배너 보관함을 불러오는 중입니다...</p>
    </div>
  `;

  state.selectedIds.clear();
  updateBatchBar();

  try {
    const params = new URLSearchParams({
      is_trash: state.isTrash ? 'true' : 'false',
      keyword: state.keyword || ''
    });
    const res = await fetch(`${API_BASE}/gallery/items?${params.toString()}`, { headers: getAuthHeaders() });
    const data = await res.json();
    state.items = data.items || [];
    renderCards(state.items);
  } catch (err) {
    el.galleryGrid.innerHTML = `<p style="color:#ef4444; text-align:center;">보관함 목록 로드 실패: ${err.message}</p>`;
  }
}

// 3. Render Item Cards
function renderCards(items) {
  if (!items || items.length === 0) {
    el.galleryGrid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #64748b;">
        <span style="font-size: 40px;">📭</span>
        <p style="margin-top: 12px; font-size: 14px;">보관된 배너가 없습니다.</p>
      </div>
    `;
    return;
  }

  el.galleryGrid.innerHTML = items.map((it, idx) => {
    const thumb = it.thumb_webp_path || it.translated_webp_path || it.original_webp_path;
    const kb = Math.round((it.image_size_bytes || 0) / 1024);
    const dateStr = (it.created_at || '').substring(0, 10);
    const lang = (it.target_lang || 'EN').toUpperCase();
    const summary = it.parsed_text_summary || '텍스트 요약 없음';

    return `
      <div class="gallery-card" data-id="${it.history_id}">
        <div class="card-thumb-wrap" onclick="openDetailModal(${idx})">
          <input type="checkbox" class="card-select-checkbox" data-id="${it.history_id}" onclick="event.stopPropagation(); toggleSelect(${it.history_id}, this.checked)" />
          <span class="card-lang-tag">${lang}</span>
          <img src="${formatImgUrl(thumb)}" alt="배너 썸네일" loading="lazy" />
        </div>
        <div class="card-info" onclick="openDetailModal(${idx})">
          <h4 class="card-summary" title="${summary}">${summary}</h4>
          <div class="card-meta-row">
            <span>${kb} KB</span>
            <span>${dateStr}</span>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// 4. Batch Selection & Action Bar
function toggleSelect(id, checked) {
  if (checked) {
    state.selectedIds.add(id);
  } else {
    state.selectedIds.delete(id);
  }
  updateBatchBar();
}

function updateBatchBar() {
  const count = state.selectedIds.size;
  if (count > 0) {
    el.batchBar.classList.remove('hidden');
    el.selectedCountLabel.textContent = `${count}개 선택됨`;
    if (state.isTrash) {
      el.btnBatchTrash.classList.add('hidden');
      el.btnBatchRestore.classList.remove('hidden');
      el.btnBatchPurge.classList.remove('hidden');
    } else {
      el.btnBatchTrash.classList.remove('hidden');
      el.btnBatchRestore.classList.add('hidden');
      el.btnBatchPurge.classList.add('hidden');
    }
  } else {
    el.batchBar.classList.add('hidden');
  }
  el.selectAllCheckbox.checked = count > 0 && count === state.items.length;
}

el.selectAllCheckbox.addEventListener('change', (e) => {
  const checked = e.target.checked;
  const checkboxes = document.querySelectorAll('.card-select-checkbox');
  checkboxes.forEach(cb => {
    cb.checked = checked;
    const id = parseInt(cb.dataset.id, 10);
    if (checked) state.selectedIds.add(id);
    else state.selectedIds.delete(id);
  });
  updateBatchBar();
});

// 5. Batch Trash / Restore / Purge API
el.btnBatchTrash.addEventListener('click', async () => {
  if (state.selectedIds.size === 0) return;
  if (!confirm(`선택한 ${state.selectedIds.size}개 배너를 휴지통으로 이동하시겠습니까? (30일간 보관)`)) return;

  const formData = new FormData();
  formData.append('history_ids', Array.from(state.selectedIds).join(','));

  const res = await fetch(`${API_BASE}/gallery/trash`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: formData
  });
  const data = await res.json();
  alert(data.message || '휴지통 이동 완료');
  await loadDashboard();
  await loadGalleryItems();
});

el.btnBatchRestore.addEventListener('click', async () => {
  if (state.selectedIds.size === 0) return;
  const formData = new FormData();
  formData.append('history_ids', Array.from(state.selectedIds).join(','));

  const res = await fetch(`${API_BASE}/gallery/restore`, {
    method: 'POST',
    headers: getAuthHeaders(),
    body: formData
  });
  const data = await res.json();
  alert(data.message || '복구 완료');
  await loadDashboard();
  await loadGalleryItems();
});

el.btnBatchPurge.addEventListener('click', async () => {
  if (state.selectedIds.size === 0) return;
  if (!confirm(`⚠️ 정말로 영구 삭제하시겠습니까? 삭제된 배너는 복구할 수 없습니다.`)) return;

  const formData = new FormData();
  formData.append('history_ids', Array.from(state.selectedIds).join(','));

  const res = await fetch(`${API_BASE}/gallery/purge`, {
    method: 'DELETE',
    headers: getAuthHeaders(),
    body: formData
  });
  const data = await res.json();
  alert(data.message || '영구 삭제 완료');
  await loadDashboard();
  await loadGalleryItems();
});

// 6. Tabs & Search
el.tabActive.addEventListener('click', () => {
  if (!state.isTrash) return;
  state.isTrash = false;
  el.tabActive.classList.add('active');
  el.tabTrash.classList.remove('active');
  loadGalleryItems();
});

el.tabTrash.addEventListener('click', () => {
  if (state.isTrash) return;
  state.isTrash = true;
  el.tabTrash.classList.add('active');
  el.tabActive.classList.remove('active');
  loadGalleryItems();
});

el.btnSearch.addEventListener('click', () => {
  state.keyword = el.searchInput.value.trim();
  loadGalleryItems();
});
el.searchInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') el.btnSearch.click();
});

// 7. CSV Export (Auth Header 포함 Blob 다운로드)
el.btnExportCsv.addEventListener('click', async () => {
  try {
    el.btnExportCsv.disabled = true;
    el.btnExportCsv.textContent = '⏳ 생성 중...';
    const url = `${API_BASE}/gallery/export?is_trash=${state.isTrash ? 'true' : 'false'}`;
    const res = await fetch(url, { headers: getAuthHeaders() });
    if (!res.ok) throw new Error('CSV 내보내기에 실패했습니다.');

    const blob = await res.blob();
    const downloadUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = downloadUrl;
    const today = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    a.download = `pictro_banners_${today}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(downloadUrl);
  } catch (err) {
    alert(err.message);
  } finally {
    el.btnExportCsv.disabled = false;
    el.btnExportCsv.innerHTML = '<span>📊 엑셀(CSV) 내보내기</span>';
  }
});

// 8. Detail Compare Modal
window.openDetailModal = function(idx) {
  const item = state.items[idx];
  if (!item) return;

  const origUrl = formatImgUrl(item.original_webp_path);
  const visUrl = formatImgUrl(item.visual_webp_path || item.original_webp_path);
  const cleanUrl = formatImgUrl(item.clean_bg_webp_path || item.original_webp_path);
  const transUrl = formatImgUrl(item.translated_webp_path || item.clean_bg_webp_path || item.original_webp_path);

  el.detailOrigImg.src = origUrl;
  el.detailVisualImg.src = visUrl;
  el.detailCleanImg.src = cleanUrl;
  el.detailTransImg.src = transUrl;

  el.detailLangLabel.textContent = (item.target_lang || 'EN').toUpperCase();
  el.detailSummary.textContent = item.parsed_text_summary || '-';
  el.detailColor.textContent = item.detected_font_color || '-';
  el.detailSize.textContent = `${Math.round((item.image_size_bytes || 0) / 1024)} KB`;

  el.btnDownloadOrig.href = origUrl || '#';
  const btnClean = document.getElementById('btnDownloadClean');
  if (btnClean) btnClean.href = cleanUrl || '#';
  el.btnDownloadTrans.href = transUrl || '#';

  // 기본적으로 4단계 전주기 모드로 시작
  switchViewMode('all');

  el.detailModal.classList.remove('hidden');
};

function renderModalOverlayBoxes(items, imgW, imgH) {
  const container = document.getElementById('modalOverlayBoxes');
  if (!container) return;
  container.innerHTML = '';
  if (!items || items.length === 0) return;

  items.forEach((it, idx) => {
    if (!it.box || it.box.length < 4) return;
    const [ymin, xmin, ymax, xmax] = it.box;
    const topPct = (ymin / imgH) * 100;
    const leftPct = (xmin / imgW) * 100;
    const heightPct = ((ymax - ymin) / imgH) * 100;
    const widthPct = ((xmax - xmin) / imgW) * 100;

    const boxEl = document.createElement('div');
    boxEl.className = 'box-highlight';
    boxEl.style.top = `${topPct}%`;
    boxEl.style.left = `${leftPct}%`;
    boxEl.style.height = `${heightPct}%`;
    boxEl.style.width = `${widthPct}%`;
    boxEl.innerHTML = `<span class="box-label">#${it.id !== undefined ? it.id : idx}</span>`;

    container.appendChild(boxEl);
  });
}

window.switchViewMode = function(mode) {
  const row = document.getElementById('compareGridRow');
  const colOrig = document.getElementById('colOrig');
  const colVisual = document.getElementById('colVisual');
  const colClean = document.getElementById('colClean');
  const colTrans = document.getElementById('colTrans');
  const btns = document.querySelectorAll('.view-mode-btn');

  btns.forEach(b => {
    b.classList.toggle('active', b.dataset.mode === mode);
  });

  if (!row) return;

  if (mode === 'all') {
    // 4단계 전주기 (4장 모두 노출)
    row.className = 'compare-view-row compare-view-4grid';
    colOrig.style.display = 'flex';
    colVisual.style.display = 'flex';
    colClean.style.display = 'flex';
    colTrans.style.display = 'flex';
  } else if (mode === 'compare') {
    // 1:1 비교 (원본 vs 다국어 치환본 2장)
    row.className = 'compare-view-row compare-view-2grid';
    colOrig.style.display = 'flex';
    colVisual.style.display = 'none';
    colClean.style.display = 'none';
    colTrans.style.display = 'flex';
  } else if (mode === 'clean') {
    // AI 배경 복원만 (원본 vs Clean BG 2장)
    row.className = 'compare-view-row compare-view-2grid';
    colOrig.style.display = 'flex';
    colVisual.style.display = 'none';
    colClean.style.display = 'flex';
    colTrans.style.display = 'none';
  } else if (mode === 'ocr') {
    // OCR 검출 확인 (원본 vs 바운딩 박스 2장)
    row.className = 'compare-view-row compare-view-2grid';
    colOrig.style.display = 'flex';
    colVisual.style.display = 'flex';
    colClean.style.display = 'none';
    colTrans.style.display = 'none';
  }
};

el.btnCloseDetail.addEventListener('click', () => el.detailModal.classList.add('hidden'));

// 9. Batch Upload Dropzone (Max 50)
let selectedUploadFiles = [];

el.btnBatchUploadModal.addEventListener('click', () => {
  selectedUploadFiles = [];
  el.queueFileList.innerHTML = '';
  el.uploadProgressWrap.classList.add('hidden');
  el.uploadModal.classList.remove('hidden');
});

el.btnCloseUpload.addEventListener('click', () => el.uploadModal.classList.add('hidden'));
el.btnCancelUpload.addEventListener('click', () => el.uploadModal.classList.add('hidden'));

el.batchDropzone.addEventListener('click', () => el.batchFileInput.click());
el.batchDropzone.addEventListener('dragover', (e) => { e.preventDefault(); el.batchDropzone.style.borderColor = '#00f076'; });
el.batchDropzone.addEventListener('dragleave', () => { el.batchDropzone.style.borderColor = 'rgba(0, 240, 118, 0.3)'; });
el.batchDropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  el.batchDropzone.style.borderColor = 'rgba(0, 240, 118, 0.3)';
  handleFilesSelected(Array.from(e.dataTransfer.files));
});
el.batchFileInput.addEventListener('change', (e) => {
  handleFilesSelected(Array.from(e.target.files));
});

function handleFilesSelected(files) {
  const imageFiles = files.filter(f => f.type.startsWith('image/')).slice(0, 50);
  selectedUploadFiles = imageFiles;
  el.queueFileList.innerHTML = imageFiles.map((f, i) => `<div>${i+1}. ${f.name} (${Math.round(f.size/1024)} KB)</div>`).join('');
  el.uploadProgressWrap.classList.remove('hidden');
  el.uploadStatusText.textContent = `대기 중: ${imageFiles.length}개 파일 선택됨`;
  el.uploadStatusPct.textContent = '0%';
  el.uploadProgressBar.style.width = '0%';
}

el.btnStartBatchUpload.addEventListener('click', async () => {
  if (selectedUploadFiles.length === 0) {
    alert('업로드할 이미지 파일을 먼저 선택해 주세요.');
    return;
  }

  el.btnStartBatchUpload.disabled = true;
  const total = selectedUploadFiles.length;
  let doneCount = 0;
  const lang = el.batchTargetLang.value;

  for (let i = 0; i < total; i++) {
    const file = selectedUploadFiles[i];
    el.uploadStatusText.textContent = `변환 진행 중 (${i + 1}/${total}): ${file.name}`;
    const pct = Math.round(((i) / total) * 100);
    el.uploadProgressBar.style.width = `${pct}%`;
    el.uploadStatusPct.textContent = `${pct}%`;

    try {
      const formData = new FormData();
      formData.append('image', file);
      formData.append('mode', 'trans');
      formData.append('target_lang', lang);
      formData.append('auto_save', 'true');

      await fetch(`${API_BASE}/pictro/process`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: formData
      });
      doneCount++;
    } catch (err) {
      console.error(`Upload error on ${file.name}:`, err);
    }
  }

  el.uploadProgressBar.style.width = '100%';
  el.uploadStatusPct.textContent = '100%';
  el.uploadStatusText.textContent = `변환 완료! (총 ${doneCount}/${total}건 성공)`;

  setTimeout(async () => {
    el.uploadModal.classList.add('hidden');
    el.btnStartBatchUpload.disabled = false;
    await loadDashboard();
    await loadGalleryItems();
  }, 1200);
});

// Initial Load
document.addEventListener('DOMContentLoaded', () => {
  loadDashboard();
  loadGalleryItems();
});
