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

namespace BaksDev\Ozon\Package\UseCase\Supply\New\Tests;

use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\Collection\OzonSupplyStatusCollection;
use BaksDev\Ozon\Package\UseCase\Supply\New\Invariable\OzonSupplyInvariableDTO;
use BaksDev\Ozon\Package\UseCase\Supply\New\OzonSupplyNewDTO;
use BaksDev\Ozon\Package\UseCase\Supply\New\OzonSupplyNewHandler;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('ozon-package')]
#[When(env: 'test')]
final class NewOzonSupplyHandleTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /**
         * Инициируем статус для итератора тегов
         * @var OzonSupplyStatusCollection $OzonSupplyStatus
         */
        $OzonSupplyStatus = self::getContainer()->get(OzonSupplyStatusCollection::class);
        $OzonSupplyStatus->cases();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $OzonSupply = $em->getRepository(OzonSupply::class)
            ->findOneBy(['id' => OzonSupplyUid::TEST]);

        if($OzonSupply)
        {
            $em->remove($OzonSupply);
        }

        $OzonSupplyEventCollection = $em->getRepository(OzonSupplyEvent::class)
            ->findBy(['main' => OzonSupplyUid::TEST]);

        foreach($OzonSupplyEventCollection as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
        $em->clear();
    }

    public function testUseCase(): void
    {
        $UserProfileUid = new UserProfileUid();
        $OzonSupplyNewDTO = new OzonSupplyNewDTO($UserProfileUid);

        /** @var OzonSupplyInvariableDTO $OzonSupplyInvariableDTO */
        $OzonSupplyInvariableDTO = $OzonSupplyNewDTO->getInvariable();
        self::assertSame($UserProfileUid, $OzonSupplyInvariableDTO->getProfile());

        /** @var OzonSupplyNewHandler $OzonSupplyOpenHandler */
        $OzonSupplyOpenHandler = self::getContainer()->get(OzonSupplyNewHandler::class);

        $handle = $OzonSupplyOpenHandler->handle($OzonSupplyNewDTO);

        self::assertTrue(($handle instanceof OzonSupply), $handle.': Ошибка OzonSupply');
    }
}