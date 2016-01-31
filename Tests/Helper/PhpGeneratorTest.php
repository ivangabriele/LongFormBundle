<?php

/*
 * This file is part of the IGLongFormBundle package.
 *
 * (c) Ivan Gabriele <http://www.ivangabriele.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IvanGabriele\LongFormBundle\Tests\Helper;

use IvanGabriele\LongFormBundle\Helper\PhpGenerator;

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
                'bt' => true,
                'bf' => false,
                'f' => 3.14,
                'i' => 42,
                'n' => null,
                's' => 'Hello PhpGenerator !',
            ),
            'bt' => true,
            'bf' => false,
            'f' => 3.14,
            'i' => 42,
            'n' => null,
            's' => 'Hello PhpGenerator !',
        ), 0, '_EOF_');

        // assert that your calculator added the numbers correctly!
        $this->assertEquals(
            "'a' => array('bt' => true, 'bf' => false, 'f' => 3.14, 'i' => 42, 'n' => null, 's' => \"Hello PhpGenerator !\", ),_EOF_'bt' => true,_EOF_'bf' => false,_EOF_'f' => 3.14,_EOF_'i' => 42,_EOF_'n' => null,_EOF_'s' => \"Hello PhpGenerator !\",_EOF_",
            $result
        );
    }
}
