<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    safeRedirect('../login.php');
}

// 页面标题
$pageTitle = '管理后台';
$currentPage = 'admin';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>控制台 - DEB包管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --primary-color: #0ea5e9;
            --secondary-color: #0284c7;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --transition-speed: 0.2s;
        }

        /* 基础布局 */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background-color: var(--background-color);
        }

        /* 侧边栏样式 */
        .admin-sidebar {
            flex: 0 0 var(--sidebar-width);
            background: var(--card-background);
            box-shadow: 1px 0 10px rgba(0,0,0,0.05);
            z-index: 1030;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            height: var(--header-height);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .sidebar-brand {
            color: white;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-content {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }

        /* 导航链接样式 */
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            border-radius: 0.5rem;
            transition: all var(--transition-speed) ease;
            font-weight: 500;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
            background: rgba(14, 165, 233, 0.1);
        }
        
        .nav-link i {
            width: 1.5rem;
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }
        
        .nav-item {
            margin: 0.5rem 1rem;
        }
        
        /* 刷新按钮样式 */
        .btn-primary {
            padding: 0.5rem 1rem;
            font-size: 0.9375rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            border: none;
            border-radius: 0.375rem;
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            transform: translateY(1px);
        }
        
        .btn-primary .mdi {
            font-size: 1.25rem;
        }

        /* 主内容区域 */
        .admin-content {
            flex: 1;
            padding: 2rem;
            overflow-x: hidden;
        }

        /* 统计卡片样式 */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            position: relative;
            padding: 1.5rem;
            border-radius: 1rem;
            color: white;
            overflow: hidden;
        }

        .stat-icon {
            position: absolute;
            right: -1rem;
            bottom: -1rem;
            font-size: 5rem;
            opacity: 0.2;
            transform: rotate(-15deg);
        }

        /* 系统状态卡片 */
        .system-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .status-card {
            background: var(--card-background);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* 活动列表样式 */
        .activity-list {
            max-height: 400px;  /* 设置最大高度，约显示5个项目 */
            overflow-y: auto;   /* 添加垂直滚动条 */
            padding-right: 10px; /* 为滚动条预留空间 */
        }

        /* 自定义滚动条样式 */
        .activity-list::-webkit-scrollbar {
            width: 6px;
        }

        .activity-list::-webkit-scrollbar-track {
            background: var(--background-color);
            border-radius: 3px;
        }

        .activity-list::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .activity-list::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: rgba(14, 165, 233, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .activity-time {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .bg-upload {
            background: var(--primary-color);
        }

        /* 顶部导航栏样式 */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .breadcrumb {
            margin: 0.5rem 0 0;
            padding: 0;
            background: none;
        }
        
        .breadcrumb-item a {
            color: var(--text-secondary);
            text-decoration: none;
        }
        
        .search-box {
            position: relative;
            margin-right: 1rem;
        }
        
        .search-box input {
            width: 300px;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: var(--card-background);
            transition: all var(--transition-speed) ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
        }
        
        .search-box .mdi {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .header-right {
            display: flex;
            align-items: center;
        }
        
        /* 统计卡片补充样式 */
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        /* 状态卡片补充样式 */
        .status-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .status-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        /* 活动列表补充样式 */
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* 响应式布局 */
        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                bottom: 0;
                transform: translateX(0);
                transition: transform var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: none;
            }
            
            .admin-sidebar.show {
                transform: translateX(280px);
                box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .admin-content {
                padding-top: 4rem;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-right {
                width: 100%;
            }
            
            .search-box {
                flex: 1;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* 禁止活动区域左右滚动 */
            .activities-card {
                overflow-x: hidden;
            }
            
            .activity-list {
                overflow-x: hidden;
                padding-right: 0;
            }
            
            .activity-item {
                width: 100%;
                padding-right: 1rem;
            }
            
            .activity-content {
                flex: 1;
                min-width: 0; /* 防止内容溢出 */
            }
            
            .activity-title {
                white-space: normal; /* 允许标题换行 */
                word-break: break-word;
            }
        }

        /* 动画效果 */
        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateX(-10px); 
            }
            to { 
                opacity: 1; 
                transform: translateX(0); 
            }
        }

        @keyframes slideInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* 为统计卡片添加动画 */
        .dashboard-stats .stat-card {
            animation: slideInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .dashboard-stats .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-stats .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-stats .stat-card:nth-child(3) { animation-delay: 0.3s; }

        /* 为系统状态卡片添加动画 */
        .system-status .status-card {
            animation: fadeIn 0.5s ease-out forwards;
            animation-delay: 0.4s;
            opacity: 0;
        }

        /* 为活动列表项添加动画 */
        .activity-item {
            animation: slideInUp 0.3s ease-out forwards;
            opacity: 0;
        }

        /* 优化卡片悬停效果 */
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        /* 优化状态卡片悬停效果 */
        .status-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* 优化活动列表项悬停效果 */
        .activity-item {
            transition: background-color 0.2s ease, transform 0.2s ease;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .activity-item:hover {
            background-color: rgba(14, 165, 233, 0.05);
            transform: translateX(5px);
        }

        /* 优化刷新按钮动画 */
        .btn-primary .mdi-refresh {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:hover .mdi-refresh {
            transform: rotate(180deg);
        }

        /* 优化搜索框动画 */
        .search-box input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-box input:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
        }

        /* Toast 提示优化 */
        .toast-container {
            z-index: 1060;
        }

        .toast {
            animation: slideInUp 0.3s ease-out;
        }

        /* 移动端菜单按钮 */
        .mobile-menu-toggle {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--primary-color);
            border-radius: 10px;
            color: white;
            display: none;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1031;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(0);
        }
        
        .mobile-menu-toggle:active {
            transform: scale(0.95);
        }
        
        .mobile-menu-toggle .mdi {
            font-size: 24px;
        }

        /* 菜单按钮隐藏状态 */
        .mobile-menu-toggle.hide {
            transform: translateX(60px);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }
            
            .mobile-menu-toggle:hover {
                background: var(--secondary-color);
            }
            
            .admin-sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                bottom: 0;
                transform: translateX(0);
                transition: transform var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: none;
            }
            
            .admin-sidebar.show {
                transform: translateX(280px);
                box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .admin-content {
                padding-top: 4rem;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-right {
                width: 100%;
            }
            
            .search-box {
                flex: 1;
            }
            
            .search-box input {
                width: 100%;
            }
        }

        /* 侧边栏动画优化 */
        .sidebar-content .nav-item {
            animation: slideIn 0.3s ease-out forwards;
            opacity: 0;
        }
        
        .sidebar-content .nav-item:nth-child(1) { animation-delay: 0.1s; }
        .sidebar-content .nav-item:nth-child(2) { animation-delay: 0.2s; }
        .sidebar-content .nav-item:nth-child(3) { animation-delay: 0.3s; }
        .sidebar-content .nav-item:nth-child(4) { animation-delay: 0.4s; }

        /* 侧边栏样式优化 */
        .sidebar-header {
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-brand {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            overflow: hidden;
        }

        .sidebar-brand .mdi {
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        .sidebar-brand:hover .mdi {
            transform: rotate(360deg);
        }

        /* 导航链接优化 */
        .nav-link {
            position: relative;
            overflow: hidden;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            transform: translateX(0);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- 移动端菜单按钮 -->
        <button class="mobile-menu-toggle" id="menuToggle">
            <i class="mdi mdi-menu"></i>
        </button>

        <!-- 侧边栏 -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="mdi mdi-package-variant"></i>
                    <span>DEB包管理系统</span>
                </a>
            </div>
            <div class="sidebar-content">
                <nav class="nav flex-column">
                    <div class="nav-item">
                        <a href="index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                            <i class="mdi mdi-view-dashboard"></i>
                            <span>控制台</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="packages.php" class="nav-link <?php echo $currentPage === 'packages' ? 'active' : ''; ?>">
                            <i class="mdi mdi-package"></i>
                            <span>包管理</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="users.php" class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                            <i class="mdi mdi-account-group"></i>
                            <span>用户管理</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                            <i class="mdi mdi-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </div>
                    <div class="nav-item mt-auto">
                        <a href="../logout.php" class="nav-link text-danger">
                            <i class="mdi mdi-logout"></i>
                            <span>退出登录</span>
                        </a>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- 主内容区域 -->
        <main class="admin-content">
            <!-- 顶部导航栏 -->
            <header class="content-header">
                <div class="header-left">
                    <h1 class="page-title">控制台</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                            <li class="breadcrumb-item active">控制台</li>
                        </ol>
                    </nav>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" id="searchInput" placeholder="搜索活动记录...">
                    </div>
                    <button class="btn btn-primary" onclick="refreshStats()">
                        <i class="mdi mdi-refresh"></i>
                        <span>刷新</span>
                    </button>
                </div>
            </header>

            <!-- 统计卡片 -->
            <div class="dashboard-stats">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                    <i class="mdi mdi-package-variant stat-icon"></i>
                    <div class="stat-value"><?php echo getPackageCount(); ?></div>
                    <div class="stat-label">总包数</div>
                </div>

                <div class="stat-card" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <i class="mdi mdi-download stat-icon"></i>
                    <div class="stat-value"><?php echo getDownloadCount(); ?></div>
                    <div class="stat-label">总下载次数</div>
                </div>

                <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="mdi mdi-database stat-icon"></i>
                    <div class="stat-value"><?php echo formatFileSize(getTotalSize()); ?></div>
                    <div class="stat-label">总存储空间</div>
                </div>
            </div>

            <!-- 系统状态 -->
            <div class="system-status">
                <div class="status-card">
                    <div class="status-header">
                        <div class="status-icon">
                            <i class="mdi mdi-server"></i>
                        </div>
                        <h3>系统状态</h3>
                    </div>
                    <div class="status-content">
                        <div class="status-item">
                            <span>CPU使用率：</span>
                            <span class="text-primary"><?php echo getCpuUsage(); ?>%</span>
                        </div>
                        <div class="status-item">
                            <span>内存使用率：</span>
                            <span class="text-primary"><?php echo getMemoryUsage(); ?>%</span>
                        </div>
                        <div class="status-item">
                            <span>磁盘使用率：</span>
                            <span class="text-primary"><?php echo getDiskUsage(); ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="status-card">
                    <div class="status-header">
                        <div class="status-icon">
                            <i class="mdi mdi-history"></i>
                        </div>
                        <h3>最近活动</h3>
                    </div>
                    <div class="activity-list">
                        <?php foreach (getRecentActivities() as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="mdi mdi-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo $activity['title']; ?></div>
                                <div class="activity-time"><?php echo $activity['time']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../templates/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 移动端菜单切换
        document.getElementById('menuToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('show');
            
            // 切换图标
            const icon = this.querySelector('.mdi');
            if (sidebar.classList.contains('show')) {
                icon.classList.remove('mdi-menu');
                icon.classList.add('mdi-close');
            } else {
                icon.classList.remove('mdi-close');
                icon.classList.add('mdi-menu');
            }
        });

        // 点击内容区域关闭菜单
        document.querySelector('.admin-content').addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.admin-sidebar');
                if (sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    const icon = document.querySelector('.mobile-menu-toggle .mdi');
                    icon.classList.remove('mdi-close');
                    icon.classList.add('mdi-menu');
                }
            }
        });

        // 监听窗口大小变化
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.querySelector('.admin-sidebar');
                sidebar.classList.remove('show');
                const icon = document.querySelector('.mobile-menu-toggle .mdi');
                icon.classList.remove('mdi-close');
                icon.classList.add('mdi-menu');
            }
        });

        // 更新统计卡片数据
        function updateStats(data) {
            const stats = data.data.packages;
            document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = stats.total;
            document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = stats.downloads;
            document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = stats.formatted_size;
        }

        // 更新系统状态
        function updateSystemStatus(data) {
            const system = data.data.system;
            document.querySelector('.status-item:nth-child(1) .text-primary').textContent = system.cpu + '%';
            document.querySelector('.status-item:nth-child(2) .text-primary').textContent = system.memory + '%';
            document.querySelector('.status-item:nth-child(3) .text-primary').textContent = system.disk + '%';
        }

        // 更新活动列表
        function updateActivities(activities) {
            const activityList = document.querySelector('.activity-list');
            if (!activities || !Array.isArray(activities)) {
                console.error('Invalid activities data:', activities);
                return;
            }
            activityList.innerHTML = activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="mdi mdi-${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.title}</div>
                        <div class="activity-time">${activity.time}</div>
                    </div>
                </div>
            `).join('');
        }

        // 刷新统计数据和系统状态
        function refreshStats() {
            const button = document.querySelector('.header-right .btn-primary');
            const icon = button.querySelector('.mdi-refresh');
            
            // 添加旋转动画
            icon.style.transition = 'transform 0.5s ease';
            icon.style.transform = 'rotate(360deg)';
            
            // 发送AJAX请求获取最新数据
            fetch('get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || '更新失败');
                    }
                    // 更新统计卡片
                    updateStats(data);
                    // 更新系统状态
                    updateSystemStatus(data);
                    // 更新活动列表
                    updateActivities(data.data.activities);
                    // 显示成功提示
                    showToast('数据已更新', 'success');
                })
                .catch(error => {
                    console.error('刷新失败:', error);
                    showToast(error.message || '更新失败', 'danger');
                })
                .finally(() => {
                    // 重置旋转动画
                    setTimeout(() => {
                        icon.style.transform = 'rotate(0deg)';
                    }, 500);
                });
        }

        // 显示提示信息
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container') || 
                document.body.appendChild(document.createElement('div'));
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        // 搜索功能
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchQuery = this.value.trim();
                // 过滤活动列表
                filterActivities(searchQuery);
            }, 300);
        });
        
        // 过滤活动列表
        function filterActivities(query) {
            const activities = document.querySelectorAll('.activity-item');
            activities.forEach(activity => {
                const title = activity.querySelector('.activity-title').textContent.toLowerCase();
                const time = activity.querySelector('.activity-time').textContent.toLowerCase();
                const matches = title.includes(query.toLowerCase()) || 
                               time.includes(query.toLowerCase());
                activity.style.display = matches ? 'flex' : 'none';
            });
        }

        // 定时刷新系统状态
        function refreshSystemStatusOnly() {
            fetch('get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const system = data.data.system;
                        document.querySelector('.status-item:nth-child(1) .text-primary').textContent = system.cpu + '%';
                        document.querySelector('.status-item:nth-child(2) .text-primary').textContent = system.memory + '%';
                        document.querySelector('.status-item:nth-child(3) .text-primary').textContent = system.disk + '%';
                    }
                })
                .catch(error => console.error('刷新系统状态失败:', error));
        }

        // 每3秒刷新一次系统状态
        setInterval(refreshSystemStatusOnly, 3000);

        // 更新系统状态
        function updateSystemStatus(data) {
            const system = data.data.system;
            document.querySelector('.status-item:nth-child(1) .text-primary').textContent = system.cpu + '%';
            document.querySelector('.status-item:nth-child(2) .text-primary').textContent = system.memory + '%';
            document.querySelector('.status-item:nth-child(3) .text-primary').textContent = system.disk + '%';
        }

        // 处理菜单按钮的显示和隐藏
        let lastScrollTop = 0;
        let scrollTimer = null;
        
        window.addEventListener('scroll', function() {
            const menuButton = document.querySelector('.mobile-menu-toggle');
            if (!menuButton) return;
            
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            // 清除之前的定时器
            clearTimeout(scrollTimer);
            
            // 向下滚动时隐藏
            if (currentScroll > lastScrollTop && currentScroll > 100) {
                menuButton.classList.add('hide');
            } else {
                // 向上滚动时显示
                menuButton.classList.remove('hide');
            }
            
            // 停止滚动3秒后显示按钮
            scrollTimer = setTimeout(() => {
                menuButton.classList.remove('hide');
            }, 3000);
            
            lastScrollTop = currentScroll;
        }, { passive: true });
    </script>
</body>
</html> 