<?php
$pass = 'gtwave!@#$';
$script = "#!/bin/bash\n";
$script .= "sudo -S DEBIAN_FRONTEND=noninteractive apt-get install -y php-mysql <<EOF\n";
$script .= $pass . "\n";
$script .= "EOF\n";

file_put_contents("/home/gtwave/Project/FoxUnni/www/run_php.sh", $script);
chmod("/home/gtwave/Project/FoxUnni/www/run_php.sh", 0777);

echo "<pre>";
echo shell_exec("/bin/bash /home/gtwave/Project/FoxUnni/www/run_php.sh 2>&1");
echo "</pre>";
?>
