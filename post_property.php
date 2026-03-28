<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$u_stmt->execute([$_SESSION['user_id']]);
$user = $u_stmt->fetch();

$allowed_badges = ['founder', 'moderator', 'business', 'verified'];
$is_verified = in_array($user['badge'], $allowed_badges);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['post_property']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-6 pt-24">
        <?php if (!$is_verified): ?>
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-500/30 rounded-[2.5rem] p-12 text-center shadow-xl">
                <div class="w-20 h-20 bg-amber-100 dark:bg-amber-800 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-user-shield text-amber-600 dark:text-amber-400 text-3xl"></i>
                </div>
                <h2 class="text-2xl font-black mb-4">Verification Required</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-8 max-w-md mx-auto">
                    Kalkan Social topluluğunun güvenliği için villa veya gayrimenkul ilanı paylaşmadan önce hesabınızı doğrulatmanız gerekmektedir.
                </p>
                <a href="request_verification.php" class="inline-block bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-10 py-4 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                    <?php echo $t['request_verification']; ?>
                </a>
            </div>
        <?php else: ?>
            <!-- Header -->
            <div class="mb-10 text-center">
                <h1 class="text-4xl font-black mb-2"><?php echo $t['post_property']; ?></h1>
                <p class="text-slate-500 dark:text-slate-400">Reach thousands of locals and digital nomads in Kalkan.</p>
                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mt-2"><i class="fas fa-check-circle"></i> Your account is verified</p>
            </div>

        <form id="postPropertyForm" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="action" value="create">

            <!-- Basic Info Card -->
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-info-circle text-emerald-500"></i>
                    Basic Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2">Listing Title</label>
                        <input type="text" name="title" required placeholder="Luxury Villa with Sea View" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2">Listing Type</label>
                        <select name="type" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold cursor-pointer">
                            <option value="short_term"><?php echo $t['short_term']; ?></option>
                            <option value="long_term"><?php echo $t['long_term']; ?></option>
                            <option value="sale"><?php echo $t['sale']; ?></option>
                            <option value="room_share"><?php echo $t['room_share']; ?></option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2">Location</label>
                        <input type="text" name="location" required placeholder="Kalkan, Center" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2">Price</label>
                        <div class="flex gap-2">
                            <input type="number" name="price" required placeholder="1200" class="flex-1 bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                            <select name="currency" class="w-32 bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                                <option value="GBP">GBP (£)</option>
                                <option value="TRY">TRY (₺)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="USD">USD ($)</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2">Area (m²)</label>
                        <input type="number" name="area_sqm" placeholder="150" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2">Description</label>
                        <textarea name="description" required rows="5" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-medium" placeholder="Describe your property..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Detailed Specs Card -->
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-th-list text-emerald-500"></i>
                    Specs & Amenities
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2"><?php echo $t['bedrooms']; ?></label>
                        <input type="number" name="bedrooms" value="1" min="0" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2"><?php echo $t['bathrooms']; ?></label>
                        <input type="number" name="bathrooms" value="1" min="0" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2"><?php echo $t['wifi_speed']; ?> (Mbps)</label>
                        <input type="number" name="wifi_speed" placeholder="100" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Video Tour URL</label>
                        <input type="url" name="video_url" placeholder="YouTube/Vimeo Link" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="has_sea_view" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm"><?php echo $t['sea_view']; ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="has_pool" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm"><?php echo $t['private_pool']; ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="is_dog_friendly" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm"><?php echo $t['dog_friendly']; ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="furnished" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm">Furnished / Eşyalı</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="parking" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm">Parking / Otopark</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="air_conditioning" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm">Air Conditioning / Klima</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="heating" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm">Heating / Isıtma</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="balcony" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm">Balcony / Balkon</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="garden" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm">Garden / Bahçe</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <input type="checkbox" name="accessibility" class="w-5 h-5 accent-emerald-500">
                        <span class="font-bold text-sm">Accessible / Engelli Dostu</span>
                    </label>
                </div>
                
                <div class="mt-6">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Year Built / Yapım Yılı</label>
                    <input type="number" name="year_built" placeholder="e.g. 2020" class="w-full md:w-1/3 bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                </div>
            </div>

            <!-- Photos Card -->
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-camera text-emerald-500"></i>
                    Property Photos
                </h3>
                <label for="propertyImageInput" class="relative group border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[2rem] p-12 text-center hover:border-emerald-500 transition-all cursor-pointer block bg-slate-50/50 dark:bg-slate-900/50">
                    <input type="file" id="propertyImageInput" name="images[]" multiple accept="image/*" class="hidden">
                    <div class="w-16 h-16 bg-white dark:bg-slate-800 rounded-2xl flex items-center justify-center shadow-sm mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-cloud-upload-alt text-3xl text-emerald-500"></i>
                    </div>
                    <p class="font-black text-lg">Click to select photos</p>
                    <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">Hold Ctrl/Cmd to select multiple</p>
                </label>
                
                <div class="flex justify-between items-center mt-4">
                    <div id="fileCountBadge" class="hidden px-4 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-black uppercase tracking-wider">0 Photos Selected</div>
                    <button type="button" id="clearPhotosBtn" class="hidden text-xs font-bold text-red-500 hover:text-red-600 uppercase tracking-widest px-4 py-2"><i class="fas fa-trash-alt mr-1"></i> Clear Selection</button>
                </div>

                <div id="imagePreviewContainer" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6"></div>
            </div>

            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-5 rounded-[2rem] shadow-2xl shadow-emerald-500/30 transition-all transform active:scale-95 text-xl">
                <i class="fas fa-paper-plane mr-2"></i> <?php echo $t['post_property']; ?>
            </button>
        </form>
    </main>

    <?php endif; ?>

    <script>
        window.onerror = function(msg, url, line) {
            alert("JS Error: " + msg + "\nLine: " + line);
        };

        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM Content Loaded - post_property.php");
            const form = document.getElementById('postPropertyForm');
            const imageInput = document.getElementById('propertyImageInput');
            const previewContainer = document.getElementById('imagePreviewContainer');
            const clearBtn = document.getElementById('clearPhotosBtn');
            const countBadge = document.getElementById('fileCountBadge');
            let selectedFiles = [];

            function updatePreviews() {
                previewContainer.innerHTML = '';
                if (selectedFiles.length === 0) {
                    clearBtn.classList.add('hidden');
                    countBadge.classList.add('hidden');
                    return;
                }

                clearBtn.classList.remove('hidden');
                countBadge.classList.remove('hidden');
                countBadge.textContent = `${selectedFiles.length} Photos Selected`;

                selectedFiles.forEach((file, index) => {
                    const div = document.createElement('div');
                    div.className = 'relative aspect-square rounded-2xl overflow-hidden border border-slate-200 shadow-md bg-white dark:bg-slate-900 animate-fade-in group/item';
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-full object-cover">
                            <button type="button" class="remove-photo absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition-opacity shadow-lg" data-index="${index}">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-2">
                                <div class="text-[8px] text-white font-bold opacity-80">${index === 0 ? 'Main Photo' : 'Photo #' + (index + 1)}</div>
                            </div>
                        `;
                        
                        div.querySelector('.remove-photo').onclick = (e) => {
                            e.preventDefault();
                            selectedFiles.splice(index, 1);
                            updatePreviews();
                        };
                    }
                    reader.readAsDataURL(file);
                    previewContainer.appendChild(div);
                });
            }

            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    const newFiles = Array.from(this.files);
                    
                    if (selectedFiles.length + newFiles.length > 20) {
                        alert("En fazla 20 fotoğraf seçebilirsiniz.");
                        this.value = '';
                        return;
                    }

                    newFiles.forEach(file => {
                        if (file.type.startsWith('image/')) {
                            // Check for duplicates by name and size
                            const isDuplicate = selectedFiles.some(f => f.name === file.name && f.size === file.size);
                            if (!isDuplicate) selectedFiles.push(file);
                        }
                    });

                    this.value = ''; // Reset input so same file can be selected again if deleted
                    updatePreviews();
                });

                if (clearBtn) {
                    clearBtn.addEventListener('click', () => {
                        selectedFiles = [];
                        updatePreviews();
                    });
                }
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const btn = this.querySelector('button[type="submit"]');
                    const originalContent = btn.innerHTML;
                    
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';

                    const formData = new FormData(this);
                    
                    // Explicitly append images from our persistent array
                    formData.delete('images[]'); 
                    selectedFiles.forEach(file => {
                        formData.append('images[]', file);
                    });

                    fetch('api/properties.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json().catch(err => {
                        return res.text().then(text => { throw new Error('Sunucu Hatası: ' + text.substring(0, 100)); });
                    }))
                    .then(data => {
                        if (data.status === 'success') {
                            let msg = 'İlan başarıyla oluşturuldu!';
                            if (data.upload_warnings) {
                                const realErrors = data.upload_warnings.filter(w => !w.includes('Error Code 4'));
                                if (realErrors.length > 0) {
                                    msg += "\n\nUyarı: Bazı resimler yüklenemedi:\n" + realErrors.join("\n");
                                }
                            }
                            alert(msg);
                            window.location.href = 'properties.php';
                        } else {
                            alert('Hata: ' + data.message);
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                        }
                    })
                    .catch(err => {
                        alert('İstek başarısız: ' + err.message);
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    });
                });
            }
        });
    </script>
</body>
</html>

