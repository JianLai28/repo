<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    safeRedirect('../login.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("开始处理设置表单提交");
    try {
        // 验证CSRF令牌
        error_log("CSRF令牌: " . ($_POST['csrf_token'] ?? 'null'));
        if (!validateCsrfToken($_POST['csrf_token'])) {
            error_log("CSRF令牌验证失败");
            throw new Exception('无效的CSRF令牌');
        }

        // 获取设置项
        error_log("提交的设置数据: " . print_r($_POST, true));
        $siteName = trim($_POST['site_name'] ?? '');
        $uploadPath = trim($_POST['upload_path'] ?? '');
        $maxFileSize = filter_var($_POST['max_file_size'] ?? 500, FILTER_VALIDATE_INT);
        $allowGuestDownload = isset($_POST['allow_guest_download']) ? '1' : '0';

        // 验证数据
        if (empty($siteName)) {
            error_log("站点名称为空");
            throw new Exception('站点名称不能为空');
        }

        if (empty($uploadPath)) {
            error_log("上传目录为空");
            throw new Exception('上传目录不能为空');
        }

        if ($maxFileSize <= 0) {
            error_log("无效的文件大小: " . $maxFileSize);
            throw new Exception('最大文件大小必须大于0');
        }

        // 保存设置
        $settings = [
            'site_name' => $siteName,
            'upload_path' => $uploadPath,
            'max_file_size' => $maxFileSize,
            'allow_guest_download' => $allowGuestDownload
        ];

        error_log("准备保存设置: " . print_r($settings, true));
        updateSettings($settings);
        error_log("设置保存成功");

        // 记录操作日志
        logOperation('update_settings', null, $settings);

        $_SESSION['success_message'] = '设置已保存';
        error_log("准备重定向到settings.php");
        safeRedirect('./settings.php');

    } catch (Exception $e) {
        error_log("保存设置失败: " . $e->getMessage());
        error_log("错误堆栈: " . $e->getTraceAsString());
        $_SESSION['error_message'] = '保存失败: ' . $e->getMessage();
        safeRedirect('./settings.php');
    }
}

// 获取当前设置
$db = getDbConnection();
$stmt = $db->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 页面标题
$pageTitle = '系统设置';
$currentPage = 'settings';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - DEB包管理系统</title>
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

        /* 设置分组样式 */
        .settings-section {
            background: var(--card-background);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            animation: slideIn 0.3s ease-out forwards;
            opacity: 0;
        }

        .settings-section:nth-child(1) { animation-delay: 0.1s; }
        .settings-section:nth-child(2) { animation-delay: 0.2s; }
        .settings-section:nth-child(3) { animation-delay: 0.3s; }

        .settings-section:hover {
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.1);
            transform: translateY(-2px);
        }

        .settings-title {
            color: var(--text-primary);
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .settings-title .mdi {
            font-size: 1.25rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }

        .settings-section:hover .settings-title .mdi {
            transform: rotate(15deg);
        }

        /* 表单控件样式 */
        .form-control,
        .input-group-text {
            border-color: var(--border-color);
            transition: all 0.3s ease;
            height: 2.75rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            background-color: var(--background-color);
            display: flex;
            align-items: center;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: stretch;
            width: 100%;
        }

        .input-group:focus-within {
            border-radius: 0.5rem;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }

        .input-group .input-group-text {
            background-color: var(--background-color);
            border: 1px solid var(--border-color);
            padding: 0.625rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 3.5rem;
            color: var(--text-secondary);
            position: relative;
            z-index: 2;
        }

        .input-group .input-group-text:first-child {
            margin-right: -1px;
        }

        .input-group .input-group-text:last-child {
            margin-left: -1px;
        }

        .input-group .form-control {
            border: 1px solid var(--border-color);
            flex: 1;
            min-width: 0;
            width: 1%;
            background-color: var(--background-color);
            position: relative;
            z-index: 1;
        }

        .input-group .form-control:focus {
            z-index: 3;
        }

        .input-group:not(:has(.form-control:focus)) .input-group-text:hover {
            z-index: 3;
        }

        .input-group > :first-child {
            border-top-left-radius: 0.5rem !important;
            border-bottom-left-radius: 0.5rem !important;
        }

        .input-group > :last-child {
            border-top-right-radius: 0.5rem !important;
            border-bottom-right-radius: 0.5rem !important;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .input-group:focus-within .form-control {
            border-color: var(--primary-color);
            box-shadow: none;
            background-color: var(--card-background);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: none;
            background-color: var(--card-background);
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .text-muted {
            font-size: 0.75rem;
            color: var(--text-secondary) !important;
        }

        /* 开关样式 */
        .form-switch {
            padding-left: 3.5em;
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-left: -3.5em;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 1%29'/%3e%3c/svg%3e");
            background-color: var(--text-secondary);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-switch .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }

        .form-switch .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }

        /* 按钮样式 */
        .btn {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.25);
        }

        .btn .mdi {
            font-size: 1.125rem;
            transition: transform 0.3s ease;
        }

        .btn:active {
            transform: translateY(0);
        }

        /* 卡片样式 */
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-radius: 0.75rem;
            background: var(--card-background);
            animation: fadeIn 0.3s ease-out;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title .mdi {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* 提示框样式 */
        .alert {
            border: none;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
        }

        .alert .mdi {
            font-size: 1.25rem;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .btn-close {
            margin-left: auto;
        }

        /* 管理后台布局 */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background-color: var(--background-color);
        }

        /* 主要内容区 */
        .admin-main {
            flex: 1;
            padding: 2rem;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background-color: var(--background-color);
        }

        /* 侧边栏样式 */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--card-background);
            box-shadow: 1px 0 10px rgba(0,0,0,0.05);
            z-index: 1030;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar-header {
            height: var(--header-height);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            height: var(--header-height);
            width: 100%;
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

        /* 动画和过渡效果 */
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* 响应式调整 */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-main {
                margin-left: 0;
                width: 100%;
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- 侧边栏导航 -->
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

        <!-- 主要内容区 -->
        <div class="admin-main">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="mdi mdi-cog-outline"></i>
                                系统设置
                            </h5>
                            <button type="button" class="btn btn-primary" onclick="saveSettings(this)">
                                <i class="mdi mdi-content-save-outline"></i>
                                保存设置
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="settings-alert"></div>
                            
                            <form method="post" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <!-- 基本设置 -->
                                <div class="settings-section">
                                    <h6 class="settings-title">
                                        <i class="mdi mdi-tune"></i>
                                        基本设置
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            站点名称
                                            <small class="text-muted">
                                                <i class="mdi mdi-information-outline"></i>
                                                显示在浏览器标题栏
                                            </small>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="mdi mdi-web"></i>
                                            </span>
                                            <input type="text" class="form-control" name="site_name" 
                                                   value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="invalid-feedback">请输入站点名称</div>
                                    </div>
                                </div>

                                <!-- 上传设置 -->
                                <div class="settings-section">
                                    <h6 class="settings-title">
                                        <i class="mdi mdi-cloud-upload-outline"></i>
                                        上传设置
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            上传目录
                                            <small class="text-muted">
                                                <i class="mdi mdi-information-outline"></i>
                                                存储DEB文件的目录路径
                                            </small>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="mdi mdi-folder-outline"></i>
                                            </span>
                                            <input type="text" class="form-control" name="upload_path" 
                                                   value="<?php echo htmlspecialchars($settings['upload_path'] ?? ''); ?>" required>
                                        </div>
                                        <div class="invalid-feedback">请输入上传目录路径</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            最大文件大小
                                            <small class="text-muted">
                                                <i class="mdi mdi-information-outline"></i>
                                                单个文件的最大上传限制(MB)
                                            </small>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="mdi mdi-file-outline"></i>
                                            </span>
                                            <input type="number" class="form-control" name="max_file_size" 
                                                   value="<?php echo htmlspecialchars($settings['max_file_size'] ?? '500'); ?>" 
                                                   required min="1">
                                            <span class="input-group-text">MB</span>
                                        </div>
                                        <div class="invalid-feedback">请输入大于0的数值</div>
                                    </div>
                                </div>

                                <!-- 访问控制 -->
                                <div class="settings-section">
                                    <h6 class="settings-title">
                                        <i class="mdi mdi-shield-outline"></i>
                                        访问控制
                                    </h6>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" name="allow_guest_download" id="allowGuestDownload"
                                                   value="1" <?php echo ($settings['allow_guest_download'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="allowGuestDownload">
                                                允许未登录用户下载文件
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="mdi mdi-information-outline me-1"></i>
                                            启用后，未登录用户可以直接下载DEB文件
                                        </small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../templates/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 表单验证
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        })();

        // 保存设置
        function saveSettings(button) {
            const form = document.querySelector('form');
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            // 禁用按钮并显示加载状态
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>保存中...';

            // 获取表单数据
            const formData = new FormData(form);

            // 发送请求
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // 显示成功消息
                const alertDiv = document.querySelector('.settings-alert');
                alertDiv.innerHTML = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                    '<i class="mdi mdi-check-circle me-2"></i>设置已保存' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';

                // 恢复按钮状态
                button.disabled = false;
                button.innerHTML = originalText;

                // 3秒后自动隐藏提示
                setTimeout(() => {
                    const alert = alertDiv.querySelector('.alert');
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 3000);
            })
            .catch(error => {
                // 显示错误消息
                const alertDiv = document.querySelector('.settings-alert');
                alertDiv.innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<i class="mdi mdi-alert me-2"></i>保存失败：' + error.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';

                // 恢复按钮状态
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }
    </script>
</body>
</html> 