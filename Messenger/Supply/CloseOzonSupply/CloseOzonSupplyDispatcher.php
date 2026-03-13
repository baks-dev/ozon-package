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

namespace BaksDev\Ozon\Package\Messenger\Supply\CloseOzonSupply;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Ozon\Package\Entity\Supply\Event\OzonSupplyEvent;
use BaksDev\Ozon\Package\Messenger\Supply\OzonSupplyMessage;
use BaksDev\Ozon\Package\Repository\Supply\OzonPackageOrdersByOzonSupply\OzonPackageOrdersByOzonSupplyInterface;
use BaksDev\Ozon\Package\Repository\Supply\OzonPackageOrdersByOzonSupply\OzonPackageOrdersByOzonSupplyResult;
use BaksDev\Ozon\Package\Repository\Supply\OzonSupplyCurrentEvent\OzonSupplyCurrentEventInterface;
use BaksDev\Products\Stocks\Messenger\Stocks\MultiplyProductStocksCompleted\MultiplyProductStocksCompletedMessage;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class CloseOzonSupplyDispatcher
{
    public function __construct(
        #[Target('ozonPackageLogger')] private LoggerInterface $logger,
        private MessageDispatchInterface $messageDispatch,

        private UserByUserProfileInterface $userByUserProfileRepository,
        private OzonSupplyCurrentEventInterface $ozonSupplyCurrentEventRepository,
        private OzonPackageOrdersByOzonSupplyInterface $ozonPackageOrdersByOzonSupplyRepository,
    ) {}

    public function __invoke(OzonSupplyMessage $message): void
    {
        /**
         * Активное событие поставки Ozon
         */
        $OzonSupplyEvent = $this->ozonSupplyCurrentEventRepository
            ->forSupply($message->getId())
            ->find();

        if(false === ($OzonSupplyEvent instanceof OzonSupplyEvent))
        {
            $this->logger->critical(
                message: 'ozon-package: Активное событие поставки Ozon - OzonSupplyEvent',
                context: [
                    self::class.':'.__LINE__,
                    var_export($message, true),
                ],
            );

            return;
        }

        /**
         * Пользователь по профилю из поставки Ozon
         */
        $User = $this->userByUserProfileRepository
            ->forProfile($OzonSupplyEvent->getInvariable()->getProfile())
            ->find();

        if(false === ($User instanceof User))
        {
            $this->logger->critical(
                message: 'ozon-package: Не найден пользователь по профилю из поставки Ozon',
                context: [
                    self::class.':'.__LINE__,
                    var_export($message, true),
                ],
            );

            return;
        }

        /**
         * Продукты в упаковке из поставки Ozon
         */
        $ozonPackageOrdersByOzonSupply = $this->ozonPackageOrdersByOzonSupplyRepository
            ->byOzonSupply($message->getId())
            ->findAll()
            ->getData();

        if(true === empty($ozonPackageOrdersByOzonSupply))
        {
            $this->logger->critical(
                message: 'ozon-package: Не найден продукты в упаковке из поставки Ozon',
                context: [
                    self::class.':'.__LINE__,
                    var_export($message, true),
                ],
            );

            return;
        }

        /** @var OzonPackageOrdersByOzonSupplyResult $supply */
        foreach($ozonPackageOrdersByOzonSupply as $supply)
        {

            $MultiplyProductStocksCompletedMessage = new MultiplyProductStocksCompletedMessage(
                $supply->getProductStockId(),
                $OzonSupplyEvent->getInvariable()->getProfile(),
                $User->getId(),
            );

            $this->messageDispatch->dispatch(
                message: $MultiplyProductStocksCompletedMessage,
                transport: 'products-stocks',
            );
        }

        $this->logger->info(
            message: sprintf('%s Поставка Ozon закрыта. Запущен процесс изменения связанных складских заявок и заказов',
                $OzonSupplyEvent->getIdentifier() ?? 'Не известно'
            ),
            context: [
                self::class.':'.__LINE__,
                var_export($message, true)
            ],
        );
    }
}
