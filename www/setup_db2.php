<?php
$pass = 'gtwave!@#$';
$script = "#!/bin/bash\n";
$script .= "sudo -S mysql < /home/gtwave/Project/FoxUnni/www/full_setup.sql <<EOF\n";
$script .= $pass . "\n";
$script .= "EOF\n";

file_put_contents("/home/gtwave/Project/FoxUnni/www/run.sh", $script);
chmod("/home/gtwave/Project/FoxUnni/www/run.sh", 0777);

echo "<h1>서버 내부 DB 자동 설치 및 세팅</h1>";
echo "<pre>";
echo shell_exec("/bin/bash /home/gtwave/Project/FoxUnni/www/run.sh 2>&1");
echo "</pre>";
echo "<h2>작업이 100% 완료되었습니다! 이제 앱 메인으로 가셔서 로그인하시면 됩니다.</h2>";
?>
