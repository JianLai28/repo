<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    safeRedirect('../login.php');
}

try {
    // 验证请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('无效的请求方法');
    }

    // 验证CSRF令牌
    if (!validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception('无效的CSRF令牌');
    }

    // 获取设置项
    $siteName = trim($_POST['site_name'] ?? '');
    $uploadPath = trim($_POST['upload_path'] ?? '');
    $maxFileSize = filter_var($_POST['max_file_size'] ?? 500, FILTER_VALIDATE_INT);
    $allowGuestDownload = isset($_POST['allow_guest_download']) ? '1' : '0';

    // 验证数据
    if (empty($siteName)) {
        throw new Exception('站点名称不能为空');
    }

    if (empty($uploadPath)) {
        throw new Exception('上传目录不能为空');
    }

    if ($maxFileSize <= 0) {
        throw new Exception('最大文件大小必须大于0');
    }

    // 保存设置
    $settings = [
        'site_name' => $siteName,
        'upload_path' => $uploadPath,
        'max_file_size' => $maxFileSize,
        'allow_guest_download' => $allowGuestDownload
    ];

    updateSettings($settings);

    // 记录操作日志
    logOperation('update_settings', null, $settings);

    // 设置成功消息并重定向
    $_SESSION['success_message'] = '设置已保存';
    safeRedirect('settings.php');

} catch (Exception $e) {
    // 记录错误
    error_log("保存设置失败: " . $e->getMessage());
    
    // 设置错误消息并重定向
    $_SESSION['error_message'] = '保存失败: ' . $e->getMessage();
    safeRedirect('settings.php');
} 