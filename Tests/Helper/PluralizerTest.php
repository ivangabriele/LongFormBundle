<?php

/*
 * This file is part of the IBLongFormBundle package.
 *
 * (c) Inspired Beings Ltd <http://www.inspired-beings.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InspiredBeings\LongFormBundle\Tests\Helper;

use InspiredBeings\LongFormBundle\Helper\Pluralizer;

/**
 * Test Helper/Pluralizer methods
 *
 * @todo   Why "criterion" => "criteria" fails ?
 *
 * @author Ivan Gabriele <ivan.gabriele@gmail.com>
 */
class PluralizerTest extends \PHPUnit_Framework_TestCase
{
    private $singleWords = array(

        // Uncountable words
        'audio'        => 'audio',
        'bison'        => 'bison',
        'chassis'      => 'chassis',
        'compensation' => 'compensation',
        'coreopsis'    => 'coreopsis',
        'data'         => 'data',
        'deer'         => 'deer',
        'education'    => 'education',
        'equipment'    => 'equipment',
        'fish'         => 'fish',
        'gold'         => 'gold',
        'information'  => 'information',
        'money'        => 'money',
        'moose'        => 'moose',
        'offspring'    => 'offspring',
        'plankton'     => 'plankton',
        'police'       => 'police',
        'rice'         => 'rice',
        'series'       => 'series',
        'sheep'        => 'sheep',
        'species'      => 'species',
        'swine'        => 'swine',
        'traffic'      => 'traffic',

        // Other words (including case)
        'CHERRY'       => 'CHERRIES',
        'Child'        => 'Children',
        'cross'        => 'crosses',
        'customer'     => 'customers',
    );
    
    private $multipleUnderscoredWords = array(
        'customer_profile' => 'customers_profiles',
        'sheep_data'       => 'sheep_data',
        'user_data'        => 'users_data',
    );

    public function testSingular()
    {
        foreach ($this->singleWords as $singular => $plural)
        {
            $this->assertEquals($singular, Pluralizer::singular($plural));
        }
    }

    public function testPlural()
    {
        foreach ($this->singleWords as $singular => $plural)
        {
            $this->assertEquals($plural, Pluralizer::plural($singular));
        }
    }

    public function testPluralUnderscore()
    {
        foreach ($this->multipleUnderscoredWords as $singular => $plural)
        {
            $this->assertEquals($plural, Pluralizer::pluralUnderscore($singular));
        }
    }
}
