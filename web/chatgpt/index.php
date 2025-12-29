<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://xis-hao.fun/web/static/love.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>你的专属恋人</title>
    <style>
        /* --- 基础样式 --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', "Microsoft YaHei", sans-serif; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); color: #333; height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 20px; }
        .container { max-width: 800px; width: 100%; height: 100%; margin: 0 auto; display: flex; flex-direction: column; gap: 15px; }
        
        /* --- 聊天窗口 --- */
        .chat-container {
            background: rgba(255, 255, 255, 0.95); 
            border-radius: 20px; 
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex; flex-direction: column; flex: 1;
            backdrop-filter: blur(10px);
        }
        
        /* 顶部栏 */
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 15px; text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chat-header h2 { font-size: 1.1rem; font-weight: 600; }
        .chat-header p { font-size: 0.8rem; opacity: 0.9; margin-top: 4px; }
        
        /* 消息区域 */
        .chat-messages {
            flex: 1; padding: 20px; overflow-y: auto;
            display: flex; flex-direction: column; gap: 15px;
            background: #fdfdfd;
            scroll-behavior: smooth;
        }
        
        /* 消息气泡 */
        .message {
            max-width: 80%; padding: 12px 16px; border-radius: 18px;
            line-height: 1.5; position: relative; word-wrap: break-word;
            font-size: 15px; letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-message {
            align-self: flex-end; 
            background: #667eea; 
            color: white; 
            border-bottom-right-radius: 4px;
        }
        
        .ai-message {
            align-self: flex-start; 
            background: white; 
            color: #444; 
            border: 1px solid #eee; 
            border-bottom-left-radius: 4px;
        }

        /* 对方正在输入... 动画 */
        .typing-wrapper { display: none; padding: 0 20px 10px 20px; }
        .typing-indicator {
            background: #f0f0f0; padding: 10px 15px; border-radius: 20px;
            border-bottom-left-radius: 4px; display: inline-block;
        }
        .typing-indicator span {
            display: inline-block; width: 6px; height: 6px; border-radius: 50%;
            background: #aaa; margin: 0 2px;
            animation: typing 1.4s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: 0s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        /* 输入框区域 */
        .chat-input {
            display: flex; padding: 15px; background: white; border-top: 1px solid #f0f0f0;
        }
        .chat-input input {
            flex: 1; padding: 12px 18px; border: 1px solid #ddd;
            border-radius: 30px; outline: none; font-size: 1rem;
            transition: all 0.3s; background: #f9f9f9;
        }
        .chat-input input:focus { border-color: #764ba2; background: white; }
        .chat-input button {
            background: #764ba2; color: white; border: none; border-radius: 50%;
            width: 48px; height: 48px; margin-left: 12px; cursor: pointer;
            transition: all 0.2s; display: flex; align-items: center; justify-content: center;
            font-size: 18px; box-shadow: 0 3px 10px rgba(118, 75, 162, 0.3);
        }
        .chat-input button:hover { transform: scale(1.05); background: #667eea; }
        
        /* 底部链接 */
        footer { text-align: center; margin-top: 10px; }
        footer a { color: #999; text-decoration: none; font-size: 12px; margin: 0 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                <h2>AI 男友 (引导型)</h2>
                <p>正在与你连接...</p>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- 消息将显示在这里 -->
            </div>
            
            <!-- 正在输入指示器 -->
            <div class="typing-wrapper" id="typingWrapper">
                <div class="typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
            
            <div class="chat-input">
                <input type="text" id="userInput" placeholder="说点什么吧..." autocomplete="off">
                <button id="sendButton">➤</button>
            </div>
        </div>
        
        <footer>
            <a href="del.php">清除记忆</a> | 
            <a href="https://xis-hao.fun/xhd/">联系管理员</a>
        </footer>
    </div>

    <script src="https://xis-hao.fun/web/static/love.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM 元素
            const chatMessages = document.getElementById('chatMessages');
            const userInput = document.getElementById('userInput');
            const sendButton = document.getElementById('sendButton');
            const typingWrapper = document.getElementById('typingWrapper');
            
            let isProcessing = false; // 状态锁，防止重复点击
            
            // 初始化爱心特效
            if(typeof HeartTraceAnimation !== 'undefined') {
                HeartTraceAnimation.init({ color: '#ff4499', size: 20 });
            }

            // 1. 页面加载时获取历史记录
            fetch('ai_chat.php?action=load_history')
                .then(res => res.json())
                .then(data => {
                    if(data.history) {
                        data.history.forEach(item => appendMessage(item.content, item.role));
                    }
                });

            // 2. 发送消息主逻辑
            function sendMessage() {
                const text = userInput.value.trim();
                if (!text || isProcessing) return;
                
                // 立即显示用户消息
                appendMessage(text, 'user');
                userInput.value = '';
                
                isProcessing = true;
                showTyping(true); // 显示"正在输入"

                // 发送请求给后端
                fetch('ai_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.reply) {
                        // 收到回复后，不直接显示，而是启动“拟人化拆分发送”逻辑
                        simulateHumanTyping(data.reply);
                    } else {
                        showTyping(false);
                        appendMessage("（系统）出错了：" + (data.error || "未知错误"), 'ai');
                        isProcessing = false;
                    }
                })
                .catch(err => {
                    showTyping(false);
                    appendMessage("（系统）网络连接失败，请稍后再试。", 'ai');
                    console.error(err);
                    isProcessing = false;
                });
            }

            // --- 核心：拟人化发送算法 (连续发多句) ---
            function simulateHumanTyping(fullText) {
                // 1. 拆分句子：按句号、问号、感叹号拆分，但保留标点
                // 逻辑：匹配非标点内容+标点，或者最后一段
                const segments = fullText.match(/[^。？！.?!~\n]+[。？！.?!~\n]*|[^。？！.?!~\n]+$/g);
                
                if (!segments || segments.length === 0) {
                    // 如果拆分失败，直接发原文
                    showTyping(false);
                    appendMessage(fullText, 'ai');
                    isProcessing = false;
                    return;
                }

                let i = 0;
                
                // 递归函数：一句一句发
                function playNext() {
                    if (i >= segments.length) {
                        showTyping(false);
                        isProcessing = false; // 解锁
                        return;
                    }

                    let segment = segments[i].trim();
                    if(!segment) {
                        i++;
                        playNext();
                        return;
                    }

                    // 确保正在输入状态是显示的
                    showTyping(true);

                    // 计算阅读/打字延迟：字数越多，停顿越久
                    // 基础延时 600ms + 每个字 50ms + 随机 0-300ms
                    let delay = 600 + (segment.length * 50) + Math.random() * 300;
                    
                    setTimeout(() => {
                        // 发送这一句
                        appendMessage(segment, 'ai');
                        // 保持滚动到底部
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        
                        i++;
                        // 准备发下一句
                        if (i < segments.length) {
                            playNext(); 
                        } else {
                            // 全部发完
                            showTyping(false);
                            isProcessing = false;
                        }
                    }, delay);
                }

                // 开始第一句
                playNext();
            }

            // 辅助：添加消息到界面
            function appendMessage(text, sender) {
                const div = document.createElement('div');
                div.classList.add('message');
                div.classList.add(sender === 'user' ? 'user-message' : 'ai-message');
                div.textContent = text;
                chatMessages.appendChild(div);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            // 辅助：显示/隐藏输入状态
            function showTyping(show) {
                typingWrapper.style.display = show ? 'block' : 'none';
                if(show) chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            // 绑定事件
            sendButton.addEventListener('click', sendMessage);
            userInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') sendMessage();
            });
        });
    </script>
    <button class="fixed-back-btn" ><a href="https://xis-hao.fun/web/fun/fur/" target="_self" style="color:#FFFFFF; text-decoration: none;">
        返回</a></button>

</body>
</html>
