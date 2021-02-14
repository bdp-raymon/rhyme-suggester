<?php

namespace BdpRaymon\RhymeSuggester\Samples;

use BdpRaymon\RhymeSuggester\Types\RhymeTypes;
use BdpRaymon\RhymeSuggester\Types\SelectionTypes;
use BdpRaymon\RhymeSuggester\Types\SimilarityTypes;


class Filter {
    public const _ = [
        'name' => 'امیر',
        'rhyme' => RhymeTypes::VOWEL,
        'selection' => SelectionTypes::NO,
        'similarity' => SimilarityTypes::NO,
        'tashdid' => true,
        'included' => false, // if it's true, the name including this name (+ reverse rule) have priority
        'showDistance' => true,
        'count' => -1, // set it to -1 to get whole
    ];
}
