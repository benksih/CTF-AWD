<?php
$queryString = $_SERVER['QUERY_STRING'] ?? '';
if (empty($queryString)) {
    exit("未检测到任何 GET 参数。");
}
if (strpos($queryString, '%') !== false) {
    exit("非法请求：GET 参数中不允许包含 '%' 字符。");
}
$params = explode('&', $queryString);
$expectedOrder = ['timestamp[year]', 'timestamp[month]', 'timestamp[day]'];
$foundKeys = [];
$duplicates = [];
$values = [];

foreach ($params as $param) {
    $parts = explode('=', $param, 2);
    $key = urldecode($parts[0]);
    $value = isset($parts[1]) ? urldecode($parts[1]) : '';

    if (preg_match('/^timestamp\[[a-zA-Z]+\]$/', $key)) {
        if (in_array($key, $foundKeys)) {
            $duplicates[] = $key;
        } else {
            $foundKeys[] = $key;
        }
        $values[$key] = $value;
    }
}

$missing = array_diff($expectedOrder, $foundKeys);
$extra   = array_diff($foundKeys, $expectedOrder);

if (!empty($duplicates)) {
    exit("检测到重复的参数：" . implode(', ', array_unique($duplicates)));
}
if (!empty($missing)) {
    exit("缺少参数：" . implode(', ', $missing));
}
if (!empty($extra)) {
    exit("含有多余参数：" . implode(', ', $extra));
}
if ($foundKeys !== $expectedOrder) {
    exit("参数顺序错误，应为：" . implode(' → ', $expectedOrder) . "。当前为：" . implode(', ', $foundKeys));
}
foreach ($expectedOrder as $k) {
    if (!isset($values[$k]) || !ctype_digit($values[$k])) {
        exit("参数 {$k} 必须为纯数字，当前为：" . ($values[$k] ?? '未提供'));
    }
}
$content = $_POST['content'] ?? '';
if (trim($content) === '') {
    exit("未检测到 POST 内容（content）。");
}


$dir = __DIR__ . '/upload';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$year  = $_GET['timestamp']['year'];
$month = $_GET['timestamp']['month'];
$day   = $_GET['timestamp']['day'];
$filename = $dir."/".$year.$month.$day;
if (file_put_contents($filename, $content . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
    exit("写入文件失败");
}

echo "日志保存成功";
?>
