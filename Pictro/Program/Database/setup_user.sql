CREATE DATABASE IF NOT EXISTS `Pictro` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'pictro'@'%' IDENTIFIED BY 'pictro!@#$';
ALTER USER 'pictro'@'%' IDENTIFIED BY 'pictro!@#$';
CREATE USER IF NOT EXISTS 'pictro'@'localhost' IDENTIFIED BY 'pictro!@#$';
ALTER USER 'pictro'@'localhost' IDENTIFIED BY 'pictro!@#$';
GRANT ALL PRIVILEGES ON `Pictro`.* TO 'pictro'@'%';
GRANT ALL PRIVILEGES ON `Pictro`.* TO 'pictro'@'localhost';
FLUSH PRIVILEGES;
