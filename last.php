<?php

require_once __DIR__ . '/vendor/autoload.php';

$yourCurrency = new \YourCurrency\Instance(__DIR__);
$yourCurrency->updateLast();
