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

namespace BaksDev\Ozon\Package\UseCase\Package\Pack\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Ozon\Package\Entity\Package\Event\OzonPackageEvent;
use BaksDev\Ozon\Package\Entity\Package\OzonPackage;
use BaksDev\Ozon\Package\Repository\Supply\OzonSupplyCurrentEvent\OzonSupplyCurrentEventInterface;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus\Collection\OzonPackageStatusCollection;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\Collection\OzonSupplyStatusCollection;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusNew;
use BaksDev\Ozon\Package\UseCase\Package\Pack\Orders\OzonPackageOrderDTO;
use BaksDev\Ozon\Package\UseCase\Package\Pack\OzonPackageDTO;
use BaksDev\Ozon\Package\UseCase\Package\Pack\OzonPackageHandler;
use BaksDev\Ozon\Package\UseCase\Supply\New\Tests\TotalOzonSupplyHandleTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group ozon-package
 * @group ozon-package-usecase
 *
 * @depends BaksDev\Ozon\Package\UseCase\Supply\New\Tests\TotalOzonSupplyHandleTest::class
 */
#[Group('ozon-package')]
#[When(env: 'test')]
final class PackOzonPackageHandleTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /**
         * Инициализируем статус для итератора тегов
         * @var OzonPackageStatusCollection $OzonPackageStatus
         */
        $OzonPackageStatus = self::getContainer()->get(OzonPackageStatusCollection::class);
        $OzonPackageStatus->cases();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $OzonPackage = $em->getRepository(OzonPackage::class)
            ->findOneBy(['id' => OzonPackageUid::TEST]);

        if($OzonPackage)
        {
            $em->remove($OzonPackage);
        }

        $OzonPackageEvent = $em->getRepository(OzonPackageEvent::class)
            ->findBy(['main' => OzonPackageUid::TEST]);

        foreach($OzonPackageEvent as $event)
        {
            $em->remove($event);
        }

        $em->flush();
        $em->clear();
    }

    #[DependsOnClass(TotalOzonSupplyHandleTest::class)]
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

        /** Проверяем, что имеется открытая поставка OzonSupply со статусом NEW */
        self::assertTrue($OzonSupplyEvent->getStatus()->equals(OzonSupplyStatusNew::STATUS));

        $profile = new UserProfileUid();

        /** Создаем НОВУЮ упаковку на заказы одного продукта, если продукт вне текущей поставки */
        $OzonPackageDTO = new OzonPackageDTO($profile)
            ->setSupply($OzonSupplyEvent->getMain());

        /** Добавляем заказ в упаковку со статусом NEW */
        $OzonPackageOrderDTO = new OzonPackageOrderDTO()
            ->setId(new OrderUid) // идентификатор заказа
            ->setProduct(new OrderProductUid) // идентификатор продукта из заказа
            ->setSort(time()); // сортировка по умолчанию

        $OzonPackageDTO->addOrd($OzonPackageOrderDTO);

        /** @var OzonPackageHandler $OzonPackageHandler */
        $OzonPackageHandler = self::getContainer()->get(OzonPackageHandler::class);
        $handle = $OzonPackageHandler->handle($OzonPackageDTO);

        self::assertTrue(($handle instanceof OzonPackage), $handle.': Ошибка OzonPackage');
    }
}