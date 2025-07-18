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

namespace BaksDev\Ozon\Package\UseCase\Supply\Close;

use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEventInterface;
use BaksDev\Ozon\Package\Type\Supply\Event\OzonSupplyEventUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusClose;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Закрывает поставку
 *
 * @see OzonSupplyEvent
 */
final class OzonSupplyCloseDTO implements OzonSupplyEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private readonly OzonSupplyEventUid $id;

    /**
     * Статус поставки
     */
    #[Assert\NotBlank]
    private readonly OzonSupplyStatus $status;

    /**
     * Константа
     */
    #[Assert\Valid]
    private Invariable\OzonSupplyInvariableDTO $invariable;

    public function __construct()
    {
        $this->status = new OzonSupplyStatus(OzonSupplyStatusClose::class);
        $this->invariable = new Invariable\OzonSupplyInvariableDTO();
    }

    public function getEvent(): OzonSupplyEventUid
    {
        return $this->id;
    }

    public function getStatus(): OzonSupplyStatus
    {
        return $this->status;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->invariable->getProfile();
    }
}