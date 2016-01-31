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
 * Pluralizer provides a help to get singular and plural forms of English words.
 *
 * Additional method (in comparison to the Laravel original code) :
 *  - Pluralizer::pluralUnderscore($value) : pluralize underscored variables name
 *
 * @see    https://github.com/laravel/framework/blob/5.0/src/Illuminate/Support/Pluralizer.php
 *
 * @author Ivan Gabriele <ivan.gabriele@gmail.com>
 */
abstract class Pluralizer
{
    /**
     * Uncountable word forms.
     *
     * @var array
     */
    public static $uncountable = array(
        'audio',
        'bison',
        'chassis',
        'compensation',
        'coreopsis',
        'data',
        'deer',
        'education',
        'equipment',
        'fish',
        'gold',
        'information',
        'money',
        'moose',
        'offspring',
        'plankton',
        'police',
        'rice',
        'series',
        'sheep',
        'species',
        'swine',
        'traffic',
    );

    /**
     * Get the plural form of an English word.
     *
     * @param string $value
     *
     * @return string
     */
    public static function plural($value)
    {
        if (static::uncountable($value))
        {
            return $value;
        }

        $plural = \Doctrine\Common\Inflector\Inflector::pluralize($value);

        return static::matchCase($plural, $value);
    }

    /**
     * Get the plural form of an underscored variable name made of English words.
     *
     * @param string $value
     *
     * @return string
     */
    public static function pluralUnderscore($value)
    {
        $words = explode('_', $value);
        $pluralizedValue = '';

        foreach ($words as $word)
        {
            $pluralizedValue .= static::plural($word) . '_';
        }

        return substr($pluralizedValue, 0, strlen($pluralizedValue) - 1);
    }

    /**
     * Get the singular form of an English word.
     *
     * @param string $value
     *
     * @return string
     */
    public static function singular($value)
    {
        if (static::uncountable($value))
        {
            return $value;
        }

        $singular = \Doctrine\Common\Inflector\Inflector::singularize($value);

        return static::matchCase($singular, $value);
    }

    /**
     * Determine if the given value is uncountable.
     *
     * @param string $value
     *
     * @return bool
     */
    protected static function uncountable($value)
    {
        return in_array(strtolower($value), static::$uncountable);
    }

    /**
     * Attempt to match the case on two strings.
     *
     * @param string $value
     * @param string $comparison
     *
     * @return string
     */
    protected static function matchCase($value, $comparison)
    {
        $functions = array('mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords');
        foreach ($functions as $function)
        {
            if (call_user_func($function, $comparison) === $comparison)
            {
                return call_user_func($function, $value);
            }
        }

        return $value;
    }
}
