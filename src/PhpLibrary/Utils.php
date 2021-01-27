<?php

namespace BdpRaymon\RhymeSuggester\PhpLibrary;

use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;
use BdpRaymon\RhymeSuggester\PhpLibrary\Str;

class Utils {
    public static function debug(string $string, ...$args): void {
        $res = '';
        $argIndex = 0;
        foreach (Str::toArray($string) as $char) {
            if ($char != '%') {
                $res .= $char;
            } else {
                $res .= $args[$argIndex];
                $argIndex += 1;
            }
        }
        while ($argIndex < count($args)) {
            $res .= ', ' . $args[$argIndex];
            $argIndex += 1;
        }
        $res .= "\n";
        print($res);
    }
}
