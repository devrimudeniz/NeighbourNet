<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Cat | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex transition-colors">

    <?php include "includes/sidebar.php"; ?>

    <!-- Main Content -->
    <main class="ml-0 lg:ml-72 flex-1 p-6 lg:p-12 mt-16 lg:mt-0 max-w-4xl">
        <header class="mb-8">
            <a href="cats" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 mb-4 transition-colors font-bold text-sm">
                <i class="fas fa-arrow-left"></i> Back to Cats
            </a>
            <h1 class="text-3xl lg:text-4xl font-black mb-2">Add New Cat</h1>
            <p class="text-slate-500 dark:text-slate-400">Define a new target for the Catdex.</p>
        </header>

        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 lg:p-10 shadow-sm border border-slate-100 dark:border-slate-700">
            <form id="addCatForm" onsubmit="submitCat(event)">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Photo Upload -->
                    <div class="flex flex-col gap-4">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Master Photo</label>
                        <label class="flex-1 aspect-square rounded-[2rem] border-3 border-dashed border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex flex-col items-center justify-center cursor-pointer transition-all relative overflow-hidden group">
                            <input type="file" name="photo" accept="image/*" class="hidden" onchange="previewImage(this)" required>
                            <div id="uploadPlaceholder" class="text-center p-6">
                                <i class="fas fa-camera text-4xl text-slate-300 mb-2"></i>
                                <p class="text-sm font-bold text-slate-400">Click to upload photo</p>
                            </div>
                            <img id="imagePreview" src="" class="absolute inset-0 w-full h-full object-cover hidden">
                            <div class="absolute inset-0 bg-black/50 hidden group-hover:flex items-center justify-center text-white font-bold opacity-0 group-hover:opacity-100 transition-opacity">
                                Change Photo
                            </div>
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="space-y-6">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Cat Name</label>
                            <input type="text" name="name" required class="w-full h-14 px-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-none font-bold focus:ring-2 focus:ring-amber-500" placeholder="e.g. The Godfather">
                        </div>
                        
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Location</label>
                            <input type="text" name="location" required class="w-full h-14 px-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-none font-bold focus:ring-2 focus:ring-amber-500" placeholder="e.g. Old Town Square">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Rarity</label>
                            <select name="rarity" class="w-full h-14 px-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-none font-bold focus:ring-2 focus:ring-amber-500">
                                <option value="common">Common</option>
                                <option value="rare">Rare</option>
                                <option value="legendary">Legendary</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Description / Story</label>
                    <textarea name="description" rows="4" required class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-none font-medium focus:ring-2 focus:ring-amber-500" placeholder="Tell the story of this cat..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Likes</label>
                        <input type="text" name="likes" class="w-full h-14 px-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-none font-medium focus:ring-2 focus:ring-green-500" placeholder="e.g. Fish, Sleeping">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Dislikes</label>
                        <input type="text" name="dislikes" class="w-full h-14 px-4 rounded-xl bg-slate-50 dark:bg-slate-900 border-none font-medium focus:ring-2 focus:ring-red-500" placeholder="e.g. Water, Dogs">
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="w-full py-5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xl shadow-xl shadow-amber-500/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Save Cat Profile
                </button>
            </form>
        </div>

    </main>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                    document.getElementById('uploadPlaceholder').classList.add('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function submitCat(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            const formData = new FormData(e.target);
            formData.append('action', 'add_cat');

            fetch('api_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Cat added successfully! 😺');
                    window.location.href = 'cats';
                } else {
                    alert(data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error submitting form');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
