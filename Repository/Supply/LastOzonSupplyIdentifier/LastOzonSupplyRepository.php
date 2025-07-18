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

namespace BaksDev\Ozon\Package\Repository\Supply\LastOzonSupplyIdentifier;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Ozon\Package\Entity\Supply\Event\Identifier\OzonSupplyIdentifier;
use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariable;
use BaksDev\Ozon\Package\Entity\Supply\Event\Modify\OzonSupplyModify;
use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class LastOzonSupplyRepository implements LastOzonSupplyInterface
{
    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage
    ) {}

    public function forProfile(UserProfile|UserProfileUid|string $profile): self
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Получаем ПОСЛЕДНЮЮ поставку профиля пользователя с любым статусом
     */
    public function find(): LastOzonSupplyResult|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('ozon_supply_invariable.total')
            ->from(OzonSupplyInvariable::class, 'ozon_supply_invariable');

        $dbal
            ->addSelect('ozon_supply.id')
            ->addSelect('ozon_supply.event')
            ->join(
                'ozon_supply_invariable',
                OzonSupply::class,
                'ozon_supply',
                'ozon_supply.id = ozon_supply_invariable.main'
            );

        $dbal
            ->addSelect('ozon_supply_event.status')
            ->join(
                'ozon_supply',
                OzonSupplyEvent::class,
                'ozon_supply_event',
                'ozon_supply_event.id = ozon_supply.event'
            );

        $dbal
            ->addSelect('ozon_supply_modify.mod_date AS supply_date')
            ->join(
                'ozon_supply',
                OzonSupplyModify::class,
                'ozon_supply_modify',
                'ozon_supply_modify.event = ozon_supply.event'
            );

        $dbal
            ->addSelect('ozon_supply_identifier.identifier')
            ->join(
                'ozon_supply',
                OzonSupplyIdentifier::class,
                'ozon_supply_identifier',
                'ozon_supply_identifier.main = ozon_supply.id'
            );

        $dbal
            ->where('ozon_supply_invariable.profile = :profile')
            ->setParameter(
                'profile',
                $this->profile ?: $this->UserProfileTokenStorage->getProfile(),
                UserProfileUid::TYPE
            );

        $dbal->orderBy('ozon_supply_modify.mod_date', 'DESC');
        $dbal->setMaxResults(1);

        $dbal->enableCache('ozon-package');

        return $dbal->fetchHydrate(LastOzonSupplyResult::class);
    }
}