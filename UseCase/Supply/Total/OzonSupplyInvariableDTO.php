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

namespace BaksDev\Ozon\Package\UseCase\Supply\Total;

use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariableInterface;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OzonSupplyInvariable */
final class OzonSupplyInvariableDTO implements OzonSupplyInvariableInterface
{
    /**
     * Идентификатор
     */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private readonly OzonSupplyUid $main;


    /**
     * Общее количество заказов в поставке
     */
    private int $total;


    public function __construct(OzonSupplyUid $main, int $total)
    {
        $this->main = $main;
        $this->total = $total;
    }

    public function getMain(): OzonSupplyUid
    {
        return $this->main;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

}