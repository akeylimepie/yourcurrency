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
    
В-четвёртых, файл с данными для выгрузки. Скопируйте шаблон и укажите свои.

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

$yourCurrencyPush = new \YourCurrency\Push(__DIR__);
$yourCurrencyPush->nextCover();
$yourCurrencyPush->hourlyImage();

$yourCurrencyPush->pushTelegram();
```

## daily.php
Следующая картинка, шаблон с изменениями за день, загрузка в каналы.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$yourCurrencyPush = new \YourCurrency\Push(__DIR__);
$yourCurrencyPush->nextCover();
$yourCurrencyPush->dailyImage();
$yourCurrencyPush->updateLast();

$yourCurrencyPush->pushTelegram();
```

## crontab -e

```
0 8,10,12,14,16,18,20,22 * * 1,2,3,4,5 cd ~/yourcurrency && php update.php && php hourly.php
0 23 * * 1,2,3,4,5 cd ~/yourcurrency && php update.php && php daily.php
```
