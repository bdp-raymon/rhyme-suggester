<?php

namespace BdpRaymon\RhymeSuggester\PhpLibrary;
use BdpRaymon\RhymeSuggester\PhpLibrary\Utils;

class Arr {

    public static function isEqual($a, $b) {
        // TODO
        return $a == $b;
    }

    public static function contains($holder, $subArray = []) {
        $bl = true;
        foreach ($subArray as $key => $value) {
            if (is_string($key)) {
                $bl &= isset($holder[$key]) &&
                Arr::isEqual($holder[$key], $value);
            } else {
                $bl &= isset($holder[$value]);
            }
        }
        return $bl;
    }

    public static function range(int $start, $end = null, $step = 1): array {
        if (\is_null($end)) {
            if ($start == 0) {
                return [];
            } elseif ($start > 0) {
                return range(0, $start - 1);
            } else {
                return Arr::range(0, $start, -1);
            }
        }
        if ($start == $end) {
            return [];
        }
        if ($start < $end) {
            if ($step < 0) {
                return [];
            }
            return range($start, $end - 1, $step);
        } else {
            if ($step > 0) {
                return [];
            }
            $value = $start;
            $res = [];
            while ($value > $end) {
                $res[] = $value;
                $value += $step;
            }
            return $res;
        }
    }

    public static function get(
        array $baseArray,
        callable $exctractor = null,
        callable $condition = null
    ): array {
        if (\is_null($exctractor)) {
            $exctractor = fn($value, $key) => $value;
        }
        if (\is_null($condition)) {
            $condition = fn($value, $key) => true;
        }
        $res = [];
        foreach ($baseArray as $key => $value) {
            if ($condition($value, $key)) {
                $res[] = $exctractor($value, $key);
            }
        }
        return $res;
    }

    public static function create(...$dimensions): ?array {
        if (count($dimensions) == 0) {
            return null;
        }
        $res = [];
        foreach (Arr::range($dimensions[0]) as $_) {
            $res[] = Arr::create(...array_slice($dimensions, 1));
        }
        return $res;
    }

    public static function copy(array $array): array {
        $res = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $res[$key] = Arr::copy($value);
            } else {
                $res[$key] = $value;
            }
        }
        return $res;
    }

    public static function sort(array $array, callable $compare): array {
        $cp = Arr::copy($array);
        $res = \usort($cp, function($a, $b) use ($compare) {
            $value = $compare($a, $b);
            return ($value < 0 ? -1 : ($value > 0 ? +1 : 0));
        });
        return $cp;
    }
}
