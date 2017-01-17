<?php

namespace YourCurrency;

use Colors\Color;

trait Log
{

    /** @var null|Color */
    private $logger = null;

    private function initLogger()
    {
        if ($this->logger == null) {
            $this->logger = new Color();
            #$this->logger->setForceStyle(true);
        }
    }

    private function log($text, $color = false, $eol = true)
    {
        $this->initLogger();

        if ($color) {
            $message = $this->logger->apply($color, $text);
        } else {
            $message = $text;
        }

        echo $message;

        if ($eol) {
            echo PHP_EOL;
        }
    }

    private function error($text)
    {
        $this->initLogger();

        $message = $this->logger->apply('red', $text);
        echo $message . PHP_EOL;
        exit;
    }

}