<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    safeRedirect('../login.php');
}

// 页面标题
$pageTitle = '包管理';
$currentPage = 'packages';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>包管理 - DEB包管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        /* 新的管理后台样式 */
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
                position: fixed;
                left: -280px;
                top: 0;
                bottom: 0;
                transform: translateX(0);
                transition: transform var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: none;
                z-index: 1040;
            }
            
            .admin-sidebar.show {
                transform: translateX(280px);
                box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .admin-content {
                margin-left: 0;
                padding-top: 4rem;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .header-right {
                width: 100%;
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                width: 100%;
            }

            .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .card-header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .list-actions {
                width: 100%;
                flex-direction: column;
                gap: 0.75rem;
            }

            .list-actions .btn-group {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .list-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                padding: 1rem;
                border-bottom: 1px solid var(--border-color);
            }

            .table tbody td {
                display: flex;
                padding: 0.5rem 0;
                border: none;
                align-items: center;
                justify-content: space-between;
            }

            .table tbody td:before {
                content: attr(data-label);
                font-weight: 500;
                color: var(--text-secondary);
            }

            .table tbody td:first-child {
                padding-top: 0;
            }

            .table tbody td:last-child {
                padding-bottom: 0;
            }

            .package-name {
                flex: 1;
                margin-left: 0.5rem;
            }

            .file-actions {
                width: 100%;
                justify-content: flex-end;
                gap: 0.5rem;
                margin-top: 0.5rem;
            }

            .file-actions .btn {
                padding: 0.5rem;
                width: auto;
            }

            .card-footer {
                padding: 1rem;
            }

            .pagination-info {
                text-align: center;
                margin-bottom: 0.75rem;
            }

            .pagination {
                justify-content: center;
            }

            /* 上传区域移动端优化 */
            .upload-zone {
                margin-bottom: 1rem;
            }

            .dropzone {
                min-height: 120px;
            }

            .dropzone .dz-message {
                margin: 1.5rem 0;
            }

            .dropzone .dz-message h4 {
                font-size: 1rem;
            }

            .dropzone .dz-message p {
                font-size: 0.875rem;
            }

            /* 网格视图移动端优化 */
            .grid-view .col {
                width: 100%;
            }

            .grid-view .file-card {
                margin-bottom: 0.75rem;
            }

            /* 移动端菜单按钮 */
            .mobile-menu-toggle {
                display: flex;
                position: fixed;
                right: 1rem;
                top: 1rem;
                z-index: 1050;
                width: 40px;
                height: 40px;
                border: none;
                border-radius: 50%;
                background: var(--primary-color);
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                align-items: center;
                justify-content: center;
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

            /* 移动端遮罩层 */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1030;
                opacity: 0;
                transition: opacity var(--transition-speed) ease;
            }

            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }

            /* 文件列表移动端优化 */
            .table tbody tr {
                position: relative;
                margin: 1rem;
                padding: 1.25rem;
                background: var(--card-background);
                border-radius: 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .table tbody tr:active {
                transform: scale(0.98);
            }

            .table tbody tr:hover {
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            /* 文件信息布局优化 */
            .file-item {
                gap: 1rem;
            }

            .file-item-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
                border-radius: 12px;
                background: rgba(14, 165, 233, 0.1);
            }

            .file-info {
                padding: 0.5rem 0;
            }

            .file-name {
                font-size: 1.125rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
                color: var(--text-primary);
            }

            .file-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                color: var(--text-secondary);
            }

            .file-meta span {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .file-meta i {
                font-size: 1.25rem;
                opacity: 0.7;
            }

            /* 操作按钮优化 */
            .file-actions {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.75rem;
                padding-top: 1rem;
                margin-top: 1rem;
                border-top: 1px solid var(--border-color);
            }

            .file-actions .btn-icon {
                width: 100%;
                height: 44px;
                font-size: 1.25rem;
                border-radius: 0.75rem;
                background: var(--background-color);
                color: var(--text-secondary);
                transition: all 0.2s ease;
            }

            .file-actions .btn-icon:hover {
                transform: translateY(-2px);
            }

            .file-actions .btn-icon:active {
                transform: scale(0.95);
            }

            /* 搜索框优化 */
            .search-box {
                position: relative;
                margin-bottom: 1rem;
            }

            .search-box input {
                width: 100%;
                height: 48px;
                padding: 0 1rem 0 3rem;
                border: 1px solid var(--border-color);
                border-radius: 1rem;
                font-size: 1rem;
                background: var(--card-background);
                transition: all 0.3s ease;
            }

            .search-box input:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
            }

            .search-box .mdi {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                font-size: 1.5rem;
                color: var(--text-secondary);
                pointer-events: none;
                transition: color 0.3s ease;
            }

            .search-box input:focus + .mdi {
                color: var(--primary-color);
            }

            /* 批量操作按钮优化 */
            .list-actions {
                padding: 1rem;
                background: var(--card-background);
                border-radius: 1rem;
                margin: 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .list-actions .btn-group {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .list-actions .btn {
                height: 44px;
                font-size: 1rem;
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }

            .list-actions .btn i {
                font-size: 1.25rem;
            }

            /* 空状态优化 */
            .empty-state {
                margin: 2rem 1rem;
                padding: 2rem;
                background: var(--card-background);
                border-radius: 1rem;
                text-align: center;
            }

            .empty-state i {
                font-size: 4rem;
                color: var(--text-secondary);
                margin-bottom: 1.5rem;
                opacity: 0.5;
            }

            .empty-state h3 {
                font-size: 1.25rem;
                color: var(--text-primary);
                margin-bottom: 0.75rem;
            }

            .empty-state p {
                color: var(--text-secondary);
                margin-bottom: 1.5rem;
            }

            /* 加载状态优化 */
            .loading-state {
                padding: 2rem;
            }

            .loading-spinner {
                width: 48px;
                height: 48px;
                border-width: 3px;
            }

            /* 分页控件优化 */
            .pagination-wrapper {
                margin: 1rem;
                padding: 1rem;
                background: var(--card-background);
                border-radius: 1rem;
            }

            .pagination {
                gap: 0.5rem;
            }

            .page-link {
                min-width: 40px;
                height: 40px;
                border-radius: 0.75rem;
                font-size: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* 触摸反馈优化 */
            .btn:active,
            .nav-link:active,
            .file-card:active,
            .page-link:active {
                transform: scale(0.95);
            }

            /* 滚动优化 */
            .admin-content {
                padding: 1rem;
                -webkit-overflow-scrolling: touch;
            }

            /* 手势区域优化 */
            .btn::after,
            .nav-link::after,
            .page-link::after {
                content: '';
                position: absolute;
                top: -10px;
                right: -10px;
                bottom: -10px;
                left: -10px;
            }

            /* 动画优化 */
            .table tbody tr {
                animation: slideInUp 0.3s ease-out forwards;
                opacity: 0;
            }

            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* 网格视图优化 */
            .grid-view .col {
                padding: 0.5rem;
            }

            .file-card {
                height: auto;
                padding: 1.25rem;
                border-radius: 1rem;
                background: var(--card-background);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .file-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .file-card-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }

            .file-card .file-name {
                font-size: 1.125rem;
                margin-bottom: 0.75rem;
            }

            .file-card .file-info {
                margin-bottom: 1rem;
            }

            .file-card .file-actions {
                padding-top: 1rem;
                border-top: 1px solid var(--border-color);
            }

            /* 删除确认框优化 */
            .modal-content {
                margin: 1rem;
                border-radius: 1rem;
                overflow: hidden;
            }

            .modal-header {
                padding: 1.25rem;
                border-bottom: 1px solid var(--border-color);
            }

            .modal-body {
                padding: 1.5rem;
            }

            .modal-footer {
                padding: 1.25rem;
                border-top: 1px solid var(--border-color);
            }

            .modal-footer .btn {
                width: 100%;
                height: 44px;
                font-size: 1rem;
                border-radius: 0.75rem;
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
        
        /* 动画和过渡效果 */
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        .nav-link:hover::after {
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
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            transform: translateY(-2px);
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
        
        /* 上传区域样式优化 */
        .dropzone {
            border: 2px dashed var(--border-color);
            border-radius: 1rem;
            background: transparent;
            transition: all var(--transition-speed) ease;
        }
        
        .dropzone:hover {
            border-color: var(--primary-color);
            background: rgba(14, 165, 233, 0.02);
        }
        
        .dropzone .dz-message {
            margin: 4rem 0;
        }
        
        .dropzone .dz-message i {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        /* 文件列表样式优化 */
        .file-list {
            position: relative;
            min-height: 200px;
        }
        
        .file-list-header {
            background: var(--background-color);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .file-item:hover {
            background: rgba(14, 165, 233, 0.05);
        }
        
        .file-item-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 8px;
            background: rgba(14, 165, 233, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }
        
        .file-item-icon i {
            font-size: 20px;
        }
        
        .file-item-content {
            flex: 1;
        }
        
        .file-item-name {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-item-info {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .file-item-actions {
            display: flex;
            gap: 0.5rem;
            opacity: 1;
            transform: none;
            transition: none;
        }
        
        tr:hover .file-actions {
            opacity: 1;
            transform: none;
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
        
        .btn-icon::after {
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
        
        .btn-icon:hover::after {
            width: 200%;
            height: 200%;
        }
        
        .btn-icon:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
        }
        
        .btn-icon.text-danger:hover {
            background: var(--danger-color);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        
        /* 视图切换按钮样式 */
        .list-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn-group-sm .btn {
            padding: 0.375rem;
            font-size: 0.875rem;
        }
        
        .btn-group-sm .btn i {
            font-size: 1rem;
        }
        
        /* 列表视图和网格视图切换动画 */
        .list-view,
        .grid-view {
            display: none;
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
        }
        
        .list-view.active,
        .grid-view.active {
            display: block;
            opacity: 1;
        }
        
        /* 表格样式优化 */
        .table {
            --bs-table-hover-bg: rgba(14, 165, 233, 0.05);
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            padding: 1rem 1.5rem;
            background: var(--background-color);
        }
        
        .table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }
        
        .table tr:hover .file-actions {
            opacity: 1;
        }
        
        /* 文件列表样式优化 */
        .table {
            margin: 0;
        }
        
        .table th {
            background: var(--background-color);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            color: var(--text-secondary);
        }
        
        .table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }
        
        .table tr {
            transition: all 0.2s ease;
        }
        
        .table tr:hover {
            background: rgba(14, 165, 233, 0.05);
        }
        
        /* 文件项样式 */
        .file-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .file-item-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 6px;
            background: rgba(14, 165, 233, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }
        
        .file-item-icon i {
            font-size: 18px;
        }
        
        .file-info {
            flex: 1;
            min-width: 0;
        }
        
        .file-name {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* 操作按钮样式 */
        .file-actions {
            display: flex;
            gap: 0.5rem;
            opacity: 1;
            transition: none;
        }
        
        .table tr:hover .file-actions {
            opacity: 1;
            transform: none;
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
        
        /* 上传区域样式优化 */
        .upload-zone {
            background: var(--card-background);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .dropzone {
            border: 2px dashed var(--border-color);
            border-radius: 0.75rem;
            background: transparent;
            transition: all 0.3s ease;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dropzone:hover,
        .dropzone.dz-drag-hover {
            border-color: var(--primary-color);
            background: rgba(14, 165, 233, 0.05);
        }
        
        .dropzone .dz-message {
            text-align: center;
            margin: 2rem 0;
        }
        
        .dropzone .dz-message .upload-icon {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        
        .dropzone:hover .dz-message .upload-icon {
            transform: translateY(-5px);
            color: var(--primary-color);
        }
        
        /* 网格视图优化 */
        .grid-view {
            display: none;
            padding: 1rem;
        }
        
        .grid-view.active {
            display: block;
        }
        
        .file-card {
            background: var(--card-background);
            border-radius: 0.75rem;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .file-card-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 8px;
            background: rgba(14, 165, 233, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .file-card-icon i {
            font-size: 20px;
        }
        
        .file-card-content {
            flex: 1;
        }
        
        .file-card .file-name {
            font-weight: 500;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-card .file-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .file-card .file-actions {
            display: flex;
            gap: 0.5rem;
            opacity: 1;
            transform: none;
            transition: none;
        }
        
        .file-card:hover .file-actions {
            opacity: 1;
            transform: none;
        }
        
        /* 空状态样式 */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            display: none;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 0;
        }
        
        /* 加载状态动画 */
        .loading-state {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        
        .loading-state.active {
            display: flex;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* 分页样式优化 */
        .pagination-wrapper {
            padding: 1rem 1.5rem;
            background: var(--background-color);
            border-top: 1px solid var(--border-color);
        }
        
        .pagination {
            margin: 0;
            gap: 0.25rem;
        }
        
        .page-link {
            border: none;
            padding: 0.5rem 0.75rem;
            color: var(--text-secondary);
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            background: rgba(14, 165, 233, 0.1);
            color: var(--primary-color);
            transform: translateY(-1px);
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 4px rgba(14, 165, 233, 0.2);
        }
        
        .pagination-info {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        /* 表格行动画效果 */
        .table tbody tr {
            animation: slideInUp 0.3s ease-out forwards;
            opacity: 0;
        }
        
        .table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .table tbody tr:nth-child(2) { animation-delay: 0.15s; }
        .table tbody tr:nth-child(3) { animation-delay: 0.2s; }
        .table tbody tr:nth-child(4) { animation-delay: 0.25s; }
        .table tbody tr:nth-child(5) { animation-delay: 0.3s; }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* 复选框样式优化 */
        .form-check-input {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transform: scale(1.1);
        }
        
        /* 文件信息悬停效果 */
        .file-info-hover {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 1000;
        }
        
        .file-name:hover + .file-info-hover {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* 表格中的文件图标样式 */
        .table .file-item-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 6px;
            margin-right: 0.75rem;
        }
        
        .table .file-item-icon i {
            font-size: 18px;
        }
        
        /* 网格视图中的文件图标样式 */
        .grid-view .file-card-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 6px;
            background: rgba(14, 165, 233, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .grid-view .file-card-icon i {
            font-size: 18px;
        }

        /* 移动端导航栏优化 */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 280px;
                background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            }

            .sidebar-header {
                height: 70px;
                background: none;
                border: none;
            }

            .sidebar-brand {
                font-size: 1.25rem;
                letter-spacing: 0.5px;
            }

            .sidebar-brand .mdi {
                font-size: 1.75rem;
            }

            .nav-item {
                margin: 0.25rem 1rem;
            }

            .nav-link {
                color: rgba(255, 255, 255, 0.8);
                padding: 0.875rem 1.25rem;
                border-radius: 0.75rem;
                font-weight: 500;
                letter-spacing: 0.3px;
            }

            .nav-link:hover,
            .nav-link.active {
                color: white;
                background: rgba(255, 255, 255, 0.15);
            }

            .nav-link .mdi {
                font-size: 1.375rem;
                margin-right: 1rem;
                opacity: 0.9;
            }

            .nav-link:hover .mdi,
            .nav-link.active .mdi {
                opacity: 1;
            }

            /* 文件列表卡片优化 */
            .table tbody tr {
                width: 380px;
                margin: 0.75rem auto;
                padding: 1rem;
                background: var(--card-background);
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
                min-height: 130px;
                display: flex;
                flex-direction: column;
            }

            .file-list {
                min-width: 380px;
                padding: 0.5rem;
            }

            .file-item {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                flex: 1;
            }

            /* 文件名称和复选框布局 */
            .file-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .file-header .form-check {
                margin: 0;
                padding: 0;
            }

            .file-header .form-check-input {
                margin: 0;
            }

            .file-name-wrapper {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex: 1;
                min-width: 0;
            }

            .file-item-icon {
                width: 36px;
                height: 36px;
                font-size: 1.125rem;
                border-radius: 8px;
                background: rgba(14, 165, 233, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--primary-color);
                flex-shrink: 0;
            }

            .file-name {
                font-size: 1rem;
                font-weight: 500;
                color: var(--text-primary);
                margin: 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .file-meta {
                font-size: 0.875rem;
                color: var(--text-secondary);
                margin-left: calc(36px + 0.5rem);
            }

            .file-meta span {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
            }

            .file-meta i {
                font-size: 1rem;
                opacity: 0.7;
            }

            .file-actions {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.375rem;
                padding-top: 0.5rem;
                margin-top: auto;
                border-top: 1px solid var(--border-color);
            }

            .file-actions .btn-icon {
                width: 100%;
                height: 36px;
                font-size: 1.125rem;
                border-radius: 0.5rem;
                background: var(--background-color);
                color: var(--text-secondary);
                transition: all 0.2s ease;
            }

            /* 修改列表视图的渲染模板 */
            function loadFileList(page = 1) {
                // ... existing code ...
                tableBody.innerHTML = data.files.map(file => {
                    // 解析control信息只获取架构
                    let architecture = '';
                    try {
                        const controlInfo = typeof file.control_info === 'string' 
                            ? JSON.parse(file.control_info) 
                            : file.control_info;
                        architecture = controlInfo?.Architecture || '未知';
                    } catch (e) {
                        architecture = '未知';
                    }

                    return `
                        <tr>
                            <td>
                                <div class="file-item">
                                    <div class="file-name-row">
                                        <div class="file-name-container">
                                            <div class="file-item-icon">
                                                <i class="mdi mdi-package-variant"></i>
                                            </div>
                                            <div class="file-name">${file.name}</div>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input file-checkbox" type="checkbox" value="${file.id}">
                                        </div>
                                    </div>
                                    <div class="file-info-container">
                                        <div class="file-meta">
                                            <span><i class="mdi mdi-chip"></i>架构: ${architecture}</span>
                                        </div>
                                        <div class="file-details">
                                            <div class="file-size">${formatFileSize(file.size)}</div>
                                            <div class="file-date">${formatDate(file.uploaded_at)}</div>
                                        </div>
                                    </div>
                                    <div class="file-actions">
                                        <button class="btn btn-icon" onclick="window.location.href='../view.php?id=${file.id}'">
                                            <i class="mdi mdi-eye"></i>
                                        </button>
                                        <button class="btn btn-icon" onclick="window.location.href='../edit.php?id=${file.id}'">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <button class="btn btn-icon" onclick="window.location.href='../api/download.php?id=${file.id}'">
                                            <i class="mdi mdi-download"></i>
                                        </button>
                                        <button class="btn btn-icon text-danger" onclick="deleteFile(${file.id}, '${file.name.replace(/'/g, "\\'")}')">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
                // ... existing code ...
            }
        }

        /* 移动端样式 */
        @media (max-width: 768px) {
            /* 隐藏表格头部 */
            .table thead {
                display: none;
            }

            /* 调整表格行样式 */
            .table tbody tr {
                width: 380px;
                margin: 0.75rem auto;
                padding: 1rem;
                background: var(--card-background);
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            /* 调整单元格样式 */
            .table tbody td {
                display: block;
                padding: 0;
                border: none;
            }

            /* 网格视图卡片样式 */
            .grid-view .file-card {
                width: 380px;
                margin: 0.75rem auto;
                padding: 1rem;
                background: var(--card-background);
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .grid-view .file-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .grid-view .col {
                width: 100%;
                display: flex;
                justify-content: center;
                padding: 0;
            }

            /* 文件列表容器 */
            .file-list {
                min-width: 380px;
                padding: 0.5rem;
            }

            /* 主要内容容器 */
            .file-item {
                display: flex;
                flex-direction: column;
            }

            /* 文件名和复选框行 */
            .file-name-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 0.75rem;
            }

            /* 文件名称容器样式优化 */
            .file-name-container {
                display: flex;
                align-items: flex-start;
                gap: 0.5rem;
                flex: 1;
                min-width: 0;
                width: 100%;
            }

            /* 文件名称样式优化 */
            .file-name {
                font-size: 1rem;
                font-weight: 500;
                color: var(--text-primary);
                margin: 0;
                white-space: normal !important;
                word-wrap: break-word !important;
                word-break: break-all !important;
                line-height: 1.4;
                flex: 1;
                min-width: 0;
                overflow: visible !important;
                text-overflow: unset !important;
            }

            /* 文件图标垂直对齐优化 */
            .file-item-icon {
                flex-shrink: 0;
                margin-top: 0.125rem;
            }

            /* 复选框容器位置调整 */
            .form-check {
                margin-left: 0.5rem;
                padding-top: 0.125rem;
                flex-shrink: 0;
            }

            /* 文件信息容器间距优化 */
            .file-info-container {
                margin-top: 0.5rem;
            }

            /* 文件名文本 */
            .file-name {
                font-size: 1rem;
                font-weight: 500;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin: 0;
            }

            /* 复选框容器 */
            .form-check {
                margin-left: 1rem;
                flex-shrink: 0;
            }

            /* 移除之前的样式 */
            .file-list-item,
            .checkbox-container {
                display: none;
            }

            /* 修改文件信息布局 */
            .file-info-container {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                margin-top: auto;
                padding-top: 0.75rem;
            }

            /* 架构信息样式 */
            .file-meta {
                font-size: 0.875rem;
                color: var(--text-secondary);
                margin-left: calc(36px + 0.5rem);
            }

            /* 文件详细信息样式 */
            .file-details {
                text-align: right;
                font-size: 0.875rem;
                color: var(--text-secondary);
            }

            .file-details > div {
                margin-top: 0.25rem;
            }

            /* 操作按钮上方添加边距 */
            .file-actions {
                margin-top: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid var(--border-color);
            }
        }

        @media (max-width: 768px) {
            /* 列表操作按钮组样式统一 */
            .list-actions {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                width: 100%;
            }

            /* 所有按钮组统一样式 */
            .list-actions .btn-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
                width: 100%;
            }

            /* 统一所有按钮样式 */
            .list-actions .btn {
                height: 44px;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                font-size: 1rem;
                padding: 0.5rem 1rem;
                border-radius: 0.75rem;
                border: 1px solid var(--border-color);
            }

            /* 移除按钮的虚线边框 */
            .list-actions .btn-outline-primary,
            .list-actions .btn-outline-danger,
            .list-actions .btn-outline-secondary {
                border: 1px solid var(--border-color);
            }

            /* 按钮激活状态 */
            .list-actions .btn.active {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                color: white;
            }

            /* 按钮图标样式统一 */
            .list-actions .btn i,
            .list-actions .btn .mdi {
                font-size: 1.25rem;
            }

            /* 移除按钮组的边框重叠和间距 */
            .list-actions .btn-group .btn + .btn {
                margin-left: 0;
            }

            /* 移除按钮组的圆角重叠 */
            .list-actions .btn-group .btn:first-child,
            .list-actions .btn-group .btn:last-child {
                border-radius: 0.75rem;
            }

            /* 优化按钮悬停状态 */
            .list-actions .btn:hover {
                border-color: var(--primary-color);
            }

            /* 优化禁用状态 */
            .list-actions .btn:disabled {
                opacity: 0.65;
                border-color: var(--border-color);
            }
        }

        @media (max-width: 768px) {
            /* 顶部导航栏移动端样式 */
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1rem;
                margin-bottom: 1rem;
                background: var(--card-background);
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            .header-left {
                width: 100%;
            }

            .page-title {
                font-size: 1.25rem;
                margin-bottom: 0.25rem;
            }

            .breadcrumb {
                margin: 0;
                font-size: 0.875rem;
            }

            .header-right {
                width: 100%;
            }

            .search-box {
                width: 100%;
                margin-right: 0;
            }

            .search-box input {
                width: 100%;
                height: 44px;
                padding: 0 1rem 0 2.75rem;
                font-size: 1rem;
                border-radius: 0.75rem;
                border: 1px solid var(--border-color);
                background: var(--card-background);
                transition: all 0.3s ease;
                /* 修复占位符文字溢出问题 */
                line-height: 44px;
                display: block;
                box-sizing: border-box;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }

            /* 占位符样式 */
            .search-box input::placeholder {
                line-height: 44px;
                color: var(--text-secondary);
                opacity: 0.8;
                /* 防止文字移动 */
                transition: none;
                position: static;
                transform: none;
                /* 确保文字不会被裁剪 */
                text-overflow: ellipsis;
                white-space: nowrap;
                overflow: hidden;
            }

            /* 搜索图标样式 */
            .search-box .mdi {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                font-size: 1.25rem;
                color: var(--text-secondary);
                pointer-events: none;
                /* 防止图标影响文字 */
                z-index: 1;
            }

            /* 搜索框焦点状态 */
            .search-box input:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
            }

            /* 确保在iOS设备上也不会出现问题 */
            .search-box input:focus::placeholder {
                opacity: 0.8;
                position: static;
                transform: none;
            }
        }

        @media (max-width: 768px) {
            /* 网格视图容器样式 */
            .grid-view {
                margin: 0 -1.5rem;
                padding: 0;
            }

            /* 网格视图列样式 */
            .grid-view .col {
                width: 100%;
                display: flex;
                justify-content: center;
                padding: 0;
            }

            /* 网格视图卡片样式 */
            .grid-view .file-card {
                width: 380px;
                margin: 0.75rem auto;
                padding: 1rem;
                background: var(--card-background);
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            /* 网格视图行样式 */
            .grid-view .row {
                margin: 0;
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            /* 网格视图列样式 */
            .grid-view .col-md-4,
            .grid-view .col-lg-3 {
                width: 100%;
                padding: 0;
            }
        }

        /* 网格视图卡片样式 */
        @media (min-width: 769px) {
            .grid-view .file-card {
                transform: translateY(-5px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .grid-view .file-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            /* 网格视图文件名样式优化 */
            .grid-view .file-card .file-name {
                font-size: 1rem;
                font-weight: 500;
                color: var(--text-primary);
                margin-bottom: 0.75rem;
                white-space: normal;
                word-wrap: break-word;
                word-break: break-all;
                line-height: 1.4;
                overflow: visible;
                text-overflow: unset;
            }
        }

        /* 移动端样式保持不变 */
        @media (max-width: 768px) {
            .grid-view .file-card {
                transform: none;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .grid-view .file-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
        }

        /* 移动端样式 */
        @media (max-width: 768px) {
            // ... existing code ...

            /* 删除确认弹窗移动端样式优化 */
            .modal-dialog {
                width: 90%;
                max-width: 360px;
                margin: 1rem auto;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) !important;
            }

            .modal-content {
                margin: 0;
                border-radius: 1rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .modal-header {
                padding: 1.25rem;
                border-bottom: 1px solid var(--border-color);
            }

            .modal-body {
                padding: 1.5rem;
                text-align: center;
            }

            .modal-footer {
                padding: 1.25rem;
                flex-direction: column;
                gap: 0.75rem;
            }

            .modal-footer .btn {
                width: 100%;
                height: 44px;
                font-size: 1rem;
                border-radius: 0.75rem;
            }
        }

        // ... existing code ...
    </style>
</head>
<body>
    <div class="admin-layout">
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
                    <h1 class="page-title">包管理</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                            <li class="breadcrumb-item active">包管理</li>
                        </ol>
                    </nav>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" id="searchInput" placeholder="搜索包名称、描述...">
                    </div>
                </div>
            </header>

            <!-- 页面内容 -->
            <div class="content-body">
                 <!-- 上传区域 -->
                 <div class="upload-zone">
                     <form action="../api/upload.php" 
                           class="dropzone" 
                           id="uploadForm">
                          <div class="dz-message">
                              <div class="upload-icon">
                                  <i class="mdi mdi-cloud-upload"></i>
                              </div>
                              <h4>拖放文件到这里或点击上传</h4>
                              <p class="text-muted">支持上传 .deb 格式的软件包文件</p>
                          </div>
                     </form>
                 </div>
                 
                 <!-- 文件列表 -->
                 <div class="card">
                     <div class="card-header">
                         <div class="d-flex align-items-center">
                             <h5 class="card-title mb-0">文件列表</h5>
                             <span class="badge bg-primary rounded-pill ms-2" id="totalItems">0</span>
                         </div>
                         <div class="list-actions">
                             <div class="btn-group">
                                 <button class="btn btn-outline-primary" onclick="refreshList(this)">
                                     <i class="mdi mdi-refresh"></i>刷新
                                 </button>
                                 <button class="btn btn-outline-danger" id="batchDeleteBtn" disabled>
                                     <i class="mdi mdi-delete"></i>批量删除
                                 </button>
                             </div>
                             <div class="btn-group">
                                 <button class="btn btn-outline-secondary active" data-view="list">
                                     <i class="mdi mdi-format-list-bulleted"></i>列表
                                 </button>
                                 <button class="btn btn-outline-secondary" data-view="grid">
                                     <i class="mdi mdi-grid"></i>网格
                                 </button>
                             </div>
                         </div>
                     </div>
                     <div class="card-body p-0">
                         <div class="file-list" id="fileList">
                             <!-- 加载状态 -->
                             <div class="loading-state" id="loadingState">
                                 <div class="loading-spinner"></div>
                             </div>
                             
                             <!-- 列表视图 -->
                             <div class="list-view active">
                                 <table class="table table-hover mb-0">
                                      <thead>
                                          <tr>
                                              <th width="40">
                                                  <div class="form-check">
                                                      <input class="form-check-input" type="checkbox" id="selectAll">
                                                  </div>
                                              </th>
                                              <th>文件名</th>
                                              <th width="100">大小</th>
                                              <th width="160">上传时间</th>
                                              <th width="160" class="text-end">操作</th>
                                          </tr>
                                      </thead>
                                      <tbody id="fileTableBody">
                                          <!-- 文件列表将通过JavaScript动态生成 -->
                                      </tbody>
                                  </table>
                             </div>
                             
                             <!-- 网格视图 -->
                             <div class="grid-view">
                                 <div class="row g-3" id="fileGridBody">
                                     <!-- 文件卡片将通过JavaScript动态生成 -->
                                 </div>
                             </div>
                             
                             <!-- 空状态 -->
                             <div class="empty-state" id="emptyState" style="display: none;">
                                 <i class="mdi mdi-package-variant"></i>
                                 <h3>暂无文件</h3>
                                 <p>拖放文件到上方区域或点击选择文件上传</p>
                             </div>
                         </div>
                     </div>
                     <div class="card-footer">
                         <nav id="pagination" aria-label="文件列表分页">
                             <div class="d-flex justify-content-between align-items-center">
                                 <div class="pagination-info">
                                     <!-- 分页信息将通过JavaScript动态更新 -->
                                 </div>
                                 <ul class="pagination mb-0">
                                     <!-- 分页按钮将通过JavaScript动态生成 -->
                                 </ul>
                             </div>
                         </nav>
                     </div>
                 </div>
             </div>
         </main>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>确定要删除这个DEB包吗？此操作无法撤销。</p>
                    <p class="text-danger mb-0">文件名：<span id="deleteFileName"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fa fa-trash me-1"></i>确认删除
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../templates/footer.php'; ?>
    
    <!-- 引入必要的JavaScript文件 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
    // Dropzone配置
    Dropzone.autoDiscover = false;
    const dropzone = new Dropzone("#uploadForm", {
        url: "../api/upload.php",
        paramName: "file",
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        params: {
            csrf_token: document.querySelector('meta[name="csrf-token"]').content
        },
        maxFilesize: <?php echo MAX_UPLOAD_SIZE / (1024 * 1024); ?>, // MB
        maxFiles: null,
        acceptedFiles: ".deb",
        previewsContainer: "#uploadForm",
        createImageThumbnails: false,
        init: function() {
            // 添加CSRF令牌到表单
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
            this.element.appendChild(csrfInput);

            this.on("addedfile", function() {
                // 始终显示滚动条
                this.element.style.overflowY = 'auto';
            });

            this.on("success", function(file, response) {
                if (response.success) {
                    showToast('文件上传成功', 'success');
                    // 延迟移除文件预览
                    setTimeout(() => {
                        this.removeFile(file);
                        loadFileList();
                    }, 2000);
                } else {
                    showToast(response.message || '上传失败', 'danger');
                    this.removeFile(file);
                }
            });

            this.on("error", function(file, errorMessage) {
                // 显示错误提示
                showToast(typeof errorMessage === 'string' ? errorMessage : '上传失败', 'danger');
                // 3秒后移除
                setTimeout(() => {
                    this.removeFile(file);
                }, 3000);
            });

            // 上传进度
            this.on("uploadprogress", function(file, progress) {
                if (file.previewElement) {
                    const progressElement = file.previewElement.querySelector('.dz-upload');
                    if (progressElement) {
                        progressElement.style.width = progress + '%';
                    }
                }
            });
        }
    });

    // 刷新列表函数
    function refreshList(button) {
        // 添加旋转动画
        const icon = button.querySelector('.mdi-refresh');
        if (icon) {
            icon.style.transition = 'transform 0.5s';
            icon.style.transform = 'rotate(360deg)';
            
            // 刷新列表
            loadFileList().then(() => {
                // 完成后重置动画
                setTimeout(() => {
                    icon.style.transform = 'rotate(0deg)';
                }, 500);
            });
        }
    }

    // 页面加载完成时自动加载文件列表
    document.addEventListener('DOMContentLoaded', function() {
        loadFileList(); // 自动加载第一页
    });

    // 加载文件列表
    function loadFileList(page = 1) {
        // 显示加载状态
        const loadingState = document.getElementById('loadingState');
        if (loadingState) loadingState.classList.add('active');

        const searchQuery = document.getElementById('searchInput').value;
        
        return fetch(`../api/list.php?page=${page}&search=${encodeURIComponent(searchQuery)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新列表视图
                    const tableBody = document.getElementById('fileTableBody');
                    if (tableBody) {
                        tableBody.innerHTML = data.files.map(file => {
                            // 解析control信息只获取架构
                            let architecture = '';
                            try {
                                const controlInfo = typeof file.control_info === 'string' 
                                    ? JSON.parse(file.control_info) 
                                    : file.control_info;
                                architecture = controlInfo?.Architecture || '未知';
                            } catch (e) {
                                architecture = '未知';
                            }

                            return `
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input file-checkbox" type="checkbox" value="${file.id}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="file-item-icon">
                                                <i class="mdi mdi-package-variant"></i>
                                            </div>
                                            <div class="file-info">
                                                <div class="file-name">${file.name}</div>
                                                <div class="file-meta">
                                                    <span><i class="mdi mdi-chip"></i>架构: ${architecture}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>${formatFileSize(file.size)}</td>
                                    <td>${formatDate(file.uploaded_at)}</td>
                                    <td>
                                        <div class="file-actions">
                                            <button class="btn btn-icon" onclick="window.location.href='../view.php?id=${file.id}'">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-icon" onclick="window.location.href='../edit.php?id=${file.id}'">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button class="btn btn-icon" onclick="window.location.href='../api/download.php?id=${file.id}'">
                                                <i class="mdi mdi-download"></i>
                                            </button>
                                            <button class="btn btn-icon text-danger" onclick="deleteFile(${file.id}, '${file.name.replace(/'/g, "\\'")}')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }

                    // 更新网格视图
                    const gridBody = document.getElementById('fileGridBody');
                    if (gridBody) {
                        gridBody.innerHTML = data.files.map(file => {
                            // 解析control信息只获取架构
                            let architecture = '';
                            try {
                                const controlInfo = typeof file.control_info === 'string' 
                                    ? JSON.parse(file.control_info) 
                                    : file.control_info;
                                architecture = controlInfo?.Architecture || '未知';
                            } catch (e) {
                                architecture = '未知';
                            }

                            return `
                                <div class="col-md-4 col-lg-3">
                                    <div class="file-card">
                                        <div class="file-card-icon">
                                            <i class="mdi mdi-package-variant"></i>
                                        </div>
                                        <div class="file-card-content">
                                            <h6 class="file-name" title="${file.name}">${file.name}</h6>
                                            <div class="file-info">
                                                <span><i class="mdi mdi-chip"></i>架构: ${architecture}</span>
                                            </div>
                                            <div class="file-actions">
                                                <button class="btn btn-icon" onclick="window.location.href='../view.php?id=${file.id}'">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button class="btn btn-icon" onclick="window.location.href='../edit.php?id=${file.id}'">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button class="btn btn-icon" onclick="window.location.href='../api/download.php?id=${file.id}'">
                                                    <i class="mdi mdi-download"></i>
                                                </button>
                                                <button class="btn btn-icon text-danger" onclick="deleteFile(${file.id}, '${file.name.replace(/'/g, "\\'")}')">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }

                    // 更新分页
                    updatePagination(data.pagination);
                    
                    // 更新总数显示
                    const totalItemsElement = document.getElementById('totalItems');
                    if (totalItemsElement) {
                        totalItemsElement.textContent = data.pagination.total_items;
                    }

                    // 检查是否显示空状态
                    const emptyState = document.getElementById('emptyState');
                    if (emptyState) {
                        emptyState.style.display = data.files.length === 0 ? 'block' : 'none';
                    }
                }
            })
            .catch(error => {
                console.error('加载文件列表失败:', error);
                showToast('加载文件列表失败', 'danger');
            })
            .finally(() => {
                // 隐藏加载状态
                if (loadingState) loadingState.classList.remove('active');
            });
    }

    // 更新分页信息的函数
    function updatePagination(pagination) {
        const paginationElement = document.querySelector('#pagination .pagination');
        const paginationInfo = document.querySelector('.pagination-info');
        
        if (!paginationElement || !paginationInfo) return;

        // 计算当前页显示的文件范围
        const startItem = (pagination.current_page - 1) * pagination.per_page + 1;
        const endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
        
        // 更新分页信息文本
        paginationInfo.innerHTML = `第 ${pagination.current_page} 页，共 ${pagination.total_items} 个文件`;

        // 生成分页按钮的HTML
        let html = '';

        // 上一页按钮
        html += `
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}" aria-label="上一页">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        // 页码按钮
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (
                i === 1 || 
                i === pagination.total_pages || 
                (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)
            ) {
                html += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (
                i === pagination.current_page - 3 || 
                i === pagination.current_page + 3
            ) {
                html += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                `;
            }
        }

        // 下一页按钮
        html += `
            <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}" aria-label="下一页">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;

        paginationElement.innerHTML = html;

        // 绑定分页事件
        paginationElement.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && page !== pagination.current_page) {
                    loadFileList(page);
                    // 平滑滚动到文件列表开始位置
                    const fileList = document.querySelector('.file-list');
                    if (fileList) {
                        fileList.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    }

    // 全选功能
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.file-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateBatchDeleteButton();
    });

    // 更新批量删除按钮状态
    function updateBatchDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
        const batchDeleteBtn = document.getElementById('batchDeleteBtn');
        if (batchDeleteBtn) {
            batchDeleteBtn.disabled = checkedBoxes.length === 0;
        }
    }

    // 监听复选框变化
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('file-checkbox')) {
            updateBatchDeleteButton();
        }
    });

    // 存储要删除的文件信息
    let deleteFileId = null;
    let deleteFileName = null;

    // 删除文件
    function deleteFile(id, name) {
        deleteFileId = id;
        deleteFileName = name;
        document.getElementById('deleteFileName').textContent = name;
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    // 确认删除
    function confirmDelete() {
        if (!deleteFileId) return;

        fetch(`../api/delete.php?id=${deleteFileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                showToast('文件删除成功', 'success');
                loadFileList();
            } else {
                showToast(data.message || '删除失败', 'danger');
            }
        })
        .catch(error => {
            showToast('删除失败：' + error.message, 'danger');
        })
        .finally(() => {
            deleteFileId = null;
            deleteFileName = null;
        });
    }

    // 格式化文件大小
    function formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    }

    // 格式化日期
    function formatDate(timestamp) {
        return new Date(timestamp * 1000).toLocaleString();
    }

    // 批量删除功能
    document.getElementById('batchDeleteBtn').addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.file-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            showToast('请选择要删除的文件', 'warning');
            return;
        }

        if (confirm('确定要删除选中的文件吗？此操作不可恢复。')) {
            const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
            batchDelete(selectedIds);
        }
    });

    // 执行批量删除
    function batchDelete(selectedIds) {
        Promise.all(selectedIds.map(id =>
            fetch(`../api/delete.php?id=${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            }).then(response => response.json())
        ))
        .then(results => {
            const successCount = results.filter(r => r.success).length;
            showToast(`成功删除 ${successCount} 个文件`, 'success');
            loadFileList(); // 刷新列表
        })
        .catch(error => {
            console.error('批量删除失败:', error);
            showToast('批量删除失败', 'danger');
        });
    }

    // 显示提示信息
    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container') || 
            document.body.appendChild(document.createElement('div'));
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, {
            animation: true,
            autohide: true,
            delay: 3000
        });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // 格式化Control信息
    function formatControlInfo(controlInfo) {
        try {
            const info = typeof controlInfo === 'string' ? JSON.parse(controlInfo) : controlInfo;
            return `架构: ${info.Architecture || '未知'}`;
        } catch (e) {
            console.error('解析Control信息失败:', e);
            return '架构: 未知';
        }
    }

    // 搜索处理函数
    let searchTimeout;
    function handleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadFileList(1); // 搜索时重置到第一页
        }, 300);
    }

    // 绑定搜索事件
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            // 输入时搜索
            searchInput.addEventListener('input', handleSearch);
            
            // 按回车搜索
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleSearch();
                }
            });
            
            // 搜索按钮点击
            const searchButton = searchInput.closest('.input-group')?.querySelector('button');
            if (searchButton) {
                searchButton.addEventListener('click', handleSearch);
            }
        }
    });

    // 修复菜单收起问题
    document.addEventListener('DOMContentLoaded', function() {
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        // 使用 Bootstrap 的 data-bs-toggle 属性自动处理折叠
        navbarToggler.setAttribute('data-bs-toggle', 'collapse');
        navbarToggler.setAttribute('data-bs-target', '#navbarNav');
        navbarCollapse.setAttribute('id', 'navbarNav');
    });

    // 视图切换功能
    document.querySelectorAll('[data-view]').forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            // 更新按钮状态
            document.querySelectorAll('[data-view]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // 切换视图
            const listView = document.querySelector('.list-view');
            const gridView = document.querySelector('.grid-view');
            
            if (view === 'list') {
                listView.classList.add('active');
                gridView.classList.remove('active');
            } else {
                gridView.classList.add('active');
                listView.classList.remove('active');
            }
            
            // 保存用户偏好
            localStorage.setItem('preferred_view', view);
        });
    });

    // 加载用户偏好的视图
    document.addEventListener('DOMContentLoaded', function() {
        const preferredView = localStorage.getItem('preferred_view') || 'list';
        document.querySelector(`[data-view="${preferredView}"]`)?.click();
    });

    // 更新统计信息
    function updateStats(pagination) {
        document.getElementById('totalItems').textContent = pagination.total_items;
        document.getElementById('startItem').textContent = 
            pagination.total_items > 0 ? (pagination.current_page - 1) * pagination.per_page + 1 : 0;
        document.getElementById('endItem').textContent = 
            Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
    }

    // 切换空状态显示
    function toggleEmptyState(isEmpty) {
        const emptyState = document.getElementById('emptyState');
        const fileList = document.querySelector('.file-list');
        
        if (isEmpty) {
            emptyState.style.display = 'block';
            fileList.classList.add('empty');
        } else {
            emptyState.style.display = 'none';
            fileList.classList.remove('empty');
        }
    }

    // 更新网格视图
    function updateGridView(files) {
        const gridBody = document.getElementById('fileGridBody');
        if (gridBody) {
            gridBody.innerHTML = files.map(file => {
                const controlInfo = JSON.parse(file.control_info || '{}');
                return `
                    <div class="col-md-4 col-lg-3">
                        <div class="file-card">
                            <div class="file-card-icon">
                                <i class="mdi mdi-package-variant"></i>
                            </div>
                            <div class="file-card-content">
                                <h6 class="file-name" title="${file.name}">${file.name}</h6>
                                <div class="file-info">
                                    <span>${formatFileSize(file.size)}</span>
                                    <span>${formatDate(file.uploaded_at)}</span>
                                </div>
                                <div class="file-actions">
                                    <button class="btn btn-icon" onclick="window.location.href='../view.php?id=${file.id}'">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-icon" onclick="window.location.href='../edit.php?id=${file.id}'">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button class="btn btn-icon" onclick="window.location.href='../api/download.php?id=${file.id}'">
                                        <i class="mdi mdi-download"></i>
                                    </button>
                                    <button class="btn btn-icon text-danger" onclick="deleteFile(${file.id}, '${file.name.replace(/'/g, "\\'")}')">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    // 移动端菜单切换
    document.getElementById('menuToggle').addEventListener('click', function() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
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

    // 点击遮罩层关闭菜单
    document.getElementById('sidebarOverlay').addEventListener('click', function() {
        const sidebar = document.querySelector('.admin-sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const icon = menuToggle.querySelector('.mdi');
        
        sidebar.classList.remove('show');
        this.classList.remove('show');
        icon.classList.remove('mdi-close');
        icon.classList.add('mdi-menu');
    });

    // 监听窗口大小变化
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuToggle = document.getElementById('menuToggle');
            const icon = menuToggle.querySelector('.mdi');
            
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            icon.classList.remove('mdi-close');
            icon.classList.add('mdi-menu');
        }
    });
    </script>
</body>
</html> 