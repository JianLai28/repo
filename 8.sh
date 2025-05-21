#!/bin/bash

# 定义本地仓库路径
LOCAL_REPO_PATH="/var/mobile/Containers/Shared/AppGroup/.jbroot-198A866B09619086/var/mobile/repo"

# 定义远程仓库地址（SSH格式）
REMOTE_REPO_URL="git@github.com:JianLai28/repo.git"  # 替换为你的远程仓库地址

# 切换到本地仓库目录
cd "$LOCAL_REPO_PATH" || { echo "本地仓库路径不存在"; exit 1; }

# 检查是否是git仓库
if [ ! -d ".git" ]; then
    echo "本地路径不是git仓库，正在初始化..."
    git init
fi

# 添加所有更改到暂存区
git add .

# 提交更改
echo "正在提交更改..."
git commit -m "Auto commit: $(date)"

# 添加远程仓库
git remote add origin "$REMOTE_REPO_URL" 2>/dev/null || git remote set-url origin "$REMOTE_REPO_URL"

# 推送到远程仓库
echo "正在推送到远程仓库..."
git push -u origin main  # 修改为推送 main 分支

# 检查是否成功
if [ $? -eq 0 ]; then
    echo "同步完成！"
else
    echo "同步失败，请检查远程仓库地址和网络连接。"
fi
