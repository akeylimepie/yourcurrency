<?php

namespace YourCurrency;


use Colors\Color;
use GuzzleHttp\Client;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class Push
{
    use Log;

    private $optionsFile;
    private $options = [];
    private $cacheDir;
    private $cacheFile;
    private $cache;
    private $coversDir;
    private $covers = [];
    private $tempFile;

    function __construct($workDir)
    {
        $color = new Color();
        $color->setForceStyle(true);

        if ($workDir) {
            echo $color('$workDir: ' . $workDir)->cyan . PHP_EOL;
        } else {
            echo $color('$workDir ?')->red . PHP_EOL;
            exit;
        }

        $this->optionsFile = $workDir . '/options.ini';
        $this->cacheDir = $workDir . '/cache';
        $this->cacheFile = $this->cacheDir . '/cache.yc';
        $this->coversDir = $workDir . '/covers';
        $this->fontDir = $workDir . '/font';
        $this->layerFile = $workDir . '/layer.png';

        if (is_readable($this->optionsFile)) {
            $this->options = parse_ini_file($this->optionsFile);
        } else {
            echo $color($this->optionsFile . ' — where?')->red . PHP_EOL;
            exit;
        }

        if (is_readable($this->cacheDir)) {

            if (!is_writable($this->cacheDir)) {
                echo $color($this->cacheDir . ' must be writable!')->red . PHP_EOL;
                exit;
            }

        } else {
            echo $color($this->cacheDir . ' — where?')->red . PHP_EOL;
            exit;
        }

        if (is_readable($this->cacheFile)) {

            if (!is_writable($this->cacheFile)) {
                echo $color($this->cacheFile . ' must be writable!')->red . PHP_EOL;
                exit;
            }

            $cache_raw = file_get_contents($this->cacheFile);
        } else {
            if (file_put_contents($this->cache, '') === false) {
                echo $color($this->cache . ' must be writable!')->red . PHP_EOL;
                exit;
            }

            $cache_raw = null;
        }

        if (!is_readable($this->coversDir)) {
            echo $color($this->coversDir . ' — where?')->red . PHP_EOL;
            exit;
        }

        $this->tempFile = $this->cacheDir . '/tmp.jpg';
        $this->cache = $cache_raw ? json_decode($cache_raw, true) : [];

        if (!isset($this->cache['last_cover'])) {
            $this->cache['last_cover'] = null;
        }
    }

    public function nextCover()
    {
        if ($handle = opendir($this->coversDir)) {

            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {
                    $this->covers[] = $entry;
                }
            }

            closedir($handle);
        }

        if ($this->covers) {
            $this->log('covers: ' . count($this->covers));
            $this->log('last cover: ' . ($this->cache['last_cover'] ?: '-'));

            natcasesort($this->covers);
            $this->covers = array_values($this->covers);

            $last_cover_index = null;

            foreach ($this->covers as $i => $file) {
                $last_cover_index = $i;

                if ($this->cache['last_cover'] != $file) {
                    continue;
                } else {
                    break;
                }
            }

            if ($last_cover_index == count($this->covers) - 1) {
                $new_cover = $this->covers[0];
            } else {
                $new_cover = $this->covers[$last_cover_index + 1];
            }

            $this->cache['last_cover'] = $new_cover;
        } else {
            $this->error('covers: 0');
        }

        $this->log('new cover: ' . $this->cache['last_cover']);
    }


    public function hourlyImage()
    {
        $this->createImage('hourly');
    }

    public function dailyImage()
    {
        $this->createImage('daily');
    }

    private function createImage($pattern_type)
    {
        $color = new Color();
        $color->setForceStyle(true);

        $width = 550;
        $height = 280;
        $ratio = $width / $height;

        $output = imagecreatetruecolor($width, $height);

        $file = $this->coversDir . '/' . $this->cache['last_cover'];
        $layer = $this->cacheDir . '/' . $pattern_type . '.png';


        if (!is_writable($file)) {
            echo $color($file . ' must be writable!')->red . PHP_EOL;
            exit;
        }

        if (!is_readable($layer)) {
            echo $color($layer . ' — where?')->red . PHP_EOL;
            exit;
        }


        if (stripos($file, '.png')) {
            $image = imagecreatefrompng($file);
        } else {
            $image = imagecreatefromjpeg($file);
        }

        $image_width = imagesx($image);
        $image_height = imagesy($image);

        $tmp_crop_width = $image_height * $ratio;

        if ($tmp_crop_width >= $image_width) {
            $crop_width = $image_width;
            $crop_height = $crop_width / $ratio;
        } else {
            $crop_width = $image_height * $ratio;
            $crop_height = $image_height;
        }

        $crop_x1 = ($image_width - $crop_width) / 2;
        $crop_y1 = ($image_height - $crop_height) / 2;

        imagecopyresampled(
            $output,
            $image,
            0,
            0,
            $crop_x1,
            $crop_y1,
            $width,
            $height,
            $crop_width,
            $crop_height
        );

        $pattern = imagecreatefrompng($layer);
        imagecopyresampled(
            $output,
            $pattern,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $width,
            $height
        );

        imagejpeg($output, $this->tempFile, 100);
    }

    public function pushTelegram()
    {
        if (empty($this->options['telegram'])) {
            throw new \Exception('TELEGRAM_CONFIG_INVALID');
        }

        $this->log('Telegram...', 'yellow', false);

        $options = $this->options['telegram'];

        $telegram = new Telegram($options['key'], $options['bot']);

        $data['chat_id'] = $options['chat'];
        $data['caption'] = $this->cache['caption'];

        $result = Request::sendPhoto($data, $this->tempFile);

        $this->log(' done!', 'green');
    }

    public function pushVk()
    {
        if (empty($this->options['vk'])) {
            throw new \Exception('VK_CONFIG_INVALID');
        }

        $this->log('Vk...', 'yellow');

        $options = $this->options['vk'];

        $client = new Client();
        $result = $client->get(sprintf(
            "https://api.vk.com/method/photos.getWallUploadServer?" .
            "&group_id={$options['group']}" .
            "&access_token={$options['token']}"
        ));

        $response = json_decode($result->getBody());


        if (@$response->response->upload_url) {

            $upload_response_raw = $client->request('POST', $response->response->upload_url, [
                'multipart' => [
                    [
                        'name' => 'photo',
                        'contents' => fopen($this->tempFile, 'r'),
                    ]
                ]
            ]);

            $upload_response = json_decode($upload_response_raw->getBody());

            if (@$upload_response->hash) {
                $save_response_raw = $client->get(
                    "https://api.vk.com/method/photos.saveWallPhoto?" .
                    "&group_id={$options['group']}" .
                    "&server={$upload_response->server}" .
                    "&photo={$upload_response->photo}" .
                    "&hash={$upload_response->hash}" .
                    "&access_token={$options['token']}"
                );

                $save_response = json_decode($save_response_raw->getBody());

                if ($photo = $save_response->response[0]->id) {
                    $post_response_raw = $client->request('POST', "https://api.vk.com/method/wall.post", [
                        'multipart' => [
                            [
                                'name' => 'owner_id',
                                'contents' => -$options['group'],
                            ],
                            [
                                'name' => 'from_group',
                                'contents' => 1,
                            ],
                            [
                                'name' => 'attachments',
                                'contents' => $photo,
                            ],
                            [
                                'name' => 'access_token',
                                'contents' => $options['token'],
                            ],
                        ]
                    ]);

                }
            }
        }

        $this->log(' done!', 'green');
    }

    public function pushTwitter()
    {
        if (empty($this->options['twitter'])) {
            throw new \Exception('TWITTER_CONFIG_INVALID');
        }

        $this->log('Twitter...', 'yellow');
        $options = $this->options['twitter'];

        $twitter = new \Twitter(
            $options['consumer_key'],
            $options['consumer_secret'],
            $options['token'],
            $options['secret']
        );
        $twitter->send('', new \CurlFile($this->tempFile));

        $this->log(' done!', 'green');
    }
}