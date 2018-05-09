<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/05/2018
 * Time: 17:31
 */

/**
 * Class Utils
 */
class Utils
{
    /**
     * @param array  $arr
     * @param string $key
     * @param string $prefix
     *
     * @return mixed
     */
    public static function getKey(array $arr, $key, $prefix = '_alt')
    {
        if (isset($arr[$key])) {
            return getKey($arr, $key.$prefix, $prefix);
        }

        return $key;
    }

    /**
     * @param object $object
     * @param string $method
     * @param array  $args
     */
    public static function execute($object, $method, array $args = [])
    {
        global $data;
        $key = getKey($data, get_class($object).'->'.$method);
        try {
            $data[$key]['success'] = call_user_func_array([$object, $method], $args);
        } catch (Exception $e) {
            $data[$key]['error'] = $e;
        }
    }
}
