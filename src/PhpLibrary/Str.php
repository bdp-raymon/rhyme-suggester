<?php

namespace BdpRaymon\RhymeSuggester\PhpLibrary;

use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;

class Str {
    public static function toArray(string $string, int $length = 1): array {
        return str_split($string, $length);
    }

    /**
     * we can use KMP instead of this, but anyway
     *
     * @param   string  $token   [$token description]
     * @param   string  $string  [$string description]
     *
     * @return  bool             [return description]
     */
    public static function in(string $token, string $string) : bool {
        foreach(Arr::range(strlen($string)) as $i) {
            $bl = true;
            if ($i + strlen($token) > strlen($string)) break;
            foreach(Arr::range(strlen($token)) as $j) {
                $bl &= $string[$i + $j] == $token[$j];
            }
            if ($bl) return true;
        }
        return false;
    }

    public static function charAt(string $string, int $index) {
        if ($index >= 0) {
            return $string[$index];
        }
        return $string[strlen($string) + $index];
    }
}
