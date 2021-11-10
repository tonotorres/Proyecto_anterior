<?php
if(!function_exists('convert_int_to_time')) {
    function convert_int_to_time($value) {
        $year = substr($value,0, 4);
        $month = substr($value, 4, 2);
        $day = substr($value, 6, 2);
        $hours = substr($value, 8, 2);
        $minutes = substr($value, 10, 2);
        $seconds = substr($value, 12, 2);

        return $year.'-'.$month.'-'.$day.' '.$hours.':'.$minutes.':'.$seconds;
    }
}