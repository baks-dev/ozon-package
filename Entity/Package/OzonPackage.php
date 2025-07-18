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

namespace BaksDev\Ozon\Package\Entity\Package;

use BaksDev\Ozon\Package\Entity\Package\Event\OzonPackageEvent;
use BaksDev\Ozon\Package\Type\Package\Event\OzonPackageEventUid;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'ozon_package')]
class OzonPackage
{
    /**
     * Идентификатор упаковки
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OzonPackageUid::TYPE)]
    private OzonPackageUid $id;

    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: OzonPackageEventUid::TYPE, unique: true)]
    private OzonPackageEventUid $event;

    public function __construct(?OzonPackageUid $id = null)
    {
        $this->id = $id ?: new OzonPackageUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): OzonPackageUid
    {
        return $this->id;
    }

    public function getEvent(): OzonPackageEventUid
    {
        return $this->event;
    }

    public function setEvent(OzonPackageEventUid|OzonPackageEvent $event): void
    {
        $this->event = $event instanceof OzonPackageEvent ? $event->getId() : $event;
    }
}