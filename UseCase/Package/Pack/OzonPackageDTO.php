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

namespace BaksDev\Ozon\Package\UseCase\Package\Pack;

use BaksDev\Ozon\Package\Entity\Package\Event\OzonPackageEventInterface;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Type\Package\Event\OzonPackageEventUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\UseCase\Package\Pack\Orders\OzonPackageOrderDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OzonPackageEvent */
final class OzonPackageDTO implements OzonPackageEventInterface
{
    #[Assert\Uuid]
    #[Assert\IsNull]
    private ?OzonPackageEventUid $id = null;

    #[Assert\Valid]
    private ArrayCollection $ord;

    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    private int $total = 0;

    #[Assert\Valid]
    private Supply\OzonPackageSupplyDTO $supply;

    private bool $inPart = true;

    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserProfileUid $profile;

    public function __construct(UserProfileUid $profile)
    {
        $this->ord = new ArrayCollection();
        $this->supply = new Supply\OzonPackageSupplyDTO();

        $this->profile = $profile;
    }

    /** Идентификатор События */
    public function getEvent(): ?OzonPackageEventUid
    {
        return $this->id;
    }

    public function setId(OzonPackageEventUid $id): void
    {
        $this->id = $id;
    }

    /**
     * @return ArrayCollection{int, OzonPackageOrderDTO}
     *
     * Заказы, добавленные в поставку
     */
    public function getOrd(): ArrayCollection
    {
        return $this->ord;
    }

    public function addOrd(Orders\OzonPackageOrderDTO $ord): self
    {
        $filter = $this->ord->filter(function(Orders\OzonPackageOrderDTO $element) use ($ord) {
            return $ord->getId()->equals($element->getId());
        });

        if($filter->isEmpty())
        {
            $this->ord->add($ord);
        }

        $this->total = $this->ord->count();

        return $this;
    }

    public function removeOrd($ord): self
    {
        $this->ord->removeElement($ord);
        return $this;
    }

    /** Поставка */
    public function getSupply(): Supply\OzonPackageSupplyDTO
    {
        return $this->supply;
    }

    public function setSupply(OzonSupply|OzonSupplyUid $supply): self
    {
        $supply = $supply instanceof OzonSupply ? $supply->getId() : $supply;
        $this->supply->setSupply($supply);

        return $this;
    }

    /** Общее количество заказов в упаковке */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * false - если продукт был произведен в разных производственных партиях
     */
    public function getInPart(): bool
    {
        return $this->inPart;
    }

    public function setOutPart(): self
    {
        $this->inPart = false;
        return $this;
    }

    public function setInPart(): self
    {
        $this->inPart = true;
        return $this;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }


}