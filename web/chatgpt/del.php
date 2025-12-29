<?php
// 设置页面编码
header('Content-Type: text/html; charset=utf-8');

// 定义文件路径
$chatFile = 'chat.txt';
$backupFile = 'old1.txt';

// 初始化结果消息
$message = '';

try {
    // 检查chat.txt是否存在
    if (file_exists($chatFile)) {
        // 读取当前聊天记录
        $content = file_get_contents($chatFile);
        
        if (!empty($content)) {
            // 准备备份内容，添加时间戳
            $backupContent = "\n\n===== 备份于 " . date('Y-m-d H:i:s') . " =====\n";
            $backupContent .= $content;
            
            // 追加到备份文件
            $backupResult = file_put_contents($backupFile, $backupContent, FILE_APPEND | LOCK_EX);
            
            if ($backupResult === false) {
                throw new Exception("无法写入备份文件");
            }
            
            // 清空聊天记录文件
            $clearResult = file_put_contents($chatFile, '');
            
            if ($clearResult === false) {
                throw new Exception("清空聊天记录失败");
            }
            
            $message = "历史对话记录已成功删除并备份";
        } else {
            $message = "聊天记录为空，无需删除";
        }
    } else {
        // 如果聊天记录文件不存在，创建一个空文件
        file_put_contents($chatFile, '');
        $message = "聊天记录文件不存在，已创建新文件";
    }
} catch (Exception $e) {
    $message = "操作失败：" . $e->getMessage();
}

// 3秒后自动跳回聊天页面
header("Refresh: 3; url=index.php");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作结果</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        .container {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .message {
            font-size: 1.2rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .redirect {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .back-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .back-btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="message"><?php echo $message; ?></div>
        <div class="redirect">3秒后将自动返回聊天页面...</div>
        <a href="index.php" class="back-btn">立即返回</a>
    </div>
</body>
</html>