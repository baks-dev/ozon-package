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

namespace BaksDev\Ozon\Package\Repository\Supply\OzonSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Ozon\Package\Entity\Supply\Event\Identifier\OzonSupplyIdentifier;
use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariable;
use BaksDev\Ozon\Package\Entity\Supply\Event\Modify\OzonSupplyModify;
use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use InvalidArgumentException;

final  class OzonSupplyRepository implements OzonSupplyInterface
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
     * Получаем поставку по указанному идентификатору
     */
    public function find(): OzonSupplyResult|false
    {
        if(false === ($this->supply instanceof OzonSupplyUid))
        {
            throw new InvalidArgumentException('Invalid Argument OzonSupply');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('supply_const.total')
            ->from(OzonSupply::class, 'supply')
            ->where('supply.id = :supply')
            ->setParameter(
                key: 'supply',
                value: $this->supply,
                type: OzonSupplyUid::TYPE
            );

        $dbal
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->leftJoin(
                'supply',
                OzonSupplyInvariable::class,
                'supply_const',
                'supply_const.main = supply.id'
            );


        $dbal
            ->addSelect('event.status')
            ->leftJoin(
                'supply',
                OzonSupplyEvent::class,
                'event',
                'event.id = supply.event'
            );

        $dbal
            ->addSelect('modify.mod_date AS date')
            ->leftJoin(
                'supply',
                OzonSupplyModify::class,
                'modify',
                'modify.event = supply.event'
            );


        $dbal
            ->addSelect('ozon.identifier')
            ->leftJoin(
                'supply',
                OzonSupplyIdentifier::class,
                'ozon',
                'ozon.main = supply.id'
            );

        $dbal->setMaxResults(1);

        return $dbal
            ->enableCache('ozon-package', 3600)
            ->fetchHydrate(OzonSupplyResult::class);
    }
}