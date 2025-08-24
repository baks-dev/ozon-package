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

namespace BaksDev\Ozon\Package\Repository\Package\OzonPackageByOzonSupply;

use BaksDev\Ozon\Package\Type\Package\Event\OzonPackageEventUid;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;

/** @see OzonPackageByOzonSupplyRepository */
final readonly class OzonPackageByOzonSupplyResult
{
    public function __construct(
        private string $main,
        private string $event,
        private string $supply,
        private bool $print
    ) {}

    /**
     * Идентификатор OzonSupply
     */
    public function getId(): OzonPackageUid
    {
        return new OzonPackageUid($this->main);
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): OzonPackageEventUid
    {
        return new OzonPackageEventUid($this->event);
    }

    /**
     * Идентификатор Поставки
     */
    public function getSupply(): OzonSupplyUid
    {
        return new OzonSupplyUid($this->supply);
    }

    /**
     * Статус печати стикеров упаковки
     */
    public function isPrint(): bool
    {
        return $this->print === true;
    }
}