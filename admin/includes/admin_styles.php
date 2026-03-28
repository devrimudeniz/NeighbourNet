<?php
/**
 * Admin Panel - Global Styles v2
 * Clean, professional, no gradients
 */
?>
<style>
/* ═══ Base Reset ═══ */
body.admin-panel {
    overflow-x: hidden;
    min-height: 100vh;
    background: #f8fafc;
    color: #0f172a;
    font-family: 'Outfit', system-ui, -apple-system, sans-serif;
}

/* ═══ Layout ═══ */
body.admin-panel main.admin-main {
    margin-left: 260px;
    min-width: 0;
    width: calc(100% - 260px);
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
    padding: 32px;
    min-height: 100vh;
}

/* ═══ Cards ═══ */
.admin-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
}
.admin-card-sm { padding: 16px; }
.admin-card:hover { border-color: #cbd5e1; }

/* ═══ Stat Cards ═══ */
.stat-box {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    transition: border-color 0.2s;
}
.stat-box:hover { border-color: #94a3b8; }
.stat-box .stat-value {
    font-size: 28px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.1;
}
.stat-box .stat-label {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.stat-box .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

/* ═══ Tables ═══ */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.admin-table thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 16px;
    text-align: left;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    white-space: nowrap;
}
.admin-table tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.admin-table tbody tr:hover {
    background: #f8fafc;
}

/* ═══ Badges / Pills ═══ */
.badge-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 800;
    color: #fff;
}
.badge-red { background: #ef4444; }
.badge-orange { background: #f97316; }
.badge-blue { background: #3b82f6; }
.badge-green { background: #10b981; }
.badge-yellow { background: #eab308; }
.badge-slate { background: #64748b; }

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-approved, .status-active { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }

/* ═══ Buttons ═══ */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
    line-height: 1.4;
}
.btn:hover { opacity: 0.9; }
.btn-primary { background: #0f172a; color: #fff; }
.btn-primary:hover { background: #1e293b; }
.btn-danger { background: #ef4444; color: #fff; }
.btn-danger:hover { background: #dc2626; }
.btn-success { background: #10b981; color: #fff; }
.btn-success:hover { background: #059669; }
.btn-outline { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
.btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-sm { padding: 5px 10px; font-size: 12px; }
.btn-lg { padding: 12px 24px; font-size: 15px; }

/* ═══ Form Inputs ═══ */
.admin-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: #fff;
    color: #0f172a;
    outline: none;
    transition: border-color 0.15s;
    box-sizing: border-box;
}
.admin-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.admin-input::placeholder { color: #94a3b8; }

/* ═══ Section Headers ═══ */
.section-title {
    font-size: 18px;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 16px;
}
.page-title {
    font-size: 24px;
    font-weight: 900;
    color: #0f172a;
}
.page-subtitle {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

/* ═══ Empty State ═══ */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #94a3b8;
}
.empty-state i { font-size: 32px; margin-bottom: 12px; display: block; }
.empty-state p { font-weight: 600; }

/* ═══ Scrollbar ═══ */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* Sidebar scrollbar (dark) */
#adminSidebar .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }
#adminSidebar .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

/* ═══ Responsive ═══ */
@media (max-width: 1023px) {
    body.admin-panel main.admin-main {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 16px;
        padding-top: 72px;
    }
}
@media (max-width: 640px) {
    body.admin-panel main.admin-main { padding: 12px; padding-top: 68px; }
    .admin-card { padding: 16px; }
    .page-title { font-size: 20px; }
}

/* ═══ Utilities ═══ */
.text-muted { color: #64748b; }
.truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.grid-auto-fit { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.flex-between { display: flex; align-items: center; justify-content: space-between; }
</style>
