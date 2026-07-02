<?php
declare(strict_types=1);

function redirectionClient( string $url): void{
    header("Location: " . $url);
    exit();
}