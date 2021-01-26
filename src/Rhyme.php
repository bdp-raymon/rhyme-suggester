<?php

namespace BdpRaymon\RhymeSuggester;

use BdpRaymon\RhymeSuggester\PhpLibrary\Utils;
use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;
use BdpRaymon\RhymeSuggester\PhpLibrary\Str;

class Rhyme {
    public const VOWELS = 'aeiouā';
    public const INF = 1e10;
    private array $db;
    private array $config;
    private array $order;
    private array $distances;
    private bool $started;

    public function __construct(array $name, array $config, array $db) {
        $this->db = $db;
        $this->config = $config;
        $this->name = $name;
        $this->started = false;
    }

    private function _config($key) {
        if (!array_key_exists($key, $this->config)) {
            return null;
        }
        return $this->config[$key];
    }

    private function _getPhonetic($name) {
        $key = $this->_config('key');
        $phonetic = trim($name[$key]);
        return $phonetic;
    }

    private function _applyFilters($a, $b) {
        return 1;
    }

    private function _charDistance($ch1, $ch2) {
        if ($ch1 == $ch2) {
            return 0;
        }
        return 1;
    }

    private function _getDistance($name) {
        $a = $this->_getPhonetic($this->name);
        $b = $this->_getPhonetic($name);
        $filterRes = $this->_applyFilters($a, $b);
        if ($filterRes == 2) {
            return 0;
        }
        if ($filterRes == 0) {
            return Rhyme::INF;
        }
        Utils::debug('%->%, %->%', $this->name['name'], $a, $name['name'], $b);

        // running the algorithm
        $xLen = strlen($a);
        $yLen = strlen($b);

        $dp = Arr::create($xLen, $yLen);
        // Utils::debug('lengths: [%, %]', $xLen, $yLen);
        foreach (Arr::range($xLen) as $i) {
            $dp[$i][$yLen - 1] = $this->_charDistance($a[$i], $b[$yLen - 1])
            + $xLen - $i - 1;
        }
        foreach (Arr::range($yLen) as $j) {
            $dp[$xLen - 1][$j] = $this->_charDistance($a[$xLen - 1], $b[$j])
            + $yLen - $j - 1;
        }
        foreach (Arr::range($xLen - 2, -1, -1) as $i) {
            foreach (Arr::range($yLen - 2, -1, -1) as $j) {
                $dp[$i][$j] = $this->_charDistance($a[$i], $b[$j])
                + $dp[$i + 1][$j + 1];
                $dp[$i][$j] = min($dp[$i][$j], 1 + $dp[$i][$j + 1]);
                $dp[$i][$j] = min($dp[$i][$j], 1 + $dp[$i + 1][$j]);
            }
        }
        // print(json_encode($dp) . "\n");
        return $dp[0][0];
    }

    /**
     * running edit-distance algorithm and ordering them
     *
     * @return  [type]
     */
    public function run() {
        $this->started = true;
        foreach (Arr::range(count($this->db)) as $i) {
            $distance = $this->_getDistance($this->db[$i]);
            $order[] = [$distance, $i];
        }
        $order = Arr::sort($order, fn($a, $b) => $a[0] - $b[0]);
        $this->order = Arr::get($order, fn($x) => $x[1]);
        $this->distances = Arr::get($order, fn($x) => $x[0]);
    }

    /**
     * returning $count nearest objects to the $name
     *
     * @param   int   $count
     * @param   bool  $showDistance
     *
     * @return  [type]
     */
    public function get(int $count, bool $showDistance) {
        if (!$this->started) {
            $this->run();
        }
        if ($count > count($this->db)) {
            $count = count($this->db);
        }
        $res = [];
        if (!$showDistance) {
            $res = Arr::get(
                Arr::range($count),
                fn($i) => $this->db[$this->order[$i]]
            );
        } else {
            $res = Arr::get(
                Arr::range($count),
                fn($i) => [$this->db[$this->order[$i]], $this->distances[$i]]
            );
        }
        return $res;
    }
}
