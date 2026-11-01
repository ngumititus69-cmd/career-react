<?php
declare(strict_types=1);

$databasePath = getenv('CAREER_ROAD_SQLITE_PATH')
    ?: dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'career_road.sqlite';

$pdo = new PDO('sqlite:' . $databasePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
