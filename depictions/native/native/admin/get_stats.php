<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => '没有权限']);
    exit;
}

try {
    // 获取所有统计数据
    $stats = [
        'packages' => [
            'total' => getPackageCount(),
            'downloads' => getDownloadCount(),
            'size' => getTotalSize(),
            'formatted_size' => formatFileSize(getTotalSize())
        ],
        'system' => [
            'cpu' => getCpuUsage(),
            'memory' => getMemoryUsage(),
            'disk' => getDiskUsage()
        ],
        'activities' => getRecentActivities()
    ];

    // 验证数据有效性
    foreach ($stats['packages'] as $key => $value) {
        if (!is_numeric($value) && $key !== 'formatted_size') {
            $stats['packages'][$key] = 0;
        }
    }

    foreach ($stats['system'] as $key => $value) {
        if (!is_numeric($value)) {
            $stats['system'][$key] = 0;
        }
    }

    // 返回JSON数据
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => '获取统计数据失败',
        'error' => $e->getMessage()
    ]);
} 