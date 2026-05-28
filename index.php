<?php
// 关闭所有错误输出
error_reporting(0);
ini_set('display_errors', 0);

// 获取请求的 action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// API 请求处理
if ($action !== '') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    try {
        if ($action === 'ping') {
            // 获取客户端发送的开始时间（毫秒时间戳）
            $clientStartTime = isset($_GET['t']) ? floatval($_GET['t']) : 0;
            // 计算延迟（微秒转毫秒）
            $serverTime = microtime(true) * 1000;
            $latency = $serverTime - $clientStartTime;
            echo json_encode(['success' => true, 'latency' => round($latency, 2)]);
            exit;
        }
        
        if ($action === 'download') {
            $size = 5 * 1024 * 1024; // 5MB
            header('Content-Length: ' . $size);
            header('Content-Type: application/octet-stream');
            $chunk = 8192;
            for ($i = 0; $i < $size; $i += $chunk) {
                echo random_bytes(min($chunk, $size - $i));
            }
            exit;
        }
        
        if ($action === 'upload') {
            $startTime = isset($_SERVER['HTTP_X_START_TIME']) ? floatval($_SERVER['HTTP_X_START_TIME']) : 0;
            $receivedBytes = isset($_SERVER['CONTENT_LENGTH']) ? intval($_SERVER['CONTENT_LENGTH']) : 0;
            
            // 读取并丢弃上传的数据
            $fp = fopen('php://input', 'rb');
            while (!feof($fp)) {
                fread($fp, 65536);
            }
            fclose($fp);
            
            $duration = microtime(true) - $startTime;
            if ($duration > 0 && $receivedBytes > 0) {
                $speedMbps = ($receivedBytes * 8) / ($duration * 1000000);
                echo json_encode(['success' => true, 'speed' => round($speedMbps, 2)]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid test data']);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// 显示 HTML 页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网络速度测试</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 30px 25px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .sub {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        .speed-panel {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        .speed-value {
            font-size: 3.2rem;
            font-weight: bold;
            color: #667eea;
        }
        .speed-unit {
            font-size: 1rem;
            color: #888;
        }
        .speed-label {
            font-size: 1rem;
            color: #555;
            margin-top: 5px;
        }
        .speed-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
        }
        .speed-card {
            flex: 1;
            background: #f8f9fa;
            border-radius: 20px;
            padding: 15px 10px;
            text-align: center;
        }
        .speed-card .label {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 8px;
        }
        .speed-card .number {
            font-size: 1.6rem;
            font-weight: bold;
            color: #333;
        }
        .speed-card .unit {
            font-size: 0.75rem;
            color: #aaa;
        }
        .progress-section {
            margin: 20px 0;
        }
        .progress-bar-container {
            background: #e0e0e0;
            border-radius: 20px;
            height: 12px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.2s ease;
        }
        .test-status {
            text-align: center;
            font-size: 0.85rem;
            color: #667eea;
            font-weight: 500;
            margin: 10px 0;
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 14px;
            border-radius: 50px;
            cursor: pointer;
            transition: transform 0.1s;
            margin-top: 15px;
        }
        button:active {
            transform: scale(0.97);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .info {
            text-align: center;
            font-size: 0.7rem;
            color: #aaa;
            margin-top: 20px;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            margin-top: 12px;
            font-size: 0.8rem;
        }
        @media (max-width: 480px) {
            .container { padding: 20px 15px; }
            .speed-value { font-size: 2.5rem; }
            .speed-card .number { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 网速测试</h1>
    <div class="sub">测试下载 · 上传 · 延迟</div>
    
    <div class="speed-panel">
        <div class="speed-value" id="downloadSpeed">--</div>
        <div class="speed-unit">Mbps</div>
        <div class="speed-label">当前下载速度</div>
    </div>
    
    <div class="speed-row">
        <div class="speed-card">
            <div class="label">📥 下载</div>
            <div class="number" id="finalDownload">--</div>
            <div class="unit">Mbps</div>
        </div>
        <div class="speed-card">
            <div class="label">📤 上传</div>
            <div class="number" id="finalUpload">--</div>
            <div class="unit">Mbps</div>
        </div>
        <div class="speed-card">
            <div class="label">⏱️ 延迟</div>
            <div class="number" id="pingValue">--</div>
            <div class="unit">ms</div>
        </div>
    </div>
    
    <div class="progress-section">
        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <div class="test-status" id="statusMsg">点击「开始测速」</div>
    </div>
    
    <button id="startBtn">▶ 开始测速</button>
    <div class="info">测试需要几秒钟，请保持网络稳定</div>
    <div id="errorMsg" class="error"></div>
</div>

<script>
    const DOWNLOAD_URL = '?action=download';
    const UPLOAD_URL = '?action=upload';
    const PING_URL = '?action=ping';
    const UPLOAD_SIZE = 1 * 1024 * 1024; // 1MB
    const MAX_TEST_TIME = 10;
    
    let isTesting = false;
    
    const startBtn = document.getElementById('startBtn');
    const progressBar = document.getElementById('progressBar');
    const statusMsg = document.getElementById('statusMsg');
    const errorDiv = document.getElementById('errorMsg');
    const downloadSpeedSpan = document.getElementById('downloadSpeed');
    const finalDownloadSpan = document.getElementById('finalDownload');
    const finalUploadSpan = document.getElementById('finalUpload');
    const pingValueSpan = document.getElementById('pingValue');
    
    function setProgress(percent) {
        progressBar.style.width = Math.min(100, Math.max(0, percent)) + '%';
    }
    
    function updateStatus(text) {
        statusMsg.innerText = text;
    }
    
    function setError(msg) {
        errorDiv.innerText = msg;
        setTimeout(() => {
            if (errorDiv.innerText === msg) errorDiv.innerText = '';
        }, 5000);
    }
    
    async function testPing() {
        try {
            // 获取当前时间戳（毫秒）
            const startTime = Date.now();
            
            // 发送请求，带上开始时间
            const response = await fetch(`${PING_URL}&t=${startTime}`, {
                cache: 'no-store',
                headers: { 'Cache-Control': 'no-cache' }
            });
            
            if (!response.ok) throw new Error('Ping 请求失败');
            
            const endTime = Date.now();
            const data = await response.json();
            
            // 计算往返延迟
            let latency = endTime - startTime;
            
            // 如果服务器返回了计算的延迟，使用较小的那个（更准确）
            if (data.success && data.latency && data.latency > 0 && data.latency < latency) {
                latency = data.latency;
            }
            
            // 限制延迟范围（0-5000ms）
            latency = Math.min(5000, Math.max(0, latency));
            
            pingValueSpan.innerText = Math.round(latency);
            return latency;
        } catch (e) {
            console.error('Ping 测试失败:', e);
            pingValueSpan.innerText = '--';
            throw new Error('延迟测试失败: ' + e.message);
        }
    }
    
    async function testDownload() {
        try {
            updateStatus('正在测试下载速度...');
            const startTime = performance.now();
            let receivedBytes = 0;
            
            const response = await fetch(DOWNLOAD_URL, {
                cache: 'no-store',
                headers: { 'Cache-Control': 'no-cache' }
            });
            
            if (!response.ok) throw new Error('下载请求失败: ' + response.status);
            
            const reader = response.body.getReader();
            let lastUpdateTime = startTime;
            let lastUpdateBytes = 0;
            
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                
                receivedBytes += value.length;
                const now = performance.now();
                const elapsed = (now - startTime) / 1000;
                
                if (now - lastUpdateTime >= 0.2 || receivedBytes - lastUpdateBytes > 262144) {
                    if (elapsed > 0) {
                        const currentSpeed = (receivedBytes * 8) / (elapsed * 1000000);
                        downloadSpeedSpan.innerText = currentSpeed.toFixed(1);
                    }
                    lastUpdateTime = now;
                    lastUpdateBytes = receivedBytes;
                }
                
                if (elapsed > MAX_TEST_TIME) {
                    reader.cancel();
                    break;
                }
            }
            
            const endTime = performance.now();
            const duration = (endTime - startTime) / 1000;
            
            if (duration < 0.1 || receivedBytes === 0) {
                throw new Error('下载数据不足');
            }
            
            const speedMbps = (receivedBytes * 8) / (duration * 1000000);
            finalDownloadSpan.innerText = speedMbps.toFixed(2);
            downloadSpeedSpan.innerText = speedMbps.toFixed(1);
            return speedMbps;
        } catch (e) {
            console.error('下载测试失败:', e);
            throw new Error('下载测试失败: ' + e.message);
        }
    }
    
    async function testUpload() {
        try {
            updateStatus('正在测试上传速度...');
            
            const testData = new Uint8Array(UPLOAD_SIZE);
            for (let i = 0; i < UPLOAD_SIZE; i++) {
                testData[i] = Math.floor(Math.random() * 256);
            }
            
            const startTime = performance.now();
            const response = await fetch(UPLOAD_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/octet-stream',
                    'X-Start-Time': (startTime / 1000).toString(),
                    'Cache-Control': 'no-cache'
                },
                body: testData.buffer,
                cache: 'no-store'
            });
            
            if (!response.ok) throw new Error('上传请求失败: ' + response.status);
            
            const result = await response.json();
            const endTime = performance.now();
            const duration = (endTime - startTime) / 1000;
            
            let speedMbps = result.success ? result.speed : 0;
            
            if (!speedMbps || speedMbps <= 0) {
                speedMbps = (UPLOAD_SIZE * 8) / (duration * 1000000);
            }
            
            finalUploadSpan.innerText = speedMbps.toFixed(2);
            return speedMbps;
        } catch (e) {
            console.error('上传测试失败:', e);
            throw new Error('上传测试失败: ' + e.message);
        }
    }
    
    function resetDisplay() {
        downloadSpeedSpan.innerText = '--';
        finalDownloadSpan.innerText = '--';
        finalUploadSpan.innerText = '--';
        pingValueSpan.innerText = '--';
        setProgress(0);
        errorDiv.innerText = '';
    }
    
    async function runSpeedTest() {
        if (isTesting) return;
        isTesting = true;
        startBtn.disabled = true;
        startBtn.innerText = '测速中...';
        resetDisplay();
        
        try {
            // 1. Ping 测试 (0-10%)
            setProgress(5);
            updateStatus('正在测试延迟...');
            await testPing();
            
            // 2. 下载测试 (10-60%)
            setProgress(15);
            await testDownload();
            setProgress(60);
            
            // 3. 上传测试 (60-100%)
            setProgress(65);
            await testUpload();
            setProgress(100);
            
            updateStatus('测速完成！');
            setTimeout(() => {
                if (!isTesting) setProgress(0);
            }, 1500);
        } catch (err) {
            console.error('测速失败:', err);
            updateStatus('测速失败');
            setError(err.message);
            setProgress(0);
        } finally {
            isTesting = false;
            startBtn.disabled = false;
            startBtn.innerText = '🔄 重新测速';
        }
    }
    
    startBtn.addEventListener('click'， runSpeedTest);
    updateStatus('点击按钮开始测速');
</script>
</body>
</html>
