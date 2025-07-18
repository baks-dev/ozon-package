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

namespace BaksDev\Ozon\Package\Entity\Supply\Event\Identifier;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/** Идентификатор поставки по номеру производственной партии */
#[ORM\Entity]
#[ORM\Table(name: 'ozon_supply_identifier')]
class OzonSupplyIdentifier extends EntityReadonly
{
    /**
     * Идентификатор OzonSupply
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OzonSupplyUid::TYPE)]
    private OzonSupplyUid $main;

    /**
     * ID события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(targetEntity: OzonSupplyEvent::class, inversedBy: 'identifier')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private OzonSupplyEvent $event;

    /**
     * Идентификатор поставки
     * Для OzonSupply - номер производственной партии
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 15)]
    private string $identifier;

    public function __construct(OzonSupplyEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();
    }

    public function __toString(): string
    {
        return (string) $this->main;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OzonSupplyIdentifierInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof OzonSupplyIdentifierInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Helpers
     */

    /** Identifier */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}