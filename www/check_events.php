<?php
$j=json_decode(file_get_contents('events.json'), true);
for($i=0;$i<5;$i++) echo $j['data'][$i]['event_name'] . " - " . $j['data'][$i]['competitor_name'] . "\n";
?>
