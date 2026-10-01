<?php
$j = json_decode(file_get_contents('../events.json'), true);
print_r(array_keys($j['data'][0]));
?>
