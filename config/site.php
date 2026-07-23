<?php

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$projectDir = realpath(__DIR__ . '/..');
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$webBase = $docRoot ? str_replace('\\', '/', str_replace($docRoot, '', $projectDir)) : '';

$baseUrl = $scheme . '://' . $host . $webBase . '/';

return [
    'url' => $baseUrl
];