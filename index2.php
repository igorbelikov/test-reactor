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
 * @param string $query
 * @return array
 */
function getPreparedSearchQuery($query) {
    $words = explode(' ', $query);
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
 * @param string $word
 * @param array $list
 * @return null|string
 */
function getWordByDictionaryList($word, array $list) {
    if (in_array($word, $list) || in_array(mb_strtolower($word), $list)) {
        return implode('|', $list);
    }
    return null;
}

/**
 * @param string $word
 * @param array $dictionary
 * @return null|string
 */
function getWordByDictionary($word, array $dictionary) {
    foreach ($dictionary as $list) {
        $wordFromDictionary = getWordByDictionaryList($word, $list);
        if ($wordFromDictionary !== null) {
            return $wordFromDictionary;
        }
    }
    return null;
}

/**
 * @param array $words
 * @param int $length
 * @return string
 */
function getCombinationByWords(array $words, $length) {
    $word = '';
    if ( ! isset($words[$length - 1])) {
        return implode(' ', $words);
    }
    for ($i = 0; $i < $length; $i++) {
        $word .= $words[$i] . ' ';
    }
    return rtrim($word);
}

$dictionary = $dictionarySimple;
echo '<h3>Dictionary:</h3>';
var_dump($dictionary);

//var_dump(buildCombinationByWords(['test', 'www', 'xxx'], 3));

//function getMultipleWordByDictionary($multipleWord, array $dictionary) {
//
//}

var_dump($searchQueries[0]);
$words = getPreparedSearchQuery($searchQueries[0]);
echo '<h3>Search query:</h3>';
var_dump($searchQueries[0]);
var_dump(implode(' ', $words));
var_dump($words);
echo '<h3>Example query:</h3>';
var_dump('(системный|системний|system & администратор|админ.)|сисадмин|“systems administrator” & офис');
echo '<h3>Prepared query:</h3>';

$ci = 0;
$wc = count($words);

// example 1:
// w1 w2 w3
// 1 2 3
// 1 2 -
// - 2 3
// 1 - -
// - 2 -
// - - 3

// example 2:
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

// structure:
// - dictionary
// - - group
// - - - item

//function checkDic($q, $d, $l = 0) {
//    $words = getPreparedSearchQuery($q);
//    $query = implode(' ', $words);
//
//    $map = [];
//    for ($i = count($words) - 1; $i >= 0; $i--) {
//        $wDic = getWordByDictionary($words[$i], $d);
//        if (isset($words[$i - 1])) {
//            $prevIsDic = getWordByDictionary($words[$i - 1], $d);
//        } else {
//            $prevIsDic = false;
//        }
//        if (isset($words[$i + 1])) {
//            $nextIsDic = getWordByDictionary($words[$i + 1], $d);
//        } else {
//            $nextIsDic = false;
//        }
//        if ($wDic) {
////            if ($nextIsDic) {
////                $query = checkDic($query, $d);
////            }
//
//            if ( ! $prevIsDic) {
//                $wDic = '(' . $wDic;
//            }
//            if ($nextIsDic) {
//                $wDic = $wDic . ' &';
//            }
//            if ( ! $nextIsDic) {
//                $wDic = $wDic . ')';
//            }
//            $query = str_replace($words[$i], $wDic, $query);
//        } else {
//            if ($prevIsDic && ! $nextIsDic) {
//                $query = str_replace($words[$i], '& ' . $words[$i], $query);
//            }
//        }
//
////        if (getWordByDictionary($words[$i], $d)) {
////            $map[$i] = true;
////        } else {
////            $map[$i] = false;
////        }
//        var_dump($map);
//    }
//    return $query;
//}
//
//$q=checkDic($searchQueries[0], $dictionary);
//var_dump($q);

exit;


function getPartsWithChild(array $parts, array $dictionary) {
    $child = [];
    if (count($parts) > 1) {
        foreach ($parts as $part) {
            $wordByDictionary = getWordByDictionary($part, $dictionary);
            if ($wordByDictionary !== null) {
                $child[] = $wordByDictionary;
            }
        }
    }
    return $child;
}

$query = implode(' ', $words);
foreach ($searchQueries as $searchQuery) {
//    checkDic($searchQuery,$dictionary);
//    continue;
    $words = getPreparedSearchQuery($searchQuery);
    echo '<h3>Search query:</h3>';
    var_dump($searchQuery);
    var_dump(implode(' ', $words));
    var_dump($words);
    echo '<h3>Example query:</h3>';
    var_dump('(системный|системний|system & администратор|админ.)|сисадмин|“systems administrator” & офис');
    echo '<h3>Prepared query:</h3>';
    $query = implode(' ', $words);
    $wordsCountMax = 3;
    $wordsCount = count($words);

    for ($i = $wordsCount; $i > 0; $i--) {
        $combination = getCombinationByWords($words, $i);
        var_dump('combination: ' . $combination);
        $wordsByDictionary = getWordByDictionary($combination, $dictionary);
        var_dump('wordsByDictionary: ' . $wordsByDictionary);

        if ($wordsByDictionary !== null) {

            $wordsByDictionaryParts = explode('|', $wordsByDictionary);
            var_dump(getPartsWithChild($wordsByDictionaryParts, $dictionary));
//            if (isHaveChildByDictionary($wordsByDictionaryParts, $dictionary)) {
//                $wordsByDictionary = '(' . $wordsByDictionary . ')';
//                $wordsByDictionaryParts = explode('|', $wordsByDictionary);
//            }

            foreach ($wordsByDictionaryParts as $wordsByDictionaryPartIndex => $wordsByDictionaryPart) {
                $spaceParts = explode(' ', $wordsByDictionaryPart);
                if (count($spaceParts) > 1) {
//                    var_dump($wordsByDictionaryParts);
//                    $wordsByDictionaryParts = str_replace(' ', ' & ', $wordsByDictionaryParts);
                    foreach ($spaceParts as $spacePart) {
                        $wordsByDictionaryPartsInSpacePart = getWordByDictionary($spacePart, $dictionary);
                        if ($wordsByDictionaryPartsInSpacePart !== null) {
                            $wordsByDictionaryParts[$wordsByDictionaryPartIndex] = str_replace(' ', ' & ', $wordsByDictionaryPart);
//                            $wordsByDictionaryParts[$wordsByDictionaryPartIndex] = str_replace($spacePart, $wordsByDictionaryPartsInSpacePart, $wordsByDictionaryParts[$wordsByDictionaryPartIndex]);
                        } else {
                            $wordsByDictionaryParts[$wordsByDictionaryPartIndex] = str_replace($wordsByDictionaryPart, "\"{$wordsByDictionaryPart}\"", $wordsByDictionaryPart);
                        }
                    }
//                    foreach ($spaceParts as $spacePart) {
//                        $wordsByDictionaryPartsInSpacePart = getWordByDictionary($spacePart, $dictionary);
//                        if ($wordsByDictionaryPartsInSpacePart !== null) {
////                            $wordsByDictionaryParts = str_replace($spacePart, $wordsByDictionaryPartsInSpacePart, $wordsByDictionaryParts);
//                        }
//                    }
                }
            }
            $wordsByDictionary = implode('|', $wordsByDictionaryParts);

            $query = str_replace($combination, $wordsByDictionary, $query);
        }
//        $words[] = $wordsByDictionary;
        var_dump('query: ' . $query);
        echo "Words: {$i} <br>";
    }

//    foreach ($words as $word) {
//        if (getWordByDictionary($word, $dictionary) === null) {
//            $query = str_replace($word, '& ' . $word, $query);
//            var_dump('www: ' .$word);
//        }
//    }
//    var_dump('query: ' . $query);

//    $cws = 0;
//    while (true) {
//        $cws++;
//        if ($cws === 1000) {
//            var_dump('MAX');
//            break;
//        }
//    }
    break;
}

// системный администратор в офис
// =>
// [[[системный]                  [администратор]]                                      ][офис]
// (системный|системний|system & администратор|админ.)|сисадмин|“systems administrator” & офис

//function getPreparedQueryRecursive($query, $max = null)
//{
//    global $dictionary;
//
//    $words = explode(' ', $query);
//    $preparedWords = [];
//
//    foreach ($words as $key => $word) {
//        if (mb_strlen($word) === 1) { // remove elements with 1 letter
//            unset($words[$key]);
//        }
//    }
//
//    if ($max === null) {
//        $max = count($words);
//    } else {
//        $max--;
//    }
//
////    $currentString = '';
////    for ($i = 0; $i < $max; $i++) {
////        $currentString .= $words[$i];
////    }
//
//    foreach ($dictionary as $list) {
//        if (in_array($query, $list) || in_array(mb_strtolower($query), $list)) {
//            $query = str_replace($query, implode('|', $list), $query);
//            break;
//        }
//    }
//
////    var_dump($words);
//
//    if ($max === 0) {
//        return $query;
//    } else {
//        return getPreparedQueryRecursive($query, $max);
//    }
//}
//
//foreach ($queries as $query) {
//    var_dump(getPreparedQueryRecursive($query));
//}
//exit;

/**
 * @param string $query
 * @param array $dictionary
 * @return string
 */
//function getPreparedQuery($query, array $dictionary)
//{
//    $words = explode(' ', $query);
//    $preparedWords = [];
//
//    foreach ($words as $key => $word) {
//        if (mb_strlen($word) === 1) { // remove elements with 1 letter
//            unset($words[$key]);
//        }
//
//        foreach ($dictionary as $list) {
//            if (in_array($word, $list) || in_array(mb_strtolower($word), $list)) {
//                $preparedWords[$word] = implode('|', $list);
//                break;
//            }
//        }
//    }
//
////    var_dump($words);
//    var_dump($preparedWords);
//
//    return '';
//}
//
//foreach ($queries as $query) {
//    var_dump(getPreparedQuery($query, $dictionary));
//}
