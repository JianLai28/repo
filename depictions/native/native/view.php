<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/deb_parser.php';

// 获取包ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('HTTP/1.1 404 Not Found');
    exit('包不存在');
}

try {
    $db = getDbConnection();
    // 获取下载设置
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'allow_guest_download'");
    $allowGuestDownload = $stmt->fetchColumn() === '1';
    
    $stmt = $db->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$id]);
    $package = $stmt->fetch();

    if (!$package) {
        header('HTTP/1.1 404 Not Found');
        exit('包不存在');
    }

    // 在获取包信息之后添加函数
    function createDepictionJson($package, $updateContent = '') {
        // 获取control信息
        $controlInfo = json_decode($package['control_info'], true);
        
        // 构建JSON数据
        $depictionData = [
            "minVersion" => "0.1",
            "headerImage" => "https://lai28.988456.xyz/img/cover.png",
            "tabs" => [
                [
                    "tabname" => "详情",
                    "views" => [
                        [
                            "class" => "DepictionMarkdownView",
                            "markdown" => $_POST['description'] ?? ($controlInfo['Description'] ?? '暂无描述')
                        ],
                        [
                            "spacing" => 0,
                            "class" => "DepictionSpacerView"
                        ],
                        [
                            "class" => "DepictionSeparatorView"
                        ],
                        [
                            "title" => "版本",
                            "text" => $controlInfo['Version'] ?? '未知',
                            "class" => "DepictionTableTextView"
                        ],
                        [
                            "title" => "兼容性",
                            "text" => "IOS",
                            "class" => "DepictionTableTextView"
                        ],
                        [
                            "title" => "开发者",
                            "text" => $controlInfo['Author'] ?? '未知',
                            "class" => "DepictionTableTextView"
                        ],
                        [
                            "title" => "更新时间",
                            "text" => date('Y-m-d'),
                            "class" => "DepictionTableTextView"
                        ],
                        [
                            "text" => "点击加入QQ群聊",
                            "action" => "http://qm.qq.com/cgi-bin/qm/qr?_wv=1027&k=Sg18HyL3SI8izCTwOAw6EYIvMTD6gSea&authKey=Iw%2F6fTq7vGRHUU3LTObwGMeGSVyE0TfvA6lWaGmFPUcmGi%2F7vVarn5SuDrM5Zbog&noverify=0&group_code=630615416",
                            "class" => "DepictionButtonView"
                        ],
                        [
                            "spacing" => 0,
                            "class" => "DepictionSpacerView"
                        ],
                        [
                            "spacing" => 0,
                            "class" => "DepictionSpacerView"
                        ]
                    ],
                    "class" => "DepictionStackView"
                ],
                [
                    "tabname" => "更新",
                    "views" => [
                        [
                            "title" => $controlInfo['Version'] ?? '未知',
                            "useBoldText" => true,
                            "useSpacing" => false,
                            "useBottomMargin" => false,
                            "class" => "DepictionSubheaderView"
                        ],
                        [
                            "markdown" => $updateContent ?: "- 首次发布",
                            "useSpacing" => false,
                            "useBottomMargin" => false,
                            "class" => "DepictionMarkdownView"
                        ],
                        [
                            "spacing" => 20,
                            "class" => "DepictionSpacerView"
                        ]
                    ],
                    "class" => "DepictionStackView"
                ],
                [
                    "tabname" => "公告",
                    "views" => [
                        [
                            "class" => "DepictionMarkdownView",
                            "markdown" => "- 源内插件仅供个人学习研究\n- 严禁用于任何商业利益或其它非法活动\n- 因使用不当而造成一切后果或任何法律纠纷均由个人承担\n- 请于24小时内自行删除"
                        ]
                    ],
                    "class" => "DepictionStackView"
                ]
            ],
            "class" => "DepictionTabView"
        ];

        // 在更新选项卡中添加新的更新记录
        foreach ($depictionData['tabs'] as &$tab) {
            if ($tab['tabname'] === '更新') {
                if (!empty($updateContent)) {
                    // 创建新的更新记录
                    $updateRecord = [
                        [
                            'title' => $controlInfo['Version'] ?? '1.0.0',
                            'useBoldText' => true,
                            'useSpacing' => false,
                            'useBottomMargin' => false,
                            'class' => 'DepictionSubheaderView'
                        ],
                        [
                            'markdown' => implode("\n", array_map(function($line) {
                                return "- " . trim($line);
                            }, explode("\n", $updateContent))),
                            'useSpacing' => false,
                            'useBottomMargin' => false,
                            'class' => 'DepictionMarkdownView'
                        ],
                        [
                            'spacing' => 20,
                            'class' => 'DepictionSpacerView'
                        ]
                    ];
                    
                    // 只在添加新记录时插入到开头
                    if (!isset($_POST['update_index'])) {
                        array_splice($tab['views'], 0, 0, $updateRecord);
                    }
                }
                break;
            }
        }

        return $depictionData;
    }

    // 处理创建描述文件的请求
    if (isset($_POST['create_depiction'])) {
        try {
            $controlInfo = json_decode($package['control_info'], true);
            $packageName = $controlInfo['Package'] ?? '';
            
            if (empty($packageName)) {
                throw new Exception('无法获取包名');
            }

            // 处理更新内容：确保每行以"- "开头
            $updateContent = '';
            if (!empty($_POST['update_content'])) {
                $lines = explode("\n", trim($_POST['update_content']));
                $formattedLines = array_map(function($line) {
                    $line = trim($line);
                    // 如果行不为空且不是以"- "开头，则添加"- "
                    if (!empty($line) && substr($line, 0, 2) !== '- ') {  // 使用 substr 替代 str_starts_with
                        $line = '- ' . $line;
                    }
                    return $line;
                }, $lines);
                $updateContent = implode("\n", array_filter($formattedLines));
            }

            // 创建目录
            $depictionDir = __DIR__ . '/depictions/native/' . $packageName;
            if (!file_exists($depictionDir)) {
                if (!mkdir($depictionDir, 0755, true)) {
                    throw new Exception('创建目录失败');
                }
            }

            // 创建描述文件
            $depictionFile = $depictionDir . '/depiction.json';
            $depictionData = createDepictionJson($package, $updateContent);
            
            // 修改这里：添加 JSON_UNESCAPED_SLASHES 选项
            $jsonContent = json_encode(
                $depictionData, 
                JSON_PRETTY_PRINT | 
                JSON_UNESCAPED_UNICODE | 
                JSON_UNESCAPED_SLASHES
            );
            
            if ($jsonContent === false) {
                throw new Exception('JSON编码失败');
            }
            
            if (file_put_contents($depictionFile, $jsonContent) === false) {
                throw new Exception('保存描述文件失败');
            }

            $_SESSION['success_message'] = '描述文件创建成功！';
            header("Location: view.php?id=" . $id);
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    // 解析control信息
    $controlInfo = !empty($package['control_info']) ? json_decode($package['control_info'], true) : [];
    
    // 获取Package字段值
    $packageName = $controlInfo['Package'] ?? '';
    
    // 构建JSON描述文件路径
    $jsonPath = __DIR__ . '/depictions/native/' . $packageName . '/depiction.json';
    
    // 读取JSON描述文件
    $depictionInfo = [];
    if (!empty($packageName) && file_exists($jsonPath)) {
        $jsonContent = file_get_contents($jsonPath);
        $fullDepictionInfo = json_decode($jsonContent, true);
        
        // 提取需要的信息
        foreach ($fullDepictionInfo['tabs'] as $tab) {
            if ($tab['tabname'] === '详情') {
                foreach ($tab['views'] as $view) {
                    if ($view['class'] === 'DepictionMarkdownView') {
                        $depictionInfo['详情'] = $view['markdown'];
                    } elseif ($view['class'] === 'DepictionTableTextView') {
                        if ($view['title'] === '开发者') {
                            $depictionInfo['开发者'] = $view['text'];
                        }
                    }
                }
            } elseif ($tab['tabname'] === '更新') {
                $depictionInfo['更新'] = [];
                foreach ($tab['views'] as $view) {
                    if ($view['class'] === 'DepictionSubheaderView') {
                        // 新版本标题
                        $currentVersion = [
                            '版本' => $view['title'],
                            '内容' => ''
                        ];
                        $depictionInfo['更新'][] = $currentVersion;
                    } elseif ($view['class'] === 'DepictionMarkdownView' && !empty($depictionInfo['更新'])) {
                        // 为最后一个版本添加更新内容
                        $lastIndex = count($depictionInfo['更新']) - 1;
                        $depictionInfo['更新'][$lastIndex]['内容'] = $view['markdown'];
                    }
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("获取包信息失败: " . $e->getMessage());
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($package['name']); ?> - DEB包管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
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

        body {
            background-color: var(--background-color);
            color: var(--text-primary);
        }

        .package-content {
            background: var(--card-background);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .content-section {
            margin-bottom: 2rem;
        }

        .content-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .section-title .mdi {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .info-table th {
            padding: 0.75rem 1rem;
            background: var(--background-color);
            font-weight: 600;
            color: var(--text-secondary);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
        }

        .info-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        .description-content {
            font-size: 0.9375rem;
            line-height: 1.6;
            color: var(--text-primary);
        }

        .update-list {
            list-style: none;
            padding: 0;
            margin: 0;
            }
            
            .update-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .update-item:last-child {
            margin-bottom: 0;
            }
            
            .update-version {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .update-version span {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .update-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn-update-action.edit,
        .btn-update-action.delete {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .btn-update-action.edit .mdi,
        .btn-update-action.delete .mdi {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .update-version {
                margin-bottom: 0.75rem;
            }

            .btn-update-action.edit,
            .btn-update-action.delete {
                width: 28px;
                height: 28px;
            }

            .btn-update-action.edit .mdi,
            .btn-update-action.delete .mdi {
                font-size: 16px;
            }
        }

        .update-content {
            font-size: 0.9375rem;
            line-height: 1.6;
            color: var(--text-primary);
            white-space: pre-line;
        }

        .btn-update-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all var(--transition-speed) ease;
            color: white;
        }

        .btn-update-action.add {
            background: linear-gradient(45deg, var(--primary-color), var(--info-color));
            padding: 0.5rem 1rem;
            height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-update-action.add .mdi {
            font-size: 1.25rem;
            background: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }

        .btn-update-action.edit {
            background: linear-gradient(45deg, var(--info-color), var(--primary-color));
            padding: 0.375rem 0.75rem;
        }

        .btn-update-action.delete {
            background: linear-gradient(45deg, var(--danger-color), #ff6b6b);
            padding: 0.375rem 0.75rem;
        }

        .btn-update-action.back {
            background: linear-gradient(45deg, var(--text-secondary), var(--secondary-color));
            padding: 0.375rem 1rem;
            height: 44px;
                margin-bottom: 0;
            }
            
        .btn-update-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-update-action .mdi {
            font-size: 1.125rem;
        }

        @media (max-width: 768px) {
            .package-header {
                padding: 0.625rem 0.875rem;
                margin-bottom: 0.875rem;
            }
            
            .package-icon {
                width: 40px;
                height: 40px;
            }
            
            .package-icon .mdi {
                font-size: 1.25rem;
            }
            
            .package-name h1 {
                font-size: 1.125rem;
            }
            
            .btn-update-action.back {
                height: 40px;
            }

            .package-meta {
                gap: 0.75rem;
                margin-top: 0.75rem;
            }

            .meta-badge {
                padding: 0.375rem 0.75rem;
                font-size: 0.8125rem;
                gap: 0.375rem;
            }

            .meta-badge .mdi {
                font-size: 1rem;
            }

            .package-actions {
                flex-direction: column;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
                padding: 0.625rem 1rem;
            }

            .package-content {
                padding: 1rem;
                border-radius: 0.75rem;
                margin: 0 -0.5rem;
            }

            .content-section {
                margin-bottom: 1.5rem;
            }

            .section-title {
                font-size: 1.125rem;
                margin-bottom: 0.75rem;
            }

            .section-title .mdi {
                font-size: 1.25rem;
            }

            .info-table {
                border-radius: 0.5rem;
                overflow: hidden;
            }

            .info-table th {
                padding: 0.75rem;
                font-size: 0.875rem;
                color: var(--text-secondary);
                background: var(--background-color);
                font-weight: 500;
                width: 90px;
                min-width: 90px;
                vertical-align: top;
                line-height: 1.4;
            }

            .info-table td {
                padding: 0.75rem;
                font-size: 0.875rem;
                min-height: 48px;
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                flex-wrap: wrap;
                line-height: 1.4;
            }

            .info-table td span {
                flex: 1;
                min-width: 0;
                word-break: break-word;
                padding: 0.25rem 0;
            }

            .info-table tr {
                display: flex;
                border-bottom: 1px solid var(--border-color);
            }

            .info-table tr:last-child {
                border-bottom: none;
            }

            .info-table .btn-update-action.edit {
                flex-shrink: 0;
                margin-left: auto;
                align-self: center;
            }

            .description-content {
                font-size: 0.875rem;
            }

            .update-item {
                padding: 0.875rem;
                border-radius: 0.5rem;
                margin-bottom: 0.75rem;
            }

            .update-item .update-version {
                font-size: 0.9375rem;
            }

            .update-item .update-content {
                font-size: 0.875rem;
                line-height: 1.5;
                margin-bottom: 0.75rem;
            }

            .update-item .update-actions {
                justify-content: flex-end;
                gap: 0.5rem;
            }

            .update-item .btn-update-action {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }

            .update-item .btn-update-action .mdi {
                font-size: 1.125rem;
            }

            .btn-update-action {
                height: 40px;
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                border-radius: 0.375rem;
            }

            .btn-update-action .mdi {
                font-size: 1.125rem;
            }

            .btn-update-action.add {
                height: 40px;
                padding: 0.375rem 0.875rem;
            }

            .btn-update-action.add .mdi {
                width: 20px;
                height: 20px;
                font-size: 1.125rem;
            }

            .update-list {
                margin: 0 -0.5rem;
            }

            .update-item {
                padding: 0.875rem;
                border-radius: 0.5rem;
                margin-bottom: 0.75rem;
            }

            .update-version {
                font-size: 0.9375rem;
            }

            .update-content {
                font-size: 0.875rem;
                line-height: 1.5;
            }

            .update-actions {
                gap: 0.375rem;
            }

            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .update-version {
                display: block;
            }

            .update-content {
                margin-bottom: 0.75rem;
            }

            .update-actions {
                justify-content: flex-end;
            }

            /* 移动端模态框 - 无动画版本 */
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: transparent;
                z-index: 1050;
            }

            .modal.show {
                display: block;
            }

            .modal-dialog {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                margin: 0;
            }
            
            .modal-content {
                border: none;
                border-top-left-radius: 1rem;
                border-top-right-radius: 1rem;
                max-height: 85vh;
                overflow-y: auto;
                background: transparent;
            }

            .modal-header {
                padding: 1rem;
                border-bottom: 1px solid var(--border-color);
            }

            .modal-body {
                padding: 1rem;
            }

            .modal-footer {
                padding: 1rem;
                border-top: 1px solid var(--border-color);
            }

            /* 简化表单控件 */
            .form-control {
                -webkit-appearance: none;
                font-size: 16px;
                padding: 0.75rem;
                border-radius: 0.5rem;
                border: 1px solid var(--border-color);
                width: 100%;
                background: #fff;
            }

            textarea.form-control {
                min-height: 120px;
                resize: none;
            }

            /* 简化按钮样式 */
            .btn-update-action {
                -webkit-tap-highlight-color: transparent;
            }

            .btn-update-action.edit,
            .btn-update-action.delete {
                width: 40px;
                height: 40px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-update-action.edit .mdi,
            .btn-update-action.delete .mdi {
                font-size: 1.25rem;
            }

            .btn-update-action.edit {
                background: linear-gradient(45deg, var(--info-color), var(--primary-color));
            }

            .btn-update-action.delete {
                background: linear-gradient(45deg, var(--danger-color), #ff6b6b);
            }

            .update-actions {
                gap: 0.5rem;
                justify-content: flex-end;
            }

            /* 移动端编辑按钮样式 */
            .d-md-none .btn-update-action.edit,
            .d-md-none .btn-update-action.delete {
                width: 40px;
                height: 40px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .d-md-none .btn-update-action.edit .mdi,
            .d-md-none .btn-update-action.delete .mdi {
                font-size: 1.25rem;
            }

            .d-md-none .btn-update-action.edit {
                background: linear-gradient(45deg, var(--info-color), var(--primary-color));
            }

            .d-md-none .btn-update-action.delete {
                background: linear-gradient(45deg, var(--danger-color), #ff6b6b);
            }

            .d-md-none .update-actions {
                gap: 0.5rem;
                justify-content: flex-end;
            }

            /* 移动端编辑按钮统一样式 */
            .info-table .btn-update-action.edit {
                width: 40px;
                height: 40px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(45deg, var(--info-color), var(--primary-color));
            }

            .info-table .btn-update-action.edit .mdi {
                font-size: 1.25rem;
            }

            .info-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.5rem;
            }

            .info-table td span {
                flex: 1;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* 移动端添加更新按钮样式 */
            .btn-update-action.add {
                width: 40px;
                height: 40px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-update-action.add .mdi {
                font-size: 1.25rem;
                width: auto;
                height: auto;
                background: none;
                color: white;
            }

            /* 可编辑内容样式 */
            .editable-content {
                cursor: pointer;
                flex: 1;
                min-width: 0;
                word-break: break-word;
            }

            .editable-content:active {
                opacity: 0.7;
            }

            /* 可编辑标题样式 */
            .info-table th.editable {
                cursor: pointer;
                position: relative;
            }

            .info-table th.editable:active {
                opacity: 0.7;
            }

            .btn-update-action.danger {
                background-color: var(--danger-color);
                color: white;
                margin-right: 0.5rem;
            }

            .btn-update-action.danger:hover {
                background-color: #dc2626;
            }

            /* 移动端按钮样式 */
            @media (max-width: 767.98px) {
                .section-title .ms-auto {
                    display: flex;
                    gap: 8px;
                }
                
                /* 移动端按钮统一样式 */
                .btn-update-action.danger.d-md-none,
                .btn-update-action.add.d-md-none {
                    width: 40px;
                    height: 40px;
                    padding: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 8px;
                }

                /* 移动端图标样式 */
                .btn-update-action.danger.d-md-none i,
                .btn-update-action.add.d-md-none i {
                    font-size: 20px;
                    width: auto;
                    height: auto;
                    background: none;
                    color: white;
                }

                /* 移动端新建描述按钮样式 */
                .btn-update-action.danger.d-md-none {
                    background: var(--danger-color);
                    order: 2;
                }

                /* 移动端添加更新按钮样式 */
                .btn-update-action.add.d-md-none {
                    background: linear-gradient(45deg, var(--primary-color), var(--info-color));
                    order: 1;
                }
            }
        }

        .section-title .ms-auto {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-update-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-update-action.danger {
            background-color: var(--danger-color);
            order: 2;
        }

        .btn-update-action.add {
            background-color: var(--primary-color);
            order: 1;
        }

        /* 移动端按钮样式 */
        @media (max-width: 767.98px) {
            .section-title .ms-auto {
                display: flex;
                gap: 8px;
            }
            
            /* 移动端按钮统一样式 */
            .btn-update-action.danger.d-md-none,
            .btn-update-action.add.d-md-none {
                width: 40px;
                height: 40px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
            }

            /* 移动端图标样式 */
            .btn-update-action.danger.d-md-none i,
            .btn-update-action.add.d-md-none i {
                font-size: 20px;
                width: auto;
                height: auto;
                background: none;
                color: white;
            }

            /* 移动端新建描述按钮样式 */
            .btn-update-action.danger.d-md-none {
                background: var(--danger-color);
                order: 2;
            }

            /* 移动端添加更新按钮样式 */
            .btn-update-action.add.d-md-none {
                background: linear-gradient(45deg, var(--primary-color), var(--info-color));
                order: 1;
            }
        }

        /* 桌面端按钮基本样式 */
        .btn-update-action.danger.d-none.d-md-flex,
        .btn-update-action.add.d-none.d-md-flex {
            width: 40px;
            height: 40px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        /* 桌面端按钮图标样式 */
        .btn-update-action.danger.d-none.d-md-flex i,
        .btn-update-action.add.d-none.d-md-flex i {
            font-size: 20px;
            width: auto;
            height: auto;
            background: none;
            color: white;
        }

        /* 桌面端新建描述按钮样式 */
        .btn-update-action.danger.d-none.d-md-flex {
            background: var(--danger-color);
        }

        /* 桌面端添加更新按钮样式 */
        .btn-update-action.add.d-none.d-md-flex {
            background: linear-gradient(45deg, var(--primary-color), var(--info-color));
        }

        /* 调整按钮间距 */
        .section-title .ms-auto {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        /* 桌面端可编辑标题样式 */
        @media (min-width: 768px) {
            .info-table th.editable {
                cursor: pointer;
                position: relative;
            }

            .info-table th.editable:hover {
                color: var(--primary-color);
            }

            .info-table th.editable::after {
                content: '\F03EB';  /* MaterialDesignIcons 的编辑图标代码 (mdi-pencil) */
                font-family: 'Material Design Icons';
                font-size: 16px;
                opacity: 0;
                margin-left: 4px;
                vertical-align: middle;
                transition: opacity 0.2s ease;
            }

            .info-table th.editable:hover::after {
                opacity: 1;
            }
        }

        /* 图片预览样式 */
        .screenshots-container {
            padding: 1rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .screenshots-scroll {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
            padding-bottom: 10px; /* 为滚动条预留空间 */
        }

        .screenshots-scroll::-webkit-scrollbar {
            display: none; /* Chrome, Safari */
        }

        .screenshot-wrapper {
            flex: 0 0 auto;
            width: calc(33.333% - 0.667rem); /* 保持原来的宽度比例 */
            scroll-snap-align: start;
        }

        .screenshot-item {
            position: relative;
            padding-top: 80%; /* 修改这里：从 100% 改为 80%，进一步降低容器高度 */
            overflow: hidden;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .screenshot-item:hover {
            transform: translateY(-2px);
        }

        .screenshot-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .screenshots-container {
                margin: 0 -0.5rem;
                border-radius: 0.5rem;
                padding: 0.75rem;
            }

            .screenshots-scroll {
                gap: 0.75rem;
            }

            .screenshot-wrapper {
                width: calc(50% - 0.375rem); /* 移动端显示两列 */
            }

            .screenshot-item {
                border-radius: 0.5rem;
                padding-top: 177.77%; /* 移动端保持原来的高度比例 */
            }
        }

        #imagePreviewModal .modal-content {
            background-color: transparent;
            border: none;
        }

        #imagePreviewModal .modal-header {
            border: none;
            padding: 0;
            position: relative;
        }

        #imagePreviewModal .btn-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 1070;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: opacity 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        #imagePreviewModal .btn-close:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            #imagePreviewModal .btn-close {
                position: fixed;
                top: 12px;
                right: 12px;
                width: 24px;
                height: 24px;
            }
        }

        #imagePreviewModal .modal-body {
            background-color: transparent;
        }

        #previewImage {
            max-height: 90vh;
            width: auto;
        }

        .image-preview-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            touch-action: none;
        }

        .image-preview-wrapper {
            flex: 1;
            max-width: 100%;
            overflow: hidden;
        }

        .btn-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1;
            transition: all 0.2s;
        }

        .btn-nav:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        .btn-nav.prev-image {
            left: 20px;
        }

        .btn-nav.next-image {
            right: 20px;
        }

        .btn-nav i {
            font-size: 24px;
            color: #333;
        }

        @media (max-width: 768px) {
            .image-preview-container {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100%;
                background: transparent;
                z-index: 1060;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: env(safe-area-inset-top, 44px) env(safe-area-inset-right, 0) env(safe-area-inset-bottom, 34px) env(safe-area-inset-left, 0);
            }

            #previewImage {
                max-width: 90%;
                max-height: 88vh;
                width: auto;
                height: auto;
                object-fit: contain;
                margin: auto;
            }

            #imagePreviewModal .btn-close {
                position: fixed;
                top: calc(env(safe-area-inset-top, 0) + 12px);
                right: 12px;
                z-index: 1070;
                background-color: rgba(255, 255, 255, 0.8);
                border-radius: 50%;
                width: 32px;
                height: 32px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            #imagePreviewModal {
                transition: none !important;
            }
            
            #imagePreviewModal.fade {
                transition: none !important;
            }
            
            #imagePreviewModal.fade .modal-dialog {
                transition: none !important;
                transform: none !important;
            }
            
            #imagePreviewModal.modal.fade .modal-dialog {
                transform: none !important;
            }
        }

        /* 添加删除按钮样式 */
        .screenshot-item {
            position: relative;
        }

        .btn-delete-screenshot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.9);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: all 0.2s ease;
            padding: 0;
        }

        .btn-delete-screenshot:hover {
            background: rgba(220, 38, 38, 1);
            transform: scale(1.1);
        }

        .btn-delete-screenshot i {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .btn-delete-screenshot {
                width: 28px;
                height: 28px;
            }
            
            .btn-delete-screenshot i {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="container mt-4">
        <div class="package-content">
            <div class="content-section">
                <h2 class="section-title">
                    <i class="mdi mdi-information"></i>
                    <span>基本信息</span>
                    <button type="button" class="btn-update-action back ms-auto" onclick="window.location.href='index.php'">
                        <i class="mdi mdi-arrow-left"></i>
                        <span>返回列表</span>
                    </button>
                </h2>
                <table class="info-table">
                    <tbody>
                        <tr>
                            <th width="150" <?php if (isAdmin()): ?>class="editable" onclick="editField('name')"<?php endif; ?>>文件名</th>
                                <td class="d-flex justify-content-between align-items-center">
                                    <?php if (isAdmin()): ?>
                                    <span class="d-md-none"><?php echo htmlspecialchars($package['name']); ?></span>
                                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($package['name']); ?></span>
                                    <?php else: ?>
                                    <span><?php echo htmlspecialchars($package['name']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>包名称</th>
                            <td><?php echo htmlspecialchars($controlInfo['Package'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th>大小</th>
                                <td><?php echo formatFileSize($package['size']); ?></td>
                            </tr>
                            <tr>
                                <th>上传时间</th>
                                <td><?php echo date('Y-m-d H:i:s', $package['uploaded_at']); ?></td>
                            </tr>
                            <?php if (!empty($depictionInfo['开发者'])): ?>
                            <tr>
                                <th <?php if (isAdmin()): ?>class="editable" onclick="editField('developer')"<?php endif; ?>>开发者</th>
                                <td class="d-flex justify-content-between align-items-center">
                                    <?php if (isAdmin()): ?>
                                    <span class="d-md-none"><?php echo htmlspecialchars($depictionInfo['开发者']); ?></span>
                                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($depictionInfo['开发者']); ?></span>
                                    <?php else: ?>
                                    <span><?php echo htmlspecialchars($depictionInfo['开发者']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                    </tbody>
                        </table>
                </div>

            <div class="content-section">
                <h2 class="section-title">
                    <i class="mdi mdi-image"></i>
                    <span>预览图</span>
                        <?php if (isAdmin()): ?>
                        <div class="ms-auto">
                            <!-- 桌面端按钮 -->
                            <button type="button" class="btn-update-action danger d-none d-md-flex" data-bs-toggle="modal" data-bs-target="#createDepictionModal">
                                <i class="mdi mdi-file-plus"></i>
                            </button>
                            <?php if (file_exists($jsonPath)): ?>
                            <button type="button" class="btn-update-action add d-none d-md-flex" onclick="addUpdate()">
                        <i class="mdi mdi-plus"></i>
                    </button>
                            <?php endif; ?>
                            <?php if (!empty($packageName)): ?>
                            <button type="button" class="btn-update-action add d-none d-md-flex" onclick="uploadScreenshot()">
                                <i class="mdi mdi-image-plus"></i>
                            </button>
                            <?php endif; ?>
                            
                            <!-- 移动端按钮 -->
                            <button type="button" class="btn-update-action danger d-md-none" data-bs-toggle="modal" data-bs-target="#createDepictionModal">
                                <i class="mdi mdi-file-plus"></i>
                            </button>
                            <?php if (file_exists($jsonPath)): ?>
                            <button type="button" class="btn-update-action add d-md-none" onclick="addUpdate()">
                        <i class="mdi mdi-plus"></i>
                    </button>
                            <?php endif; ?>
                            <?php if (!empty($packageName)): ?>
                            <button type="button" class="btn-update-action add d-md-none" onclick="uploadScreenshot()">
                                <i class="mdi mdi-image-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                </h2>
                <?php if (!empty($packageName) && file_exists($jsonPath)): ?>
                    <?php
                    // 从 JSON 文件中获取图片数据
                    $screenshots = [];
                    foreach ($fullDepictionInfo['tabs'] as $tab) {
                        if ($tab['tabname'] === '详情') {
                            foreach ($tab['views'] as $view) {
                                if ($view['class'] === 'DepictionScreenshotsView') {
                                    $screenshots = $view['screenshots'];
                                    break;
                                }
                            }
                            break;
                        }
                    }
                    ?>
                    <?php if (!empty($screenshots)): ?>
                        <div class="screenshots-container">
                            <div class="screenshots-scroll">
                                <?php foreach ($screenshots as $screenshot): ?>
                                    <div class="screenshot-wrapper">
                                        <div class="screenshot-item">
                                            <?php if (isAdmin()): ?>
                                            <button type="button" class="btn-delete-screenshot" 
                                                    onclick="deleteScreenshot('<?php echo htmlspecialchars($screenshot['url']); ?>')">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                                            <?php endif; ?>
                                            <img src="<?php echo htmlspecialchars($screenshot['url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($screenshot['accessibilityText']); ?>"
                                                 class="img-fluid rounded"
                                                 onclick="showImagePreview(this.src)">
                                            </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">暂无预览图</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center text-danger">暂无描述文件</p>
                                            <?php endif; ?>
                                        </div>

            <div class="content-section">
                <h2 class="section-title">
                    <i class="mdi mdi-history"></i>
                    <span>更新历史</span>
                </h2>

                <?php if (!empty($packageName) && file_exists($jsonPath)): ?>
                    <?php if (!empty($depictionInfo['更新'])): ?>
                <ul class="update-list">
                    <?php foreach ($depictionInfo['更新'] as $index => $update): ?>
                    <li class="update-item">
                        <div class="update-version d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($update['版本']); ?></span>
                                    <?php if (isAdmin()): ?>
                            <div class="update-actions">
                            <button type="button" class="btn-update-action edit" 
                                                    onclick="editUpdate('<?php echo htmlspecialchars($update['版本']); ?>', <?php echo $index; ?>)">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                            <button type="button" class="btn-update-action delete" 
                                                    onclick="deleteUpdate(<?php echo $index; ?>)">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </div>
                                    <?php endif; ?>
                        </div>
                        <div class="update-content"><?php echo htmlspecialchars($update['内容']); ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                    <?php else: ?>
                        <p class="text-center text-muted">暂无更新记录</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center text-danger">暂无描述文件</p>
                <?php endif; ?>
                </div>

            <!-- 图片预览模态框 -->
            <div class="modal fade" id="imagePreviewModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <div class="image-preview-container">
                                <button type="button" class="btn-nav prev-image d-none d-md-flex">
                                    <i class="mdi mdi-chevron-left"></i>
                    </button>
                                <div class="image-preview-wrapper">
                                    <img src="" id="previewImage" class="img-fluid">
                                </div>
                                <button type="button" class="btn-nav next-image d-none d-md-flex">
                                    <i class="mdi mdi-chevron-right"></i>
                    </button>
                    </div>
                </div>
                    </div>
                </div>
            </div>

            <script>
            // 图片预览功能
            let currentImageIndex = 0;
            let screenshots = [];
            
            // 初始化图片数组
            function initScreenshots() {
                screenshots = Array.from(document.querySelectorAll('.screenshot-item img')).map(img => img.src);
            }
            
            // 显示指定索引的图片
            function showImageAtIndex(index) {
                if (screenshots.length === 0) return;
                
                currentImageIndex = (index + screenshots.length) % screenshots.length;
                const previewImage = document.getElementById('previewImage');
                previewImage.src = screenshots[currentImageIndex];
            }
            
            // 显示图片预览
            function showImagePreview(src) {
                initScreenshots();
                currentImageIndex = screenshots.indexOf(src);
                showImageAtIndex(currentImageIndex);
                
                new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
            }
            
            // 切换到上一张图片
            function showPrevImage() {
                showImageAtIndex(currentImageIndex - 1);
            }
            
            // 切换到下一张图片
            function showNextImage() {
                showImageAtIndex(currentImageIndex + 1);
            }
            
            // 初始化移动端手势
            function initTouchGestures() {
                const container = document.querySelector('.image-preview-container');
                let startX = 0;
                let currentX = 0;
                
                container.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    currentX = startX;
                });
                
                container.addEventListener('touchmove', (e) => {
                    currentX = e.touches[0].clientX;
                });
                
                container.addEventListener('touchend', () => {
                    const diff = currentX - startX;
                    const threshold = 50; // 滑动阈值
                    
                    if (Math.abs(diff) >= threshold) {
                        if (diff > 0) {
                            showPrevImage();
                        } else {
                            showNextImage();
                        }
                    }
                });
            }
            
            // 初始化事件监听
            document.addEventListener('DOMContentLoaded', function() {
                // 初始化导航按钮事件
                const prevButton = document.querySelector('.prev-image');
                const nextButton = document.querySelector('.next-image');
                
                if (prevButton) prevButton.addEventListener('click', showPrevImage);
                if (nextButton) nextButton.addEventListener('click', showNextImage);
                
                // 初始化键盘事件
                document.addEventListener('keydown', (e) => {
                    if (!document.getElementById('imagePreviewModal').classList.contains('show')) return;
                    
                    if (e.key === 'ArrowLeft') {
                        showPrevImage();
                    } else if (e.key === 'ArrowRight') {
                        showNextImage();
                    }
                });
                
                // 初始化移动端手势
                initTouchGestures();
            });
            </script>
                    </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isAdmin()): ?>
    <!-- 更新记录编辑模态框 -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加更新记录</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm">
                        <input type="hidden" name="package_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($packageName); ?>">
                        <div class="mb-3">
                            <label class="form-label">版本号</label>
                            <input type="text" class="form-control" name="version" required>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0">更新日期</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="skip_date_update" id="skipDateUpdate">
                                    <label class="form-check-label" for="skipDateUpdate">不修改更新日期</label>
                                </div>
                            </div>
                            <input type="text" class="form-control" name="update_date" placeholder="YYYY/MM/DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">更新内容</label>
                            <textarea class="form-control" name="content" rows="5" placeholder="每行一个更新内容，会自动添加'-'前缀" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitUpdate()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 编辑更新记录模态框 -->
    <div class="modal fade" id="editUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑更新记录</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUpdateForm">
                        <input type="hidden" name="package_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($packageName); ?>">
                        <input type="hidden" name="update_index" value="">
                        <div class="mb-3">
                            <label class="form-label">版本号</label>
                            <input type="text" class="form-control" name="version" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">更新内容</label>
                            <textarea class="form-control" name="content" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditUpdate()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 字段编辑模态框 -->
    <div class="modal fade" id="editFieldModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑信息</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editFieldForm">
                        <input type="hidden" name="package_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($packageName); ?>">
                        <input type="hidden" name="field_type" value="">
                        <div class="mb-3">
                            <label class="form-label" id="fieldLabel"></label>
                            <input type="text" class="form-control" name="field_value" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitFieldEdit()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 添加模态框 -->
    <div class="modal fade" id="createDepictionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">创建描述文件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createDepictionForm" method="post">
                        <input type="hidden" name="create_depiction" value="1">
                        <input type="hidden" name="package_id" value="<?php echo $id; ?>">
                        
                        <!-- 添加描述内容编辑区域 -->
                        <div class="mb-3">
                            <label for="description" class="form-label">描述内容</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($controlInfo['Description']) ? htmlspecialchars($controlInfo['Description']) : ''; ?></textarea>
                            <div class="form-text">您可以编辑此处的描述内容，支持markdown格式</div>
                        </div>

                        <!-- 更新内容区域 -->
                        <div class="mb-3">
                            <label for="update_content" class="form-label">更新内容</label>
                            <textarea class="form-control" id="update_content" name="update_content" rows="4" placeholder="每行一个更新内容，会自动添加列表符号"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('createDepictionForm').submit()">创建</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 移动端检测
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    
    // 定义管理员状态
    const isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;
    
    // 缓存DOM元素
    const elements = {
        updateForm: document.getElementById('updateForm'),
        editUpdateForm: document.getElementById('editUpdateForm'),
        editFieldForm: document.getElementById('editFieldForm'),
        updateModal: document.getElementById('updateModal'),
        editUpdateModal: document.getElementById('editUpdateModal'),
        editFieldModal: document.getElementById('editFieldModal')
    };
    
    // 初始化移动端模态框
    if (isMobile) {
        Object.keys(elements).forEach(key => {
            if (key.includes('Modal') && elements[key]) {
                const modalElement = elements[key];
                
                // 简化显示/隐藏方法
                modalElement._show = function() {
                    modalElement.style.display = 'block';
                    modalElement.classList.add('show');
                };
                
                modalElement._hide = function() {
                    modalElement.classList.remove('show');
                    modalElement.style.display = 'none';
                };
                
                // 点击背景关闭
                modalElement.addEventListener('click', (e) => {
                    if (e.target === modalElement) {
                        modalElement._hide();
                    }
                });

                // 关闭按钮
                const closeBtn = modalElement.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => modalElement._hide());
                }

                // 取消按钮
                const cancelBtn = modalElement.querySelector('.btn-secondary');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => modalElement._hide());
                }
            }
        });
    }

    // 添加更新记录
    function addUpdate() {
        if (!elements.updateForm) return;
        
        elements.updateForm.reset();
        
        const now = new Date();
        const dateStr = `${now.getFullYear()}/${String(now.getMonth() + 1).padStart(2, '0')}/${String(now.getDate()).padStart(2, '0')}`;
        
        const dateInput = elements.updateForm.querySelector('[name="update_date"]');
        if (dateInput) {
            dateInput.value = dateStr;
        }

        if (isMobile) {
            elements.updateModal._show();
        } else {
            new bootstrap.Modal(elements.updateModal).show();
        }
    }

    // 编辑更新记录
    function editUpdate(version, index) {
        if (!elements.editUpdateForm) return;
        
        const form = elements.editUpdateForm;
        form.querySelector('[name="version"]').value = version;
        form.querySelector('[name="update_index"]').value = index;
        
        // 获取更新内容并保持原有格式
        const updateItems = document.querySelectorAll('.update-item');
        if (updateItems[index]) {
            const contentElement = updateItems[index].querySelector('.update-content');
        if (contentElement) {
                // 保持原有的换行格式
                const content = contentElement.textContent
                .split('\n')
                    .map(line => line.trim().replace(/^- /, ''))
                    .filter(line => line)
                .join('\n');
            form.querySelector('[name="content"]').value = content;
            }
        }
        
        if (isMobile) {
            elements.editUpdateModal._show();
        } else {
            new bootstrap.Modal(elements.editUpdateModal).show();
        }
    }

    // 修改删除更新记录函数
    function deleteUpdate(index) {
        if (!confirm('确定要删除这条更新记录吗？此操作无法撤销。')) {
            return;
        }

        const packageName = '<?php echo htmlspecialchars($packageName); ?>';
        
        fetch('api/delete_update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `package_name=${encodeURIComponent(packageName)}&update_index=${index}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 找到要删除的更新记录元素
                const updateItems = document.querySelectorAll('.update-item');
                const itemToRemove = updateItems[index];
                
                if (itemToRemove) {
                    // 删除元素
                    itemToRemove.remove();
                    
                    // 检查是否还有其他更新记录
                    const remainingItems = document.querySelectorAll('.update-item');
                    if (remainingItems.length === 0) {
                        // 如果没有更新记录了，显示"暂无更新记录"提示
                        const updateList = document.querySelector('.update-list');
                        if (updateList) {
                            updateList.innerHTML = '<p class="text-center text-muted">暂无更新记录</p>';
                        }
        } else {
                        // 更新剩余记录的索引
                        updateIndexes();
                    }
                }
                
                showToast('更新记录删除成功', 'success');
            } else {
                showToast(data.message || '删除失败', 'danger');
            }
        })
        .catch(error => {
            showToast('删除失败：' + error.message, 'danger');
        });
    }

    // 编辑字段
    function editField(type) {
        if (!elements.editFieldForm) return;
        
        const form = elements.editFieldForm;
        form.querySelector('[name="field_type"]').value = type;
        
        const label = elements.editFieldModal.querySelector('#fieldLabel');
        const input = form.querySelector('[name="field_value"]');
        
        if (type === 'name') {
            label.textContent = '文件名';
            // 使用当前显示的文件名
            const fileNameElement = document.querySelector('td span:first-child');
            input.value = fileNameElement ? fileNameElement.textContent : '';
        } else if (type === 'developer') {
            label.textContent = '开发者';
            // 使用当前显示的开发者名
            const developerElement = document.querySelector('tr:last-child td span:first-child');
            input.value = developerElement ? developerElement.textContent : '';
        }
        
        if (isMobile) {
            elements.editFieldModal._show();
        } else {
            new bootstrap.Modal(elements.editFieldModal).show();
        }
    }

    // 提交字段编辑
    function submitFieldEdit() {
        const form = document.getElementById('editFieldForm');
        const formData = new FormData(form);
        const fieldType = formData.get('field_type');
        const fieldValue = formData.get('field_value');
        
        fetch('api/edit_field.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isMobile) {
                    elements.editFieldModal._hide();
                } else {
                    bootstrap.Modal.getInstance(elements.editFieldModal).hide();
                }
                
                // 更新对应字段的显示内容
                if (fieldType === 'name') {
                    // 更新文件名显示元素（只更新第一行的元素）
                    document.querySelectorAll('tr:first-child td span[class*="d-"]').forEach(span => {
                        if (span.textContent.trim() !== '') {
                            span.textContent = fieldValue;
                        }
                    });
                } else if (fieldType === 'developer') {
                    // 更新开发者显示元素（只更新最后一行的元素）
                    document.querySelectorAll('tr:last-child td span[class*="d-"]').forEach(span => {
                        if (span.textContent.trim() !== '') {
                            span.textContent = fieldValue;
                        }
                    });
                }
                
                showToast('保存成功', 'success');
            } else {
                showToast(data.message || '保存失败', 'danger');
            }
        })
        .catch(error => {
            showToast('保存失败：' + error.message, 'danger');
        });
    }

        // 提交编辑更新
        function submitEditUpdate() {
            const form = document.getElementById('editUpdateForm');
            const formData = new FormData(form);
            
        const version = formData.get('version');
        const content = formData.get('content');
            const index = formData.get('update_index');
            const packageName = formData.get('package_name');
        
            const updatedData = {
            title: version,
            useBoldText: true,
            useSpacing: false,
            useBottomMargin: false,
            class: "DepictionSubheaderView"
        };
        
            const updatedContent = {
                markdown: content.split('\n')
                    .map(line => line.trim())
                    .filter(line => line)
                    .map(line => line.startsWith('- ') ? line : `- ${line}`)
                    .join('\n'),
            useSpacing: false,
            useBottomMargin: false,
            class: "DepictionMarkdownView"
        };
        
            const requestData = {
                package_name: packageName,
                update_index: index,
                version: version,
                content: content,
                update_data: JSON.stringify([updatedData, updatedContent])
            };
            
            fetch('api/edit_update.php', {
            method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    if (isMobile) {
                        elements.editUpdateModal._hide();
                    } else {
                        bootstrap.Modal.getInstance(elements.editUpdateModal).hide();
                    }
                    
                    // 直接更新DOM，不重新创建元素
                    const updateItems = document.querySelectorAll('.update-item');
                    if (updateItems[index]) {
                        const item = updateItems[index];
                        const versionSpan = item.querySelector('.update-version span');
                        const contentDiv = item.querySelector('.update-content');
                        
                        if (versionSpan) {
                            versionSpan.textContent = version;
                        }
                        if (contentDiv) {
                            contentDiv.textContent = updatedContent.markdown;
                        }
                    }
                    
                    showToast('更新记录已保存', 'success');
            } else {
                showToast(data.message || '保存失败', 'danger');
            }
        })
        .catch(error => {
            showToast('保存失败：' + error.message, 'danger');
        });
    }

    // 存储当前操作的模态框实例
    let currentModal = null;

    // 初始化所有模态框
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化所有模态框
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modalElement => {
            const modal = new bootstrap.Modal(modalElement);
            
            // 存储模态框实例到元素上
            modalElement.bootstrapModal = modal;
            
            // 监听模态框关闭事件
            modalElement.addEventListener('hidden.bs.modal', function() {
                currentModal = null;
            });
        });
    });

    // 显示提示信息
    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container') || 
            document.body.appendChild(document.createElement('div'));
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('data-bs-delay', '1000'); // 设置显示时间为1秒
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, {
            delay: 1000 // 设置显示时间为1秒
        });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
    
    // 提交更新记录
    function submitUpdate() {
        const form = elements.updateForm;
        if (!form) return;

        const formData = new FormData(form);
        const version = formData.get('version');
        const content = formData.get('content');
        const skipDateUpdate = formData.get('skip_date_update') === 'on';
        
        if (!version || !content) {
            showToast('请填写完整信息', 'danger');
            return;
        }
        
        // 构建更新记录数据
        const updateData = [
            {
            title: version,
            useBoldText: true,
            useSpacing: false,
            useBottomMargin: false,
            class: "DepictionSubheaderView"
            },
            {
                markdown: content.split('\n')
                    .map(line => line.trim())
                    .filter(line => line)
                    .map(line => line.startsWith('- ') ? line : `- ${line}`)
                    .join('\n'),
            useSpacing: false,
            useBottomMargin: false,
            class: "DepictionMarkdownView"
            }
        ];

        // 创建新的 FormData 对象
        const requestData = new FormData();
        requestData.append('package_id', formData.get('package_id'));
        requestData.append('package_name', formData.get('package_name'));
        requestData.append('version', version);
        requestData.append('content', content);
        requestData.append('update_data', JSON.stringify(updateData));
        requestData.append('skip_date_update', skipDateUpdate ? '1' : '0');
        
        // 只有在不跳过更新日期时才添加日期
        if (!skipDateUpdate) {
            requestData.append('update_date', formData.get('update_date'));
        }
        
        fetch('api/update_depiction.php', {
            method: 'POST',
            body: requestData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isMobile) {
                    elements.updateModal._hide();
            } else {
                    bootstrap.Modal.getInstance(elements.updateModal).hide();
                }
                
                // 获取更新历史部分
                const updateHistorySection = document.querySelector('.content-section h2 i.mdi-history').closest('.content-section');
                if (!updateHistorySection) {
                    showToast('无法找到更新历史部分', 'danger');
                    return;
                }

                // 获取或创建更新列表
                let updateList = updateHistorySection.querySelector('.update-list');
                const noUpdateText = updateHistorySection.querySelector('.text-center');
                
                // 如果没有更新列表，创建一个新的
                if (!updateList) {
                    // 移除"暂无更新记录"或"暂无描述文件"提示
                    if (noUpdateText) {
                        noUpdateText.remove();
                    }
                    
                    updateList = document.createElement('ul');
                    updateList.className = 'update-list';
                    updateHistorySection.appendChild(updateList);
                }
                
                // 创建新的更新记录元素
                const li = document.createElement('li');
                li.className = 'update-item';
                
                const versionDiv = document.createElement('div');
                versionDiv.className = 'update-version d-flex justify-content-between align-items-center';
                
                const versionSpan = document.createElement('span');
                versionSpan.textContent = version;
                versionDiv.appendChild(versionSpan);
                
                if (isAdmin) {
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'update-actions';
                    actionsDiv.innerHTML = `
                        <button type="button" class="btn-update-action edit" onclick="editUpdate('${version}', 0)">
                            <i class="mdi mdi-pencil"></i>
                        </button>
                        <button type="button" class="btn-update-action delete" onclick="deleteUpdate(0)">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    `;
                    versionDiv.appendChild(actionsDiv);
                }
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'update-content';
                contentDiv.textContent = updateData[1].markdown;
                
                li.appendChild(versionDiv);
                li.appendChild(contentDiv);
                
                // 插入到列表开头
                if (updateList.firstChild) {
                    updateList.insertBefore(li, updateList.firstChild);
                } else {
                    updateList.appendChild(li);
                }
                
                // 更新所有更新记录的索引
                updateIndexes();
                
                // 重置表单
                form.reset();
                
                showToast('更新记录添加成功', 'success');
            } else {
                showToast(data.message || '保存失败', 'danger');
            }
        })
        .catch(error => {
            showToast('保存失败：' + error.message, 'danger');
        });
    }

    // 添加更新索引函数
    function updateIndexes() {
        const updateItems = document.querySelectorAll('.update-list .update-item');
        updateItems.forEach((item, index) => {
            const editButton = item.querySelector('.btn-update-action.edit');
            const deleteButton = item.querySelector('.btn-update-action.delete');
            const version = item.querySelector('.update-version span').textContent;
            
            if (editButton) {
                editButton.setAttribute('onclick', `editUpdate('${version}', ${index})`);
            }
            if (deleteButton) {
                deleteButton.setAttribute('onclick', `deleteUpdate(${index})`);
            }
        });
    }

    // 添加上传预览图功能
    function uploadScreenshot() {
        // 创建文件输入元素
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true; // 启用多选
        input.style.display = 'none';
        document.body.appendChild(input);

        // 监听文件选择
        input.addEventListener('change', async function() {
            if (!this.files || this.files.length === 0) {
                return;
            }

            const files = Array.from(this.files);
            let successCount = 0;
            let failCount = 0;

            // 显示开始上传提示
            showToast(`开始上传 ${files.length} 张图片...`, 'info');

            // 依次上传每个文件
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const formData = new FormData();
                formData.append('screenshot', file);
                formData.append('package_name', '<?php echo htmlspecialchars($packageName); ?>');

                try {
                    // 显示当前上传进度
                    showToast(`正在上传第 ${i + 1}/${files.length} 张图片...`, 'info');

                    const response = await fetch('api/upload_screenshot.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        successCount++;
                        showToast(`第 ${i + 1} 张图片上传成功`, 'success');
            } else {
                        failCount++;
                        showToast(`第 ${i + 1} 张图片上传失败: ${data.message}`, 'danger');
                        console.error(`图片 ${file.name} 上传失败: ${data.message}`);
                    }
                } catch (error) {
                    failCount++;
                    showToast(`第 ${i + 1} 张图片上传出错: ${error.message}`, 'danger');
                    console.error(`图片 ${file.name} 上传出错: ${error.message}`);
                }

                // 等待一小段时间，让用户能看到每张图片的上传结果
                await new Promise(resolve => setTimeout(resolve, 800));
            }

            // 显示最终上传结果
            if (successCount > 0) {
                showToast(`成功上传 ${successCount} 张图片${failCount > 0 ? `，${failCount} 张上传失败` : ''}`, 
                    failCount > 0 ? 'warning' : 'success');
                // 延迟一秒后刷新页面，让用户能看到提示
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('所有图片上传失败', 'danger');
            }

            // 清理临时元素
            document.body.removeChild(input);
        });

        // 触发文件选择
        input.click();
    }

    // 修改删除预览图功能
    function deleteScreenshot(imageUrl) {
        if (!confirm('确定要删除这张预览图吗？此操作无法撤销。')) {
            return;
        }

        const formData = new FormData();
        formData.append('package_name', '<?php echo htmlspecialchars($packageName); ?>');
        formData.append('image_url', imageUrl);

        fetch('api/delete_screenshot.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('删除成功', 'success');
                // 找到并移除对应的预览图元素
                const screenshots = document.querySelectorAll('.screenshot-item img');
                for (let img of screenshots) {
                    if (img.src === imageUrl) {
                        const wrapper = img.closest('.screenshot-wrapper');
                        if (wrapper) {
                            wrapper.remove();
                            
                            // 检查是否还有其他预览图
                            const remainingScreenshots = document.querySelectorAll('.screenshot-wrapper');
                            if (remainingScreenshots.length === 0) {
                                const screenshotsContainer = document.querySelector('.screenshots-container');
                                if (screenshotsContainer) {
                                    // 替换整个 screenshots-container 为提示文本
                                    screenshotsContainer.outerHTML = '<p class="text-center text-muted">暂无预览图</p>';
                                }
                            }
                        }
                        break;
                    }
                }
            } else {
                showToast(data.message || '删除失败', 'danger');
            }
        })
        .catch(error => {
            showToast('删除失败：' + error.message, 'danger');
        });
    }
    </script>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('<?php echo addslashes($_SESSION['success_message']); ?>', 'success');
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</body>
</html> 