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
        'included' => true,
        'same' => Types::SAME_NO,
    ];
}
