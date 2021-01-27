<?php

namespace BdpRaymon\RhymeSuggester\PhpLibrary;

use BdpRaymon\RhymeSuggester\PhpLibrary\Utils;

class File {
    /**
     * returns the csv file contents as an php array
     *
     * @param   string  $path
     * @param   string  $delimiter
     *
     * @return  [type]
     */
    public static function readCSVFile(string $path, string $delimiter = ",") {
        // Utils::debug('reading csv file: %', $path);
        if (!File::fileExists($path)) {
            throw new Exception('File not found');
        }
        $file = fopen($path, "r");
        $row = 0;
        $res = [];
        $columns = [];
        while (!\feof($file)) {
            $line = \fgets($file);
            $tokens = \explode($delimiter, $line);
            $temp = [];
            foreach ($tokens as $index => $token) {
                $token = trim($token);
                if ($row == 0) {
                    $columns[] = $token;
                } else {
                    $temp[$columns[$index]] = $token;
                }
            }
            if ($row > 0 && count($temp) == count($columns)) {
                $res[] = $temp;
            }
            $row += 1;
        }
        return $res;
    }

    /**
     * return true if the file exists in the path
     *
     * @param   string  $path 
     *
     * @return  [type]
     */
    public static function fileExists(string $path) {
        return \file_exists($path);
    }
}
