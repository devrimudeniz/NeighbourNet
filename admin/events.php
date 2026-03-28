<?php
require_once 'auth_session.php';
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once '../includes/icon_helper.php';

// Fetch Events
$status_filter = $_GET['status'] ?? 'all';
$sql = "SELECT e.*, u.full_name, u.username, u.avatar FROM events e JOIN users u ON e.user_id = u.id";
if ($status_filter != 'all') {
    $sql .= " WHERE e.status = :status";
}
$sql .= " ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($status_filter != 'all') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | Kalkan Social Admin</title>
    <?php include '../includes/header_css.php'; ?>
</head>
<body class="bg-gray-50 text-slate-900 dark:bg-slate-900 dark:text-white transition-colors duration-300">

    <?php include "includes/sidebar.php"; ?>

    <main class="lg:ml-72 min-h-screen p-4 sm:p-6 pt-20 lg:pt-6">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-slate-800 dark:text-white">Events Management</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Approve, edit, or remove events.</p>
            </div>
            <div class="flex gap-2">
                <a href="events.php" class="px-4 py-2 rounded-xl text-sm font-bold <?php echo $status_filter == 'all' ? 'bg-slate-800 text-white dark:bg-white dark:text-slate-900' : 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">All</a>
                <a href="events.php?status=pending" class="px-4 py-2 rounded-xl text-sm font-bold <?php echo $status_filter == 'pending' ? 'bg-orange-500 text-white' : 'bg-white dark:bg-slate-800 text-orange-500 hover:bg-orange-50'; ?>">Pending</a>
                <a href="events.php?status=approved" class="px-4 py-2 rounded-xl text-sm font-bold <?php echo $status_filter == 'approved' ? 'bg-green-500 text-white' : 'bg-white dark:bg-slate-800 text-green-500 hover:bg-green-50'; ?>">Approved</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach($events as $event): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700 relative group">
                
                <!-- Status Badge -->
                <div class="absolute top-4 right-4 z-10">
                    <?php if($event['status'] == 'pending'): ?>
                        <span class="px-3 py-1 bg-orange-100 text-orange-600 rounded-full text-xs font-black uppercase tracking-wider">Pending</span>
                    <?php elseif($event['status'] == 'approved'): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-600 rounded-full text-xs font-black uppercase tracking-wider">Active</span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-black uppercase tracking-wider">Rejected</span>
                    <?php endif; ?>
                </div>

                <div class="flex gap-4">
                    <img src="<?php echo !empty($event['image_url']) ? htmlspecialchars($event['image_url']) : '../assets/img/default-event.jpg'; ?>" class="w-24 h-24 rounded-xl object-cover bg-slate-100">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-lg truncate mb-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="text-sm text-slate-500 mb-2 line-clamp-2"><?php echo htmlspecialchars($event['description']); ?></p>
                        
                        <div class="flex items-center gap-2 text-xs text-slate-400 font-bold">
                            <span><i class="fas fa-calendar mr-1"></i> <?php echo date('d M', strtotime($event['event_date'])); ?></span>
                            <span>•</span>
                            <span><?php echo htmlspecialchars($event['category']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <img src="<?php echo getAdminAvatar($event['avatar']); ?>" class="w-6 h-6 rounded-full object-cover">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">@<?php echo htmlspecialchars($event['username']); ?></span>
                    </div>
                    
                    <div class="flex gap-2">
                        <?php if($event['status'] == 'pending'): ?>
                        <button onclick="updateEventStatus(<?php echo $event['id']; ?>, 'approved')" class="w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-500 hover:text-white transition-colors flex items-center justify-center">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="updateEventStatus(<?php echo $event['id']; ?>, 'rejected')" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                        
                        <a href="../edit_event?id=<?php echo $event['id']; ?>" target="_blank" class="w-8 h-8 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 transition-colors flex items-center justify-center">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteEvent(<?php echo $event['id']; ?>)" class="w-8 h-8 rounded-lg bg-slate-50 text-slate-600 hover:bg-red-500 hover:text-white dark:bg-slate-700 dark:text-slate-300 transition-colors flex items-center justify-center">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateEventStatus(id, status) {
            Swal.fire({
                title: status === 'approved' ? 'Approve Event?' : 'Reject Event?',
                text: "This action will update the event status.",
                icon: status === 'approved' ? 'success' : 'warning',
                showCancelButton: true,
                confirmButtonColor: status === 'approved' ? '#10b981' : '#ef4444',
                confirmButtonText: 'Yes, do it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api_admin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=update_event_status&id=${id}&status=${status}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire('Updated!', 'Event status has been updated.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'Something went wrong', 'error');
                        }
                    });
                }
            });
        }

        function deleteEvent(id) {
            Swal.fire({
                title: 'Delete Event?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api_admin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete_event&id=${id}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire('Deleted!', 'Event has been deleted.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'Something went wrong', 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
