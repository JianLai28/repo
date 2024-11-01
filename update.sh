#!/bin/bash

# 确保脚本在遇到错误时停止执行，并显示错误信息
set -e

# 定义一个函数来显示错误信息
error_exit()
{
    echo "Error: $1" 1>&2
    exit 1
}

# 步骤 1: 切换到仓库目录
cd repo || error_exit "Failed to change directory to 'repo'."

# 步骤 2: 删除旧的 Packages 文件
git rm Packages Packages.gz Packages.bz2 Packages.xz || error_exit "Failed to remove old Packages files."

# 步骤 3: 重新生成 Packages 文件
dpkg-scanpackages -m debs/ > Packages || error_exit "Failed to generate Packages file."
gzip -c Packages > Packages.gz || error_exit "Failed to compress Packages file with gzip."
bzip2 -k Packages || error_exit "Failed to compress Packages file with bzip2."
xz -k Packages || error_exit "Failed to compress Packages file with xz."

# 步骤 4: 添加新文件并提交
git add -A || error_exit "Failed to add new files."
git commit -m "Add new file" || error_exit "Failed to commit changes."
git push origin main || error_exit "Failed to push changes to origin main."

# 打印完成消息
echo "GitHub repository has been updated successfully."
