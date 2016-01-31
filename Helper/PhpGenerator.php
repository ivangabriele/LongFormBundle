<?php

/*
 * This file is part of the IGLongFormBundle package.
 *
 * (c) Ivan Gabriele <http://www.ivangabriele.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IvanGabriele\LongFormBundle\Helper;

/**
 * PhpGenerator provides a help to generate PHP source code.
 *
 * @author Ivan Gabriele <ivan.gabriele@gmail.com>
 */
abstract class PhpGenerator
{
    /**
     * Recursive function converting an array value into arrays written in PHP source code.
     *
     * @param array  $array       The array to be converted
     * @param int    $tabulations Numbers of tabulation to indent the code with
     * @param string $endOfLine   Ends of line for code formatting
     *
     * @return string The PHP source code
     */
    public static function arrayToPhp($array, $tabulations = 4, $endOfLine = "\n")
    {
        $source = '';
        $spaces = '';
        for ($index = 0; $index < $tabulations; $index++)
        {
            $spaces .= '    ';
        }

        foreach ($array as $key => $value)
        {
            $source .= (($endOfLine === "\n") ? $spaces : '') . "'$key' => ";

            switch (gettype($value))
            {
                case 'array':
                    $source .= 'array(' . self::arrayToPhp($value, ++$tabulations, ' ') . ')';
                    break;

                case 'boolean':
                    $source .= $value ? 'true' : 'false';
                    break;

                case 'double': // float
                case 'integer':
                    $source .= $value;
                    break;

                case 'NULL':
                    $source .= 'null';
                    break;

                case 'string':
                    $source .= '"' . $value . '"';
                    break;
            }

            $source .= ',' . $endOfLine;
        }

        return $source;
    }
}
