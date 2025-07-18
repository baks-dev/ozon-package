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

namespace BaksDev\Ozon\Package\Repository\Supply\ExistOpenOzonSupplyProfile;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Ozon\Package\Entity\Supply\Event\Identifier\OzonSupplyIdentifier;
use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariable;
use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusNew;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusOpen;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\DBAL\ArrayParameterType;
use InvalidArgumentException;

final class ExistOzonSupplyRepository implements ExistOzonSupplyInterface
{
    private UserProfileUid|false $profile = false;

    private string|false $identifier = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

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

    public function forIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    private function builder(): DBALQueryBuilder
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(OzonSupplyInvariable::class, 'ozon_supply_invariable')
            ->where('ozon_supply_invariable.profile = :profile')
            ->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE
            );

        $dbal->join(
            'ozon_supply_invariable',
            OzonSupply::class, 'ozon_supply',
            '
                ozon_supply.id = ozon_supply_invariable.main AND 
                ozon_supply.event = ozon_supply_invariable.event'
        );

        $dbal
            ->join(
                'ozon_supply_invariable',
                OzonSupplyEvent::class, 'ozon_supply_event',
                '
                    ozon_supply_event.id = ozon_supply_invariable.event AND 
                    ozon_supply_event.status IN (:status)'
            );

        if($this->identifier)
        {
            $dbal
                ->join(
                    'ozon_supply_invariable',
                    OzonSupplyIdentifier::class, 'ozon_supply_identifier',
                    '
                        ozon_supply_identifier.main = ozon_supply_invariable.main AND 
                        ozon_supply_identifier.event = ozon_supply_invariable.event AND
                        ozon_supply_identifier.identifier = :identifier
                    '
                )
                ->setParameter(
                    key: 'identifier',
                    value: $this->identifier,
                );
        }

        return $dbal;
    }

    /**
     * Метод проверяет, имеется ли поставка со статусом «New»
     */
    public function isExistNewOzonSupply(): bool
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument Profile');
        }

        $dbal = $this->builder();

        $dbal->setParameter(
            key: 'status',
            value: [OzonSupplyStatusNew::STATUS],
            type: ArrayParameterType::STRING
        );

        return $dbal->fetchExist();
    }

    /**
     * Метод проверяет, имеется ли поставка со статусом «Open»
     */
    public function isExistOpenSupply(): bool
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument Profile');
        }

        $dbal = $this->builder();

        $dbal->setParameter(
            key: 'status',
            value: [OzonSupplyStatusOpen::STATUS],
            type: ArrayParameterType::STRING
        );

        return $dbal->fetchExist();
    }

    /**
     * Метод проверяет, имеется ли поставка со статусом «New» либо «Open»
     */
    public function isExistNewOrOpenOzonSupply(): bool
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument Profile');
        }

        $dbal = $this->builder();

        $dbal->setParameter(
            key: 'status',
            value: [OzonSupplyStatusNew::STATUS, OzonSupplyStatusOpen::STATUS],
            type: ArrayParameterType::STRING
        );

        return $dbal->fetchExist();
    }
}