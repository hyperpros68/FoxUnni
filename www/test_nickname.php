<?php
$_POST = ['action' => 'check_nickname', 'nickname' => '카리'];
$_GET = [];
ob_start();
include 'api/auth.php';
$out = ob_get_clean();
echo "Result: " . $out . "\n";
