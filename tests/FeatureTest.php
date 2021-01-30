<?php
namespace BdpRaymon\RhymeSuggester\Tests;

use PHPUnit\Framework\TestCase;
use BdpRaymon\RhymeSuggester\Rhyme;
use BdpRaymon\RhymeSuggester\Samples\Database as SampleDatabase;
use BdpRaymon\RhymeSuggester\Samples\Config as SampleConfig;
use BdpRaymon\RhymeSuggester\Types\RhymeTypes;
use BdpRaymon\RhymeSuggester\Types\SelectionTypes;
use BdpRaymon\RhymeSuggester\Types\SimilarityTypes;
use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;


class FeatureTest extends TestCase {
    protected $rhyme;

    protected function setUp(): void {
        $this->rhyme = Rhyme::db(SampleDatabase::_);
    }

    public function test_self_generate() {
        $name = 'حسین';
        $list = $this->rhyme->filter([
            'name' => $name,
            'rhyme' => RhymeTypes::VOWEL,
            'count' => 1,
        ]);
        // print_r($list);
        $this->assertContains($name, $list[0]);
    }

    public function test_included() {
        $name = 'امیر';
        $nameContainer = 'امیر حسین';
        $list = $this->rhyme->filter([
            'name' => $name,
            'rhyme' => RhymeTypes::VOWEL,
            'count' => 10,
            'included' => true,
        ]);
        $bl = false;
        foreach($list as $obj) {
            $bl |= Arr::contains($obj, 
            ['name' => Rhyme::removeSpaces($nameContainer)]);
        }
        $this->assertTrue($bl == true);
    }

    public function test_algorithm_distance() {
        $name = 'حمید';
        $otherName = 'حبیب';
        $list = $this->rhyme->filter([
            'name' => $name,
            'rhyme' => RhymeTypes::VOWEL,
            'showDistance' => true,
        ]);
        $target = Arr::get($list, null, fn($value) => $value[0]['name'] == $otherName);
        $distance = $target[0][1];
        $this->assertEquals($distance, 2 * SampleConfig::_['rhymeDistance']);
    }
}
