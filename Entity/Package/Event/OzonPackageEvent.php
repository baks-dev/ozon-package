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

namespace BaksDev\Ozon\Package\Entity\Package\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Ozon\Package\Entity\Package\Event\Modify\OzonPackageModify;
use BaksDev\Ozon\Package\Entity\Package\Event\Orders\OzonPackageOrder;
use BaksDev\Ozon\Package\Entity\Package\Event\Supply\OzonPackageSupply;
use BaksDev\Ozon\Package\Entity\Package\OzonPackage;
use BaksDev\Ozon\Package\Type\Package\Event\OzonPackageEventUid;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'ozon_package_event')]
class OzonPackageEvent extends EntityEvent
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OzonPackageEventUid::TYPE)]
    private OzonPackageEventUid $id;

    /**
     * Идентификатор OzonPackage
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: OzonPackageUid::TYPE, nullable: false)]
    private ?OzonPackageUid $main = null;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: OzonPackageModify::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private OzonPackageModify $modify;

    /**
     * Общее количество заказов в упаковке
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $total = 0;

    /**
     * false - если продукт был произведен в разных производственных партиях
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $inPart = true;

    /**
     * Поставка
     */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: OzonPackageSupply::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private OzonPackageSupply $supply;

    /**
     * Заказы, добавленные в поставку
     */
    #[ORM\OneToMany(targetEntity: OzonPackageOrder::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private Collection $ord;

    public function __construct()
    {
        $this->id = new OzonPackageEventUid();
        $this->modify = new OzonPackageModify($this);
    }

    public function __clone()
    {
        $this->id = new OzonPackageEventUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof OzonPackageEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof OzonPackageEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getId(): OzonPackageEventUid
    {
        return $this->id;
    }

    public function getMain(): ?OzonPackageUid
    {
        return $this->main;
    }

    public function setMain(OzonPackageUid|OzonPackage $main): void
    {
        $this->main = $main instanceof OzonPackage ? $main->getId() : $main;
    }
}