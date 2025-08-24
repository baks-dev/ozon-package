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

namespace BaksDev\Ozon\Package\UseCase\Supply\Close\Tests;

use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Repository\Supply\OzonSupplyCurrentEvent\OzonSupplyCurrentEventInterface;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\Collection\OzonSupplyStatusCollection;
use BaksDev\Ozon\Package\UseCase\Package\Pack\Tests\PackOzonPackageHandleTest;
use BaksDev\Ozon\Package\UseCase\Supply\Close\OzonSupplyCloseDTO;
use BaksDev\Ozon\Package\UseCase\Supply\Close\OzonSupplyCloseHandler;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group ozon-package
 * @group ozon-package-usecase
 *
 * @depends BaksDev\Ozon\Package\UseCase\Package\Pack\Tests\PackOzonPackageHandleTest::class
 */
#[Group('ozon-package')]
#[When(env: 'test')]
final class CloseOzonSupplyHandleTest extends KernelTestCase
{
    #[DependsOnClass(PackOzonPackageHandleTest::class)]
    public function testUseCase(): void
    {
        /**
         * Инициируем статус для итератора тегов
         * @var OzonSupplyStatusCollection $OzonSupplyStatus
         */
        $OzonSupplyStatus = self::getContainer()->get(OzonSupplyStatusCollection::class);
        $OzonSupplyStatus->cases();

        /** @var OzonSupplyCurrentEventInterface $OzonSupplyCurrent */
        $OzonSupplyCurrent = self::getContainer()->get(OzonSupplyCurrentEventInterface::class);

        $OzonSupplyEvent = $OzonSupplyCurrent
            ->forSupply(OzonSupplyUid::TEST)
            ->find();

        self::assertNotNull($OzonSupplyEvent);

        $OzonSupplyCloseDTO = new OzonSupplyCloseDTO();
        $OzonSupplyEvent->getDto($OzonSupplyCloseDTO);

        /** @var OzonSupplyCloseHandler $OzonSupplyCloseHandler */
        $OzonSupplyCloseHandler = self::getContainer()->get(OzonSupplyCloseHandler::class);
        $handle = $OzonSupplyCloseHandler->handle($OzonSupplyCloseDTO);

        self::assertTrue(($handle instanceof OzonSupply), $handle.': Ошибка OzonSupply');
    }

    //    public static function tearDownAfterClass(): void
    //    {
    //        NewOzonSupplyHandleTest::setUpBeforeClass();
    //    }
}