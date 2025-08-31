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

namespace BaksDev\Ozon\Package\Repository\Supply\LastOzonSupplyIdentifier\Tests;

use BaksDev\Ozon\Package\Repository\Supply\LastOzonSupplyIdentifier\LastOzonSupplyInterface;
use BaksDev\Ozon\Package\Repository\Supply\LastOzonSupplyIdentifier\LastOzonSupplyResult;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\Collection\OzonSupplyStatusCollection;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('ozon-package')]
class LastOzonSupplyRepositoryTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /**
         * Инициализируем статус для итератора тегов
         * @var OzonSupplyStatusCollection $OzonSupplyStatusCollection
         */
        $OzonSupplyStatusCollection = self::getContainer()->get(OzonSupplyStatusCollection::class);
        $OzonSupplyStatusCollection->cases();

        /** @var LastOzonSupplyInterface $LastOzonSupplyInterface */
        $LastOzonSupplyInterface = self::getContainer()->get(LastOzonSupplyInterface::class);

        $result = $LastOzonSupplyInterface
            ->forProfile($_SERVER['TEST_PROFILE'])
            ->find();

        $reflectionClass = new ReflectionClass(LastOzonSupplyResult::class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach($methods as $method)
        {
            // Методы без аргументов
            if($method->getNumberOfParameters() === 0)
            {
                // Вызываем метод
                $method->invoke($result);
            }
        }

        self::assertTrue(true);
    }
}
