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

use InspiredBeings\LongFormBundle\Helper\PhpGenerator;

/**
 * Test Helper/PhpGenerator methods.
 *
 * @author Ivan Gabriele <ivan.gabriele@gmail.com>
 */
class PhpGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayToPhp()
    {
        $result = PhpGenerator::arrayToPhp(array(
            'a' => array(
                'b' => true,
                'f' => 3.14,
                'i' => 42,
                's' => 'Hello PhpGenerator !',
            ),
            'b' => true,
            'f' => 3.14,
            'i' => 42,
            's' => 'Hello PhpGenerator !',
        ), 0, '_EOF_');

        // assert that your calculator added the numbers correctly!
        $this->assertEquals(
            "'a' => array('b' => true, 'f' => 3.14, 'i' => 42, 's' => \"Hello PhpGenerator !\", ),_EOF_'b' => true,_EOF_'f' => 3.14,_EOF_'i' => 42,_EOF_'s' => \"Hello PhpGenerator !\",_EOF_",
            $result
        );
    }
}
