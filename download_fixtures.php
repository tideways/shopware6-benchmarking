<?php

$config = parse_ini_file(__DIR__ . '/.env');
var_dump($config);

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_URL, 'https://shopware6.tideways.io/api/oauth/token');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'grant_type' => 'client_credentials',
    'client_id' => $config['CLIENT_ID'],
    'client_secret' => $config['CLIENT_SECRET'],
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
));

$content = json_decode(curl_exec($ch), true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, 'https://shopware6.tideways.io/api/_tideways/loadtesting-fixtures');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $content['access_token'],
));

$fixtures = json_decode(curl_exec($ch), true);

if (isset($fixtures['errors'])) {
    var_dump($fixtures);
    die("error");
}

foreach ($fixtures as $file => $data) {
    if (strpos($file, '.csv') !== false) {
        $data = implode(PHP_EOL, $data);
    } else if (strpos($file, '.json') !== false) {
        $data = json_encode($data, JSON_THROW_ON_ERROR);
    }

    file_put_contents("fixtures/" . $file, $data);
    echo "Stored {$file}\n";
}
