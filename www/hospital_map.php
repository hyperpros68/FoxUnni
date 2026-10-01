<?php
require_once 'config/db_connect.php';
// Fetch distinct hospitals from events
$stmt = $pdo->query("SELECT name as hospital_name FROM hospitals LIMIT 15");
$hospitals = $stmt->fetchAll();

// Generate some random coordinates around Gangnam Station (37.4979, 127.0276)
$mapData = [];
foreach($hospitals as $h) {
    $lat = 37.4979 + (rand(-100, 100) / 20000); // 37.4929 ~ 37.5029
    $lng = 127.0276 + (rand(-100, 100) / 20000);
    $mapData[] = [
        'name' => $h['hospital_name'],
        'lat' => $lat,
        'lng' => $lng,
        'url' => '/events/list.php?keyword=' . urlencode($h['hospital_name'])
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>내 주변 병원 찾기 - 여우언니</title>
    <style>
        :root {
            --primary-color: #f5576c;
            --bg-color: #f4f5f7;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Pretendard', -apple-system, sans-serif; }
        body { 
            background-color: #fff; color: #333; 
            max-width: 480px; margin: 0 auto; 
            position: relative; min-height: 100vh; 
            box-shadow: 0 0 20px rgba(0,0,0,0.05); 
            overflow-x: hidden; 
        }
        
        .header-top { display: flex; align-items: center; justify-content: center; height: 56px; border-bottom: 1px solid #eaeaea; position: relative; z-index: 100; background: #fff; width: 100%; }
        .header-top .back { position: absolute; left: 15px; font-size: 20px; text-decoration: none; color: #333; font-weight: bold; }
        .header-title { font-size: 17px; font-weight: 700; }
        
        #map { width: 100%; height: calc(100vh - 56px); background: #eee; z-index: 1; }
        
        /* 커스텀 마커(오버레이) 스타일 */
        .custom-div-icon { background: transparent; border: none; }
        .custom-overlay { 
            background: #fff; border: 1.5px solid var(--primary-color); border-radius: 20px; 
            padding: 6px 12px; font-size: 12px; font-weight: 700; color: var(--primary-color); 
            box-shadow: 0 3px 8px rgba(245,87,108,0.3); text-align: center; white-space: nowrap;
            display: inline-block; cursor: pointer;
            position: relative;
            left: 50%; transform: translateX(-50%);
        }
        .custom-overlay::after { 
            content: ''; position: absolute; bottom: -6px; left: 50%; margin-left: -5px; 
            border-width: 6px 5px 0; border-style: solid; 
            border-color: var(--primary-color) transparent transparent transparent; 
        }
    </style>
</head>
<body>
    <header class="header-top">
        <a href="javascript:history.back()" class="back">←</a>
        <div class="header-title">강남역 / 신논현역 주변</div>
    </header>

    <div id="map"></div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.onload = function() {
            var map = L.map('map', { zoomControl: false }).setView([37.4979, 127.0276], 15);
            
            // 아름다운 CartoDB Voyager 지도 타일 사용 (API 키 필요 없음)
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19
            }).addTo(map);

            var hospitals = <?= json_encode($mapData) ?>;

            hospitals.forEach(function(hospital) {
                var iconHtml = `<div class="custom-overlay" onclick="location.href='${hospital.url}'">${hospital.name}</div>`;
                
                var customIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: iconHtml,
                    iconSize: [0, 0], // CSS 내부에서 크기 결정
                    iconAnchor: [0, 40] // 앵커 포인트를 하단 중앙으로
                });

                L.marker([hospital.lat, hospital.lng], {icon: customIcon}).addTo(map);
            });
        };
    </script>
</body>
</html>


