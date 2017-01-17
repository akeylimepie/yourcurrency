<?php

namespace YourCurrency;


use GuzzleHttp\Client;

class Instance
{
    use Log;

    private $cacheDir;
    private $cacheFile;
    private $cache;
    private $fontDir;
    private $layerFile;

    function __construct($workDir)
    {
        if ($workDir) {
            $this->log('$workDir: ' . $workDir, 'cyan');
        } else {
            $this->error('$workDir ?');
        }

        $this->cacheDir = $workDir . '/cache';
        $this->cacheFile = $this->cacheDir . '/cache.yc';
        $this->fontDir = $workDir . '/font';
        $this->layerFile = $workDir . '/layer.png';

        if (is_readable($this->cacheDir)) {

            if (!is_writable($this->cacheDir)) {
                $this->error($this->cacheDir . ' must be writable!');
            }

        } else {
            $this->error($this->cacheDir . ' — where?');
        }

        if (is_readable($this->cacheFile)) {

            if (!is_writable($this->cacheFile)) {
                $this->error($this->cacheFile . ' must be writable!');
            }

            $cache_raw = file_get_contents($this->cacheFile);
        } else {
            if (file_put_contents($this->cacheFile, '') === false) {
                $this->error($this->cacheFile . ' must be writable!');
            }

            $cache_raw = null;
        }

        $this->cache = $cache_raw ? json_decode($cache_raw, true) : [];
    }

    public function updateCurrent()
    {
        $this->log('fetch data... ', 'yellow', false);

        preg_match('/([0-9.]+),([0-9.]+),([0-9.]+)/', file_get_contents('http://zenrus.ru/build/js/currents.js'),
            $matches);

        $client = new Client();
        $result = $client->get('https://query.yahooapis.com/v1/public/yql?q=select%20LastTradePriceOnly%20from%20yahoo.finance.quote%20where%20symbol%20in%20(%22USDRUB%3DX%22%2C%22EURRUB%3DX%22%2C%22ICE%22%2C%22BTCUSD%3DX%22)&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&time=' . time());

        $result = json_decode($result->getBody());

        $data = $result->query->results->quote;

        $usd = round($data[0]->LastTradePriceOnly, 2);
        $eur = round($data[1]->LastTradePriceOnly, 2);
        $oil = round($data[2]->LastTradePriceOnly, 2);
        $btc = round($data[3]->LastTradePriceOnly, 2);

        if (date('d.m') === '01.04') {
        }

        $caption = sprintf('%s /$   %s /€   $%s /btc   $%s /oil', $usd, $eur, $btc, $oil);
        $this->cache['caption'] = $caption;

        $this->log($caption, 'green');


        $this->cache['current'] = [
            'usd' => $usd,
            'eur' => $eur,
            'btc' => $btc,
            'oil' => $oil,
        ];

        if (!isset($this->cache['history'])) {
            $this->cache['history'] = [];
        }

        $this->cache['history'][] = [
            'date' => date('d.m'),
            'hour' => date('H'),
            'data' => [
                'usd' => $usd,
                'eur' => $eur,
                'btc' => $btc,
                'oil' => $oil,
            ]
        ];

        $points = 27;
        $this->cache['history'] = array_slice($this->cache['history'], -$points, $points);

        file_put_contents($this->cacheFile, json_encode($this->cache));
    }

    public function createHourlyPattern()
    {
        $current = $this->cache['current'];
        $history = $this->cache['history'];

        $text = sprintf('%s /$   %s /€',
            number_format($current['usd'], 2, ',', ''),
            number_format($current['eur'], 2, ',', '')
        );

        $text2 = sprintf('$%s /btc   $%s /oil',
            number_format($current['btc'], 2, ',', ''),
            number_format($current['oil'], 2, ',', '')
        );


        $width = 550;
        $height = 280;

        $offset = 5;
        $border = 1;

        $image = imagecreatetruecolor($width, $height);

        $border_color = imagecolorallocatealpha($image, 255, 255, 255, 50);
        $text_color = imagecolorallocate($image, 255, 255, 255);
        $chart_color = imagecolorallocate($image, 255, 255, 255);

        $overlay = imagecolorallocatealpha($image, 0, 0, 0, 110);
        imagefill($image, 0, 0, $overlay);

        imagesavealpha($image, true);

        /**
         *
         */

        imagefilledrectangle($image, $offset, $offset, $width - $offset - 1, $border + $offset - 1, $border_color);
        imagefilledrectangle(
            $image,
            $offset,
            $offset + $border,
            $border + $offset - 1,
            $height - $offset - $border - 1,
            $border_color
        );
        imagefilledrectangle(
            $image,
            $width - $border - $offset,
            $offset + $border,
            $width - $offset - 1,
            $height - $offset - $border - 1,
            $border_color
        );
        imagefilledrectangle(
            $image,
            $offset,
            $height - $border - $offset,
            $width - $offset - 1,
            $height - $offset - 1,
            $border_color
        );

        $last_point = false;
        $step = 20;

        $chart_width = (count($history) - 1) * $step;

        /**
         *
         */

        $fontSemibold = $this->fontDir . '/OpenSans-Semibold.ttf';
        $fontLite = $this->fontDir . '/OpenSans-Light.ttf';

        $font_size = 40;
        $angle = 0;


        $text_box = imagettfbbox($font_size, $angle, $fontSemibold, $text);

        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[3] - $text_box[1];

        $x = ($width / 2) - ($text_width / 2);
        $y = ($height / 2) - ($text_height / 2) - 30;

        imagettftext($image, $font_size, 0, $x, $y + 20, $text_color, $fontSemibold, $text);


        $font_size = 15;

        $text_box = imagettfbbox($font_size, $angle, $fontLite, $text2);
        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[3] - $text_box[1];

        $x = ($width / 2) - ($text_width / 2);
        $y += 65;

        imagettftext($image, $font_size, 0, $x, $y, $text_color, $fontLite, $text2);


        /**
         *
         */


        $offset_x = 5;
        $offset_y = $y + 70;

        $average = 0;
        foreach ($history as $i => $point) {
            $average += $point['data']['usd'];
        }

        $last_ceil = round($average / count($history));

        $last_date = null;

        $chart_line_color = imagecolorallocatealpha($image, 255, 255, 255, 80);

        foreach ($history as $i => $point) {

            if ($last_date && $last_date !== $point['date']) {

                $x = $offset_x + $i * $step;
                $y = $offset_y;

                imageline(
                    $image,
                    $x,
                    $y - 1,
                    $x,
                    $y - 4,
                    $chart_line_color
                );


                $font_size = 7;

                $text_box = imagettfbbox($font_size, $angle, $fontLite, $point['date']);
                $text_width = $text_box[2] - $text_box[0];
                $text_height = $text_box[3] - $text_box[1];

                $x = $x - ($text_width / 2);
                $y -= 6;

                imagettftext($image, $font_size, 0, $x, $y, $chart_line_color, $fontSemibold, $point['date']);
            }

            $last_date = $point['date'];

            $x = $offset_x + $i * $step;
            $y = $offset_y - ($point['data']['usd'] - $last_ceil) * 30;

            if ($last_point) {
                $last_x = $last_point['x'];
                $last_y = $last_point['y'];
            } else {
                $last_x = $x;
                $last_y = $y;
            }

            $last_point = [
                'x' => $x,
                'y' => $y,
            ];

            imageline(
                $image,
                $last_x,
                $last_y,
                $x,
                $y,
                $chart_color
            );
        }


        $font_size = 10;

        imagettftext($image, $font_size, 0, $offset_x + $chart_width + 3, $offset_y + 5, $border_color, $fontLite,
            $last_ceil);

        imageline(
            $image,
            $offset_x,
            $offset_y,
            $offset_x + (count($history) - 1) * $step,
            $offset_y,
            $chart_line_color
        );

        $output = $this->cacheDir . '/hourly.png';

        imagepng($image, $output);

        $this->log('hourly pattern created', 'green');

        return $output;
    }

    public function createDailyPattern()
    {
        $width = 550;
        $height = 280;

        $title = 'итоги дня';

        $image = imagecreatetruecolor($width, $height);

        $text_color = imagecolorallocate($image, 0, 0, 0);

        $overlay = imagecolorallocatealpha($image, 0, 0, 0, 110);
        imagefill($image, 0, 0, $overlay);

        imagesavealpha($image, true);

        /**
         *
         */


        $layer = imagecreatefrompng($this->layerFile);

        imagecopyresampled(
            $image,
            $layer,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $width,
            $height
        );


        /**
         *
         */

        $fontLite = $this->fontDir . '/OpenSans-Light.ttf';

        $font_size = 10;

        $x = 380;
        $y = 25;

        imagettftext($image, $font_size, 0, $x, $y, $text_color, $fontLite, $title);

        $font_size = 20;

        $y = 60;

        foreach (array_keys($this->cache['current']) as $what) {

            $value = [
                0 => @$this->cache['last'][$what],
                1 => $this->cache['current'][$what],
            ];

            $dif =
                (float)str_replace(',', '.',
                    $value[1])
                - (float)str_replace(',', '.',
                    $value[0]);

            if ($dif == 0) {
                $dif_sign = '~';
            } elseif ($dif > 0) {
                $dif_sign = '+';
            } else {
                $dif_sign = '−';
            }

            $status = 'neh';

            if (in_array($what, ['oil', 'btc'])) {

                if ($dif > 0) {
                    $status = 'good';
                }

                if ($dif < 0) {
                    $status = 'bad';
                }

            } else {

                if ($dif > 0) {
                    $status = 'bad';
                }

                if ($dif < 0) {
                    $status = 'good';
                }

            }

            switch ($status) {
                case 'neh':
                    $text_color = imagecolorallocate($image, 0, 0, 0);
                    break;
                case 'good':
                    $text_color = imagecolorallocate($image, 0, 128, 0);
                    break;
                case 'bad':
                    $text_color = imagecolorallocate($image, 255, 0, 0);
                    break;
            }

            $what_formatted = str_replace([
                'usd',
                'eur',
                'btc',
                'oil',
            ], [
                '$',
                '€',
                'btc',
                'oil',
            ], $what);

            $dif_format = sprintf('%s%s', $dif_sign, number_format(abs($dif), 2, ',', ''));

            $what_text = number_format($value[1], 2, ',', '') . ' /' . $what_formatted;

            imagettftext($image, $font_size, 0, $x, $y, $text_color, $fontLite, $what_text);

            imagettftext($image, 11, 0, $x - 8, $y + 20, $text_color, $fontLite, $dif_format);

            $y += 60;
        }


        $output = $this->cacheDir . '/daily.png';

        imagepng($image, $output);

        $this->log('daily pattern created', 'green');

        return $output;
    }

}