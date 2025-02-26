<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    safeRedirect('../login.php');
}

// 页面标题
$pageTitle = '用户管理';
$currentPage = 'users';

// 获取用户列表
$db = getDbConnection();
$stmt = $db->query("SELECT * FROM users ORDER BY is_admin DESC, created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - DEB包管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
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
        
        .admin-layout {
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
        }
        
        /* 侧边栏样式 */
        .admin-sidebar {
            flex: 0 0 var(--sidebar-width);
            left: 0;
            top: 0;
            bottom: 0;
            background: var(--card-background);
            box-shadow: 1px 0 10px rgba(0,0,0,0.05);
            z-index: 1030;
            transition: transform var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            height: var(--header-height);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }
        
        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.5rem 1rem;
        }
        
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
        
        .nav-link .mdi {
            width: 1.5rem;
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }
        
        /* 主内容区域 */
        .admin-content {
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* 卡片样式 */
        .card {
            background: var(--card-background);
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: box-shadow var(--transition-speed) ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        /* 按钮样式 */
        .btn {
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        /* 表格样式 */
        .table {
            margin: 0;
            border-spacing: 0;
            border-collapse: separate;
        }
        
        .table th {
            background: var(--background-color);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border-color);
        }
        
        .table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* 移动端适配 */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-content {
                margin-left: 0;
                padding: 1rem;
            }
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
        
        .header-left {
            display: flex;
            flex-direction: column;
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
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .search-box {
            position: relative;
            width: 300px;
        }
        
        .search-box input {
            width: 100%;
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
            font-size: 1.25rem;
        }

        /* 用户列表样式 */
        .user-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .user-role {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .user-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: var(--background-color);
            border: none;
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-icon.text-danger:hover {
            background: var(--danger-color);
        }

        /* 模态框样式 */
        .modal-content {
            border: none;
            border-radius: 1rem;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

        /* 动画和过渡效果 */
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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

        /* 侧边栏动画优化 */
        .sidebar-content .nav-item {
            animation: slideIn 0.3s ease-out forwards;
            opacity: 0;
        }

        .sidebar-content .nav-item:nth-child(1) { animation-delay: 0.1s; }
        .sidebar-content .nav-item:nth-child(2) { animation-delay: 0.2s; }
        .sidebar-content .nav-item:nth-child(3) { animation-delay: 0.3s; }
        .sidebar-content .nav-item:nth-child(4) { animation-delay: 0.4s; }

        /* 侧边栏品牌Logo动画 */
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

        /* 导航链接悬停效果 */
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

        /* 内容区域动画 */
        .content-body {
            animation: fadeIn 0.3s ease-out;
        }

        .card {
            animation: slideIn 0.3s ease-out;
        }

        /* 按钮悬停效果 */
        .btn {
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .btn:hover::after {
            width: 300%;
            height: 300%;
        }

        /* 搜索框动画效果 */
        .search-box input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-box input:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
        }

        .search-box .mdi {
            transition: all 0.3s ease;
        }

        .search-box input:focus + .mdi {
            color: var(--primary-color);
        }

        /* 表格行悬停效果 */
        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(14, 165, 233, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
            display: none;
            position: fixed;
            right: 1rem;
            top: 1rem;
            z-index: 1040;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .mobile-menu-toggle:hover {
            transform: scale(1.1);
            background: var(--secondary-color);
        }

        .mobile-menu-toggle:active {
            transform: scale(0.95);
        }

        /* 移动端样式优化 */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
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

            /* 用户列表标题和刷新按钮布局 */
            .card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
            }

            .card-header .d-flex {
                margin: 0;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .card-header .card-title {
                margin: 0;
            }

            .list-actions {
                margin: 0;
            }

            .list-actions .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                height: 36px;
                display: flex;
                align-items: center;
            }

            /* 其他移动端样式保持不变 */
            // ... existing code ...
        }

        /* 用户列表动画效果 */
        .table tbody tr {
            animation: slideInUp 0.3s ease-out forwards;
            opacity: 0;
        }

        .table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .table tbody tr:nth-child(2) { animation-delay: 0.15s; }
        .table tbody tr:nth-child(3) { animation-delay: 0.2s; }
        .table tbody tr:nth-child(4) { animation-delay: 0.25s; }
        .table tbody tr:nth-child(5) { animation-delay: 0.3s; }

        /* 按钮动画效果 */
        .btn-primary .mdi-refresh {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:hover .mdi-refresh {
            transform: rotate(180deg);
        }

        /* 移动端样式优化 */
        @media (max-width: 768px) {
            /* 禁止横向滚动 */
            .admin-content {
                overflow-x: hidden;
            }

            /* 隐藏表格头部 */
            .table thead {
                display: none;
            }

            /* 用户列表卡片样式 */
            .table tbody tr {
                width: 380px;
                margin: 0.75rem auto;
                padding: 1rem;
                background: var(--card-background);
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .table tbody td {
                display: block;
                padding: 0;
                border: none;
            }

            /* 用户名和操作按钮布局 */
            .table tbody td:first-child {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.5rem;
            }

            /* 移动操作按钮到用户名同一行 */
            .table tbody td:last-child {
                order: -1;
                position: absolute;
                right: 1rem;
                margin: 0;
                padding: 0;
            }

            /* 用户信息布局 */
            .user-info {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            /* 用户名和角色 */
            .user-name {
                font-size: 1.125rem;
                font-weight: 500;
                color: var(--text-primary);
            }

            .user-role {
                font-size: 0.875rem;
                color: var(--text-secondary);
            }

            /* 用户操作按钮样式 */
            .user-actions, 
            .table tbody td:last-child {
                display: flex;
                gap: 0.5rem;
                margin: 0;
                padding: 0;
                border: none;
            }

            .user-actions .btn,
            .table tbody td:last-child .btn {
                width: auto;
                height: 36px;
                padding: 0 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.5rem;
                font-size: 1rem;
                gap: 0.25rem;
            }

            /* 列表容器样式 */
            .user-list {
                min-width: 380px;
                padding: 0.5rem;
            }
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
                    <h1 class="page-title">用户管理</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                            <li class="breadcrumb-item active">用户管理</li>
                        </ol>
                    </nav>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" id="searchInput" placeholder="搜索用户...">
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="mdi mdi-account-plus"></i>
                        <span>添加用户</span>
                    </button>
                </div>
            </header>

            <!-- 用户列表 -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0">用户列表</h5>
                            <span class="badge bg-primary rounded-pill ms-2"><?php echo count($users); ?></span>
                        </div>
                        <div class="list-actions">
                            <button class="btn btn-outline-primary btn-sm" onclick="refreshList(this)">
                                <i class="mdi mdi-refresh me-1"></i>刷新
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>用户名</th>
                                    <th>角色</th>
                                    <th>创建时间</th>
                                    <th class="text-end">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['is_admin'] ? 'primary' : 'secondary'; ?>">
                                            <?php echo $user['is_admin'] ? '管理员' : '普通用户'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                            <i class="mdi mdi-pencil"></i>
                                            <span class="d-none d-md-inline">编辑</span>
                                        </button>
                                        <?php if (!$user['is_admin']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                            <i class="mdi mdi-delete"></i>
                                            <span class="d-none d-md-inline">删除</span>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 添加用户模态框 -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加用户</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_admin" id="addIsAdmin">
                                <label class="form-check-label" for="addIsAdmin">管理员权限</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">添加</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 编辑用户模态框 -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑用户</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" class="form-control" name="username" id="editUsername" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">新密码</label>
                            <input type="password" class="form-control" name="password" placeholder="不修改请留空">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_admin" id="editIsAdmin">
                                <label class="form-check-label" for="editIsAdmin">管理员权限</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>确定要删除用户 "<span id="deleteUserName"></span>" 吗？此操作无法撤销。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="mdi mdi-delete me-1"></i>确认删除
                    </button>
                </div>
            </div>
        </div>
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

    // 存储要删除的用户ID
    let deleteUserId = null;

    // 刷新列表
    function refreshList(button) {
        const icon = button.querySelector('.mdi-refresh');
        icon.style.transition = 'transform 0.5s ease';
        icon.style.transform = 'rotate(360deg)';
        
        location.reload();
    }

    // 添加用户
    function addUser() {
        const form = document.getElementById('addUserForm');
        const formData = new FormData(form);

        fetch('../api/add_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('用户添加成功', 'success');
                location.reload();
            } else {
                showToast(data.message || '添加失败', 'danger');
            }
        })
        .catch(error => {
            showToast('添加失败：' + error.message, 'danger');
        })
        .finally(() => {
            bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
        });
    }

    // 编辑用户
    function editUser(id) {
        fetch(`../api/get_user.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editUserId').value = data.user.id;
                    document.getElementById('editUsername').value = data.user.username;
                    const isAdminCheckbox = document.getElementById('editIsAdmin');
                    const isAdminContainer = isAdminCheckbox.closest('.mb-3');
                    
                    // 如果是管理员账号，隐藏管理员权限选项
                    if (data.user.is_admin === '1') {
                        isAdminContainer.style.display = 'none';
                    } else {
                        isAdminContainer.style.display = 'block';
                        isAdminCheckbox.checked = false;
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    modal.show();
                } else {
                    showToast(data.message || '获取用户信息失败', 'danger');
                }
            })
            .catch(error => {
                showToast('获取用户信息失败：' + error.message, 'danger');
            });
    }

    // 更新用户
    function updateUser() {
        const form = document.getElementById('editUserForm');
        const formData = new FormData(form);
        
        fetch('../api/update_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('用户信息更新成功', 'success');
                location.reload();
            } else {
                showToast(data.message || '更新失败', 'danger');
            }
        })
        .catch(error => {
            showToast('更新失败：' + error.message, 'danger');
        });
    }

    // 删除用户
    function deleteUser(id, username) {
        deleteUserId = id;
        document.getElementById('deleteUserName').textContent = username;
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    // 确认删除
    function confirmDelete() {
        if (!deleteUserId) return;

        fetch(`../api/delete_user.php?id=${deleteUserId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('用户删除成功', 'success');
                location.reload();
            } else {
                showToast(data.message || '删除失败', 'danger');
            }
        })
        .catch(error => {
            showToast('删除失败：' + error.message, 'danger');
        })
        .finally(() => {
            deleteUserId = null;
            bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
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
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchQuery = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const username = row.querySelector('.user-name').textContent.toLowerCase();
            const role = row.querySelector('.badge').textContent.toLowerCase();
            const matches = username.includes(searchQuery) || role.includes(searchQuery);
            row.style.display = matches ? '' : 'none';
        });
    });
    </script>
</body>
</html> 