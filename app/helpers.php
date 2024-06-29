<?php

if (! function_exists('treadDiff')) {
    function treadDiff($starting_tread, $ending_tread): int|string
    {
        if (! is_int($starting_tread)) {
            return '';
        }

        if (! is_int($ending_tread)) {
            return '';
        }

        return $starting_tread - $ending_tread;
    }
}

if (! function_exists('milesPerOne32ndLoss')) {
    function milesPerOne32ndLoss($starting_tread, $ending_tread, $starting_odometer, $ending_odometer): int|string
    {
        if (! is_int($starting_tread)) {
            return '';
        }

        if (! is_int($ending_tread)) {
            return '';
        }

        if (! is_int($starting_odometer)) {
            return '';
        }

        if (! is_int($ending_odometer)) {
            return '';
        }

        if ($starting_tread - $ending_tread <= 0) {
            return 'No tread loss to calculate.';
        }

        return intval(($ending_odometer - $starting_odometer) / ($starting_tread - $ending_tread));
    }
}
