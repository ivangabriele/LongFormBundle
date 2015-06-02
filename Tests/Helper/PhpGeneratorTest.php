<?php

namespace InspiredBeings\LongFormBundle\Tests\Helper;

use InspiredBeings\LongFormBundle\Helper\PhpGenerator;

class PhpGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayToPhp()
    {
        $result = PhpGenerator::arrayToPhp(array(
            'a' => [
                'b' => true,
                'f' => 3.14,
                'i' => 42,
                's' => "Hello PhpGenerator !",
            ],
            'b' => true,
            'f' => 3.14,
            'i' => 42,
            's' => "Hello PhpGenerator !",
        ), 0, "_EOF_");

        // assert that your calculator added the numbers correctly!
        $this->assertEquals(
            "'a' => array('b' => true, 'f' => 3.14, 'i' => 42, 's' => \"Hello PhpGenerator !\", ),_EOF_'b' => true,_EOF_'f' => 3.14,_EOF_'i' => 42,_EOF_'s' => \"Hello PhpGenerator !\",_EOF_",
            $result
        );
    }
}
