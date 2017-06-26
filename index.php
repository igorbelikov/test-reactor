<?php

$searchQueries = [
    "Системный администратор в офис",
    "Системный администратор баз данных и безопасности",
    "Системный администратор БД и безопасности",
];

$dictionaryAdvanced = [
    // администратор
    ['администратор', 'админ.'],
    // сисадмин
    ['системный администратор', 'сисадмин', 'systems administrator', 'DevOps engineer'],
    // бд
    ['баз данных', 'БД', 'database'],
    // безопасности
    ['безопасности', 'безпеки', 'security'],
    // dba
    ['администратор баз данных', 'адміністратор БД', 'администратор БД', 'database administrator', 'dba'],
    // системный
    ['системный', 'system', 'системний'],
];

$dictionarySimple = [
    ['системный', 'системний', 'system'],
    ['администратор', 'админ.'],
    ['системный администратор', 'сисадмин', 'systems administrator'],
];

/**
 * Class Dictionary
 */
class Dictionary
{
    /**
     * @var array|DictionaryGroup[]
     */
    public $groups = [];

    /**
     * Dictionary constructor.
     * @param array $groups
     */
    public function __construct(array $groups)
    {
        foreach ($groups as $group) {
            $this->groups[] = new DictionaryGroup($group);
        }
    }

    /**
     * @param string $item
     * @return null|DictionaryGroup
     */
    public function getGroupByItem($item)
    {
        foreach ($this->groups as $group) {
            if ($group->getItem($item)) {
                return $group;
            }
        }
        return null;
    }
}

/**
 * Class DictionaryGroup
 */
class DictionaryGroup
{
    /**
     * @var array|DictionaryItem[]
     */
    public $items = [];

    /**
     * DictionaryGroup constructor.
     * @param array $items
     */
    public function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->items[] = new DictionaryItem($item);
        }
    }

    /**
     * @param string $item
     * @return DictionaryItem|bool
     */
    public function getItem($item)
    {
        $index = array_search($item, $this->toArray());
        if ($index === false) {
            $index = array_search(mb_strtolower($item), $this->toArray());
        }
        return $index === false ? $index : $this->items[$index];
    }

    /**
     * @return string
     */
    public function toString()
    {
        return implode('|', $this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($item) {
            return $item->item;
        }, $this->items);
    }
}

/**
 * Class DictionaryItem
 */
class DictionaryItem
{
    /**
     * @var string
     */
    public $item = '';

    /**
     * DictionaryItem constructor.
     * @param string $item
     */
    public function __construct($item)
    {
        $this->item = mb_strtolower($item);
    }

    /**
     * @param DictionaryParser $parser
     * @return string
     */
    public function isCombination($parser)
    {
        $items = DictionaryParser::getPreparedSearchQuery($this->item);
        if (count($items) > 1) {
            foreach ($items as $item) {
                $group = $parser->dictionary->getGroupByItem($item);
                if ($group && ! strstr($this->item, $group->toString())) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param DictionaryParser $parser
     * @return string
     */
    public function prepareOperators($parser)
    {
        $items = DictionaryParser::getPreparedSearchQuery($this->item);
        $condition = DictionaryParser::getCombinationByItems($items);

        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $group = $parser->dictionary->getGroupByItem($item);
                if ($group) {
                    $lBracket = $key === 0 ? '(' : '';
                    $rBracket = $key === (count($items) - 1) ? ')' : '';
                    $operatorAnd = isset($items[$key + 1]) ? ' &' : '';
                    $condition = str_replace($item, $lBracket . $group->toString() . $operatorAnd . $rBracket, $condition);
                }
            }
            if (strstr($condition, '(') || strstr($condition, ')')) {
                $lBracketCount = substr_count($condition, '(');
                $rBracketCount = substr_count($condition, ')');
                if ($lBracketCount > $rBracketCount) {
                    $condition .= ')';
                } else if ($rBracketCount > $lBracketCount) {
                    $condition = '(' . $condition;
                }
            }
        }

        return $condition;
    }
}

/**
 * Class DictionaryParser
 */
class DictionaryParser
{
    /**
     * @var Dictionary
     */
    public $dictionary;

    /**
     * DictionaryParser constructor.
     * @param Dictionary $dictionary
     */
    public function __construct($dictionary)
    {
        $this->dictionary = $dictionary;
    }

    /**
     * @param string $searchQuery
     * @return string
     */
    public function parse($searchQuery)
    {
        $items = DictionaryParser::getPreparedSearchQuery($searchQuery);
        $string = DictionaryParser::getCombinationByItems($items);

        // structure:
        //
        // classes:
        // - DictionaryParser
        // - - Dictionary
        // - - - DictionaryGroup
        // - - - - DictionaryItem
        //
        // data:
        // - dictionary
        // - - group
        // - - - item

        // example 1:
        //
        // w1 w2 w3
        // 1 2 3 [0,2] 3 words 1 combinations
        // 1 2 - [0,1] 2 words 2 combinations
        // - 2 3 [1,2] 2 words 2 combinations
        // 1 - - [0,0] 1 word 3 combinations
        // - 2 - [1,1] 1 word 3 combinations
        // - - 3 [2,2] 1 word 3 combinations

        // example 2:
        //
        // w1 w2 w3 w4
        // 1 2 3 4
        // 1 2 3 -
        // - 2 3 4
        // 1 2 - -
        // - 2 3 -
        // - - 3 4
        // 1 - - -
        // - 2 - -
        // - - 3 -
        // - - - 4

        $dictionary = $this->dictionary;
        $this->eachItems($items, function ($combination) use ($dictionary, &$string) {
            $group = $dictionary->getGroupByItem($combination);
            if ($group && ! strstr($string, $group->toString())) {
                $groupParse = $this->parseGroup($group);
                $prev = $groupParse;
                if ($groupParse === $prev) {
                    $groupParse .= ' &';
                }
                $string = str_replace($combination, $groupParse, $string);
            }
        });

        return preg_replace('/ &$/', '', $string);
    }

    /**
     * @param DictionaryGroup $group
     * @return string
     */
    public function parseGroup($group)
    {
        $replace = $group->toString();
        foreach ($group->items as $item) {
            $replace = $this->parseItem($item, $replace);
        }
        return $replace;
    }

    /**
     * @param DictionaryItem $item
     * @param string $replace
     * @return string
     */
    public function parseItem($item, $replace)
    {
        if ($item->isCombination($this)) {
            $replace = str_replace($item->item, $item->prepareOperators($this), $replace);
        } else {
            if (strstr($item->item, ' ')) {
                $replace = str_replace($item->item, "\"{$item->item}\"", $replace);
            }
        }
        return $replace;
    }

    /**
     * @param array $items
     * @param callable $callback
     */
    public static function eachItems(array $items, callable $callback) {
        $count = count($items);
        for ($wordsCount = $count; $wordsCount > 0; $wordsCount--) {
            $combinations = $count - $wordsCount + 1;
            for ($start = 0; $start < $combinations; $start++) {
                $end = $start;
                if ($wordsCount === $count) {
                    $end = $wordsCount - $combinations;
                } else if ($wordsCount > $combinations) {
                    $end = $start + $combinations;
                    if ($count % 2 !== 0 && isset($items[$end + 1])) {
                        $end++;
                    }
                } else if ($wordsCount === $combinations) {
                    $end = $start + 1;
                    if ($count % 3 !== 0 && isset($items[$end + 1])) {
                        $end++;
                    }
                } else if ($combinations === $count) {
                    $end = $start;
                } else if ($wordsCount < $combinations) {
                    $end = $start + ($combinations - $wordsCount);
                    if ($count % 2 !== 0 && isset($items[$end - 1])) {
                        $end--;
                    }
                }
                call_user_func($callback, DictionaryParser::getCombinationByItems($items, $start, $end), $wordsCount, $count, $combinations, $start, $end);
            }
        }
    }

    /**
     * @param string $searchQuery
     * @return array
     */
    public static function getPreparedSearchQuery($searchQuery)
    {
        $words = explode(' ', $searchQuery);
        foreach ($words as $key => $word) {
            if (mb_strlen($word) === 1) { // remove elements with 1 letter
                unset($words[$key]);
            } else {
                $words[$key] = mb_strtolower($word);
            }
        }
        return array_values($words);
    }

    /**
     * @param array $items
     * @param int $start
     * @param int $end
     * @return string
     */
    public static function getCombinationByItems(array $items, $start = -1, $end = -1)
    {
        if ($start === -1) {
            return implode(' ', $items);
        }
        if ($end === -1) {
            $end = count($items) - 1;
        }
        $combination = '';
        for ($i = $start; $i <= $end; $i++) {
            $combination .= $items[$i] . ' ';
        }
        return rtrim($combination);
    }
}

$currentDictionary = $dictionaryAdvanced;
$parser = new DictionaryParser(new Dictionary($currentDictionary));

echo '<pre><h3>Dictionary:</h3>';
var_dump($currentDictionary);

echo '<h3>Example query:</h3>';
var_dump('(системный|системний|system & администратор|админ.)|сисадмин|“systems administrator” & офис');

foreach ($searchQueries as $index => $searchQuery) {
    echo "<hr><h3>Search query #{$index}: {$searchQuery}</h3>";
    var_dump(DictionaryParser::getPreparedSearchQuery($searchQuery));

    echo '<h3>Prepared query:</h3>';
    var_dump($parser->parse($searchQuery));
}
echo '</pre>';

