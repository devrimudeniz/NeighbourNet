<?php
require_once 'includes/bootstrap.php';

// Fetch Category Filter
$filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Build Query
$sql = "SELECT ci.*, u.username, u.full_name, u.avatar 
        FROM community_items ci 
        JOIN users u ON ci.user_id = u.id 
        WHERE ci.status = 'active' AND ci.quantity > 0";

$params = [];
if ($filter !== 'all') {
    $sql .= " AND ci.category = ?";
    $params[] = $filter;
}

$sql .= " ORDER BY ci.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Icon Helper
function getCategoryIcon($cat) {
    $icons = [
        'food' => '🍔',
        'book' => '📚',
        'clothing' => '👕',
        'pet' => '🐾',
        'other' => '🎁'
    ];
    return $icons[$cat] ?? '🎁';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Support | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 pb-24 md:pb-12 max-w-4xl">
        
        <!-- Hero Section -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-extrabold mb-3 bg-clip-text text-transparent bg-gradient-to-r from-green-500 to-emerald-600">
                <?php echo $lang == 'en' ? 'Community Support' : 'Askıda İyilik'; ?>
            </h1>
            <p class="text-slate-600 dark:text-slate-400 max-w-xl mx-auto">
                <?php echo $lang == 'en' 
                    ? 'Share what you have, take what you need. Let\'s support each other.' 
                    : 'İhtiyacın varsa al, fazlan varsa paylaş. Kalkan dayanışması.'; ?>
            </p>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <a href="?category=all" class="px-5 py-2 rounded-full font-bold text-sm transition-all <?php echo $filter == 'all' ? 'bg-green-500 text-white shadow-lg shadow-green-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'; ?>">
                <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?>
            </a>
            <a href="?category=food" class="px-5 py-2 rounded-full font-bold text-sm transition-all <?php echo $filter == 'food' ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'; ?>">
                🍔 <?php echo $lang == 'en' ? 'Food' : 'Gıda'; ?>
            </a>
            <a href="?category=pet" class="px-5 py-2 rounded-full font-bold text-sm transition-all <?php echo $filter == 'pet' ? 'bg-pink-500 text-white shadow-lg shadow-pink-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'; ?>">
                🐾 <?php echo $lang == 'en' ? 'Pet Food' : 'Mama'; ?>
            </a>
            <a href="?category=book" class="px-5 py-2 rounded-full font-bold text-sm transition-all <?php echo $filter == 'book' ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'; ?>">
                📚 <?php echo $lang == 'en' ? 'Books' : 'Kitap'; ?>
            </a>
            <a href="?category=clothing" class="px-5 py-2 rounded-full font-bold text-sm transition-all <?php echo $filter == 'clothing' ? 'bg-purple-500 text-white shadow-lg shadow-purple-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'; ?>">
                👕 <?php echo $lang == 'en' ? 'Clothing' : 'Giyim'; ?>
            </a>
        </div>

        <!-- Items Grid -->
        <?php if (count($items) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach($items as $item): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-md transition-all relative overflow-hidden group">
                <!-- Icon Bg -->
                <div class="absolute -right-4 -bottom-4 text-9xl opacity-5 pointer-events-none group-hover:scale-110 transition-transform">
                    <?php echo getCategoryIcon($item['category']); ?>
                </div>

                <div class="flex items-start gap-4 relaitve z-10">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-3xl shrink-0">
                        <?php echo getCategoryIcon($item['category']); ?>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white leading-tight mb-1">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h3>
                            <span class="bg-green-100 text-green-700 text-xs font-black px-2 py-1 rounded-lg shrink-0">
                                x<?php echo $item['quantity']; ?>
                            </span>
                        </div>
                        
                        <p class="text-slate-500 dark:text-slate-400 text-sm mb-3 line-clamp-2">
                             <?php echo htmlspecialchars($item['description']); ?>
                        </p>

                        <div class="flex items-center gap-2 mb-3 text-xs text-slate-400 font-bold uppercase tracking-wide">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($item['location_name']); ?>
                        </div>

                        <div class="flex justify-between items-center mt-4">
                            <!-- User Info -->
                            <div class="flex items-center gap-2">
                                <img src="<?php echo $item['avatar'] ?? 'assets/img/default-avatar.png'; ?>" class="w-6 h-6 rounded-full object-cover">
                                <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($item['full_name']); ?></span>
                            </div>

                            <!-- Claim Button -->
                            <button onclick="claimItem(<?php echo $item['id']; ?>)" class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-4 py-2 rounded-lg text-xs font-black hover:scale-105 transition-transform">
                                <?php echo $lang == 'en' ? 'I Took One' : '1 Tane Aldım'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-20 bg-white dark:bg-slate-800 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700">
            <div class="text-6xl mb-4">🎁</div>
            <h3 class="font-bold text-xl text-slate-700 dark:text-slate-200 mb-2">
                <?php echo $lang == 'en' ? 'No items found yet.' : 'Henüz askıda ürün yok.'; ?>
            </h3>
            <p class="text-slate-500 dark:text-slate-400">
                <?php echo $lang == 'en' ? 'Be the first to share support!' : 'İlk iyiliği sen yap!'; ?>
            </p>
            <button onclick="openAddItemModal()" class="mt-6 px-6 py-3 bg-green-500 text-white rounded-xl font-bold hover:shadow-lg hover:shadow-green-500/30 transition-all">
                <?php echo $lang == 'en' ? 'Add Support' : 'İyilik Ekle'; ?>
            </button>
        </div>
        <?php endif; ?>

    </main>

    <!-- Floating Action Button (Mobile) -->
    <button onclick="openAddItemModal()" class="fixed bottom-24 right-6 md:bottom-10 md:right-10 w-14 h-14 bg-green-500 text-white rounded-full shadow-2xl shadow-green-500/40 flex items-center justify-center text-2xl hover:scale-110 active:scale-95 transition-all z-40">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAddItemModal()"></div>
        <div class="absolute bottom-0 md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 w-full md:w-[500px] bg-white dark:bg-slate-900 rounded-t-3xl md:rounded-3xl p-6 transition-all transform translate-y-full md:translate-y-0 duration-300" id="modalContent">
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-black text-slate-800 dark:text-white">
                    <?php echo $lang == 'en' ? 'Add Support' : 'İyilik Ekle'; ?>
                </h3>
                <button onclick="closeAddItemModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="addItemForm" onsubmit="submitItem(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Title' : 'Başlık'; ?></label>
                        <input type="text" name="title" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl p-3 font-bold" placeholder="Ex: 5 Ekmek / 5 Bread" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                            <select name="category" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl p-3 font-bold">
                                <option value="food">🍔 Food</option>
                                <option value="pet">🐾 Pet Food</option>
                                <option value="book">📚 Book</option>
                                <option value="clothing">👕 Clothing</option>
                                <option value="other">🎁 Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Quantity' : 'Adet'; ?></label>
                            <input type="number" name="quantity" value="1" min="1" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl p-3 font-bold" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Location / Venue' : 'Konum / Mekan'; ?></label>
                        <input type="text" name="location_name" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl p-3 font-bold" placeholder="Ex: Dostlar Fırını" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Description (Optional)' : 'Açıklama (İsteğe Bağlı)'; ?></label>
                        <textarea name="description" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl p-3 font-bold" rows="2"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-green-500 text-white py-4 rounded-xl font-black text-lg shadow-lg shadow-green-500/30 hover:scale-[1.02] transition-transform">
                        <?php echo $lang == 'en' ? 'Share Support' : 'Paylaş'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    function openAddItemModal() {
        <?php if(!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login';
        return;
        <?php endif; ?>
        
        document.getElementById('addItemModal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modalContent').classList.remove('translate-y-full');
        }, 10);
    }

    function closeAddItemModal() {
        document.getElementById('modalContent').classList.add('translate-y-full');
        setTimeout(() => {
            document.getElementById('addItemModal').classList.add('hidden');
        }, 300);
    }

    async function submitItem(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('api/add_community_item.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error');
            }
        } catch(err) {
            console.error(err);
            alert('An error occurred');
        }
    }

    async function claimItem(id) {
        if(!confirm('<?php echo $lang == 'en' ? 'Confirm you took one item?' : '1 adet aldığınızı onaylıyor musunuz?'; ?>')) return;
        
        try {
            const formData = new FormData();
            formData.append('id', id);
            
            const res = await fetch('api/claim_community_item.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error');
            }
        } catch(err) {
            console.error(err);
        }
    }
    </script>
</body>
</html>
