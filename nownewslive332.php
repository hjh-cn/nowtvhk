<?php

function generateRandomUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function getFinalRedirectUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);       // get headers
    curl_setopt($ch, CURLOPT_NOBODY, true);       // we don't need body
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // manual redirect
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'PHP');

    curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if (isset($info['http_code']) && $info['http_code'] >= 300 && $info['http_code'] < 400 && !empty($info['redirect_url'])) {
        return $info['redirect_url'];
    }

    // 有啲 CDN 會用 Location header，但 curl_getinfo 唔會出 redirect_url，手動讀 headers
    preg_match('/Location:\s*(.+?)\s*$/im', $info['header_size'] ?? '', $matches);
    return $matches[1] ?? $url;
}

$deviceId = 'W-' . generateRandomUUID();
$callerRef = 'W' . time() . rand(1000, 9999);

$url = 'https://webtvapi.now.com/10/7/getLiveURL';

$headers = [
    'Accept: application/json, text/javascript, */*; q=0.01',
    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6,zh-TW;q=0.5,zh-HK;q=0.4',
    'Cache-Control: no-cache',
    'Content-Type: application/json',
    'Origin: https://news.now.com',
    'Pragma: no-cache',
    'Priority: u=1, i',
    'Referer: https://news.now.com/',
    'Sec-CH-UA: "Chromium";v="134", "Not:A-Brand";v="24", "Microsoft Edge";v="134"',
    'Sec-CH-UA-Mobile: ?0',
    'Sec-CH-UA-Platform: "macOS"',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-site',
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'
];

$data = [
    "contentId" => "332",
    "contentType" => "Channel",
    "audioCode" => "N",
    "deviceId" => $deviceId,
    "deviceType" => "WEB",
    "secureCookie" => null,
    "callerReferenceNo" => $callerRef,
    "profileId" => null
];

// Step 1: get JSON response
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);

if ($json && isset($json['asset'][0])) {
    $streamUrl = $json['asset'][0];

    // Step 2: follow first 302 manually
    $finalUrl = getFinalRedirectUrl($streamUrl);

    // Step 3: redirect client to final URL
    header("Location: $finalUrl", true, 302);
    exit;
} else {
    http_response_code(500);
    echo 'Invalid response or missing asset URL.';
}
