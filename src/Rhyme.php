<?php
namespace Raymon\RhymeSuggester;

class Rhyme {
    public const VOWELS = "aeiouā";
    public const INF = 1e10;
    private array $db;
    private array $config;
    private array $order;
    private bool $started;

    public function __construct(array $name, array $config, array $db) {
        $this->db = $db;
        $this->config = $config;
        $this->nameQuery = $name;
    }

    public function get(int $count, bool $showDistance) {
        if (!$started) $this->run();
        
    }
}
