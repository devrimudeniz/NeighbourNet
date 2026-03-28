/**
 * Kalkan Social Image Editor
 * Instagram-style mobile-first image editing
 * Touch-friendly crop, filters, and drawing
 */

class ImageEditor {
    constructor(options = {}) {
        this.originalImage = null;
        this.cropper = null;
        this.currentFilter = 'none';
        this.isDrawing = false;
        this.drawColor = '#ffffff';
        this.drawSize = 4;
        this.currentMode = 'crop';
        this.onSave = options.onSave || null;
        this.lang = options.lang || 'tr';
        this.drawCanvas = null;
        this.drawCtx = null;

        this.filters = [
            { name: 'Normal', value: 'none' },
            { name: 'B&W', value: 'grayscale(100%)' },
            { name: 'Sepia', value: 'sepia(80%)' },
            { name: 'Vivid', value: 'saturate(150%) contrast(110%)' },
            { name: 'Warm', value: 'sepia(30%) saturate(140%)' },
            { name: 'Cool', value: 'hue-rotate(20deg) saturate(90%)' },
            { name: 'Fade', value: 'contrast(90%) brightness(110%) saturate(80%)' },
            { name: 'Drama', value: 'contrast(130%) saturate(120%)' }
        ];

        this.init();
    }

    init() {
        this.createModal();
        this.bindEvents();
    }

    createModal() {
        const t = this.lang === 'en' ? {
            cancel: 'Cancel', done: 'Done', crop: 'Crop', filter: 'Filter', draw: 'Draw', clear: 'Clear'
        } : {
            cancel: 'İptal', done: 'Tamam', crop: 'Kırp', filter: 'Filtre', draw: 'Çiz', clear: 'Temizle'
        };

        const modal = document.createElement('div');
        modal.id = 'imgEditorModal';
        modal.className = 'fixed inset-0 z-[9999] bg-black hidden';
        modal.innerHTML = `
            <div class="flex flex-col h-full w-full">
                <!-- Header -->
                <header class="flex-none flex items-center justify-between px-4 py-3 bg-black/90 backdrop-blur-lg border-b border-white/10 safe-area-top">
                    <button id="imgEditorCancel" class="text-white text-base font-medium px-2 py-1 -ml-2 active:opacity-60">${t.cancel}</button>
                    <div class="flex bg-white/10 rounded-full p-1">
                        <button data-mode="crop" class="img-mode-btn px-4 py-1.5 rounded-full text-xs font-bold text-white bg-white/20">${t.crop}</button>
                        <button data-mode="filter" class="img-mode-btn px-4 py-1.5 rounded-full text-xs font-bold text-white/60">${t.filter}</button>
                        <button data-mode="draw" class="img-mode-btn px-4 py-1.5 rounded-full text-xs font-bold text-white/60">${t.draw}</button>
                    </div>
                    <button id="imgEditorDone" class="text-blue-400 text-base font-bold px-2 py-1 -mr-2 active:opacity-60">${t.done}</button>
                </header>

                <!-- Image Area -->
                <main class="flex-1 relative bg-black flex items-center justify-center overflow-hidden" id="imgEditorMain">
                    <img id="imgEditorImg" class="max-w-full max-h-full object-contain">
                    <canvas id="imgEditorDrawCanvas" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
                </main>

                <!-- Bottom Tools -->
                <footer class="flex-none bg-black/90 backdrop-blur-lg border-t border-white/10 safe-area-bottom">
                    <!-- Crop Tools -->
                    <div id="imgCropTools" class="py-4 px-2">
                        <div class="flex justify-center gap-2">
                            <button data-ratio="free" class="crop-btn active flex flex-col items-center gap-1 px-4 py-2 rounded-xl">
                                <div class="w-8 h-6 border-2 border-white rounded"></div>
                                <span class="text-[10px] text-white/80">Free</span>
                            </button>
                            <button data-ratio="1" class="crop-btn flex flex-col items-center gap-1 px-4 py-2 rounded-xl">
                                <div class="w-6 h-6 border-2 border-white/40 rounded"></div>
                                <span class="text-[10px] text-white/40">1:1</span>
                            </button>
                            <button data-ratio="4/5" class="crop-btn flex flex-col items-center gap-1 px-4 py-2 rounded-xl">
                                <div class="w-5 h-6 border-2 border-white/40 rounded"></div>
                                <span class="text-[10px] text-white/40">4:5</span>
                            </button>
                            <button data-ratio="16/9" class="crop-btn flex flex-col items-center gap-1 px-4 py-2 rounded-xl">
                                <div class="w-8 h-5 border-2 border-white/40 rounded"></div>
                                <span class="text-[10px] text-white/40">16:9</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filter Tools -->
                    <div id="imgFilterTools" class="py-3 hidden">
                        <div class="flex gap-3 px-4 overflow-x-auto scrollbar-hide pb-1" id="filterScroll">
                            ${this.filters.map((f, i) => `
                                <button data-filter="${f.value}" class="filter-btn flex-shrink-0 flex flex-col items-center gap-1.5 ${i === 0 ? 'active' : ''}">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden ring-2 ${i === 0 ? 'ring-white' : 'ring-transparent'} transition-all">
                                        <div class="w-full h-full bg-gradient-to-br from-orange-400 via-pink-500 to-purple-600" style="filter: ${f.value}"></div>
                                    </div>
                                    <span class="text-[10px] ${i === 0 ? 'text-white font-bold' : 'text-white/50'}">${f.name}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <!-- Draw Tools -->
                    <div id="imgDrawTools" class="py-4 px-4 hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex gap-2">
                                ${['#ffffff', '#000000', '#ff3b5c', '#ffcc00', '#34c759', '#007aff', '#af52de'].map((c, i) => `
                                    <button data-color="${c}" class="color-btn w-8 h-8 rounded-full ${i === 0 ? 'ring-2 ring-white ring-offset-2 ring-offset-black' : ''}" style="background:${c}"></button>
                                `).join('')}
                            </div>
                            <button id="clearDrawBtn" class="text-red-400 text-sm font-bold px-3 py-1">${t.clear}</button>
                        </div>
                        <div class="mt-3 flex items-center gap-3">
                            <span class="text-white/50 text-xs">Thin</span>
                            <input type="range" id="drawSizeRange" min="2" max="20" value="4" class="flex-1 accent-white h-1">
                            <span class="text-white/50 text-xs">Thick</span>
                        </div>
                    </div>
                </footer>
            </div>
            <style>
                #imgEditorModal .safe-area-top { padding-top: max(12px, env(safe-area-inset-top)); }
                #imgEditorModal .safe-area-bottom { padding-bottom: max(12px, env(safe-area-inset-bottom)); }
                #imgEditorModal .scrollbar-hide::-webkit-scrollbar { display: none; }
                #imgEditorModal .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
                #imgEditorModal .crop-btn.active div { border-color: white; }
                #imgEditorModal .crop-btn.active span { color: white; }
                #imgEditorModal .filter-btn.active .ring-transparent { --tw-ring-color: white; }
                #imgEditorModal .filter-btn.active span { color: white; font-weight: 700; }
                .cropper-container { touch-action: none; }
            </style>
        `;

        document.body.appendChild(modal);
        this.modal = modal;
    }

    bindEvents() {
        // Cancel
        const cancelBtn = this.modal.querySelector('#imgEditorCancel') || document.getElementById('imgEditorCancel');
        if (cancelBtn) cancelBtn.onclick = () => this.close();

        // Done
        const doneBtn = this.modal.querySelector('#imgEditorDone') || document.getElementById('imgEditorDone');
        if (doneBtn) doneBtn.onclick = () => this.save();

        // Mode switching
        this.modal.querySelectorAll('.img-mode-btn').forEach(btn => {
            btn.onclick = () => this.switchMode(btn.dataset.mode);
        });

        // Crop ratios
        this.modal.querySelectorAll('.crop-btn').forEach(btn => {
            btn.onclick = () => {
                this.modal.querySelectorAll('.crop-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.setCropRatio(btn.dataset.ratio);
            };
        });

        // Filters
        this.modal.querySelectorAll('.filter-btn').forEach(btn => {
            btn.onclick = () => {
                this.modal.querySelectorAll('.filter-btn').forEach(b => {
                    b.classList.remove('active');
                    b.querySelector('div').classList.remove('ring-white');
                    b.querySelector('div').classList.add('ring-transparent');
                });
                btn.classList.add('active');
                btn.querySelector('div').classList.remove('ring-transparent');
                btn.querySelector('div').classList.add('ring-white');
                this.applyFilter(btn.dataset.filter);
            };
        });

        // Colors
        this.modal.querySelectorAll('.color-btn').forEach(btn => {
            btn.onclick = () => {
                this.modal.querySelectorAll('.color-btn').forEach(b => b.classList.remove('ring-2', 'ring-white', 'ring-offset-2', 'ring-offset-black'));
                btn.classList.add('ring-2', 'ring-white', 'ring-offset-2', 'ring-offset-black');
                this.drawColor = btn.dataset.color;
            };
        });

        // Draw size
        const drawRange = this.modal.querySelector('#drawSizeRange') || document.getElementById('drawSizeRange');
        if (drawRange) {
            drawRange.oninput = (e) => {
                this.drawSize = parseInt(e.target.value);
            };
        }

        // Clear draw
        const clearBtn = this.modal.querySelector('#clearDrawBtn') || document.getElementById('clearDrawBtn');
        if (clearBtn) {
            clearBtn.onclick = () => {
                if (this.drawCtx) {
                    this.drawCtx.clearRect(0, 0, this.drawCanvas.width, this.drawCanvas.height);
                }
            };
        }
    }

    open(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            this.originalImage = new Image();
            this.originalImage.onload = () => {
                const img = this.modal.querySelector('#imgEditorImg') || document.getElementById('imgEditorImg');
                img.src = e.target.result;

                this.modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';

                // Reset state
                this.currentMode = 'crop';
                this.currentFilter = 'none';
                this.switchMode('crop');

                // Init cropper after image loads
                setTimeout(() => this.initCropper(), 150);
            };
            this.originalImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    initCropper() {
        const img = this.modal.querySelector('#imgEditorImg') || document.getElementById('imgEditorImg');

        if (this.cropper) {
            this.cropper.destroy();
        }

        if (typeof Cropper !== 'undefined') {
            this.cropper = new Cropper(img, {
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.9,
                restore: false,
                guides: false,
                center: false,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                background: false,
                responsive: true,
            });
        }
    }

    switchMode(mode) {
        this.currentMode = mode;

        // Update mode buttons
        this.modal.querySelectorAll('.img-mode-btn').forEach(btn => {
            if (btn.dataset.mode === mode) {
                btn.classList.add('bg-white/20');
                btn.classList.remove('text-white/60');
                btn.classList.add('text-white');
            } else {
                btn.classList.remove('bg-white/20');
                btn.classList.add('text-white/60');
                btn.classList.remove('text-white');
            }
        });

        // Show/hide tool panels
        const cropTools = this.modal.querySelector('#imgCropTools') || document.getElementById('imgCropTools');
        const filterTools = this.modal.querySelector('#imgFilterTools') || document.getElementById('imgFilterTools');
        const drawTools = this.modal.querySelector('#imgDrawTools') || document.getElementById('imgDrawTools');

        if (cropTools) cropTools.classList.toggle('hidden', mode !== 'crop');
        if (filterTools) filterTools.classList.toggle('hidden', mode !== 'filter');
        if (drawTools) drawTools.classList.toggle('hidden', mode !== 'draw');

        // Handle drawing mode
        const drawCanvas = this.modal.querySelector('#imgEditorDrawCanvas') || document.getElementById('imgEditorDrawCanvas');
        if (mode === 'draw') {
            this.enableDrawing();
        } else {
            drawCanvas.style.pointerEvents = 'none';
        }

        // Handle cropper
        if (this.cropper) {
            if (mode === 'crop') {
                this.cropper.enable();
            } else {
                this.cropper.disable();
            }
        }
    }

    setCropRatio(ratio) {
        if (!this.cropper) return;

        if (ratio === 'free') {
            this.cropper.setAspectRatio(NaN);
        } else if (ratio.includes('/')) {
            const [w, h] = ratio.split('/').map(Number);
            this.cropper.setAspectRatio(w / h);
        } else {
            this.cropper.setAspectRatio(parseFloat(ratio));
        }
    }

    applyFilter(filter) {
        this.currentFilter = filter;
        const img = document.getElementById('imgEditorImg');
        img.style.filter = filter;

        // Apply to cropper container too
        const container = this.modal.querySelector('.cropper-container');
        if (container) {
            container.style.filter = filter;
        }
    }

    enableDrawing() {
        const mainArea = document.getElementById('imgEditorMain');
        const canvas = document.getElementById('imgEditorDrawCanvas');

        canvas.style.pointerEvents = 'auto';
        canvas.width = mainArea.offsetWidth;
        canvas.height = mainArea.offsetHeight;

        this.drawCanvas = canvas;
        this.drawCtx = canvas.getContext('2d');

        let drawing = false;
        let lastX, lastY;

        const getPos = (e) => {
            const rect = canvas.getBoundingClientRect();
            const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
            const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
            return { x, y };
        };

        const startDraw = (e) => {
            e.preventDefault();
            drawing = true;
            const pos = getPos(e);
            lastX = pos.x;
            lastY = pos.y;
        };

        const draw = (e) => {
            if (!drawing) return;
            e.preventDefault();
            const pos = getPos(e);

            this.drawCtx.beginPath();
            this.drawCtx.moveTo(lastX, lastY);
            this.drawCtx.lineTo(pos.x, pos.y);
            this.drawCtx.strokeStyle = this.drawColor;
            this.drawCtx.lineWidth = this.drawSize;
            this.drawCtx.lineCap = 'round';
            this.drawCtx.lineJoin = 'round';
            this.drawCtx.stroke();

            lastX = pos.x;
            lastY = pos.y;
        };

        const endDraw = () => {
            drawing = false;
        };

        // Remove old listeners
        canvas.onmousedown = startDraw;
        canvas.onmousemove = draw;
        canvas.onmouseup = endDraw;
        canvas.onmouseleave = endDraw;
        canvas.ontouchstart = startDraw;
        canvas.ontouchmove = draw;
        canvas.ontouchend = endDraw;
    }

    save() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        let sourceCanvas;

        if (this.cropper) {
            sourceCanvas = this.cropper.getCroppedCanvas({
                maxWidth: 2048,
                maxHeight: 2048,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
        } else if (this.originalImage) {
            sourceCanvas = document.createElement('canvas');
            sourceCanvas.width = this.originalImage.width;
            sourceCanvas.height = this.originalImage.height;
            sourceCanvas.getContext('2d').drawImage(this.originalImage, 0, 0);
        } else {
            console.error('Image Editor: No source image found');
            return;
        }

        canvas.width = sourceCanvas.width;
        canvas.height = sourceCanvas.height;

        // Apply filter
        if (this.currentFilter !== 'none') {
            ctx.filter = this.currentFilter;
        }
        ctx.drawImage(sourceCanvas, 0, 0);
        ctx.filter = 'none';

        // Overlay drawing
        if (this.drawCanvas && this.drawCtx) {
            ctx.drawImage(this.drawCanvas, 0, 0, canvas.width, canvas.height);
        }

        canvas.toBlob((blob) => {
            if (this.onSave) {
                this.onSave(blob);
            }
            this.close();
        }, 'image/jpeg', 0.92);
    }

    close() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }

        this.modal.classList.add('hidden');
        document.body.style.overflow = '';

        // Reset
        const img = this.modal.querySelector('#imgEditorImg') || document.getElementById('imgEditorImg');
        if (img) {
            img.src = '';
            img.style.filter = '';
        }
        if (this.drawCtx) {
            this.drawCtx.clearRect(0, 0, this.drawCanvas.width, this.drawCanvas.height);
        }
        this.currentFilter = 'none';
    }
}

window.ImageEditor = ImageEditor;
