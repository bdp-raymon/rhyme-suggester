<?php
namespace BdpRaymon\RhymeSuggester\Tests;

use PHPUnit\Framework\TestCase;
use BdpRaymon\RhymeSuggester\Rhyme;
use BdpRaymon\RhymeSuggester\Samples\Filter as SampleFilter;
use BdpRaymon\RhymeSuggester\Types\RhymeTypes;
use shamir0xe\PhpLibrary\Arr;


class FeatureTest extends TestCase {
    protected $rhyme;
    protected $db;
    protected $config;

    protected function setUp(): void {
        $this->rhyme = Rhyme::db(__DIR__ . '/../samples/output_phonetic.csv');
        $this->db = $this->rhyme->getDatabase();
        $this->config = $this->rhyme->getConfig();
    }

    public function test_self_generate() {
        $name = 'حسین';
        $list = $this->rhyme->filter([
            'name' => $name,
            'rhyme' => RhymeTypes::VOWEL,
            'count' => 1,
        ]);
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
        $this->assertEquals($distance, 2 * $this->config['rhymeDistance']);
    }

    public function test_null_filter() {
        $list = $this->rhyme->filter();
        $size = SampleFilter::_['count'];
        $size = $size == -1 ? count($this->db) : $size;
        $this->assertEquals(count($list), $size);
    }

    public function test_empty_phonetic_not_appear_in_included() {
        $name = 'عباس';
        $list = $this->rhyme->filter([
            'name' => $name,
            'rhyme' => RhymeTypes::VOWEL,
            'included' => true,
            'showDistance' => false,
        ]);
        $this->assertNotEquals($list[0]['phonetic'], '');
    }
}
