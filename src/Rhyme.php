<?php

namespace BdpRaymon\RhymeSuggester;

use BdpRaymon\RhymeSuggester\PhpLibrary\Utils;
use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;
use BdpRaymon\RhymeSuggester\PhpLibrary\Str;
use BdpRaymon\RhymeSuggester\Types;

class Rhyme {
    public const VOWELS = 'aeiouā';
    public const INF = 1e10;
    public const SPACES = "     \n\r";
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

    private function _removeTashdid($phonetic) {
        $res = '';
        foreach (Arr::range(1, strlen($phonetic)) as $i) {
            if (
                Rhyme::isConstant($phonetic[$i]) &&
                $phonetic[$i - 1] == $phonetic[$i]) {
                continue;
            }
            $res .= $phonetic[$i];
        }
        return $res;
    }

    public static function removeParanthesis($phonetic) {
        $index = 0;
        $res = '';
        foreach(Str::toArray($phonetic) as $char) {
            if ($char == '(') {
                $index += 1;
                continue;
            }
            if ($char == ')') {
                $index -= 1;
                continue;
            }
            if ($index == 0) $res .= $char;
        }
        return $res;
    }

    public static function removeSpaces($phonetic) {
        $res = '';
        foreach(Str::toArray($phonetic) as $char) {
            if (!Str::in($char, Rhyme::SPACES)) {
                $res .= $char;
            }
        }
        return $res;
    }

    private function _getPhonetic($name) {
        $key = $this->_config('key');
        $phonetic = trim($name[$key]);
        // removing tashdid
        if (!$this->_config('tashdid')) {
            $phonetic = $this->_removeTashdid($phonetic);
        }

        // removing paranthesis
        $phonetic = Rhyme::removeParanthesis($phonetic);

        // removing spaces
        $phonetic = Rhyme::removeSpaces($phonetic);

        // selection part
        $selection = $this->_config('selection');
        switch ($selection) {
            case Types::SELECTION_FIRST:
            case Types::SELECTION_LAST:
                $phonetic = Rhyme::selection($phonetic, $selection);
                break;
            case Types::SELECTION_BOTH:
                $phonetic = Rhyme::selection($phonetic, Types::SELECTION_FIRST) .
                Rhyme::selection($phonetic, Types::SELECTION_LAST);
                break;
        }
        return $phonetic;
    }

    /**
     * return the selection part of the phonetic
     *
     * @param   string  $phonetic
     * @param   string  $selection  it could be [first / last / null-no]
     *
     * @return  [type]
     */
    public static function selection(string $phonetic, string $selection) {
        $res = '';
        switch ($selection) {
            case Types::SELECTION_FIRST:
            case Types::SAME_FIRST:
                $res = '';
                $i = 0;
                while ($i < strlen($phonetic) && Rhyme::isConstant($phonetic[$i])) {
                    $res .= $phonetic[$i];
                    ++$i;
                }
                if ($i < strlen($phonetic)) {
                    $res .= $phonetic[$i];
                }
                break;
            case Types::SELECTION_LAST:
            case Types::SAME_LAST:
                $res = '';
                $i = strlen($phonetic) - 1;
                while ($i >= 0 && Rhyme::isConstant($phonetic[$i])) {
                    $res = $phonetic[$i] . $res;
                    --$i;
                }
                if ($i >= 0) {
                    $res = $phonetic[$i] . $res;
                    --$i;
                }
                if ($i >= 0 && Rhyme::isVowel(Str::charAt($phonetic, -1))) {
                    $res = $phonetic[$i] . $res;
                }
                break;
        }
        return $res;
    }

    private function _applyFilters($a, $b) {
        // included test
        if ($this->_config('included') && (Str::in($a, $b) || Str::in($b, $a))) {
            return 2;
        }
        // similarity rule
        $selection = $this->_config('same');
        if (Rhyme::selection($a, $selection) != Rhyme::selection($b, $selection)) {
            return 0;
        }
        return 1;
    }

    public static function isVowel($ch) {
        return Str::in($ch, Rhyme::VOWELS);
    }

    public static function isConstant($ch) {
        return !Str::in($ch, Rhyme::VOWELS);
    }

    private function _charDistance($ch1, $ch2) {
        if ($ch1 == $ch2) {
            return 0;
        }
        switch ($this->_config('rhyme')) {
            case Types::VOWEL:
                if (Rhyme::isConstant($ch1) && Rhyme::isConstant($ch2)) {
                    return $this->_config('rhymeDistance');
                }
                break;
            case Types::CONSTANT:
                if (Rhyme::isVowel($ch1) && Rhyme::isVowel($ch2)) {
                    return $this->_config('rhymeDistance');
                }
                break;
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
