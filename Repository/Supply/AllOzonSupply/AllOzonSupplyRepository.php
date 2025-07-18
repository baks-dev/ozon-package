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

namespace BaksDev\Ozon\Package\Repository\Supply\AllOzonSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Ozon\Package\Entity\Supply\Event\Identifier\OzonSupplyIdentifier;
use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariable;
use BaksDev\Ozon\Package\Entity\Supply\Event\Modify\OzonSupplyModify;
use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Forms\Supply\SupplyFilter\SupplyFilterDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;

final class AllOzonSupplyRepository implements AllOzonSupplyInterface
{
    private ?SupplyFilterDTO $filter = null;

    private SearchDTO $search;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function setFilter(SupplyFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function setSearch(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    /**
     * Метод возвращает пагинатор OzonSupply
     */
    public function fetchAllOzonSupplyAssociative(UserProfileUid $profile): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('supply_const.total')
            ->from(OzonSupplyInvariable::class, 'supply_const');

        $dbal
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->join(
                'supply_const',
                OzonSupply::class,
                'supply',
                'supply.id = supply_const.main'
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
            ->addSelect('modify.mod_date AS supply_date')
            ->leftJoin(
                'supply',
                OzonSupplyModify::class,
                'modify',
                'modify.event = supply.event'
            );

        /**
         * Фильтр по дате
         */
        if(!$this->search?->getQuery() && $this->filter?->getDate())
        {
            $date = $this->filter?->getDate() ?: new DateTimeImmutable();

            // Начало дня
            $startOfDay = $date->setTime(0, 0, 0);
            // Конец дня
            $endOfDay = $date->setTime(23, 59, 59);

            //($date ? ' AND part_modify.mod_date = :date' : '')
            $dbal->andWhere('modify.mod_date BETWEEN :start AND :end');

            $dbal->setParameter('start', $startOfDay->format("Y-m-d H:i:s"));
            $dbal->setParameter('end', $endOfDay->format("Y-m-d H:i:s"));
        }


        $dbal
            ->addSelect('ozon.identifier')
            ->leftJoin(
                'supply',
                OzonSupplyIdentifier::class,
                'ozon',
                'ozon.main = supply.id'
            );

        $dbal
            ->where('supply_const.profile = :profile')
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $dbal->orderBy('modify.mod_date', 'DESC');

        if($this->search?->getQuery())
        {

            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('supply.id')
                ->addSearchEqualUid('supply.event')
                ->addSearchLike('ozon.identifier');
        }


        return $this->paginator->fetchAllAssociative($dbal);
    }
}
