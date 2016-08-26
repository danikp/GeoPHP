<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Util;

class TrimUnneededDecimalsFunction
{
    /**
     * @param float|string $number
     *
     * @return string
     */
    public function __invoke($number)
    {
        if (!is_numeric($number)) {
            throw new \InvalidArgumentException('The number is not numeric');
        }

        return $this->trimDecimals(str_split($number));
    }

    private function trimDecimals($buffer)
    {
        $roundNines = false;
        $i = count($buffer);
        // arrays go to length-1 of course
        --$i;
        //  Is there a rounding error at the end?
        if ($i > 6 &&
            $buffer[$i] != '0' &&
            $buffer[$i - 1] == '0' &&
            $buffer[$i - 2] == '0' &&
            $buffer[$i - 3] == '0' &&
            $buffer[$i - 4] == '0' &&
            $buffer[$i - 5] == '0'
        ) {
            $buffer[$i--] = null;
        }

        // Do we need to round 9's?
        if ($i > 6 &&
            $buffer[$i] == '9' &&
            $buffer[$i - 1] == '9' &&
            $buffer[$i - 2] == '9' &&
            $buffer[$i - 3] == '9' &&
            $buffer[$i - 4] == '9' &&
            $buffer[$i - 5] == '9'
        ) {
            $roundNines = true;
        }

        // Now let's format the string
        for ($j = $i; $j >= 0; --$j) {
            if ($roundNines) {
                if ($buffer[$j] == '9') {
                    $buffer[$j] = null;
                } else {
                    ++$buffer[$j];
                    $roundNines = false;
                }
            } elseif ($buffer[$j] == '0') {
                $buffer[$j] = null;
            } else {
                // remove period if no decimals
                if ($buffer[$j] == '.' && $buffer[$j + 1] == null) {
                    $buffer[$j] = null;
                }
                // and we're done
                break;
            }
        }

        return implode('', $buffer);
    }

    public function __construct()
    {
    }
}
