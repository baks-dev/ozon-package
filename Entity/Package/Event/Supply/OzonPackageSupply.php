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

namespace BaksDev\Ozon\Package\Entity\Package\Event\Supply;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Ozon\Package\Entity\Package\Event\OzonPackageEvent;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'ozon_package_supply')]
#[ORM\Index(columns: ['supply', 'print'])]
class OzonPackageSupply extends EntityReadonly
{
    /**
     * Идентификатор OzonPackage
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OzonPackageUid::TYPE)]
    private OzonPackageUid $main;

    /**
     * Идентификатор события OzonPackageEvent
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(targetEntity: OzonPackageEvent::class, inversedBy: 'supply')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private OzonPackageEvent $event;

    /**
     * Идентификатор Поставки OzonPackage
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: OzonSupplyUid::TYPE)]
    private OzonSupplyUid $supply;

    /**
     * Статус печати стикеров упаковки
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $print = false;

    public function __construct(OzonPackageEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();
    }

    public function getSupply(): OzonSupplyUid
    {
        return $this->supply;
    }

    public function __toString(): string
    {
        return (string) $this->main;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OzonPackageSupplyInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OzonPackageSupplyInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}