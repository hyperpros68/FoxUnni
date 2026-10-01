<?php
$html = file_get_contents('https://mediicon03.mycafe24.com/index.php');
file_put_contents('temp_page.html', $html);
?>
