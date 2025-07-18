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

namespace BaksDev\Ozon\Package\Repository\Package\CountOrdersInOzonSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Ozon\Package\Entity\Package\Event\Orders\OzonPackageOrder;
use BaksDev\Ozon\Package\Entity\Package\Event\Supply\OzonPackageSupply;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use InvalidArgumentException;

final class TotalOrdersInOzonSupplyRepository implements TotalOrdersInOzonSupplyInterface
{
    private OzonSupplyUid|false $supply = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forSupply(OzonSupply|OzonSupplyUid|string $supply): self
    {
        if(is_string($supply))
        {
            $supply = new OzonSupplyUid($supply);
        }

        if($supply instanceof OzonSupply)
        {
            $supply = $supply->getId();
        }

        $this->supply = $supply;

        return $this;
    }

    /**
     * Метод возвращает количество всех заказов в поставке
     */
    public function total(): int
    {
        if(false === ($this->supply instanceof OzonSupplyUid))
        {
            throw new InvalidArgumentException('Invalid Argument OzonSupply');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(OzonPackageSupply::class, 'package_supply')
            ->where('package_supply.supply = :supply')
            ->setParameter('supply', $this->supply, OzonSupplyUid::TYPE);

        $dbal->leftJoin(
            'package_supply',
            OzonPackageOrder::class,
            'package_order',
            'package_order.event = package_supply.event'
        );

        return $dbal->count();
    }
}