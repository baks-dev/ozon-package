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

namespace BaksDev\Ozon\Package\Entity\Supply\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Ozon\Package\Entity\Supply\Event\Identifier\OzonSupplyIdentifier;
use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariable;
use BaksDev\Ozon\Package\Entity\Supply\Event\Modify\OzonSupplyModify;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Type\Supply\Event\OzonSupplyEventUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/** Событие поставки Ozon  */
#[ORM\Entity]
#[ORM\Table(name: 'ozon_supply_event')]
#[ORM\Index(columns: ['status'])]
class OzonSupplyEvent extends EntityEvent
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OzonSupplyEventUid::TYPE)]
    private OzonSupplyEventUid $id;

    /**
     * Идентификатор OzonSupply
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: OzonSupplyUid::TYPE, nullable: false)]
    private ?OzonSupplyUid $main = null;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: OzonSupplyModify::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private OzonSupplyModify $modify;

    /**
     * Константы сущности
     */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: OzonSupplyInvariable::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private ?OzonSupplyInvariable $invariable = null;

    /**
     * Статус поставки
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: OzonSupplyStatus::TYPE)]
    private OzonSupplyStatus $status;

    /**
     * Поставка Ozon
     */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: OzonSupplyIdentifier::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private ?OzonSupplyIdentifier $identifier = null;

    public function __construct()
    {
        $this->id = new OzonSupplyEventUid();
        $this->modify = new OzonSupplyModify($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OzonSupplyEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OzonSupplyEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getId(): OzonSupplyEventUid
    {
        return $this->id;
    }

    /**
     * Helpers
     */

    /** Идентификатор корня - OzonSupply */
    public function setMain(OzonSupplyUid|OzonSupply $main): void
    {
        $this->main = $main instanceof OzonSupply ? $main->getId() : $main;
    }

    public function getMain(): ?OzonSupplyUid
    {
        return $this->main;
    }

    /** Статус поставки */
    public function getStatus(): OzonSupplyStatus
    {
        return $this->status;
    }

    /** Внутренний идентификатор поставки */
    public function getIdentifier(): ?string
    {
        return $this->identifier?->getIdentifier();
    }

    /** Общее количество заказов в поставке */
    public function getTotal(): int
    {
        return $this->invariable->getTotal();
    }
}