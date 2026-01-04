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

namespace BaksDev\Ozon\Package\Type\Supply\Status\Tests;

use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\Collection\OzonSupplyStatusCollection;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\Collection\OzonSupplyStatusInterface;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatusType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('ozon-package')]
#[When(env: 'test')]
final class OzonSupplyStatusTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var OzonSupplyStatusCollection $OzonSupplyStatusCollection */
        $OzonSupplyStatusCollection = self::getContainer()->get(OzonSupplyStatusCollection::class);

        /** @var OzonSupplyStatusInterface $case */
        foreach($OzonSupplyStatusCollection->cases() as $case)
        {
            $OzonSupplyStatus = new OzonSupplyStatus($case->getValue());

            self::assertTrue($OzonSupplyStatus->equals($case)); // объект интерфейса
            self::assertTrue($OzonSupplyStatus->equals($case::class)); // немспейс интерфейса
            self::assertTrue($OzonSupplyStatus->equals($case->getValue())); // срока
            self::assertTrue($OzonSupplyStatus->equals($OzonSupplyStatus)); // объект класса

            $OzonSupplyStatusType = new OzonSupplyStatusType();

            $platform = $this
                ->getMockBuilder(AbstractPlatform::class)
                ->getMock();

            $convertToDatabase = $OzonSupplyStatusType->convertToDatabaseValue($OzonSupplyStatus, $platform);
            self::assertEquals($OzonSupplyStatus->getSupplyStatusValue(), $convertToDatabase);

            $convertToPHP = $OzonSupplyStatusType->convertToPHPValue($convertToDatabase, $platform);
            self::assertInstanceOf(OzonSupplyStatus::class, $convertToPHP);
            self::assertEquals($case, $convertToPHP->getSupplyStatus());
        }

        self::assertTrue(true);

    }
}