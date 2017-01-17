# Курс валют
Берём картинку, ставим на неё курс валют и переодически выгружаем в паблик.

## Установка

Во-первых, композер.

    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install

Во-вторых, папка для кеша и временных файлов.

    $ mkdir cache

В-третьих, папка с картинками. Положите в неё котиков, женщин или что угодно.

    $ mkdir covers
    
В-четрвёртых, файл с данными для выгрузки. Скопируйте шаблон и укажите свои.

    $ cp options.ini.default options.ini

## update.php
Получение свежих цифр и обновление шаблонов.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$yourCurrency = new \YourCurrency\Instance(__DIR__);
$yourCurrency->updateCurrent();
$yourCurrency->createHourlyPattern();
$yourCurrency->createDailyPattern();
```

## hourly.php
Следующая картинка, шаблон с графиком, загрузка в каналы.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$yourCurrency = new \YourCurrency\Push(__DIR__);
$yourCurrency->nextCover();
$yourCurrency->hourlyImage();

$yourCurrency->pushTelegram();
```

## daily.php
Следующая картинка, шаблон с изменениями за день, загрузка в каналы.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$yourCurrency = new \YourCurrency\Push(__DIR__);
$yourCurrency->nextCover();
$yourCurrency->dailyImage();
$yourCurrency->updateLast();

$yourCurrency->pushTelegram();
```

## cron

```
0 8,10,12,14,16,18,20,22 * * 1,2,3,4,5 cd ~/yourcurrency && php update.php && php hourly.php
0 23 * * 1,2,3,4,5 cd ~/yourcurrency && php update.php && php daily.php
```
