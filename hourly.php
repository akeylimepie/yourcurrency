<?php
require_once __DIR__ . '/vendor/autoload.php';

$yourCurrencyPush = new \YourCurrency\Push(__DIR__);
$yourCurrencyPush->nextCover();
$yourCurrencyPush->hourlyImage();

$yourCurrencyPush->pushTelegram();
