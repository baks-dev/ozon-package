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

namespace BaksDev\Ozon\Package\Messenger\ProductStocks;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Ozon\Package\Entity\Package\Event\OzonPackageEvent;
use BaksDev\Ozon\Package\Messenger\Package\OzonPackageMessage;
use BaksDev\Ozon\Package\Repository\Package\CurrentOzonPackage\CurrentOzonPackageInterface;
use BaksDev\Ozon\Package\Repository\Supply\OzonSupply\OzonSupplyInterface;
use BaksDev\Ozon\Package\Repository\Supply\OzonSupply\OzonSupplyResult;
use BaksDev\Products\Stocks\Messenger\Stocks\MultiplyProductStocksExtradition\MultiplyProductStocksExtraditionMessage;
use BaksDev\Products\Stocks\Repository\ProductStocksByOrder\ProductStocksByOrderInterface;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusPackage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Меняет статус складской заявки на Extradition «Укомплектована, готова к выдаче»:
 * - при добавлении продуктов из заказа в поставку Ozon
 * - при выполнении производственной парии Ozon Fbs
 */
#[AsMessageHandler(priority: 0)]
final readonly class UpdateProductStocksToExtraditionDispatcher
{
    public function __construct(
        #[Target('ozonPackageLogger')] private LoggerInterface $logger,
        private MessageDispatchInterface $messageDispatch,
        private DeduplicatorInterface $deduplicator,
        private ProductStocksByOrderInterface $productStocksByOrderRepository,
        private CurrentOzonPackageInterface $currentOzonPackageRepository,
        private OzonSupplyInterface $ozonSupplyRepository,
    ) {}

    public function __invoke(OzonPackageMessage $message): void
    {
        $DeduplicatorExecuted = $this->deduplicator
            ->namespace('ozon-package')
            ->deduplication([
                (string) $message->getId(),
                self::class,
            ]);

        if($DeduplicatorExecuted->isExecuted())
        {
            return;
        }

        /** Активное событие упаковки Ozon */
        $OzonPackageEvent = $this->currentOzonPackageRepository
            ->forPackage($message->getId())
            ->find();

        if(false === ($OzonPackageEvent instanceof OzonPackageEvent))
        {
            $this->logger->critical(
                message: 'ozon-package: Активное событие поставки Ozon - OzonPackageEvent',
                context: [
                    self::class.':'.__LINE__,
                    var_export($message, true),
                ],
            );

            return;
        }

        /** Идентификатор поставки Ozon */
        $OzonSupplyResult = $this->ozonSupplyRepository
            ->forSupply($OzonPackageEvent->getSupplyId())
            ->find();

        if(false === ($OzonSupplyResult instanceof OzonSupplyResult))
        {
            $this->logger->critical(
                message: 'ozon-package: Не найдена поставка Ozon',
                context: [
                    self::class.':'.__LINE__,
                    var_export($message, true),
                ],
            );

            return;
        }

        /**
         * Для каждого продукта из производственной партии
         */
        foreach($OzonPackageEvent->getOrd() as $OzonPackageOrder)
        {

            /**
             * Находим событие складской заявки со статусом Package «Упаковка» и связанной с заказом и упаковкой Ozon
             *
             * @note при производстве на заказы Ozon FBS - один заказ - одна СЗ
             */
            $ProductStockEventArray = $this->productStocksByOrderRepository
                ->onStatus(ProductStockStatusPackage::class)
                ->onOrder($OzonPackageOrder->getOrderId())
                ->findAll();

            if(true === empty($ProductStockEventArray))
            {
                /**
                 * При завершении производственной партии Ozon статус складской заявки
                 * должен был изменен на Package «Упаковка»
                 */
                $this->logger->info(
                    message: 'Не найдено складской заявки, связанной с заказом из упаковки Ozon',
                    context: [
                        self::class.':'.__LINE__,
                        var_export($message, true),
                    ],
                );
                continue;
            }

            foreach($ProductStockEventArray as $ProductStockEvent)
            {

                /**
                 * Идентификаторы Profile и User берем из складской заявки - они были присвоены в момент выполнения производственно партии
                 */
                $MultiplyProductStocksExtraditionMessage = new MultiplyProductStocksExtraditionMessage(
                    $ProductStockEvent->getId(),
                    $ProductStockEvent->getInvariable()->getProfile(),
                    $ProductStockEvent->getInvariable()->getUsr(),
                    sprintf(
                        'Статус складской заявки изменен для закрытия поставки Ozon %s',
                        $OzonSupplyResult->getIdentifier()),
                );

                $this->messageDispatch->dispatch(
                    message: $MultiplyProductStocksExtraditionMessage,
                    transport: 'products-stocks',
                );
            }

        }

        $DeduplicatorExecuted->save();
    }
}
