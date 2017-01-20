<?php

namespace App;

use Imagick;
use ImagickDraw;

class ImageHelper
{
    /**
     * http://stackoverflow.com/a/28288589
     */
    public static function wordWrapAnnotation($image, $draw, $text, $maxWidth)
    {
        $text = trim($text);

        $words = preg_split('%\s%', $text, -1, PREG_SPLIT_NO_EMPTY);
        $lines = [];
        $i = 0;
        $lineHeight = 0;

        while (count($words) > 0)
        {
            $metrics = $image->queryFontMetrics($draw, implode(' ', array_slice($words, 0, ++$i)));
            $lineHeight = max($metrics['textHeight'], $lineHeight);

            // check if we have found the word that exceeds the line width
            if ($metrics['textWidth'] > $maxWidth or count($words) < $i) {
                // handle case where a single word is longer than the allowed line width (just add this as a word on its own line?)
                if ($i == 1)
                    $i++;

                $lines[] = implode(' ', array_slice($words, 0, --$i));
                $words = array_slice($words, $i);
                $i = 0;
            }
        }

        return array($lines, $lineHeight);
    }


    public static function createImage(string $text)
    {
        $width = 320;

        $draw = new ImagickDraw();
        $draw->setFont(env('FONT_PATH'));
        $draw->setFontSize(40);
        $draw->setGravity(Imagick::GRAVITY_NORTHWEST);
        $draw->setFillColor('#000');

        $canvas = new Imagick();

        list($lines, $line_height) = self::wordWrapAnnotation($canvas, $draw, $text, $width);

        $canvas->newImage($width, $line_height * count($lines), '#fff', 'png');

        for ($i = 0; $i < count($lines); $i++) {
            $canvas->annotateImage($draw, 0, 0 + ($i * $line_height), 0, $lines[$i]);
        }

        $canvas->setImageFormat('PNG');

        return $canvas;
    }
}
