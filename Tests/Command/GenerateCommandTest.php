<?php

namespace InspiredBeings\LongFormBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

use InspiredBeings\LongFormBundle\Command\GenerateCommand;

class GenerateCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        // -----------------------------------------------------------------------
        // Boot kernel and load application

        $kernel = $this->createKernel();
        $kernel->boot();

        $application = new Application($kernel);

        $application->add(new GenerateCommand());

        $command = $application->find('generate:form');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'formModel'    => 'TestBundle:StudentApplication',
            )
        );

        exit(var_dump($commandTester->getDisplay()));

        $this->assertRegExp('/.../', $commandTester->getDisplay());
    }
}
