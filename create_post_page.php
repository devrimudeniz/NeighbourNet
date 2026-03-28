<?php
require_once 'includes/db.php';
require_once 'includes/ui_components.php';
session_start();
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Define constant to prevent header from rendering the hidden modal
define('HIDE_COMPOSER_MODAL', true);
$needs_editor_early = true; // Load Editor.js immediately on this page
?>
<!DOCTYPE html>
<html lang="tr" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'New Post' : 'Yeni Paylaşım'; ?> - Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-white dark:bg-slate-900 min-h-screen text-slate-900 dark:text-white pb-20">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-2xl mx-auto pt-4 px-0 sm:px-4">
        
        <div class="bg-white dark:bg-slate-900 sm:rounded-3xl sm:shadow-xl sm:border border-slate-200 dark:border-slate-800 overflow-hidden min-h-[calc(100vh-80px)] sm:min-h-0">
            <form action="api/create_post.php" method="POST" enctype="multipart/form-data" id="composerForm" class="h-full flex flex-col">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- Expanded Header -->
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm sticky top-16 z-20">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                        <?php echo $lang == 'en' ? 'Create Post' : 'Yeni Paylaşım'; ?>
                    </h1>
                     <a href="feed" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <?php echo heroicon('times', 'w-5 h-5'); ?>
                    </a>
                </div>

                <div class="p-4 flex-1 flex flex-col">
                    <div class="flex gap-3 mb-4">
                        <?php 
                        $avatar = $_SESSION['avatar'] ?? '';
                        if (empty($avatar) || strpos($avatar, 'default-avatar.png') !== false) {
                            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User');
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($avatar); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-slate-100 dark:border-slate-800 flex-shrink-0">
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-900 dark:text-white"><?php echo $_SESSION['full_name'] ?? $_SESSION['username']; ?></h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <i class="fas fa-globe-americas"></i> <?php echo $lang == 'en' ? 'Public' : 'Herkese Açık'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Feeling/Activity Display -->
                    <div id="feelingDisplay" class="hidden mb-3 flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in duration-200">
                        <span id="feelingText" class="text-sm font-semibold text-slate-700 dark:text-slate-200"></span>
                        <button type="button" onclick="removeFeel()" class="ml-auto text-slate-400 hover:text-[#0055FF] transition-colors">
                            <?php echo heroicon('times', 'w-4 h-4'); ?>
                        </button>
                    </div>
                
                    <!-- Editor.js Container (No Toolbox) -->
                    <div id="editorjs-page" class="w-full flex-1 bg-transparent border-none text-lg text-slate-900 dark:text-white leading-relaxed min-h-[200px]"></div>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" id="wordCountPage">0 <?php echo $lang == 'en' ? 'word' : 'sözcük'; ?></p>
                    <input type="hidden" name="content" id="composerContentPage">

                    <!-- Unified Media Preview Grid -->
                    <div id="mediaPreviewGrid" class="hidden mt-4 grid grid-cols-2 sm:grid-cols-3 gap-2 animate-in fade-in duration-300">
                        <!-- Preview items will be injected here via JS -->
                    </div>

                    <div id="videoUrlSection" class="hidden mt-4 animate-in slide-in-from-top-2 duration-200">
                        <div class="flex gap-2">
                             <div class="relative flex-1">
                                <i class="fab fa-youtube absolute left-4 top-1/2 -translate-y-1/2 text-red-500"></i>
                                <input type="url" name="video_url" id="videoUrlInput" placeholder="<?php echo $lang == 'en' ? 'YouTube link here...' : 'YouTube linkini buraya yapıştır...'; ?>" class="w-full pl-12 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 focus:border-red-500 outline-none font-medium transition-colors">
                            </div>
                            <button type="button" onclick="removeModalVideoUrl()" class="px-4 bg-red-50 dark:bg-red-900/20 text-red-500 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                <?php echo heroicon('times', 'w-5 h-5'); ?>
                            </button>
                        </div>
                    </div>

                    <div id="locationSection" class="hidden mt-4 animate-in slide-in-from-top-2 duration-200">
                         <div class="relative">
                            <i class="fas fa-map-marker-alt absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500"></i>
                            <input type="text" name="location" id="locationInput" placeholder="<?php echo $lang == 'en' ? 'Where are you?' : 'Neredesin?'; ?>" class="w-full pl-12 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 focus:border-emerald-500 outline-none font-medium transition-colors">
                        </div>
                    </div>

                    <div id="link-preview-container-modal" class="hidden mt-4 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden bg-slate-50 dark:bg-slate-900 relative group animate-in fade-in duration-300">
                        <button type="button" onclick="clearLinkPreviewModal()" class="absolute top-2 right-2 bg-white/80 p-1.5 rounded-full shadow-sm hover:bg-red-50 text-red-500 z-10 transition-colors">
                            <?php echo heroicon('times', 'w-4 h-4'); ?>
                        </button>
                        <div class="h-48 w-full bg-slate-200 dark:bg-slate-800 bg-cover bg-center" id="preview-image-modal"></div>
                        <div class="p-4">
                            <h4 class="font-bold text-base text-slate-800 dark:text-slate-100 truncate" id="preview-title-modal"><?php echo $t['loading']; ?></h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mt-1" id="preview-desc-modal"></p>
                            <p class="text-xs text-slate-400 uppercase mt-2 font-bold tracking-wider" id="preview-domain-modal"></p>
                        </div>
                    </div>

                    <!-- Hidden Inputs -->
                    <input type="hidden" name="feeling_action" id="feelingAction">
                    <input type="hidden" name="feeling_value" id="feelingValue">
                    <input type="hidden" name="link_title" id="meta-link-title-modal">
                    <input type="hidden" name="link_description" id="meta-link-desc-modal">
                    <input type="hidden" name="link_image" id="meta-link-image-modal">
                    <input type="file" name="images[]" id="imageInput" accept="image/*" class="hidden" multiple onchange="handleFileSelect(event, 'image')">
                    <input type="file" name="videos[]" id="videoFileInput" accept="video/*" class="hidden" multiple onchange="handleFileSelect(event, 'video')">
                    <!-- Hidden input to store edited image URL/Data if needed, but the form uses formData.set in JS -->
                    <input type="hidden" id="linkInput"> 
                </div>

                <!-- Footer / Tools -->
                <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 sticky bottom-0 pb-safe">
                     
                     <div class="flex gap-2 mb-4 overflow-x-auto pb-2 no-scrollbar">
                        <button type="button" onclick="document.getElementById('imageInput').click()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-green-50 dark:hover:bg-green-900/20 text-slate-600 dark:text-slate-300 hover:text-green-600 transition-all font-bold text-sm whitespace-nowrap">
                            <?php echo heroicon('image', 'w-5 h-5'); ?>
                            <?php echo $t['photo']; ?>
                        </button>
                        
                        <button type="button" onclick="document.getElementById('videoFileInput').click()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-red-900/20 text-slate-600 dark:text-slate-300 hover:text-red-500 transition-all font-bold text-sm whitespace-nowrap">
                            <?php echo heroicon('video', 'w-5 h-5'); ?>
                            Video
                        </button>
                        
                         <button type="button" onclick="toggleLink()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-slate-600 dark:text-slate-300 hover:text-blue-500 transition-all font-bold text-sm whitespace-nowrap">
                            <?php echo heroicon('link', 'w-5 h-5'); ?>
                            Link
                        </button>
                        
                        <button type="button" onclick="toggleVideoUrl()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-purple-900/20 text-slate-600 dark:text-slate-300 hover:text-purple-500 transition-all font-bold text-sm whitespace-nowrap">
                            <?php echo heroicon('play_circle', 'w-5 h-5'); ?>
                            YouTube
                        </button>

                        <button type="button" onclick="document.getElementById('locationSection').classList.toggle('hidden')" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-slate-600 dark:text-slate-300 hover:text-emerald-500 transition-all font-bold text-sm whitespace-nowrap">
                            <?php echo heroicon('location', 'w-5 h-5'); ?>
                            <?php echo $lang == 'en' ? 'Location' : 'Konum'; ?>
                        </button>

                        <button type="button" onclick="openFeelingPicker()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 text-slate-600 dark:text-slate-300 hover:text-yellow-500 transition-all font-bold text-sm whitespace-nowrap">
                            <?php echo heroicon('smile', 'w-5 h-5'); ?>
                            <?php echo $lang == 'en' ? 'Feeling' : 'Duygu'; ?>
                        </button>
                    </div>

                    <button type="submit" class="w-full py-4 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-lg shadow-lg hover:shadow-xl hover:translate-y-[-1px] active:translate-y-[1px] transition-all flex items-center justify-center gap-2">
                        <?php echo heroicon('paper_plane', 'w-5 h-5'); ?>
                        <?php echo $lang == 'en' ? 'Share Post' : 'Paylaş'; ?>
                    </button>
                </div>
            </form>
        </div>
    </main>


    
    <!-- Link Input for toggleLink to work (it usually is inside header but we might need it here if JS expects it) -->
    <!-- Actually, fetchUrlPreview uses document.getElementById('linkInput') which is missing in my form above. I added it as hidden input but toggleLink expects a prompt or input. header.php doesn't have toggleLink function visible in my previous snippet? Ah, I must check header.php script for toggleLink implementation. -->
    
    <script>
    // Global Editor instance
    let editorPage = null;
    
    // Override the default form submission handler from header.php
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Editor.js WITHOUT toolbox (no + button)
        const MIN_WORDS = 1;
            function countWords(text) {
                if (!text || !text.trim()) return 0;
                return text.trim().split(/\s+/).filter(Boolean).length;
            }
            function updateWordCountPage() {
                const el = document.getElementById('wordCountPage');
                if (!el) return;
                if (!editorPage) { el.textContent = '0 <?php echo $lang == "en" ? "word" : "sözcük"; ?>'; return; }
                editorPage.save().then(function(outputData) {
                    let text = '';
                    if (outputData.blocks) {
                        outputData.blocks.forEach(function(block) {
                            if (block.data && block.data.text) text += block.data.text + ' ';
                        });
                    }
                    const n = countWords(text);
                    el.textContent = n + ' <?php echo $lang == "en" ? "words" : "sözcük"; ?>';
                    el.classList.remove('text-red-500', 'text-emerald-600');
                    if (n >= MIN_WORDS) el.classList.add('text-emerald-600');
                }).catch(function() {});
            }
            if(typeof EditorJS !== 'undefined') {
                editorPage = new EditorJS({
                    holder: 'editorjs-page',
                    placeholder: '<?php echo $lang == "en" ? "What\'s on your mind?" : "Ne düşünüyorsun?"; ?>',
                    tools: {}, // No tools = no + button
                    minHeight: 150,
                    autofocus: true,
                    data: {},
                    onChange: function() { updateWordCountPage(); }
                });
            }
        
        const oldForm = document.getElementById('composerForm');
        if (oldForm) {
            // Clone the form to remove existing event listeners
            const newForm = oldForm.cloneNode(true);
            oldForm.parentNode.replaceChild(newForm, oldForm);

            // Bind new submit handler
            newForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = newForm.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<?php echo heroicon("spinner", "animate-spin inline-block mr-2 w-4 h-4"); ?><?php echo $lang == "en" ? "Posting..." : "Paylaşılıyor..."; ?>';
                
                try {
                    // Extract content from Editor.js
                    let textContent = '';
                    if (editorPage) {
                        const outputData = await editorPage.save();
                        if(outputData.blocks) {
                            outputData.blocks.forEach(block => {
                                if (block.type === 'paragraph' && block.data.text) {
                                    textContent += block.data.text + '\n';
                                }
                            });
                        }
                    }
                    textContent = textContent.trim();
                    const wordCount = countWords(textContent);
                    if (wordCount < MIN_WORDS) {
                        alert('<?php echo $lang == "en" ? "Please write at least one word." : "En az bir sözcük yazın."; ?>');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnContent;
                        return;
                    }
                    
                    // Update the hidden input
                    document.getElementById('composerContentPage').value = textContent;
                    
                    const formData = new FormData(newForm);
                    
                    // Append all selected media files
                    // Clear existing file inputs from formData to avoid duplication/confusion
                    formData.delete('images[]');
                    formData.delete('videos[]');
                    
                    mediaFiles.forEach(media => {
                        if (media.type === 'image') {
                            formData.append('images[]', media.file);
                        } else if (media.type === 'video') {
                            formData.append('videos[]', media.file);
                        }
                    });
                    
                    const response = await fetch('api/create_post.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = 'feed';
                    } else {
                        alert(data.message || '<?php echo $lang == "en" ? "Error creating post" : "Paylaşım oluşturulamadı"; ?>');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnContent;
                    }
                } catch (error) {
                    console.error(error);
                    alert('<?php echo $lang == "en" ? "Network error" : "Bağlantı hatası"; ?>');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
            });
        }
    });

    // --- Media Handling Logic ---
    let mediaFiles = []; // Stores {file: File, type: 'image'|'video', url: string}

    function handleFileSelect(event, type) {
        const files = Array.from(event.target.files);
        if (!files.length) return;

        // Limit total files (e.g. 10)
        if (mediaFiles.length + files.length > 10) {
            alert('En fazla 10 medya dosyası yükleyebilirsiniz.');
            return;
        }

        files.forEach(file => {
            const url = URL.createObjectURL(file);
            mediaFiles.push({ file, type, url });
        });

        renderMediaPreview();
        
        // Reset input so same files can be selected again if needed (though we handle duplication via array)
        event.target.value = '';
    }

    function renderMediaPreview() {
        const grid = document.getElementById('mediaPreviewGrid');
        if (!grid) return;

        grid.innerHTML = '';
        
        if (mediaFiles.length === 0) {
            grid.classList.add('hidden');
            return;
        }

        grid.classList.remove('hidden');

        mediaFiles.forEach((media, index) => {
            const item = document.createElement('div');
            item.className = 'relative aspect-square rounded-xl overflow-hidden group border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800';
            
            let contentHtml = '';
            if (media.type === 'image') {
                contentHtml = `<img src="${media.url}" class="w-full h-full object-cover">`;
            } else {
                contentHtml = `<video src="${media.url}" class="w-full h-full object-cover"></video>
                               <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                                   <i class="fas fa-play text-white text-2xl drop-shadow-md"></i>
                               </div>`;
            }

            item.innerHTML = `
                ${contentHtml}
                <button type="button" onclick="removeMedia(${index})" class="absolute top-1 right-1 bg-black/60 hover:bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center backdrop-blur transition-all shadow-sm z-10">
                    <i class="fas fa-times text-xs"></i>
                </button>
            `;
            
            grid.appendChild(item);
        });
    }

    function removeMedia(index) {
        // Revoke URL to prevent memory leaks
        URL.revokeObjectURL(mediaFiles[index].url);
        
        mediaFiles.splice(index, 1);
        renderMediaPreview();
    }

    // Keep these for backward compatibility or reset logic if needed
    function removeModalImage() { mediaFiles = []; renderMediaPreview(); }
    function removeModalVideoFile() { mediaFiles = []; renderMediaPreview(); }

    function removeModalVideoUrl() {
        const section = document.getElementById('videoUrlSection');
        const input = document.getElementById('videoUrlInput');
        if(section) section.classList.add('hidden');
        if(input) input.value = '';
    }
    
    // Link Previews (unchanged)
    async function fetchPreviewModal(url) {
        // ... (existing code)
        // Show loading state if needed
        const container = document.getElementById('link-preview-container-modal');
        if(container) container.classList.remove('hidden');
        
        try {
            const formData = new FormData();
            formData.append('url', url);
            
            const response = await fetch('api/get_url_preview.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('meta-link-title-modal').value = data.title;
                document.getElementById('meta-link-desc-modal').value = data.description;
                document.getElementById('meta-link-image-modal').value = data.image; 
                
                // Update UI
                document.getElementById('preview-title-modal').textContent = data.title;
                document.getElementById('preview-desc-modal').textContent = data.description;
                document.getElementById('preview-domain-modal').textContent = new URL(url).hostname;
                
                const imgContainer = document.getElementById('preview-image-modal');
                if(data.image) {
                     imgContainer.style.backgroundImage = `url('${data.image}')`;
                } else {
                     imgContainer.style.backgroundColor = '#cbd5e1'; 
                }
                
                 let hiddenLinkInput = document.getElementById('hiddenLinkUrl');
                 if(!hiddenLinkInput) {
                     hiddenLinkInput = document.createElement('input');
                     hiddenLinkInput.type = 'hidden';
                     hiddenLinkInput.id = 'hiddenLinkUrl';
                     hiddenLinkInput.name = 'link_url';
                     document.getElementById('composerForm').appendChild(hiddenLinkInput);
                 }
                 hiddenLinkInput.value = url;
                 
            } else {
                console.error('Preview failed');
                clearLinkPreviewModal();
            }
        } catch (error) {
            console.error(error);
            clearLinkPreviewModal();
        }
    }

    function clearLinkPreviewModal() {
        document.getElementById('link-preview-container-modal').classList.add('hidden');
        if(document.getElementById('hiddenLinkUrl')) document.getElementById('hiddenLinkUrl').value = ''; // Check if element exists
        document.getElementById('meta-link-title-modal').value = '';
        document.getElementById('meta-link-desc-modal').value = '';
        document.getElementById('meta-link-image-modal').value = '';
    }

    // Polyfill or override link toggle if specific UI is needed
    function toggleLink() {
        const url = prompt("<?php echo $lang == 'en' ? 'Paste URL here:' : 'Link yapıştır:'; ?>");
        if(url && url.trim() !== '') {
            // Trigger fetch preview
            fetchPreviewModal(url);
        }
    }

    // Video URL toggle
    function toggleVideoUrl() {
        document.getElementById('videoUrlSection').classList.toggle('hidden');
        if(!document.getElementById('videoUrlSection').classList.contains('hidden')) {
             document.getElementById('videoUrlInput').focus();
        }
    }
    </script>
</body>
</html>
