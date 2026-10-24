<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; }
    .admin-container { display: flex; gap: 16px; min-height: 100vh; padding: 16px; align-items: flex-start; }
    .sidebar { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 220px; height: fit-content; position: sticky; top: 16px; display: flex; flex-direction: column; }
    .sidebar-logo { color: #111; font-size: 18px; margin-bottom: 18px; font-weight: 700; }
    .sidebar-menu { list-style: none; display: flex; flex-direction: column; gap: 12px; margin-bottom: 8px; }
    .sidebar-menu a { display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-radius: 8px; text-decoration: none; color: #ff6b42; font-weight: 600; transition: all 0.3s ease; font-size: 14px; position: relative; }
    .sidebar-menu a:hover { background-color: rgba(255, 138, 91, 0.05); }
    .sidebar-menu a.active { color: #ff6b42; background-color: #ffe8d6; font-weight: 700; }
    .sidebar-menu a.active img { opacity: 1 !important; filter: none !important; }
    .sidebar-menu a img, .sidebar-menu a i { opacity: 0.73; color: inherit !important; filter: none !important; }
    .sidebar-menu a.active img, .sidebar-menu a.active i { opacity: 1 !important; color: #ff6b42 !important; }
    .sign-out-section { margin-top: auto; padding-top: 20px; border-top: 1px solid #eee; }
    .sign-out-section a { color: #ff6b42 !important; font-weight: 600 !important; display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-radius: 8px; text-decoration: none; }
    .main-content { flex: 1; background-color: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); min-height: calc(100vh - 32px); }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
    .page-title { font-size: 28px; font-weight: 700; color: #1a3a52; margin: 0; }
    .page-subtitle { color: #777; font-size: 14px; margin-top: 5px; }
    .add-btn { background-color: #5ec8d0; color: white; border: none; padding: 12px 24px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .add-btn:hover { background-color: #4ab8c0; }
    .secondary-btn { background-color: #ff8a5b; color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .secondary-btn:hover { background-color: #ff6b42; }
    .search-filter-bar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 25px; align-items: center; }
    .search-box { display: flex; align-items: center; gap: 12px; background-color: #fff; padding: 12px 20px; border-radius: 10px; border: 1px solid #e8e8e8; flex: 1; min-width: 200px; max-width: 350px; }
    .search-box input { flex: 1; border: none; outline: none; font-size: 14px; color: #1a3a52; background: transparent; }
    .filter-select { padding: 12px 15px; border: 1px solid #e8e8e8; border-radius: 10px; font-size: 14px; color: #1a3a52; background: #fff; }
    .data-table { background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; min-width: 700px; }
    thead { background-color: #f9f9f9; border-bottom: 2px solid #e0e0e0; }
    th { padding: 16px 18px; text-align: left; font-weight: 600; color: #1a3a52; font-size: 13px; white-space: nowrap; cursor: pointer; }
    th:hover { background-color: #f0f0f0; }
    td { padding: 16px 18px; border-bottom: 1px solid #f0f0f0; color: #555; font-size: 14px; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background-color: #fafafa; }
    .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .stock-in { background-color: #a8d9a8; color: #1a5c1a; }
    .stock-low { background-color: #ffe08a; color: #7a5c00; }
    .stock-out { background-color: #ff9999; color: #7a1a1a; }
    .actions { display: flex; gap: 8px; align-items: center; }
    .action-btn { background: none; border: none; cursor: pointer; font-size: 15px; color: #555; padding: 5px; transition: color 0.2s; }
    .action-btn:hover { color: #ff8a5b; }
    .action-btn.delete:hover { color: #ff4444; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { padding: 20px; border-radius: 12px; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .stat-card-cyan { background-color: #7dd4dd; }
    .stat-card-orange { background-color: #ffb399; }
    .stat-card-green { background-color: #a8d9a8; }
    .stat-card-pink { background-color: #ffb3d9; }
    .stat-card-navy { background-color: #5a8fb4; }
    .stat-card-red { background-color: #ff9999; }
    .stat-content h3 { font-size: 13px; font-weight: 600; margin-bottom: 6px; opacity: 0.9; }
    .stat-content p { font-size: 28px; font-weight: 700; margin: 0; }
    .stat-icon { font-size: 28px; opacity: 0.8; }
    .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .alert-success { background-color: #d4edda; color: #155724; }
    .alert-error { background-color: #f8d7da; color: #721c24; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background-color: #fff; padding: 30px; border-radius: 12px; width: 90%; max-width: 550px; max-height: 90vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-title { font-size: 20px; font-weight: 700; color: #1a3a52; }
    .close-btn { background: none; border: none; font-size: 24px; color: #1a3a52; cursor: pointer; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; color: #1a3a52; font-weight: 600; margin-bottom: 6px; font-size: 13px; }
    .form-input, .form-textarea, .form-select { width: 100%; padding: 11px 14px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #f9f9f9; color: #1a3a52; font-size: 14px; font-family: inherit; }
    .form-textarea { resize: vertical; min-height: 80px; }
    .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: #5ec8d0; background-color: #fff; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
    .btn-cancel { background-color: #fff; color: #1a3a52; border: 1px solid #ddd; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
    .btn-create { background-color: #ff8a5b; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
    .btn-create:hover { background-color: #ff6b42; }
    .delete-modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
    .delete-modal.active { display: flex; }
    .delete-modal-content { background: white; border-radius: 16px; padding: 35px; max-width: 420px; text-align: center; }
    .delete-modal-content h3 { color: #1a3a52; margin-bottom: 10px; }
    .delete-modal-content p { color: #666; margin-bottom: 25px; }
    .delete-modal-buttons { display: flex; gap: 12px; justify-content: center; }
    .btn-delete { background-color: #ff4444; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 25px; flex-wrap: wrap; }
    .pagination a, .pagination span { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .pagination a { background: #f0f0f0; color: #1a3a52; }
    .pagination a:hover { background: #ffe8d6; }
    .pagination .active { background: #ff8a5b; color: white; }
    .tabs { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid #eee; }
    .tab-btn { padding: 12px 20px; border: none; background: none; cursor: pointer; font-weight: 600; color: #777; font-size: 14px; border-bottom: 3px solid transparent; margin-bottom: -2px; }
    .tab-btn.active { color: #ff8a5b; border-bottom-color: #ff8a5b; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .notif-badge { background: #ff4444; color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; margin-left: auto; min-width: 18px; text-align: center; }
    .warning-badge { background: #ff6b42; color: white; font-size: 10px; padding: 3px 8px; border-radius: 10px; font-weight: 600; }
    .overdue-badge { background: #ff4444; color: white; font-size: 10px; padding: 3px 8px; border-radius: 10px; font-weight: 600; }
    .no-results { text-align: center; padding: 50px 20px; color: #999; }
    .report-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
    .report-btn { background: #1a3a52; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .report-btn:hover { background: #2a5a72; }
    @media (max-width: 900px) {
        .admin-container { flex-direction: column; }
        .sidebar { width: 100%; position: static; }
        .form-row { grid-template-columns: 1fr; }
    }
</style>
