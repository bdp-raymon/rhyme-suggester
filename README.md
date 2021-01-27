# rhyme-suggester
Rhyme Suggester Php package

[![Total Downloads](https://img.shields.io/packagist/dt/bdp-raymon/rhyme-suggester.svg?style=flat-square)](https://packagist.org/packages/bdp-raymon/rhyme-suggester)

This package helps you to find the nearest object to your desired object, using [edit-distance algorithm](https://en.wikipedia.org/wiki/Edit_distance#:~:text=In%20computational%20linguistics%20and%20computer,one%20string%20into%20the%20other.), specifically for phonetic of words.
In the other words, this package will suggest closest elements of the database to the desired word based on it's pronunciation.

## Installation
For installing this package, you just need to require it via composer in the root of your project:

```bash
composer require bdp-raymon/rhyme-suggester
```

## Basic Usage
In order to getting familiar with the pacakge, we provided a small database in the `samples` directory. You just need to run codes bellow to see the rhytmic related words to `امیر`:

``` php
use BdpRaymon\RhymeSuggester\Rhyme;
use BdpRaymon\RhymeSuggester\Samples\Database as SampleDatabase;
use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;

// this will return the array containing the nearest objects to the ‍‍`امیر`
$list = Rhyme::db(SampleDatabase::_)->filter();
$output = Arr::get($list, fn($value) => $value[0]['name']);
print_r($output);
```
The generated output should be something like this:
``` php
[
     "امیر",
     "عمید",
     "امین",
     "عزیز",
     "عقیل",
     "عقیق",
     "ادیب",
     "عفیف",
     "علیم",
     "اوین"
]
```

## Full Usage
### Feeding Database
You can feed database to the package with two different ways. First way have been already shown in the example above, with injecting it as an array to the static `Rhyme::db` function. Secondly, you can provide a `.csv` file of your own dataset and use it as an argument:
```php
Rhyme::db("PATH-TO-THE-CSV-FILE.csv");
```

### Configuration
The sample configuration file is placed in the `samples/Config.php` file. The fields that should be used in the config file are as follow:
* **searchKey**
It's the key that package uses to search your query to find the query object in the database. for example in our database, we use *name* key 
* **phoneticKey**
The key in the database that we use edit-distance algorithm on it. Usually it should be the *phonetic* field.
* **vowels**
Specify the vowels of your language alphabet in a single string here.
* **rhymeDistance**
It's the distance between **not important characters** in the phonetic algorithm. we will discuss about it later.

Config file need to be set after instantiating an object of the `Rhyme` class, with following structure:
```php
$config = [
    'searchKey' => 'name',
    'phoneticKey' => 'phonetic',
    'vowels' => 'aeiouā',
    'rhymeDistance' =>  0.1,
];
Rhyme::db($db)->setConfig($config);
```

### Running a Query and Filtering
After specifying the database and configuration, you can run queries using `filter` function. This function allows customizing the search query with what you desired. Allowable fields in the filter array are as follow:
* **config searchKey**
The search key you have configured as a config in previous step. In our example we used *name* key for *searchKey*.
* **rhyme**
The rhyme field accepts two values:
    * RhymeTypes::VOWEL
    * RhymeTypes::CONSONENT
* **selection**
* **similarity**
* **tashdid**
* **included**
* **showDistance**
* **count**

## Example
The complete working example should be something like this:
```php
use BdpRaymon\RhymeSuggester\Rhyme;
use BdpRaymon\RhymeSuggester\Types\RhymeTypes;
use BdpRaymon\RhymeSuggester\Types\SelectionTypes;
use BdpRaymon\RhymeSuggester\Types\SimilarityTypes;
use BdpRaymon\RhymeSuggester\PhpLibrary\Arr;

$dbPath = __DIR__ . "/vendor/bdp-raymon/rhyme-suggester/samples/output_phonetic.csv";
$config = [
    'searchKey' => 'name',
    'phoneticKey' => 'phonetic',
    'vowels' => 'aeiouā',
    'rhymeDistance' =>  0.1,
];
$filter = [
    'name' => 'مهدی',
    'rhyme' => RhymeTypes::VOWEL,
    'selection' => SelectionTypes::NO,
    'similarity' => SimilarityTypes::FIRST,
    'tashdid' => false,
    'included' => true, 
    'showDistance' => true,
    'count' => 15,
];
$list = Rhyme::db($dbPath)->setConfig($config)->filter($filter);
$output = Arr::get($list, fn($value) => $value[0]['name']);
print_r($output);
```
And the output should be like this:
```php
[
    "مهدی",
    "امیر مهدی",
    "مهدیس",
    "امیرمهدی",
    "اوتانا",
    "مهدیسا",
    "هستی",
    "فخری",
    "نرسی",
    "سلمی",
    "بدری",
    "زردیس",
    "تقی",
    "پردیس",
    "پری",
]
```