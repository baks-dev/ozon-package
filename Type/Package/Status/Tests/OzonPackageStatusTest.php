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

namespace BaksDev\Ozon\Package\Type\Package\Status\Tests;

use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus\Collection\OzonPackageStatusCollection;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus\Collection\OzonPackageStatusInterface;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatusType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('ozon-package')]
#[When(env: 'test')]
final class OzonPackageStatusTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var OzonPackageStatusCollection $OzonPackageStatusCollection */
        $OzonPackageStatusCollection = self::getContainer()->get(OzonPackageStatusCollection::class);

        /** @var OzonPackageStatusInterface $case */
        foreach($OzonPackageStatusCollection->cases() as $case)
        {
            $OzonPackageStatus = new OzonPackageStatus($case->getValue());

            self::assertTrue($OzonPackageStatus->equals($case::class)); // немспейс интерфейса
            self::assertTrue($OzonPackageStatus->equals($case)); // объект интерфейса
            self::assertTrue($OzonPackageStatus->equals($case->getValue())); // срока
            self::assertTrue($OzonPackageStatus->equals($OzonPackageStatus)); // объект класса

            $OzonPackageStatusType = new OzonPackageStatusType();

            $platform = $this
                ->getMockBuilder(AbstractPlatform::class)
                ->getMock();

            $convertToDatabase = $OzonPackageStatusType->convertToDatabaseValue($OzonPackageStatus, $platform);
            self::assertEquals($OzonPackageStatus->getPackageStatusValue(), $convertToDatabase);

            /** @var OzonPackageStatus $convertToPHP */
            $convertToPHP = $OzonPackageStatusType->convertToPHPValue($convertToDatabase, $platform);
            self::assertInstanceOf(OzonPackageStatus::class, $convertToPHP);
            self::assertInstanceOf($case::class, $convertToPHP->getPackageStatus());
            self::assertEquals($case, $convertToPHP->getPackageStatus());

        }

        self::assertTrue(true);

    }
}