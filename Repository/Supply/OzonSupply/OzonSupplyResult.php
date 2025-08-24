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

namespace BaksDev\Ozon\Package\Repository\Supply\OzonSupply;

use BaksDev\Ozon\Package\Type\Supply\Event\OzonSupplyEventUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus;
use DateTimeImmutable;

final class OzonSupplyResult
{
    public function __construct(
        private $id,
        private $event,
        private $identifier,
        private $total,
        private $status,
        private $date,
    ) {}

    /**
     * Total
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Id
     */
    public function getId(): OzonSupplyUid
    {
        return new OzonSupplyUid($this->id);
    }

    public function getEvent(): OzonSupplyEventUid
    {
        return new OzonSupplyEventUid($this->event);
    }

    /**
     * Identifier
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Status
     */
    public function getStatus(): OzonSupplyStatus
    {
        return new OzonSupplyStatus($this->status);
    }

    /**
     * Date
     */
    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->date);
    }
}