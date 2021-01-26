<?php

namespace BdpRaymon\RhymeSuggester\PhpLibrary;

use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;

class Str {
    public static function toArray(string $string, int $length = 1): array {
        return str_split($string, $length);
    }
}
