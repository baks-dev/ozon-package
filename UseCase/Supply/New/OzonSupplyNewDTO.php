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

namespace BaksDev\Ozon\Package\UseCase\Supply\New;

use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEventInterface;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusNew;
use BaksDev\Ozon\Package\UseCase\Supply\New\Identifier\OzonSupplyIdentifierDTO;
use BaksDev\Ozon\Package\UseCase\Supply\New\Invariable\OzonSupplyInvariableDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OzonSupplyEvent */
final class OzonSupplyNewDTO implements OzonSupplyEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    #[Assert\IsNull]
    private null $id = null;

    /** Статус поставки */
    #[Assert\NotBlank]
    private readonly OzonSupplyStatus $status;

    /** Константа */
    #[Assert\Valid]
    private readonly OzonSupplyInvariableDTO $invariable;

    /** Внутренний идентификатор поставки для Ozon */
    #[Assert\Valid]
    private readonly OzonSupplyIdentifierDTO $identifier;

    public function __construct(UserProfileUid $profile)
    {
        $this->status = new OzonSupplyStatus(OzonSupplyStatusNew::class);
        $this->invariable = new OzonSupplyInvariableDTO()->setProfile($profile);

        $identifier = number_format((microtime(true) * 100), 0, '.', '.');
        $this->identifier = new OzonSupplyIdentifierDTO()->setIdentifier($identifier);
    }

    /** Идентификатор события */
    public function getEvent(): null
    {
        return $this->id;
    }

    /** Статус поставки */
    public function getStatus(): OzonSupplyStatus
    {
        return $this->status;
    }

    /** Константа */
    public function getInvariable(): OzonSupplyInvariableDTO
    {
        return $this->invariable;
    }

    /** Внутренний идентификатор поставки для Ozon */
    public function getIdentifier(): OzonSupplyIdentifierDTO
    {
        return $this->identifier;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->invariable->getProfile();
    }
}