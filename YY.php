<?php
/**
* kudogxy - 原创
*/

error_reporting(0);
set_time_limit(0);
ini_set('memory_limit', '128M');
date_default_timezone_set("Asia/Shanghai");

const CACHE_DIR = __DIR__ . '/yycache';
const CACHE_TIME = 1800;
const DEFAULT_ID = '34229877';
// 画质配置：1200(360P)、2500(480P)、4500(720P)、8000(1080P)
const QUALITY_LEVEL = 4500;


$id = isset($_GET['id']) ? preg_replace('/[^0-9]/', '', $_GET['id']) : DEFAULT_ID;
if (empty($id)) $id = DEFAULT_ID;

$quality = isset($_GET['quality']) ? preg_replace('/[^0-9]/', '', $_GET['quality']) : QUALITY_LEVEL;
$quality = in_array($quality, [1200,2500,4500,8000]) ? $quality : QUALITY_LEVEL;

$playUrl = getRealUrl($id, $quality);

if (!$playUrl) {
header("HTTP/1.1 404 Not Found");
exit("直播未开始/频道无对应画质流或接口失效");
}


serveM3u8Direct($playUrl);


function getRealUrl($rid, $quality) {
$cacheFile = CACHE_DIR . "/room_{$rid}_{$quality}.json";

if (file_exists($cacheFile)) {
$data = json_decode(file_get_contents($cacheFile), true);
if (isset($data['time'], $data['url']) && time() - $data['time'] < CACHE_TIME) {
return $data['url'];
}
}

$apiUrl = "https://interface.yy.com/hls/new/get/{$rid}/{$rid}/{$quality}?source=wapyy&callback=jsonp3";

$headers = [
"Referer: https://wap.yy.com/",
"User-Agent: Mozilla/5.0 (Linux; Android 10; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.181 Mobile Safari/537.36",
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$res = curl_exec($ch);
curl_close($ch);

if ($res && preg_match('/jsonp3\((.*)\)/', $res, $matches)) {
$json = json_decode($matches[1], true);
if (isset($json['hls']) && !empty($json['hls'])) {
$realUrl = $json['hls'];

if (!is_dir(CACHE_DIR)) mkdir(CACHE_DIR, 0755, true);

file_put_contents($cacheFile, json_encode([
'time' => time(),
'url' => $realUrl
]));
return $realUrl;
}
}
return null;
}


function serveM3u8Direct($url) {
$m3u8Content = curlGet($url);

if (!$m3u8Content) {
header("HTTP/1.1 502 Bad Gateway");
exit("Failed to fetch playlist");
}

$baseUrl = dirname($url) . '/';
$lines = explode("\n", $m3u8Content);

header("Content-Type: application/vnd.apple.mpegurl");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache");
header("Content-Disposition: inline");

foreach ($lines as $line) {
$line = trim($line);
if (empty($line)) continue;

if ($line[0] === '#') {
echo $line . "\n";
} else {
if (strpos($line, 'http') !== 0) {
echo $baseUrl . $line . "\n";
} else {
echo $line . "\n";
}
}
}
}


function curlGet($url) {
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
"Referer: https://wap.yy.com/",
"User-Agent: Mozilla/5.0 (Linux; Android 10; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.181 Mobile Safari/537.36"
]);
$data = curl_exec($ch);
curl_close($ch);
return $data;
}
?>
