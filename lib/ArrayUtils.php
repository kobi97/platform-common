<?php

namespace Ridibooks\Platform\Common;

class ArrayUtils
{
    /**
     * $array1을 기준으로 $array2와 비교했을 때, $array1이 바뀐 값들을 리턴한다.
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function getArrayDiffRecursively($array1, $array2)
    {
        $diff_array = [];

        // value 비교
        if (!is_array($array1)) {
            if ($array1 != $array2) {
                return $array1;
            } else {
                return [];
            }
        }

        // array 비교
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!is_array($array2[$key])) {
                    $diff_array[$key] = $value;
                } else {
                    $sub_diff_array = ArrayUtils::getArrayDiffRecursively($value, $array2[$key]);
                    if (count($sub_diff_array)) {
//                        $diff_array[$key] = $sub_diff_array;
                        $diff_array[$key] = $value;
                    }
                }
            } elseif ($array2[$key] != $value) {
                $diff_array[$key] = $value;
            }
        }
        return $diff_array;
    }

    public static function joinDicts($leftDicts, $rightDicts, $leftDictsColumnIndex = 0, $rightDictsColumnIndex = 0)
    {
        if (count($leftDicts) == 0) {
            return $rightDicts;
        }
        if (count($rightDicts) == 0) {
            return $leftDicts;
        }

        $leftKeys = array_keys($leftDicts[0]);
        $leftKey = $leftKeys[$leftDictsColumnIndex];
        $rightKeys = array_keys($rightDicts[0]);
        $rightKey = $rightKeys[$rightDictsColumnIndex];

        foreach ($leftDicts as $lk => $lv) {
            foreach ($rightDicts as $rk => $rv) {
                if ($lv[$leftKey] != $rv[$rightKey]) {
                    continue;
                }
                $leftDicts[$lk] = array_merge($lv, $rv);
            }
        }

        $keys = [];
        foreach ($leftDicts as $dict) {
            $keys = array_unique(array_merge($keys, array_keys($dict)));
        }

        foreach ($leftDicts as $dictKey => $dict) {
            foreach ($keys as $key) {
                if (!isset($leftDicts[$dictKey][$key])) {
                    $leftDicts[$dictKey][$key] = null;
                }
            }
        }

        return $leftDicts;
    }

    /**
     * @param $from_list
     * @param $to_list
     * @return array
     * [input left_list]   [input right_list]
     * 111              => 444
     * 222              => 444
     * 444              => 333
     * 444              => 111
     * 555              => 666
     * [return]
     * array(
     *   array(111, 222, 444, 333)
     *   array(555, 666)
     * )
     */
    public static function convertLinksToSets($from_list, $to_list)
    {
        $sets = [];
        $setid_by_bid_map = [];
        foreach ($from_list as $i => $b_id) {
            $b_id2 = $to_list[$i];
            if (!strlen($b_id)) {
                continue;
            }
            if (!strlen($b_id2)) {
                continue;
            }

            if (!isset($setid_by_bid_map[$b_id]) && !isset($setid_by_bid_map[$b_id2])) {
                //no set
                $setid = count($sets);
                $setid_by_bid_map[$b_id] = $setid;
                $setid_by_bid_map[$b_id2] = $setid;
                $sets[$setid][] = $b_id;
                $sets[$setid][] = $b_id2;
            } elseif (isset($setid_by_bid_map[$b_id]) || isset($setid_by_bid_map[$b_id2])) {
                //found 1 set
                $setid = (isset($setid_by_bid_map[$b_id]) ? $setid_by_bid_map[$b_id] : $setid_by_bid_map[$b_id2]);
                $setid_by_bid_map[$b_id] = $setid;
                $setid_by_bid_map[$b_id2] = $setid;
                $sets[$setid][] = $b_id;
                $sets[$setid][] = $b_id2;
            } else {
                //found 2 sets => merge
                $setid_to_merge = $setid_by_bid_map[$b_id];
                $setid_to_destory = $setid_by_bid_map[$b_id2];
                $set_to_destory = $sets[$setid_to_destory];
                foreach ($set_to_destory as $merge_bid) {
                    $setid_by_bid_map[$merge_bid] = $setid_to_merge;
                }
                $sets[$setid_to_merge] = array_merge($sets[$setid_to_merge], $set_to_destory);
                $sets[$setid_to_merge] = array_filter(array_unique($sets[$setid_to_merge]));
                unset($sets[$setid_to_destory]);
            }
        }
        foreach ($sets as &$set) {
            $set = array_unique($set);
        }
        return $sets;
    }

    public static function arrayFilterByKey($dict, $keys)
    {
        $ret = [];
        foreach ($keys as $key) {
            $ret[$key] = $dict[$key];
        }
        return $ret;
    }

    public static function convertValuesToKey($array)
    {
        return array_combine($array, $array);
    }

    /**
     * @param $object
     * @return array
     */
    public static function parseArray($object)
    {
        if (is_array($object) === false) {
            if (is_null($object)) {
                return [];
            } else {
                return [$object];
            }
        }
        return $object;
    }

    /**
     * 순차적인 array item 을 생략해주는 메서드입니다.
     * @example [1, 2, 3, 5, 6, 8] -> ['1~3', '5~6', '8']
     *
     * @param array  $values
     *
     * @param string $glue
     *
     * @return \string[]
     */
    public static function shortenSequential(array $values, $glue = '~')
    {
        $result = [];
        $sequential_values = [];

        sort($values);
        foreach ($values as $index => $value) {
            $previous_value = $values[$index - 1];

            if (!in_array(($value - 1), $values)) {
                if (count($sequential_values) > 0) {
                    $result[] = self::implodeSequential($glue, $sequential_values);
                }
                $sequential_values = [$value];
            } else {
                if ($previous_value + 1 === $value) {
                    $sequential_values[] = $value;
                }
            }

            if ($value === end($values)) {
                $result[] = self::implodeSequential($glue, $sequential_values);
            }
        }

        return $result;
    }

    /**
     * @param string $glue
     * @param array  $values
     *
     * @return string
     */
    private static function implodeSequential(string $glue, array $values)
    {
        $first_value = reset($values);
        $last_value = end($values);
        if ($first_value !== $last_value) {
            return $first_value . $glue . $last_value;
        }

        return $first_value;
    }
}
