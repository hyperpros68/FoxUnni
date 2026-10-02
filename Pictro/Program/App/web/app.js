/**
 * Pictro Studio - Enterprise AI Banner Review & Live Typography Editor
 * Vanilla JavaScript Core Engine (with Clipboard Paste & JSON Detect Viewer)
 */

// Application State
const state = {
  currentMode: 'trans', // 'detect' | 'clear' | 'trans'
  targetLang: 'en',
  currentImageSource: null, // File object, URL string, or DataURL
  currentImageBlob: null,
  cleanBgImageObj: null,
  metaData: null,
  items: [],
  zoomLevel: 1.0,
  activeTab: 'sideBySide',
  activeInspectorTab: 'editorView', // 'editorView' | 'jsonView'
  currentHistoryId: null
};

let currentJsonPayload = null;

// Built-in Demo Data for Banner 5 (Fallback / Standalone Mode)
const SAMPLE_BANNER5 = {
  name: "banner5_orig.jpg",
  origUrl: "examples/banner5_orig.jpg",
  cleanBgUrl: "examples/banner5_clean_bg.jpg",
  transOutUrl: "examples/banner5_translated.jpg",
  ocrVisUrl: "examples/banner5_ocr_detected.jpg",
  width: 800,
  height: 800,
  sourceLang: "ko",
  latency: "2.1s",
  items: [
    { id: 0, box: [35, 37, 72, 196], orig: "ANOTHER", trans: "ANOTHER", hex: "#ffffff", role: "title", fontSize: 32, conf: 0.994 },
    { id: 1, box: [76, 57, 89, 175], orig: "PLASIC SURGERX", trans: "PLASTIC SURGERY", hex: "#cccccc", role: "subtitle", fontSize: 13, conf: 0.759 },
    { id: 2, box: [290, 51, 323, 137], orig: "코부터", trans: "Nose to", hex: "#1f2937", role: "text", fontSize: 24, conf: 1.0 },
    { id: 3, box: [289, 145, 324, 259], orig: "인중까지", trans: "Philtrum", hex: "#1f2937", role: "text", fontSize: 24, conf: 0.999 },
    { id: 4, box: [327, 50, 366, 112], orig: "맞춤", trans: "Optimized", hex: "#1f2937", role: "text", fontSize: 28, conf: 0.999 },
    { id: 5, box: [329, 115, 365, 205], orig: "각도로", trans: "Angle", hex: "#1f2937", role: "text", fontSize: 28, conf: 1.0 },
    { id: 6, box: [328, 210, 364, 306], orig: "최적화!", trans: "Care!", hex: "#166534", role: "title", fontSize: 28, conf: 0.998 },
    { id: 7, box: [450, 52, 533, 284], orig: "어나더", trans: "ANOTHER", hex: "#1e3a2f", role: "title", fontSize: 58, conf: 0.997 },
    { id: 8, box: [554, 54, 641, 295], orig: "비순각", trans: "NASOLABIAL", hex: "#166534", role: "title", fontSize: 62, conf: 0.999 },
    { id: 9, box: [554, 457, 762, 702], orig: "50", trans: "50", hex: "#166534", role: "price", fontSize: 140, conf: 0.999 },
    { id: 10, box: [655, 52, 739, 371], orig: "코재수술", trans: "RHINOPLASTY", hex: "#166534", role: "title", fontSize: 58, conf: 0.998 },
    { id: 11, box: [688, 703, 710, 757], orig: "VAT포함", trans: "VAT IncL.", hex: "#444444", role: "meta", fontSize: 14, conf: 0.923 },
    { id: 12, box: [711, 705, 745, 756], orig: "만원", trans: "10K KRW", hex: "#333333", role: "price", fontSize: 20, conf: 0.602 }
  ]
};

// DOM References
const dropzoneBox = document.getElementById('dropzoneBox');
const fileInput = document.getElementById('fileInput');
const btnPasteClipboard = document.getElementById('btnPasteClipboard');
const remoteUrlInput = document.getElementById('remoteUrlInput');
const btnFetchUrl = document.getElementById('btnFetchUrl');
const btnProcessAI = document.getElementById('btnProcessAI');
const targetLangSelect = document.getElementById('targetLangSelect');

const dualViewerWrap = document.getElementById('dualViewerWrap');
const canvasViewerWrap = document.getElementById('canvasViewerWrap');
const singleViewerWrap = document.getElementById('singleViewerWrap');
const loadingOverlay = document.getElementById('loadingOverlay');

const origImg = document.getElementById('origImg');
const outputImg = document.getElementById('outputImg');
const outputPlaceholder = document.getElementById('outputPlaceholder');
const singleImg = document.getElementById('singleImg');
const singleViewBadge = document.getElementById('singleViewBadge');
const liveCanvas = document.getElementById('liveCanvas');
const overlayBoxes = document.getElementById('overlayBoxes');

// Inspector Panels
const tabTextEditor = document.getElementById('tabTextEditor');
const tabJsonViewer = document.getElementById('tabJsonViewer');
const itemsListWrap = document.getElementById('itemsListWrap');
const jsonViewerWrap = document.getElementById('jsonViewerWrap');
const jsonTreeContainer = document.getElementById('jsonTreeContainer');
const btnFoldAllJson = document.getElementById('btnFoldAllJson');
const btnUnfoldAllJson = document.getElementById('btnUnfoldAllJson');
const btnCopyJson = document.getElementById('btnCopyJson');

const itemCountBadge = document.getElementById('itemCountBadge');
const statDim = document.getElementById('statDim');
const statLang = document.getElementById('statLang');
const statLatency = document.getElementById('statLatency');
const statWcag = document.getElementById('statWcag');

const btnDownloadJson = document.getElementById('btnDownloadJson');
const btnDownloadImage = document.getElementById('btnDownloadImage');
const btnSaveToGallery = document.getElementById('btnSaveToGallery');

// Initialize Event Listeners
document.addEventListener('DOMContentLoaded', () => {
  setupModeSelectors();
  setupDropzone();
  setupClipboardPaste();
  setupInspectorTabs();
  setupSampleButtons();
  setupViewTabs();
  setupZoomTools();
  setupProcessButton();
  setupExportButtons();
  setupAuthUI();

  // Load Sample 1 by default
  loadSampleData(SAMPLE_BANNER5);
});

// Mode Selector Buttons
function setupModeSelectors() {
  const modeLabels = document.querySelectorAll('.mode-btn');
  modeLabels.forEach(label => {
    label.addEventListener('click', (e) => {
      modeLabels.forEach(l => l.classList.remove('active'));
      label.classList.add('active');
      const radio = label.querySelector('input');
      radio.checked = true;
      state.currentMode = radio.value;
      console.log(`[Pictro Mode Changed] ${state.currentMode}`);

      // Mode selection auto sync view
      if (state.currentMode === 'detect') {
        switchInspectorTab('jsonView');
        updateJsonBoxWithCurrentData();
        origImg.src = state.ocrVisUrl || SAMPLE_BANNER5.ocrVisUrl;
        overlayBoxes.classList.add('hidden');
        const ocrTab = document.querySelector('[data-tab="ocrBoxes"]');
        if (ocrTab) ocrTab.click();
      } else if (state.currentMode === 'clear') {
        switchInspectorTab('editorView');
        origImg.src = SAMPLE_BANNER5.origUrl;
        overlayBoxes.classList.add('hidden');
        const bgTab = document.querySelector('[data-tab="cleanBg"]');
        if (bgTab) bgTab.click();
      } else {
        switchInspectorTab('editorView');
        origImg.src = SAMPLE_BANNER5.origUrl;
        overlayBoxes.classList.remove('hidden');
        const sideTab = document.querySelector('[data-tab="sideBySide"]');
        if (sideTab) sideTab.click();
      }
    });
  });
}

// Inspector Tabs Switcher (Text Editor vs JSON Viewer)
function setupInspectorTabs() {
  tabTextEditor.addEventListener('click', () => switchInspectorTab('editorView'));
  tabJsonViewer.addEventListener('click', () => {
    switchInspectorTab('jsonView');
    updateJsonBoxWithCurrentData();
  });

  btnCopyJson.addEventListener('click', () => {
    const jsonText = currentJsonPayload ? JSON.stringify(currentJsonPayload, null, 2) : "{}";
    navigator.clipboard.writeText(jsonText).then(() => {
      const origText = btnCopyJson.textContent;
      btnCopyJson.textContent = '✅ 복사 완료!';
      btnCopyJson.style.background = '#00d2ff';
      btnCopyJson.style.color = '#000';
      setTimeout(() => {
        btnCopyJson.textContent = origText;
        btnCopyJson.style.background = '';
        btnCopyJson.style.color = '';
      }, 1500);
    });
  });

  // Fold All JSON ([+] 상태로 전체 접기)
  btnFoldAllJson.addEventListener('click', () => {
    const childrenContainers = jsonTreeContainer.querySelectorAll('.json-children');
    const toggleBtns = jsonTreeContainer.querySelectorAll('.json-toggle');
    const previews = jsonTreeContainer.querySelectorAll('.json-collapsed-preview');
    const footers = jsonTreeContainer.querySelectorAll('.json-footer');

    childrenContainers.forEach(c => c.classList.add('collapsed'));
    toggleBtns.forEach(t => {
      t.classList.add('collapsed');
      t.textContent = '+';
    });
    previews.forEach(p => p.classList.remove('hidden'));
    footers.forEach(f => f.classList.add('hidden'));
  });

  // Unfold All JSON ([-] 상태로 전체 펼치기)
  btnUnfoldAllJson.addEventListener('click', () => {
    const childrenContainers = jsonTreeContainer.querySelectorAll('.json-children');
    const toggleBtns = jsonTreeContainer.querySelectorAll('.json-toggle');
    const previews = jsonTreeContainer.querySelectorAll('.json-collapsed-preview');
    const footers = jsonTreeContainer.querySelectorAll('.json-footer');

    childrenContainers.forEach(c => c.classList.remove('collapsed'));
    toggleBtns.forEach(t => {
      t.classList.remove('collapsed');
      t.textContent = '−';
    });
    previews.forEach(p => p.classList.add('hidden'));
    footers.forEach(f => f.classList.remove('hidden'));
  });
}

function switchInspectorTab(target) {
  state.activeInspectorTab = target;
  if (target === 'editorView') {
    tabTextEditor.classList.add('active');
    tabJsonViewer.classList.remove('active');
    itemsListWrap.classList.remove('hidden');
    jsonViewerWrap.classList.add('hidden');
  } else {
    tabJsonViewer.classList.add('active');
    tabTextEditor.classList.remove('active');
    itemsListWrap.classList.add('hidden');
    jsonViewerWrap.classList.remove('hidden');
  }
}

// Update JSON Tree with Current Data
function updateJsonBoxWithCurrentData() {
  currentJsonPayload = {
    engine: "PaddleOCR Multilingual DBNet (Mika SOTA)",
    mode: state.currentMode,
    timestamp: new Date().toISOString(),
    image_info: {
      source: typeof state.currentImageSource === 'string' ? state.currentImageSource : (state.currentImageSource?.name || "clipboard_image.png"),
      dimensions: statDim.textContent,
      detected_language: statLang.textContent
    },
    total_boxes_detected: state.items.length,
    items: state.items.map((it, idx) => ({
      index: idx + 1,
      role: it.role || "text",
      detected_text: it.orig,
      confidence: it.conf || 0.99,
      translated_text: state.currentMode === 'detect' ? "(detect_only)" : it.trans,
      bounding_box: {
        ymin: it.box[0],
        xmin: it.box[1],
        ymax: it.box[2],
        xmax: it.box[3],
        width: it.box[3] - it.box[1],
        height: it.box[2] - it.box[0]
      },
      style_metadata: {
        hex_color: it.hex,
        font_size_px: it.fontSize
      }
    }))
  };

  renderCollapsibleTree(currentJsonPayload, jsonTreeContainer);
}

// Interactive Collapsible Tree Renderer
function renderCollapsibleTree(data, container) {
  container.innerHTML = '';
  const rootNode = createCollapsibleNode(null, data, true, 0);
  container.appendChild(rootNode);
}

function createCollapsibleNode(key, value, isLast = true, depth = 0) {
  const wrapper = document.createElement('div');
  wrapper.className = 'json-node';

  const isArray = Array.isArray(value);
  const isObject = value !== null && typeof value === 'object';

  // 1. Primitive Values (string, number, boolean, null)
  if (!isObject) {
    const row = document.createElement('div');
    row.className = 'json-row';
    row.innerHTML = `${renderKey(key)}${renderPrimitive(value)}${isLast ? '' : '<span class="json-comma">,</span>'}`;
    wrapper.appendChild(row);
    return wrapper;
  }

  // 2. Object or Array (Collapsible)
  const openBracket = isArray ? '[' : '{';
  const closeBracket = isArray ? ']' : '}';
  const keys = isArray ? value : Object.keys(value);
  const count = isArray ? value.length : keys.length;

  const headerRow = document.createElement('div');
  headerRow.className = 'json-row';

  // Toggle button ([-] 사각형 테두리 버튼)
  const toggleBtn = document.createElement('span');
  toggleBtn.className = 'json-toggle';
  toggleBtn.textContent = '−';

  // Collapsed Preview Badge (e.g. "... 13 items ...")
  let previewText = `${count} ${isArray ? 'items' : 'keys'}`;
  if (!isArray && value.detected_text) {
    previewText = `#${value.index || ''} "${value.detected_text}" (${value.confidence || ''})`;
  }
  const previewBadge = document.createElement('span');
  previewBadge.className = 'json-collapsed-preview hidden';
  previewBadge.textContent = `{ ... ${previewText} ... }`;

  headerRow.appendChild(toggleBtn);
  if (key !== null) {
    headerRow.insertAdjacentHTML('beforeend', renderKey(key));
  }
  headerRow.insertAdjacentHTML('beforeend', `<span class="json-bracket">${openBracket}</span>`);
  headerRow.appendChild(previewBadge);

  // Children Container
  const childrenContainer = document.createElement('div');
  childrenContainer.className = 'json-children';

  if (isArray) {
    value.forEach((item, idx) => {
      const childNode = createCollapsibleNode(null, item, idx === value.length - 1, depth + 1);
      childrenContainer.appendChild(childNode);
    });
  } else {
    const objKeys = Object.keys(value);
    objKeys.forEach((k, idx) => {
      const childNode = createCollapsibleNode(k, value[k], idx === objKeys.length - 1, depth + 1);
      childrenContainer.appendChild(childNode);
    });
  }

  // Footer Row (Closing bracket)
  const footerRow = document.createElement('div');
  footerRow.className = 'json-row json-footer';
  footerRow.innerHTML = `<span class="json-bracket" style="margin-left: 18px;">${closeBracket}</span>${isLast ? '' : '<span class="json-comma">,</span>'}`;

  // Click handler for collapse/expand
  const toggleCollapse = () => {
    const isCollapsed = childrenContainer.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed', isCollapsed);
    toggleBtn.textContent = isCollapsed ? '+' : '−';
    previewBadge.classList.toggle('hidden', !isCollapsed);
    footerRow.classList.toggle('hidden', isCollapsed);
  };

  toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleCollapse();
  });

  previewBadge.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleCollapse();
  });

  // Assemble Node
  wrapper.appendChild(headerRow);
  wrapper.appendChild(childrenContainer);
  wrapper.appendChild(footerRow);

  return wrapper;
}

function renderKey(key) {
  if (key === null) return '';
  return `<span class="json-key">"${escapeHtml(key)}"</span><span class="json-colon">: </span>`;
}

function renderPrimitive(val) {
  if (typeof val === 'string') {
    return `<span class="json-string">"${escapeHtml(val)}"</span>`;
  } else if (typeof val === 'number') {
    return `<span class="json-number">${val}</span>`;
  } else if (typeof val === 'boolean') {
    return `<span class="json-bool">${val}</span>`;
  } else if (val === null) {
    return `<span class="json-null">null</span>`;
  }
  return escapeHtml(String(val));
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// Drag & Drop Setup
function setupDropzone() {
  dropzoneBox.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzoneBox.classList.add('dragover');
  });

  dropzoneBox.addEventListener('dragleave', () => {
    dropzoneBox.classList.remove('dragover');
  });

  dropzoneBox.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzoneBox.classList.remove('dragover');
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleLocalFile(e.dataTransfer.files[0]);
    }
  });

  fileInput.addEventListener('change', (e) => {
    if (e.target.files && e.target.files[0]) {
      handleLocalFile(e.target.files[0]);
    }
  });

  btnFetchUrl.addEventListener('click', () => {
    const url = remoteUrlInput.value.trim();
    if (!url) {
      alert('이미지 URL을 입력해주세요.');
      return;
    }
    handleRemoteUrl(url);
  });
}

// Clipboard Paste (Ctrl+C, Ctrl+V) Setup
function setupClipboardPaste() {
  function handlePasteEvent(e) {
    const clipboardData = e.clipboardData || window.clipboardData;
    if (!clipboardData) return;

    // 1. files 우선 검사 (가장 신뢰성 높음)
    if (clipboardData.files && clipboardData.files.length > 0) {
      for (let i = 0; i < clipboardData.files.length; i++) {
        const file = clipboardData.files[i];
        if (file && file.type && file.type.startsWith('image/')) {
          console.log('[Pictro Clipboard] Image pasted via files:', file.name || file.type);
          handleLocalFile(file, `clipboard_${Date.now()}.png`);
          e.preventDefault();
          e.stopPropagation();
          return;
        }
      }
    }

    // 2. items 정밀 순회 (length 기반 루프)
    const items = clipboardData.items;
    if (items && items.length > 0) {
      for (let i = 0; i < items.length; i++) {
        const item = items[i];
        if (!item) continue;
        if (item.type && item.type.startsWith('image/')) {
          const blob = item.getAsFile();
          if (blob) {
            console.log('[Pictro Clipboard] Image pasted via items:', blob.type);
            handleLocalFile(blob, `clipboard_${Date.now()}.png`);
            e.preventDefault();
            e.stopPropagation();
            return;
          }
        } else if (item.type === 'text/html') {
          item.getAsString((html) => {
            const match = html.match(/<img[^>]+src=["']([^"']+)["']/i);
            if (match && match[1]) {
              const imgSrc = match[1];
              console.log('[Pictro Clipboard] Extracted image from HTML clipboard:', imgSrc);
              if (imgSrc.startsWith('data:image')) {
                displayUploadedImage(imgSrc, 'pasted_html_img.png');
              } else if (imgSrc.startsWith('http')) {
                handleRemoteUrl(imgSrc);
              }
            }
          });
        } else if (item.kind === 'string' && item.type === 'text/plain') {
          item.getAsString((text) => {
            if (text && (text.startsWith('http://') || text.startsWith('https://'))) {
              remoteUrlInput.value = text.trim();
              console.log('[Pictro Clipboard] Image URL pasted from clipboard:', text);
              handleRemoteUrl(text.trim());
            }
          });
        }
      }
    }
  }

  // window와 document 모두에 캡처링(capture: true)으로 등록하여 어디서든 Ctrl+V 수신
  window.addEventListener('paste', handlePasteEvent, true);
  document.addEventListener('paste', handlePasteEvent, true);

  // Explicit Clipboard button click
  btnPasteClipboard.addEventListener('click', async () => {
    // 1. 최신 브라우저의 Async Clipboard API 시도 (HTTPS 환경)
    if (navigator.clipboard && typeof navigator.clipboard.read === 'function') {
      try {
        const clipboardItems = await navigator.clipboard.read();
        for (const item of clipboardItems) {
          for (const type of item.types) {
            if (type.startsWith('image/')) {
              const blob = await item.getType(type);
              handleLocalFile(blob, `pasted_${Date.now()}.png`);
              return;
            }
          }
        }
      } catch (err) {
        console.warn('[Pictro Clipboard] navigator.clipboard.read restricted:', err);
      }
    }

    // 2. HTTP 환경에서는 브라우저 보안상 버튼 직접 읽기가 제한되므로 즉시 파일 탐색기를 띄워줌
    fileInput.click();
  });
}

// Handle Local File
function handleLocalFile(file, customName) {
  state.currentImageSource = file;
  const reader = new FileReader();
  reader.onload = (e) => {
    const dataUrl = e.target.result;
    displayUploadedImage(dataUrl, customName || file.name);
  };
  reader.readAsDataURL(file);
}

// Handle Remote URL
function handleRemoteUrl(url) {
  state.currentImageSource = url;
  displayUploadedImage(url, url.split('/').pop() || 'remote_banner.jpg');
}

// Display Uploaded Image into Viewport (Ready State before AI Run)
function displayUploadedImage(src, name) {
  origImg.src = src;
  outputImg.src = '';
  outputImg.classList.add('hidden');
  if (outputPlaceholder) {
    outputPlaceholder.classList.remove('hidden');
    outputPlaceholder.innerHTML = `
      <div style="font-size: 28px; margin-bottom: 8px;">⚡</div>
      <p>상단의 <strong>[AI 변환 실행]</strong> 버튼을 누르면<br>고화질 변환 결과가 여기에 표시됩니다.</p>
    `;
  }

  // Clear any existing OCR bounding boxes before AI process
  overlayBoxes.innerHTML = '';

  // Reset previously processed background & live canvas
  state.cleanBgImageObj = null;
  state.cleanBgUrl = null;
  state.ocrVisUrl = null;
  state.transOutUrl = null;
  if (liveCanvas) {
    const ctx = liveCanvas.getContext('2d');
    ctx.clearRect(0, 0, liveCanvas.width, liveCanvas.height);
  }
  if (typeof singleImg !== 'undefined' && singleImg) {
    singleImg.src = '';
  }

  // Switch to Side-by-Side View
  dropzoneBox.classList.add('hidden');
  dualViewerWrap.classList.remove('hidden');
  singleViewerWrap.classList.add('hidden');
  canvasViewerWrap.classList.add('hidden');

  const sideTab = document.querySelector('[data-tab="sideBySide"]');
  if (sideTab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    sideTab.classList.add('active');
  }

  // Reset Inspector items & stats
  state.items = [];
  renderItemsList([]);
  itemCountBadge.textContent = '0 Items';
  btnDownloadJson.disabled = true;
  btnDownloadImage.disabled = true;

  origImg.onload = () => {
    statDim.textContent = `${origImg.naturalWidth}x${origImg.naturalHeight}`;
    statLang.textContent = '감지 대기';
    statLatency.textContent = '-';
    statWcag.textContent = '변환 대기 (READY)';

    updateJsonBoxWithCurrentData();
    console.log(`[Pictro Studio] Loaded new image: ${name} (${origImg.naturalWidth}x${origImg.naturalHeight}) - Ready for AI Process`);
  };
}

// Sample Buttons
function setupSampleButtons() {
  const sampleBtns = document.querySelectorAll('.btn-sample');
  sampleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const sType = btn.dataset.sample;
      if (sType === 'banner5') {
        loadSampleData(SAMPLE_BANNER5);
      } else {
        loadSampleData({
          ...SAMPLE_BANNER5,
          name: "banner6_orig.jpg",
          origUrl: "examples/banner6_orig.jpg",
          cleanBgUrl: "examples/test_modes_out/banner6_orig_clean_bg.jpg",
          transOutUrl: "examples/test_modes_out/banner6_orig_translated_ko.jpg",
          ocrVisUrl: "examples/test_modes_out/banner6_orig_ocr_detected.jpg",
          width: 460,
          height: 460
        });
      }
    });
  });
}

// Load Sample Data
function loadSampleData(sample) {
  state.currentImageSource = sample.origUrl;
  dropzoneBox.classList.add('hidden');
  dualViewerWrap.classList.remove('hidden');

  origImg.src = sample.origUrl;
  outputImg.src = sample.transOutUrl;
  outputImg.classList.remove('hidden');
  if (outputPlaceholder) outputPlaceholder.classList.add('hidden');
  
  statDim.textContent = `${sample.width}x${sample.height}`;
  statLang.textContent = sample.sourceLang.toUpperCase() + ' (감지됨)';
  statLatency.textContent = sample.latency;
  statWcag.textContent = state.currentMode.toUpperCase();

  state.items = JSON.parse(JSON.stringify(sample.items));
  renderItemsList(state.items);
  renderOverlayBoxes(state.items, sample.width, sample.height);
  updateJsonBoxWithCurrentData();

  // Preload clean background image for Live Canvas
  const cleanImg = new Image();
  cleanImg.src = sample.cleanBgUrl;
  cleanImg.onload = () => {
    state.cleanBgImageObj = cleanImg;
    renderLiveCanvas();
  };

  btnDownloadJson.disabled = false;
  btnDownloadImage.disabled = false;

  if (state.currentMode === 'detect') {
    const ocrTab = document.querySelector('[data-tab="ocrBoxes"]');
    if (ocrTab) ocrTab.click();
  }
}

// Tab Switching
function setupViewTabs() {
  const tabBtns = document.querySelectorAll('.tab-btn');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const tab = btn.dataset.tab;
      state.activeTab = tab;

      dualViewerWrap.classList.add('hidden');
      canvasViewerWrap.classList.add('hidden');
      singleViewerWrap.classList.add('hidden');

      if (tab === 'sideBySide') {
        dualViewerWrap.classList.remove('hidden');
      } else if (tab === 'liveCanvas') {
        canvasViewerWrap.classList.remove('hidden');
        renderLiveCanvas();
      } else if (tab === 'cleanBg') {
        singleViewerWrap.classList.remove('hidden');
        singleViewBadge.textContent = '복원 배경 (글자 완벽 제거)';
        singleImg.src = state.cleanBgUrl || '';
      } else if (tab === 'ocrBoxes') {
        singleViewerWrap.classList.remove('hidden');
        singleViewBadge.textContent = 'PaddleOCR 바운딩 박스 검출 결과';
        singleImg.src = state.ocrVisUrl || '';
      }
    });
  });
}

// Setup Zoom Controls
function setupZoomTools() {
  document.getElementById('btnZoomIn').addEventListener('click', () => {
    state.zoomLevel = Math.min(2.5, state.zoomLevel + 0.15);
    applyZoom();
  });
  document.getElementById('btnZoomOut').addEventListener('click', () => {
    state.zoomLevel = Math.max(0.5, state.zoomLevel - 0.15);
    applyZoom();
  });
  document.getElementById('btnZoomReset').addEventListener('click', () => {
    state.zoomLevel = 1.0;
    applyZoom();
  });
}

function applyZoom() {
  const wrappers = document.querySelectorAll('.image-wrapper, .canvas-container');
  wrappers.forEach(wrap => {
    wrap.style.transform = `scale(${state.zoomLevel})`;
    wrap.style.transformOrigin = 'center center';
  });
}

// Render Item Editor Cards in Right Panel
function renderItemsList(items) {
  itemsListWrap.innerHTML = '';
  itemCountBadge.textContent = `${items.length} Items`;

  items.forEach((it, idx) => {
    const card = document.createElement('div');
    card.className = 'item-card';
    card.id = `item-card-${idx}`;

    card.innerHTML = `
      <div class="item-card-top">
        <span class="item-idx-badge">#${idx + 1}</span>
        <span class="item-role-tag">${(it.role || 'text').toUpperCase()}</span>
        <span class="wcag-badge pass">신뢰도 ${( (it.conf || 0.99) * 100 ).toFixed(1)}%</span>
      </div>
      <div class="item-orig-text" title="원본 텍스트">${it.orig}</div>
      <div class="item-trans-input-wrap" style="display: flex; gap: 6px; align-items: center;">
        <input type="text" class="item-trans-input" value="${it.trans}" data-idx="${idx}" placeholder="번역 문구 입력" style="flex: 1;">
        <button type="button" class="btn-learn-glossary" data-idx="${idx}" title="이 교정 단어를 문맥과 함께 사전에 자동 학습 등록" style="background: rgba(0, 240, 118, 0.15); border: 1px solid rgba(0, 240, 118, 0.4); color: #00f076; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer; white-space: nowrap;">
          사전 학습
        </button>
      </div>
      <div class="item-controls-row">
        <div class="color-picker-wrap">
          <span>색상</span>
          <div class="color-preview-box" style="background-color: ${it.hex};">
            <input type="color" value="${it.hex}" data-idx="${idx}">
          </div>
          <span class="color-hex-label">${it.hex}</span>
        </div>
        <div class="font-size-wrap">
          <span>크기</span>
          <input type="range" class="font-size-slider" min="12" max="72" value="${it.fontSize}" data-idx="${idx}">
          <span class="size-val">${it.fontSize}px</span>
        </div>
      </div>
    `;

    // Highlight on Hover
    card.addEventListener('mouseenter', () => highlightBox(idx, true));
    card.addEventListener('mouseleave', () => highlightBox(idx, false));

    // Live Text Input Change
    const textInput = card.querySelector('.item-trans-input');
    textInput.addEventListener('input', (e) => {
      state.items[idx].trans = e.target.value;
      renderLiveCanvas();
      updateJsonBoxWithCurrentData();
    });

    // Learn to Glossary Button Click
    const btnLearn = card.querySelector('.btn-learn-glossary');
    if (btnLearn) {
      btnLearn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const currentItem = state.items[idx];
        const srcTerm = (currentItem.orig || '').trim();
        const corrTerm = (currentItem.trans || '').trim();
        if (!srcTerm || !corrTerm) {
          alert('원문과 교정 문구를 모두 확인해 주세요.');
          return;
        }

        // 전체 배너 문맥 추출
        const fullContext = (state.items || []).map(x => x.orig).filter(Boolean).join(' ');

        btnLearn.textContent = '등록 중...';
        btnLearn.disabled = true;

        try {
          const formData = new FormData();
          formData.append('source_term', srcTerm);
          formData.append('corrected_term', corrTerm);
          formData.append('target_lang', state.targetLang || 'en');
          formData.append('category', 'COMMON');
          formData.append('context_text', fullContext);

          const res = await fetch('/pictro-api/api/v1/glossary/save', {
            method: 'POST',
            body: formData
          });
          const jsonRes = await res.json();
          if (jsonRes.success) {
            btnLearn.textContent = '✔ 학습 완료';
            btnLearn.style.background = 'rgba(0, 240, 118, 0.3)';
            setTimeout(() => {
              btnLearn.textContent = '사전 학습';
              btnLearn.disabled = false;
            }, 3000);
          } else {
            alert('사전 등록 실패: ' + (jsonRes.detail || jsonRes.message));
            btnLearn.textContent = '사전 학습';
            btnLearn.disabled = false;
          }
        } catch (err) {
          console.error('[Glossary Save Error]', err);
          alert('사전 등록 중 오류가 발생했습니다.');
          btnLearn.textContent = '사전 학습';
          btnLearn.disabled = false;
        }
      });
    }

    // Live Color Change
    const colorInput = card.querySelector('input[type="color"]');
    colorInput.addEventListener('input', (e) => {
      const newHex = e.target.value;
      state.items[idx].hex = newHex;
      card.querySelector('.color-preview-box').style.backgroundColor = newHex;
      card.querySelector('.color-hex-label').textContent = newHex;
      renderLiveCanvas();
      updateJsonBoxWithCurrentData();
    });

    // Live Font Size Slider
    const sizeSlider = card.querySelector('.font-size-slider');
    sizeSlider.addEventListener('input', (e) => {
      const newSize = parseInt(e.target.value, 10);
      state.items[idx].fontSize = newSize;
      card.querySelector('.size-val').textContent = `${newSize}px`;
      renderLiveCanvas();
      updateJsonBoxWithCurrentData();
    });

    itemsListWrap.appendChild(card);
  });
}

// Render Overlay Bounding Boxes on Original Image
function renderOverlayBoxes(items, imgW, imgH) {
  overlayBoxes.innerHTML = '';
  items.forEach((it, idx) => {
    const [ymin, xmin, ymax, xmax] = it.box;
    const topPct = (ymin / imgH) * 100;
    const leftPct = (xmin / imgW) * 100;
    const heightPct = ((ymax - ymin) / imgH) * 100;
    const widthPct = ((xmax - xmin) / imgW) * 100;

    const boxEl = document.createElement('div');
    boxEl.className = 'box-highlight';
    boxEl.id = `box-hl-${idx}`;
    boxEl.style.top = `${topPct}%`;
    boxEl.style.left = `${leftPct}%`;
    boxEl.style.height = `${heightPct}%`;
    boxEl.style.width = `${widthPct}%`;
    boxEl.innerHTML = `<span class="box-label">#${it.id !== undefined ? it.id : idx}</span>`;

    boxEl.addEventListener('mouseenter', () => highlightBox(idx, true));
    boxEl.addEventListener('mouseleave', () => highlightBox(idx, false));
    boxEl.addEventListener('click', () => {
      const card = document.getElementById(`item-card-${idx}`);
      if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.querySelector('.item-trans-input').focus();
      }
    });

    overlayBoxes.appendChild(boxEl);
  });
}

// Highlight Bounding Box & Card
function highlightBox(idx, active) {
  const box = document.getElementById(`box-hl-${idx}`);
  const card = document.getElementById(`item-card-${idx}`);
  if (box) {
    if (active) box.classList.add('active-highlight');
    else box.classList.remove('active-highlight');
  }
  if (card) {
    if (active) card.classList.add('active-card');
    else card.classList.remove('active-card');
  }
}

// Render Live Interactive Canvas (Inpainting Clean BG + Custom Typography)
function renderLiveCanvas() {
  if (!liveCanvas) return;
  const ctx = liveCanvas.getContext('2d');

  if (!state.cleanBgImageObj) {
    ctx.clearRect(0, 0, liveCanvas.width, liveCanvas.height);
    return;
  }

  const bgImg = state.cleanBgImageObj;
  liveCanvas.width = bgImg.naturalWidth || bgImg.width || 800;
  liveCanvas.height = bgImg.naturalHeight || bgImg.height || 800;

  ctx.clearRect(0, 0, liveCanvas.width, liveCanvas.height);

  // 1. Draw Clean Inpainted Background
  ctx.drawImage(bgImg, 0, 0, liveCanvas.width, liveCanvas.height);

  // 2. Draw Transformed Typography Items
  state.items.forEach(it => {
    if (!it.trans) return;

    const [ymin, xmin, ymax, xmax] = it.box;
    const boxW = xmax - xmin;
    const boxH = ymax - ymin;

    ctx.save();
    ctx.fillStyle = it.hex || '#222222';
    ctx.textBaseline = 'middle';

    const fSize = it.fontSize || Math.max(14, Math.floor(boxH * 0.85));
    const isBold = it.role === 'title' || it.role === 'price';
    ctx.font = `${isBold ? '700' : '500'} ${fSize}px 'Noto Sans KR', 'Inter', sans-serif`;

    // Price items: right align; Title items: left align
    let textX = xmin;
    if (it.role === 'price') {
      ctx.textAlign = 'right';
      textX = xmax;
    } else {
      ctx.textAlign = 'left';
    }
    const textY = ymin + boxH / 2;

    ctx.fillText(it.trans, textX, textY);
    ctx.restore();
  });
}

// Process AI Execution Trigger
function setupProcessButton() {
  btnProcessAI.addEventListener('click', async () => {
    loadingOverlay.classList.remove('hidden');
    const loadingText = document.getElementById('loadingText');
    const loadingSub = document.getElementById('loadingSub');

    if (state.currentMode === 'detect') {
      loadingText.textContent = '[1/1] PaddleOCR 텍스트 검출 중...';
      loadingSub.textContent = 'Mika 서버 고정밀 DBNet 엔진 가동';
    } else if (state.currentMode === 'clear') {
      loadingText.textContent = '[2/2] Big-LaMa 배경 인페인팅 복원 중...';
      loadingSub.textContent = '글자 제거 및 원본 텍스처 복원';
    } else {
      loadingText.textContent = '[풀코스] 검출 -> 번역 -> 배경 복원 -> 타이포 치환';
      loadingSub.textContent = '다국어 신경망 번역 + Mika TorchScript 엔진 가동';
    }

    const tStart = performance.now();
    let apiSuccess = false;

    const formData = new FormData();
      formData.append('mode', state.currentMode);
      formData.append('lang', targetLangSelect.value);
      formData.append('target_lang', targetLangSelect.value);

      if (state.currentImageSource instanceof File || state.currentImageSource instanceof Blob) {
        formData.append('image', state.currentImageSource, 'clipboard.png');
        formData.append('image_file', state.currentImageSource, 'clipboard.png');
      } else if (typeof state.currentImageSource === 'string' && state.currentImageSource.startsWith('data:image')) {
        const arr = state.currentImageSource.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) {
          u8arr[n] = bstr.charCodeAt(n);
        }
        const blob = new Blob([u8arr], { type: mime });
        formData.append('image', blob, 'clipboard.png');
        formData.append('image_file', blob, 'clipboard.png');
      } else if (typeof state.currentImageSource === 'string' && state.currentImageSource.startsWith('http')) {
        formData.append('url', state.currentImageSource);
        formData.append('image_url', state.currentImageSource);
      }

      // 현재 로그인된 사용자 API 키 및 auto_save(0: 임시/테스트, 즉시 용량 차감) 전달
      const userApiKey = localStorage.getItem('pictro_api_key') || 'FOXUNNI-PARTNER-MASTER-KEY-2026';
      formData.append('api_key', userApiKey);
      formData.append('auto_save', '0');

      let errorMessage = null;

      try {
        const apiUrl = window.location.origin.includes('thrillrig.com') 
          ? '/api/pictro_process.php' 
          : 'http://thrillrig.com:9990/api/pictro_process.php';

        console.log(`[Pictro Studio] Calling API: ${apiUrl} (Mode: ${state.currentMode})`);
        const resp = await fetch(apiUrl, { method: 'POST', body: formData });
        const result = await resp.json();

        if (result && result.success && result.data) {
          apiSuccess = true;
          const d = result.data;
          console.log('[Pictro Studio] Real API Response Received:', d);

          const elapsedSec = ((performance.now() - tStart) / 1000).toFixed(1) + 's';
          statLatency.textContent = elapsedSec;
          statWcag.textContent = state.currentMode.toUpperCase() + ' DONE';

          function toWebUrl(serverPath) {
            if (!serverPath) return "";
            if (serverPath.startsWith("http")) return serverPath;
            if (serverPath.startsWith("/pictro-api/")) return serverPath;
            if (serverPath.startsWith("/files/")) return "/pictro-api" + serverPath;
            if (serverPath.startsWith("files/")) return "/pictro-api/" + serverPath;
            if (serverPath.includes("web_output/")) {
              return "web_output/" + serverPath.split("web_output/")[1];
            }
            if (serverPath.includes("examples/")) {
              return "examples/" + serverPath.split("examples/")[1];
            }
            return serverPath;
          }

          if (d.ocr_vis) {
            state.ocrVisUrl = toWebUrl(d.ocr_vis);
          }
          if (d.clean_bg) {
            state.cleanBgUrl = toWebUrl(d.clean_bg);
            const cleanImg = new Image();
            cleanImg.src = state.cleanBgUrl;
            cleanImg.onload = () => {
              state.cleanBgImageObj = cleanImg;
              if (state.activeTab === 'liveCanvas') {
                renderLiveCanvas();
              }
            };
          }
          if (d.translated_image) {
            state.transOutUrl = toWebUrl(d.translated_image);
            outputImg.src = state.transOutUrl;
            outputImg.classList.remove('hidden');
            if (outputPlaceholder) outputPlaceholder.classList.add('hidden');
          }

          if (d.history_id || state.transOutUrl) {
            state.currentHistoryId = d.history_id || 0;
            const isLoggedIn = !!(localStorage.getItem('pictro_token') && localStorage.getItem('pictro_api_key'));
            if (btnSaveToGallery) {
              if (isLoggedIn) {
                btnSaveToGallery.style.display = 'block';
                btnSaveToGallery.disabled = false;
                btnSaveToGallery.style.opacity = '1';
                btnSaveToGallery.innerHTML = '📁 내 보관함(갤러리)에 저장';
                btnSaveToGallery.style.background = 'linear-gradient(135deg, #10b981, #059669)';
              } else {
                btnSaveToGallery.style.display = 'none';
              }
            }
          }

          const rawItems = d.items || (d.meta_data && d.meta_data.items) || [];
          if (rawItems.length > 0) {
            state.items = rawItems.map((it, idx) => {
              const b = it.box || (it.bounding_box && it.bounding_box.ymin_xmin_ymax_xmax) || [0, 0, 10, 10];
              const origText = it.text || it.source_text || "";
              const transText = it.translated_text || it.text || "";
              const hexColor = it.custom_color || (it.typography_style && it.typography_style.text_color_hex) || "#ffffff";
              const boxH = Math.max(10, b[2] - b[0]);
              return {
                id: it.id !== undefined ? it.id : (it.item_id !== undefined ? it.item_id : idx),
                box: b,
                orig: origText,
                trans: transText,
                hex: hexColor,
                role: (it.semantic_analysis && it.semantic_analysis.role) || "text",
                fontSize: Math.max(12, Math.floor(boxH * 0.85)),
                conf: it.score || it.confidence_score || 0.99
              };
            });
            renderItemsList(state.items);
            renderOverlayBoxes(state.items, origImg.naturalWidth || 800, origImg.naturalHeight || 800);
          }

          updateJsonBoxWithCurrentData();
        } else {
          errorMessage = (result && result.error) ? result.error : '서버 처리 중 오류가 발생했습니다.';
          if (result && result.raw_output) {
            console.error('[Pictro API Raw Error]:', result.raw_output);
          }
        }
      } catch (err) {
        console.error('[Pictro Studio API Error]:', err);
        errorMessage = err.message || '네트워크 연결 오류가 발생했습니다.';
      }

      loadingOverlay.classList.add('hidden');

      // [핵심 요구사항] 에러 발생 시 처리 (오른쪽 화면 숨김 & 에러 안내)
      if (!apiSuccess) {
        statLatency.textContent = '-';
        statWcag.textContent = 'ERROR (변환 실패)';

        // 1. 오른쪽 화면 이미지 절대 보이지 않게 숨김
        outputImg.src = '';
        outputImg.classList.add('hidden');

        // 2. 오른쪽 화면에 에러 발생 안내 박스 표시
        if (outputPlaceholder) {
          outputPlaceholder.classList.remove('hidden');
          outputPlaceholder.innerHTML = `
            <div style="font-size: 34px; margin-bottom: 8px;">⚠️</div>
            <h4 style="color: #ef4444; font-size: 15px; font-weight: 700; margin-bottom: 6px;">AI 변환 실패</h4>
            <p style="color: #94a3b8; font-size: 12px; line-height: 1.5;">${errorMessage}<br>다시 시도하거나 다른 이미지를 업로드해 주세요.</p>
          `;
        }

        // 3. 바운딩 박스 제거 및 우측 인스펙터 리셋
        overlayBoxes.innerHTML = '';
        state.items = [];
        state.cleanBgImageObj = null;
        state.cleanBgUrl = null;
        state.ocrVisUrl = null;
        state.transOutUrl = null;
        if (liveCanvas) {
          const ctx = liveCanvas.getContext('2d');
          ctx.clearRect(0, 0, liveCanvas.width, liveCanvas.height);
        }
        renderItemsList([]);
        itemCountBadge.textContent = '0 Items';
        btnDownloadJson.disabled = true;
        btnDownloadImage.disabled = true;

        switchInspectorTab('editorView');
        const sideTab = document.querySelector('[data-tab="sideBySide"]');
        if (sideTab) sideTab.click();

        alert(`[에러 발생] AI 변환 처리에 실패했습니다.\n\n오류 내용: ${errorMessage}`);
        return;
      }

      statWcag.textContent = state.currentMode.toUpperCase() + ' DONE';
      updateJsonBoxWithCurrentData();

      if (state.currentMode === 'detect') {
        switchInspectorTab('jsonView');
        document.querySelector('[data-tab="ocrBoxes"]').click();
      } else if (state.currentMode === 'clear') {
        switchInspectorTab('editorView');
        document.querySelector('[data-tab="cleanBg"]').click();
      } else {
        switchInspectorTab('editorView');
        document.querySelector('[data-tab="sideBySide"]').click();
      }
  });
}

// Export & Downloads
function setupExportButtons() {
  btnDownloadJson.addEventListener('click', () => {
    const exportData = {
      app: "Pictro Studio",
      exported_at: new Date().toISOString(),
      mode: state.currentMode,
      target_lang: targetLangSelect.value,
      banner_dim: statDim.textContent,
      items: state.items
    };
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `pictro_${state.currentMode}_${Date.now()}.json`;
    a.click();
    URL.revokeObjectURL(url);
  });

  btnDownloadImage.addEventListener('click', () => {
    liveCanvas.toBlob((blob) => {
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `pictro_final_${targetLangSelect.value}_${Date.now()}.png`;
      a.click();
      URL.revokeObjectURL(url);
    }, 'image/png', 0.98);
  });

  // 내 보관함(갤러리)에 정식 등록 버튼
  if (btnSaveToGallery) {
    btnSaveToGallery.addEventListener('click', async () => {
      if (!state.currentHistoryId) {
        alert('보관함에 저장할 변환 결과물이 없습니다.');
        return;
      }
      btnSaveToGallery.disabled = true;
      btnSaveToGallery.innerHTML = '⏳ 보관함 등록 처리 중...';

      try {
        const apiKey = localStorage.getItem('pictro_api_key') || 'FOXUNNI-PARTNER-MASTER-KEY-2026';
        const saveUrl = window.location.origin.includes('thrillrig.com')
          ? '/pictro-api/api/v1/gallery/save_banner'
          : 'http://thrillrig.com:9990/pictro-api/api/v1/gallery/save_banner';

        const fd = new FormData();
        fd.append('history_id', state.currentHistoryId);

        const res = await fetch(saveUrl, {
          method: 'POST',
          headers: { 'X-API-Key': apiKey },
          body: fd
        });
        const resData = await res.json();

        if (resData.success) {
          btnSaveToGallery.innerHTML = '✔ 보관함 저장 완료';
          btnSaveToGallery.style.background = '#047857';
          alert('📁 내 갤러리 보관함에 안전하게 저장되었습니다!\n상단 [구독자 포털 (My Gallery)]에서 확인하실 수 있습니다.');
        } else {
          throw new Error(resData.detail || '보관함 등록에 실패했습니다.');
        }
      } catch (err) {
        alert('보관함 등록 실패: ' + err.message);
        btnSaveToGallery.disabled = false;
        btnSaveToGallery.innerHTML = '📁 내 보관함(갤러리)에 저장';
      }
    });
  }
}

// Check Login State and Adjust UI Elements
function setupAuthUI() {
  const token = localStorage.getItem('pictro_token');
  const apiKey = localStorage.getItem('pictro_api_key');
  const isLoggedIn = !!(token && apiKey);

  const btnGalleryLink = document.querySelector('.btn-gallery');
  if (!isLoggedIn) {
    // 비로그인 사용자: 보관함 저장 버튼 완전 숨김 (개인 보관함 없음)
    if (btnSaveToGallery) {
      btnSaveToGallery.style.display = 'none';
    }
    // 상단 네비게이션을 로그인 유도로 전환
    if (btnGalleryLink) {
      btnGalleryLink.innerHTML = '🔑 로그인 / 가입';
      btnGalleryLink.href = 'login.html';
      btnGalleryLink.style.background = 'rgba(59, 130, 246, 0.15)';
      btnGalleryLink.style.borderColor = 'rgba(59, 130, 246, 0.4)';
    }
  } else {
    // 로그인 사용자: 저장 버튼 표시 (초기에는 비활성화)
    if (btnSaveToGallery) {
      btnSaveToGallery.style.display = 'block';
    }
  }
}
