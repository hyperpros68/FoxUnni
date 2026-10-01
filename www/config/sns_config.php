<?php
// config/sns_config.php
// 카카오, 네이버, 구글, 애플의 OAuth 2.0 연동 설정 파일

$sns_config = [
    'kakao' => [
        'client_id' => '6f87a8de077837cd29c46324105cdf03',
        'client_secret' => '',
        'javascript_key' => '8bef00e97793340d80a37b7277aca104',
        'redirect_uri' => 'http://localhost:9001/api/sns_callback.php?provider=kakao'
    ],
    'naver' => [
        'client_id' => '9G4evm0anbGfzarocYy3',
        'client_secret' => 'zNrw19YGBN',
        'redirect_uri' => 'http://localhost:9001/api/sns_callback.php?provider=naver'
    ],
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost:9001/api/sns_callback.php?provider=google'
    ],
    'apple' => [
        'client_id' => 'your_apple_client_id',
        'client_secret' => 'your_apple_client_secret',
        'redirect_uri' => 'http://localhost:9001/api/sns_callback.php?provider=apple'
    ]
];
?>
