<?php

// 从 URL 查询字符串获取频道号
$channelNo = isset($_GET['ch']) ? $_GET['ch'] : '332'; // 如果没有提供，则默认使用 332

// 初始化 cURL 执行第一次请求
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://nowplayer.now.com/liveplayer/play/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "channelNo=" . $channelNo, // 使用动态的频道号
    CURLOPT_HTTPHEADER => [
        "Cookie: 填入你的cookie",
        "Referer: https://nowplayer.now.com/liveplayer/" . $channelNo, // 动态的 referer
        "User-Agent: 填入你的ua",
    ]
]);

// 执行第一次 cURL 请求并获取响应
$response = curl_exec($curl);

// 获取 HTTP 状态码
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// 关闭 cURL
curl_close($curl);

// 检查 cURL 请求是否成功
if ($response === false) {
    echo "无法从服务器获取响应。";
    exit;
}

// 检查 HTTP 响应状态码
if ($httpCode !== 200) {
    echo "错误：无法获取视频流，HTTP 状态码：" . $httpCode;
    exit;
}

// 解析 JSON 响应
$data = json_decode($response, true);

// 检查 'asset' 字段是否存在并提取 URL
if (isset($data['asset'][0])) {
    $assetUrl = $data['asset'][0]; // 这是第二次请求的目标链接

    // 执行第三次请求（通过第二次请求的 assetUrl）
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $assetUrl, // 使用第二次请求的链接
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0"
        ]
    ]);

    // 执行第三次请求并获取响应
    $thirdResponse = curl_exec($curl);

    // 获取第三次请求的 HTTP 状态码
    $thirdHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // 获取第三次请求的最终 URL（即重定向后的链接）
    $finalUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

    // 关闭 cURL
    curl_close($curl);

    // 检查第三次请求是否成功
    if ($thirdResponse === false) {
        echo "无法从第三次请求获取响应。";
        exit;
    }

    // 检查 HTTP 响应状态码
    if ($thirdHttpCode !== 200) {
        echo "错误：第三次请求失败，HTTP 状态码：" . $thirdHttpCode;
        exit;
    }

    // 重定向到第三次请求的最终 URL
    header("Location: " . $finalUrl);
    exit; // 结束脚本执行，确保重定向生效
} else {
    echo "响应中未找到 Asset URL。";
}

?>
