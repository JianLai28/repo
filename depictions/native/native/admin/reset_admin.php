<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    safeRedirect('../login.php');
}

try {
    $db = getDbConnection();
    
    // 重置管理员密码
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE is_admin = 1");
    $stmt->execute([$password]);
    
    echo "管理员密码已重置为: admin123";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage();
} 