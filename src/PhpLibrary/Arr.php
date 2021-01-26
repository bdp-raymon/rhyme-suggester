<?php

namespace BdpRaymon\RhymeSuggester\PhpLibrary;

class Arr {
    public static function range(int $start, $end = null, $step = 1) {
        if ($end == null) {
            return range(0, $start - 1);
        }
        if ($step == 0) {
            return [$start];
        }
        if ($start < $end) {
            if ($step < 0) {
                return [];
            }
            return range($start, $end, $step);
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
    ) {
        if ($exctractor == null) {
            $exctractor = fn($value, $key) => $value;
        }
        if ($condition == null) {
            $condition = fn($value, $key) => true;
        }
        $res = [];
        foreach ($baseArray as $key => $value) {
            if ($condition($key, $value)) {
                $res[] = $exctractor($value, $key);
            }
        }
        return $res;
    }

    public static function create(...$dimensions) {
        if (count($dimensions) == 0) {
            return null;
        }
        $res = [];
        foreach (Arr::range($dimensions[0]) as $_) {
            $res[] = Arr::create(...array_slice($dimensions, 1));
        }
        return $res;
    }

    public static function copy($array) {
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

    public static function sort($array, callable $compare) {
        $cp = Arr::copy($array);
        $res = \usort($cp, function($a, $b) use ($compare) {
            $value = $compare($a, $b);
            return ($value < 0 ? -1 : ($value > 0 ? +1 : 0));
        });
        return $cp;
    }
}
