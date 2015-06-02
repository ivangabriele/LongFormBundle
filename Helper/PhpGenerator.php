<?php

/*
 * This file is part of the IBLongFormBundle package.
 *
 * (c) Inspired Beings Ltd <http://www.inspired-beings.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/laravel/framework/blob/5.0/src/Illuminate/Support/Pluralizer.php
 */

namespace InspiredBeings\LongFormBundle\Helper;

use Doctrine\Common\Inflector\Inflector;

/**
 * Pluralizer provides a help to get singular and plural forms of English words.
 * Contains new methods to the Laravel original code :
 *  - Pluralizer::
 *
 * @see https://github.com/laravel/framework/blob/5.0/src/Illuminate/Support/Pluralizer.php
 *
 * @author Ivan Gabriele <ivan.gabriele@gmail.com>
 */
abstract class PhpGenerator
{
    /**
     * Recursive function converting an array value into arrays written in PHP source code
     *
     * @param array  $array       The array to be converted
     * @param int    $tabulations Numbers of tabulation to indent the code with
     * @param string $endOfLine   Ends of line for code formatting
     *
     * @return string The PHP source code
     */
    static public function arrayToPhp($array, $tabulations = 4, $endOfLine = "\n")
    {
        $source = "";
        $spaces = "";
        for ($index = 0; $index < $tabulations; $index++)
        {
            $spaces .= "    ";
        }

        foreach ($array as $option => $value)
        {
            $source .= (($endOfLine === "\n") ? $spaces : "") . "'$option' => ";

            if (is_array($value)) $source .= "[" . self::arrayToPhp($value, ++$tabulations, " ") . "]";
            elseif (is_bool($value)) $source .= ($value) ? "true" : "false";
            elseif (is_int($value) || is_float($value)) $source .= $value;
            else                                        $source .= "\"" . $value . "\"";

            $source .= "," . $endOfLine;
        }

        return $source;
    }
}
