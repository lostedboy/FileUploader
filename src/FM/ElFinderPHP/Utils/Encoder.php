<?php

namespace FM\ElFinderPHP\Utils;
/**
 * User: Alexander Egurtsov
 * Date: 5/21/14
 * Time: 11:33 AM
 */
interface Encoder
{
    public static function transliterate($text, $separator = '.');
}