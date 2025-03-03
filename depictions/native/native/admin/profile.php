<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查是否是管理员
if (!isAdmin()) {
    safeRedirect('../login.php');
}

// 页面标题
$pageTitle = '个人资料';
$currentPage = 'profile';

// 获取当前用户信息
$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 验证CSRF令牌
        if (!validateCsrfToken($_POST['csrf_token'])) {
            throw new Exception('无效的CSRF令牌');
        }

        $username = trim($_POST['username']);
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // 验证当前密码
        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception('当前密码不正确');
        }

        // 如果要修改密码
        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                throw new Exception('新密码两次输入不一致');
            }
            if (strlen($newPassword) < 6) {
                throw new Exception('新密码长度不能小于6位');
            }
            $password = password_hash($newPassword, PASSWORD_DEFAULT);
        } else {
            $password = $user['password'];
        }

        // 更新用户信息
        $stmt = $db->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $password, $_SESSION['user_id']]);

        $success = '个人资料更新成功';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人资料 - DEB包管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../templates/admin_nav.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <!-- 侧边栏菜单 -->
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-dashboard me-2"></i>控制台
                    </a>
                    <a href="packages.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-archive me-2"></i>包管理
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-users me-2"></i>用户管理
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-cog me-2"></i>系统设置
                    </a>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">个人资料</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">用户名</label>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">当前密码</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">新密码（不修改请留空）</label>
                                <input type="password" class="form-control" name="new_password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">确认新密码</label>
                                <input type="password" class="form-control" name="confirm_password">
                            </div>

                            <button type="submit" class="btn btn-primary">保存修改</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../templates/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 