<?php

require_once __DIR__ . '/vendor/autoload.php';

$yourCurrency = new \YourCurrency\Push(__DIR__);
$yourCurrency->nextCover();

$yourCurrency->dailyImage();

$yourCurrency->pushTelegram();
