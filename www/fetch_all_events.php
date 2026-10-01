<?php
$allData = [];
$page = 1;
$hasMore = true;
$cookie = 'PHPSESSID=ee1b3debeb55d7f79758b146b233dce4';

while ($hasMore) {
    $ch = curl_init("https://mediicon03.mycafe24.com/api.php?action=event_reports&page=" . $page);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Cookie: " . $cookie,
        "Referer: https://mediicon03.mycafe24.com/index.php",
        "X-Requested-With: XMLHttpRequest"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        echo "Failed at page $page. HTTP Code: $httpCode\n";
        break;
    }
    
    $json = json_decode($response, true);
    if (!is_array($json) || !isset($json['data'])) {
        echo "Invalid JSON or missing 'data' key at page $page.\n";
        break;
    }
    
    $allData = array_merge($allData, $json['data']);
    $hasMore = !empty($json['hasMore']);
    
    echo "Fetched page $page, total items so far: " . count($allData) . "\n";
    $page++;
    
    if ($page > 100) break; // limit to 100 pages just in case
}

$finalJson = json_encode(['data' => $allData], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents('events.json', $finalJson);
echo "Saved all data to events.json\n";
?>
