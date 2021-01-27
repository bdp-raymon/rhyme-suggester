<?php

namespace BdpRaymon\RhymeSuggester\Samples;

use BdpRaymon\RhymeSuggester\Types;

class Config {
    public const _ = [
        'key' => 'phonetic',
        'rhyme' => Types::VOWEL,
        'rhymeDistance' =>  0.3,
        'tashdid' => true,
        'selection' => Types::SELECTION_NO,
        'included' => false, // if it's true, the name including this name (+ reverse rule) have priority
        'same' => Types::SAME_NO,
    ];
}
