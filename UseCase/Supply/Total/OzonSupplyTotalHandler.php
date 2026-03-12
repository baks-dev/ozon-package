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

namespace BaksDev\Ozon\Package\UseCase\Supply\Total;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Ozon\Package\Entity\Supply\Event\Invariable\OzonSupplyInvariable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Изменяем количество не вызывая сообщения
 */
//#[Autoconfigure(public: true)]
final class OzonSupplyTotalHandler extends AbstractHandler
{
    public function __construct(
        #[Target('ozonPackageLogger')] private readonly LoggerInterface $logger,

        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }

    public function handle(OzonSupplyInvariableDTO $command): string|OzonSupplyInvariable
    {
        $this->setCommand($command);

        /** @var OzonSupplyInvariable $OzonSupplyInvariable */
        $OzonSupplyInvariable = $this
            ->getRepository(OzonSupplyInvariable::class)
            ->find($command->getMain());

        if(false === ($OzonSupplyInvariable instanceof OzonSupplyInvariable))
        {
            $error = uniqid('', true);

            $this->logger->warning(
                message: sprintf('%s: Не найдено OzonSupplyInvariable', $error),
                context: [
                    self::class.':'.__LINE__
                ],
            );

            return $error;
        }

        if(false === $this->validatorCollection->add($OzonSupplyInvariable, context: [self::class.':'.__LINE__]))
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $OzonSupplyInvariable->setEntity($command);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        return $OzonSupplyInvariable;
    }
}