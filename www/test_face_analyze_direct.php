<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_FILES = [
    'face_image' => [
        'name' => 'test.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => __DIR__ . '/test_image.jpg',
        'error' => 0,
        'size' => 1024
    ]
];
if (!file_exists($_FILES['face_image']['tmp_name'])) {
    file_put_contents($_FILES['face_image']['tmp_name'], 'dummy data');
}
require 'api/face_analyze.php';
