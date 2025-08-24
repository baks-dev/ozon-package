<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 */

declare(strict_types=1);

namespace BaksDev\Ozon\Package\Messenger\Package\UpdateTotalOrdersInSupply\Tests;

use BaksDev\Ozon\Package\Entity\Package\OzonPackage;
use BaksDev\Ozon\Package\Messenger\Package\OzonPackageMessage;
use BaksDev\Ozon\Package\Messenger\Package\UpdateTotalOrdersInSupply\UpdateTotalOrdersInOzonSupplyDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group ozon-package
 */
#[Group('ozon-package')]
#[When(env: 'test')]
class UpdateTotalOrdersInOzonSupplyDispatcherDebugTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        // Бросаем событие консольной команды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var UpdateTotalOrdersInOzonSupplyDispatcher $UpdateTotalOrdersInOzonSupplyDispatcher */
        $UpdateTotalOrdersInOzonSupplyDispatcher = self::getContainer()->get(UpdateTotalOrdersInOzonSupplyDispatcher::class);

        self::assertTrue(true);
        return;

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        /** @var OzonPackage|null $OzonPackage */
        $OzonPackage = $em->getRepository(OzonPackage::class)->find('01984db4-f233-74b3-86a3-81c941b24a0d');

        $OzonPackageMessage = new OzonPackageMessage(
            $OzonPackage->getId(),
            $OzonPackage->getEvent(),
        );

        $UpdateTotalOrdersInOzonSupplyDispatcher($OzonPackageMessage);
    }
}