<?php
header("Content-Type: application/json; charset=utf-8");

$date = $_GET["date"] ?? "";
if (empty($date)) {
    echo json_encode(["ok" => false, "msg" => "날짜를 선택해주세요."]);
    exit;
}

// In a real system, you would query the database to see which times are already booked.
// For this demo, we will generate timeslots from 10:00 to 18:00 with 30min intervals.
// We will randomly mark some as unavailable.

$times = [];
$start = strtotime("10:00");
$end = strtotime("18:00");
$interval = 30 * 60; // 30 minutes

// Use the date string to seed the random generator so it's consistent for the same date
$seed = crc32($date);
srand($seed);

for ($t = $start; $t <= $end; $t += $interval) {
    $timeStr = date("H:i", $t);
    // 13:00 to 14:00 is lunch time (unavailable)
    if ($timeStr >= "13:00" && $timeStr < "14:00") {
        continue;
    }
    
    // 20% chance of being booked
    $is_available = (rand(1, 100) > 20);
    
    $times[] = [
        "time" => $timeStr,
        "available" => $is_available
    ];
}

echo json_encode(["ok" => true, "times" => $times]);

