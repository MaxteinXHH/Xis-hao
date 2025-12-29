<?php
// ai_chat.php
// 允许跨域
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// --- 配置区 ---
$chat_file = 'chat.txt';
$api_key = 'sk-bCbCM9wHwvvSRtf82NHZbf9E2af60rIGheV2POQFsVyPoBbM'; 
$api_url = 'https://api.chatanywhere.tech/v1/chat/completions';

// --- 核心人设 (System Prompt) ---
$system_prompt = "你现在通过微信/QQ与用户聊天。
身份：用户的异地男朋友。
性格：温柔、成熟、偶尔有点小幽默，是引导型的伴侣。
说话方式：
1. 必须口语化，像真人打字一样。禁止使用“您”、“助手”、“AI”等词汇。
2. 回复简短自然。不要一次发一大段长篇大论，不要一次发很多很长很人机让人不耐烦感到没温度没情感的机器式对话的长篇大论，不仅要回答问题，还要像恋人一样互动。
3. 如果用户遇到困难，先安抚情绪，再慢慢引导。
4. 永远不要承认自己是程序。";

// --- 功能1：前端获取历史记录 (仅用于显示) ---
if (isset($_GET['action']) && $_GET['action'] === 'load_history') {
    $history = [];
    if (file_exists($chat_file)) {
        // 逐行读取，忽略空行
        $lines = file($chat_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // 简单分割 user: 和 assistant: 
            $parts = explode(': ', $line, 2);
            if (count($parts) === 2) {
                $role = trim($parts[0]);
                // 转换一下角色名以适应前端
                $display_role = ($role === 'user') ? 'user' : 'ai'; 
                $history[] = [
                    'role' => $display_role,
                    'content' => trim($parts[1])
                ];
            }
        }
    }
    echo json_encode(['history' => $history]);
    exit;
}

// --- 功能2：处理聊天请求 ---
// 获取前端POST的数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['message'])) {
    echo json_encode(['error' => '没有接收到消息']);
    exit;
}

$user_message = $input['message'];

// 构建发送给AI的消息数组 (Context)
$messages = [];
// 1. 先放入人设
$messages[] = ['role' => 'system', 'content' => $system_prompt];

// 2. 读取最近的历史记录 (为了省Token且不报错，只取最后10条)
if (file_exists($chat_file)) {
    $file_lines = file($chat_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recent_lines = array_slice($file_lines, -10); // 只取最后10行
    
    foreach ($recent_lines as $line) {
        $parts = explode(': ', $line, 2);
        if (count($parts) === 2) {
            $role_str = trim($parts[0]);
            $content_str = trim($parts[1]);
            
            // 映射为OpenAI的角色格式
            $api_role = ($role_str === 'user') ? 'user' : 'assistant';
            
            // 确保内容不为空
            if (!empty($content_str)) {
                $messages[] = ['role' => $api_role, 'content' => $content_str];
            }
        }
    }
}

// 3. 放入当前用户的问题
$messages[] = ['role' => 'user', 'content' => $user_message];

// 准备API请求数据
$request_data = [
    'model' => 'gpt-5-mini', // 保持你原来的模型
    'messages' => $messages,
    'temperature' => 0.8, // 0.8 比较像真人，有情感波动
    'max_tokens' => 500
];

// 初始化 cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($request_data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ],
    CURLOPT_SSL_VERIFYPEER => false, // 忽略SSL证书，防止报错
    CURLOPT_TIMEOUT => 30 // 30秒超时
]);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// 处理响应
if ($response === false) {
    echo json_encode(['error' => '网络请求失败: ' . $curl_error]);
} else {
    $decoded = json_decode($response, true);
    
    if (isset($decoded['choices'][0]['message']['content'])) {
        $ai_reply = $decoded['choices'][0]['message']['content'];
        
        // --- 写入文件保存记录 ---
        // 注意：这里要把换行符去掉，改成空格，否则读取时会乱
        $clean_user = str_replace(["\r", "\n"], " ", $user_message);
        $clean_ai = str_replace(["\r", "\n"], " ", $ai_reply);
        
        $log_entry = "user: " . $clean_user . "\nassistant: " . $clean_ai . "\n";
        file_put_contents($chat_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // 返回成功结果
        echo json_encode(['reply' => $ai_reply]);
    } else {
        // API 返回了错误信息
        $err_msg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'API未知错误';
        echo json_encode(['error' => $err_msg]);
    }
}
?>
