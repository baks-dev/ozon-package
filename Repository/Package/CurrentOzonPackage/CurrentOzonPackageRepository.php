<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Ozon\Package\Repository\Package\CurrentOzonPackage;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Ozon\Package\Entity\Package\Event\OzonPackageEvent;
use BaksDev\Ozon\Package\Entity\Package\OzonPackage;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use Doctrine\ORM\Query\Expr\Join;
use InvalidArgumentException;

final class CurrentOzonPackageRepository implements CurrentOzonPackageInterface
{
    private OzonPackageUid|false $package = false;

    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
    ) {}

    public function forPackage(OzonPackage|OzonPackageUid $package): self
    {
        if($package instanceof OzonPackage)
        {
            $package = $package->getId();
        }

        $this->package = $package;
        return $this;
    }

    /**
     * Текущее событие OzonPackage
     */
    public function find(): OzonPackageEvent|false
    {
        if(false === ($this->package instanceof OzonPackageUid))
        {
            throw new InvalidArgumentException('Не передан обязательный параметр запроса package');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        /** main */
        $orm
            ->from(OzonPackage::class, 'main')
            ->where('main.id = :package')
            ->setParameter(
                'package',
                $this->package,
                OzonPackageUid::TYPE
            );

        /** event */
        $orm
            ->select('event')
            ->join(
                OzonPackageEvent::class,
                'event',
                Join::WITH,
                'event.main = main.id'
            );

        return $orm->getOneOrNullResult() ?: false;
    }
}