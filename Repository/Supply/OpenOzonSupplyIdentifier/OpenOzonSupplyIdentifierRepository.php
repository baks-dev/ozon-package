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
 */

declare(strict_types=1);

namespace BaksDev\Ozon\Package\Repository\Supply\OpenOzonSupplyIdentifier;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariable;
use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusNew;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusOpen;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class OpenOzonSupplyIdentifierRepository implements OpenOzonSupplyIdentifierInterface
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
     * Получаем идентификатор ОТКРЫТОЙ поставки профиля пользователя
     */
    public function find(): OzonSupplyUid|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(OzonSupplyInvariable::class, 'invariable')
            ->where('invariable.profile = :profile')
            ->setParameter(
                key: 'profile',
                value: $this->profile ?: $this->UserProfileTokenStorage->getProfile(),
                type: UserProfileUid::TYPE);

        $dbal
            ->addSelect('supply.id')
            ->join(
                'invariable',
                OzonSupply::class,
                'supply',
                'supply.id = invariable.main'
            );

        $dbal
            ->addSelect('event.status')
            ->join(
                'supply',
                OzonSupplyEvent::class,
                'event',
                'event.id = supply.event AND (event.status = :new OR event.status = :open) '
            )
            ->setParameter('new', OzonSupplyStatusNew::class, OzonSupplyStatus::TYPE)
            ->setParameter('open', OzonSupplyStatusOpen::class, OzonSupplyStatus::TYPE);

        $dbal->setMaxResults(1);

        $result = $dbal->fetchOne();

        return $result ? new OzonSupplyUid($result) : false;
    }
}