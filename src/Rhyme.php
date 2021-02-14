<?php

namespace BdpRaymon\RhymeSuggester;

use shamir0xe\PhpLibrary\Utils;
use shamir0xe\PhpLibrary\Arr;
use shamir0xe\PhpLibrary\Str;
use shamir0xe\PhpLibrary\File;
use BdpRaymon\RhymeSuggester\Types\RhymeTypes;
use BdpRaymon\RhymeSuggester\Types\SelectionTypes;
use BdpRaymon\RhymeSuggester\Types\SimilarityTypes;
use BdpRaymon\RhymeSuggester\Samples\Config as SampleConfig;
use BdpRaymon\RhymeSuggester\Samples\Filter as SampleFilter;

class Rhyme {
    public const INF = 1e10;
    public const SPACES = "     \n\r\t";
    private array $database;
    private array $config;
    private array $filters;
    private array $order;
    private array $distances;

    public function __construct(array $db) {
        // filters and config are default
        $this->database = $db;
        $this->config = SampleConfig::_;
        $this->filter = SampleFilter::_;
    }

    public static function db($db): self {
        // Utils::debug('creating new instance');
        if (\is_string($db)) {
            // it's path, not an array!
            return new self(File::readCSVFile($db));
        }
        return new self($db);
    }

    /**
     * setting config array to the $config
     *
     * @param   array  $config
     *
     * @return  self
     */
    public function setConfig(array $config): self {
        $this->config = $config;
        return $this;
    }

    public function filter($filter = null): array {
        if (!\is_null($filter)) {
            $this->filter = $filter;
        }
        $searchKey = $this->_config('searchKey');
        $name = $this->_filter($searchKey);
        $nameObj = Arr::get(
            $this->database,
            null,
            fn($value) => Rhyme::removeSpaces($value[$searchKey]) ==
            Rhyme::removeSpaces($name)
        );
        $nameObj = count($nameObj) > 0 ? $nameObj[0] : null;
        $count = $this->_filter('count', -1);
        $showDistance = $this->_filter('showDistance', false);
        return $this->get($nameObj, $count, $showDistance);
    }

    private function _filter($key, $defaultValue = null) {
        if (!isset($this->filter[$key])) {
            return $defaultValue;
        }
        return $this->filter[$key];
    }

    private function _config($key) {
        if (!isset($this->config[$key])) {
            return null;
        }
        return $this->config[$key];
    }

    private function _removeTashdid($phonetic) {
        $res = '';
        if (strlen($phonetic) > 0) {
            $res .= $phonetic[0];
        }
        // Utils::debug('phonetic: %', $phonetic);
        foreach (Arr::range(1, strlen($phonetic)) as $i) {
            // Utils::debug("phonetic[%] = %", $i, $phonetic[$i]);
            if (
                $this->isConsonant($phonetic[$i]) &&
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
        foreach (Str::toArray($phonetic) as $char) {
            if ($char == '(') {
                $index += 1;
                continue;
            }
            if ($char == ')') {
                $index -= 1;
                continue;
            }
            if ($index == 0) {
                $res .= $char;
            }
        }
        return $res;
    }

    public static function removeSpaces($phonetic) {
        $res = '';
        foreach (Str::toArray($phonetic) as $char) {
            if (!Str::in($char, Rhyme::SPACES)) {
                $res .= $char;
            }
        }
        return $res;
    }

    private function _getPhonetic($name) {
        $key = $this->_config('phoneticKey');
        // Utils::debug('phonetic/pure: %', $name[$key]);
        $phonetic = trim($name[$key]);
        // Utils::debug('phonetic/trim: %', $phonetic);
        // removing tashdid
        if (!$this->_filter('tashdid')) {
            $phonetic = $this->_removeTashdid($phonetic);
        }
        // Utils::debug('phonetic/tashdid: %', $phonetic);

        // removing paranthesis
        $phonetic = Rhyme::removeParanthesis($phonetic);
        // Utils::debug('phonetic/paranthesis: %', $phonetic);

        // removing spaces
        $phonetic = Rhyme::removeSpaces($phonetic);
        // Utils::debug('phonetic/removeSpaces: %', $phonetic);

        // selection part
        $selection = $this->_filter('selection');
        switch ($selection) {
            case SelectionTypes::FIRST:
            case SelectionTypes::LAST:
                $phonetic = $this->selection($phonetic, $selection);
                break;
            case SelectionTypes::BOTH:
                $phonetic = $this->selection($phonetic, SelectionTypes::FIRST) .
                $this->selection($phonetic, SelectionTypes::LAST);
                break;
        }
        // Utils::debug('phonetic/selection: %', $phonetic);
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
    public function selection(string $phonetic, ?string $selection) {
        $res = '';
        switch ($selection) {
            case SelectionTypes::FIRST:
            case SimilarityTypes::FIRST:
                $res = '';
                $i = 0;
                while ($i < strlen($phonetic) && $this->isConsonant($phonetic[$i])) {
                    $res .= $phonetic[$i];
                    ++$i;
                }
                if ($i < strlen($phonetic)) {
                    $res .= $phonetic[$i];
                }
                break;
            case SelectionTypes::LAST:
            case SimilarityTypes::LAST:
                $res = '';
                $i = strlen($phonetic) - 1;
                while ($i >= 0 && $this->isConsonant($phonetic[$i])) {
                    $res = $phonetic[$i] . $res;
                    --$i;
                }
                if ($i >= 0) {
                    $res = $phonetic[$i] . $res;
                    --$i;
                }
                if ($i >= 0 && $this->isVowel(Str::charAt($phonetic, -1))) {
                    $res = $phonetic[$i] . $res;
                }
                break;
            case SimilarityTypes::NO:
                $res = '';
                break;
            case SelectionTypes::NO:
            case null:
                $res = $phonetic;
                break;
        }
        return $res;
    }

    private function _applyFilters($a, $b) {
        // included test
        if ($this->_filter('included') && (Str::in($a, $b) || Str::in($b, $a))) {
            return 2;
        }
        // similarity rule
        $similarity = $this->_filter('similarity');
        // Utils::debug('selection: %', $selection);
        if (!\is_null($similarity) && 
        $this->selection($a, $similarity) != $this->selection($b, $similarity)) {
            return 0;
        }
        return 1;
    }

    public function isVowel($ch) {
        return Str::in($ch, $this->_config('vowels'));
    }

    public function isConsonant($ch) {
        return !Str::in($ch, $this->_config('vowels'));
    }

    private function _charDistance($ch1, $ch2) {
        if ($ch1 == $ch2) {
            return 0;
        }
        switch ($this->_filter('rhyme')) {
            case RhymeTypes::VOWEL:
                if ($this->isConsonant($ch1) && $this->isConsonant($ch2)) {
                    return $this->_config('rhymeDistance');
                }
                break;
            case RhymeTypes::CONSONANT:
                if ($this->isVowel($ch1) && $this->isVowel($ch2)) {
                    return $this->_config('rhymeDistance');
                }
                break;
        }
        return 1;
    }

    private function _getDistance(?array $nameObj, array $name) {
        if (\is_null($nameObj)) {
            return 0;
        }
        $a = $this->_getPhonetic($nameObj);
        $b = $this->_getPhonetic($name);
        $filterRes = $this->_applyFilters($a, $b);
        // Utils::debug('filter res = %', $filterRes);
        if ($filterRes == 2) {
            return 0;
        }
        if ($filterRes == 0) {
            return Rhyme::INF;
        }
        // Utils::debug('%->%, %->%', $nameObj['name'], $a, $name['name'], $b);

        // running the algorithm
        $xLen = strlen($a);
        $yLen = strlen($b);
        if ($xLen == 0 || $yLen == 0) {
            return max($xLen, $yLen);
        }

        $dp = Arr::create($xLen, $yLen);
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
                // Utils::debug('[%, %]', $i, $j);
                $dp[$i][$j] = $this->_charDistance($a[$i], $b[$j])
                + $dp[$i + 1][$j + 1];
                $dp[$i][$j] = min($dp[$i][$j], 1 + $dp[$i][$j + 1]);
                $dp[$i][$j] = min($dp[$i][$j], 1 + $dp[$i + 1][$j]);
            }
        }
        return $dp[0][0];
    }

    /**
     * running edit-distance algorithm and ordering them
     *
     * @return  [type]
     */
    private function _run(?array $nameObj): void {
        foreach (Arr::range(count($this->database)) as $i) {
            $distance = $this->_getDistance($nameObj, $this->database[$i]);
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
    public function get(?array $nameObj, int $count, bool $showDistance): array {
        // running each time
        $this->_run($nameObj);
        if ($count == -1) {
            $count = count($this->database);
        }
        if ($count > count($this->database)) {
            $count = count($this->database);
        }
        $res = [];
        if (!$showDistance) {
            $res = Arr::get(
                Arr::range($count),
                fn($i) => $this->database[$this->order[$i]],
                fn($i) => $this->distances[$i] < Rhyme::INF
            );
        } else {
            $res = Arr::get(
                Arr::range($count),
                fn($i) => [$this->database[$this->order[$i]], $this->distances[$i]],
                fn($i) => $this->distances[$i] < Rhyme::INF
            );
        }
        return $res;
    }
}
