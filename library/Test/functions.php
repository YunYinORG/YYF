<?php
//import functions

if (!function_exists('array_column')) {
    function array_column($array, $column_name)
    {
        return array_map(function ($element) use ($column_name) {
            return $element[$column_name];
        }, $array);
    }
}


if (!function_exists('test_log')) {
    //log test
    function test_log($mssage)
    {
        file_put_contents(APP_PATH.'/runtime/error.txt', $mssage.PHP_EOL, FILE_APPEND);
    }
}
