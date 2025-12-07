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

namespace BaksDev\Ozon\Package\Controller\Admin\Package;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Repository\RelevantNewOrderByProduct\RelevantNewOrderByProductInterface;
use BaksDev\Ozon\Orders\Type\DeliveryType\TypeDeliveryFbsOzon;
use BaksDev\Ozon\Package\Entity\Package\OzonPackage;
use BaksDev\Ozon\Package\Forms\Package\AddOrdersPackage\AddOrdersPackageDTO;
use BaksDev\Ozon\Package\Forms\Package\AddOrdersPackage\AddOrdersPackageForm;
use BaksDev\Ozon\Package\Repository\Package\ExistOrderInOzonPackage\ExistOrderInOzonPackageInterface;
use BaksDev\Ozon\Package\Repository\Supply\OzonSupplyIdentifierByStatuses\OzonSupplyIdentifierByStatusInterface;
use BaksDev\Ozon\Package\UseCase\Package\Pack\Orders\OzonPackageOrderDTO;
use BaksDev\Ozon\Package\UseCase\Package\Pack\OzonPackageDTO;
use BaksDev\Ozon\Package\UseCase\Package\Pack\OzonPackageHandler;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByEventInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByEventResult;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductStocksByOrder\ProductStocksByOrderInterface;
use BaksDev\Products\Stocks\UseCase\Admin\Extradition\ExtraditionProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Extradition\ExtraditionProductStockHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_OZON_PACKAGE_ADD')]
final class AddController extends AbstractController
{
    /**
     * Добавить заказы в открытую поставку
     */
    #[Route('/admin/ozon/supply/add/{total}', name: 'admin.package.add', methods: ['GET', 'POST'])]
    public function news(
        #[Target('ozonPackageLogger')] LoggerInterface $Logger,
        Request $Request,
        CentrifugoPublishInterface $CentrifugoPublish,
        OzonPackageHandler $OzonPackageHandler,
        ExtraditionProductStockHandler $ExtraditionProductStockHandler,
        ProductDetailByEventInterface $productDetailByUidRepository,
        ExistOrderInOzonPackageInterface $existOrderInOzonPackageRepository,
        OzonSupplyIdentifierByStatusInterface $ozonSupplyIdentifierByStatusRepository,
        RelevantNewOrderByProductInterface $relevantNewOrderByProductRepository,
        ProductStocksByOrderInterface $productStocksByOrderRepository,
        #[ParamConverter(ProductEventUid::class)] $product = null,
        #[ParamConverter(ProductOfferUid::class)] $offer = null,
        #[ParamConverter(ProductVariationUid::class)] $variation = null,
        #[ParamConverter(ProductModificationUid::class)] $modification = null,
        ?int $total = null,
    ): Response
    {
        /** Продукты из производственной партии */
        $manufacturePartProducts = new AddOrdersPackageDTO($this->getProfileUid());

        if($Request->isMethod('GET'))
        {
            $manufacturePartProducts
                ->setProduct($product)
                ->setOffer($offer)
                ->setVariation($variation)
                ->setModification($modification)
                ->setTotal($total);
        }

        // Форма
        $form = $this->createForm(
            type: AddOrdersPackageForm::class,
            data: $manufacturePartProducts,
            options: ['action' => $this->generateUrl('ozon-package:admin.package.add'),]
        );

        $form->handleRequest($Request);

        /** Информация о продукте
         *
         * @var ProductDetailByEventResult|false $productDetail
         */
        $productDetail = $productDetailByUidRepository
            ->event($manufacturePartProducts->getProduct())
            ->offer($manufacturePartProducts->getOffer())
            ->variation($manufacturePartProducts->getVariation())
            ->modification($manufacturePartProducts->getModification())
            ->findResult();

        if(false === $productDetail)
        {
            $Logger->critical(
                'ozon-package: Информация о продукте не найдена',
                [
                    self::class.':'.__LINE__,
                    var_export($manufacturePartProducts, true)
                ]
            );

            return new Response('Информация о продукте не найдена', Response::HTTP_NOT_FOUND);
        }

        if($form->isSubmitted() && $form->isValid() && $form->has('package_orders'))
        {
            $this->refreshTokenForm($form);

            /** Скрываем у всех продукт */
            $CentrifugoPublish
                ->addData(['identifier' => $manufacturePartProducts->getIdentifier()]) // ID продукта
                ->send('remove');

            /** Идентификатор НОВОЙ поставки по профилю */
            $OzonSupplyUid = $ozonSupplyIdentifierByStatusRepository
                ->byStatusNew()
                ->find();

            if(false === $OzonSupplyUid)
            {
                return $this->errorController('Supply');
            }

            /** Создаем упаковку */
            $OzonPackageDTO = new OzonPackageDTO($this->getProfileUid())
                ->setSupply($OzonSupplyUid);

            /**
             * Перебираем все количество произведенной продукции
             */

            $total = (int) $manufacturePartProducts->getTotal();

            for($i = 1; $i <= $total;)
            {
                /**
                 * Получаем заказ со статусом «УПАКОВКА» на данную продукцию
                 * @var $OrderEvent OrderEvent|false
                 */
                $OrderEvent = $relevantNewOrderByProductRepository
                    ->forProductEvent($manufacturePartProducts->getProduct())
                    ->forOffer($manufacturePartProducts->getOffer())
                    ->forVariation($manufacturePartProducts->getVariation())
                    ->forModification($manufacturePartProducts->getModification())
                    ->forDelivery(TypeDeliveryFbsOzon::TYPE)
                    ->onlyPackageStatus() // статус «УПАКОВКА»
                    ->find();

                if(false === $OrderEvent)
                {
                    $this->addFlash(
                        'page.add',
                        'Заказ № %s: заказ со статусом «УПАКОВКА» не найден',
                        'ozon-package.package',
                        $OrderEvent->getOrderNumber(),
                    );

                    return $this->redirectToReferer();
                }

                /** Находим текущий продукт в заказе */
                $orderProduct = $OrderEvent->getProduct()
                    ->findFirst(function($k, OrderProduct $orderProduct) use ($manufacturePartProducts) {

                        return
                            $orderProduct->getProduct()->equals($manufacturePartProducts->getProduct())
                            && ((is_null($orderProduct->getOffer()) === true && is_null($manufacturePartProducts->getOffer()) === true) || $orderProduct->getOffer()?->equals($manufacturePartProducts->getOffer()))
                            && ((is_null($orderProduct->getVariation()) === true && is_null($manufacturePartProducts->getVariation()) === true) || $orderProduct->getVariation()?->equals($manufacturePartProducts->getVariation()))
                            && ((is_null($orderProduct->getModification()) === true && is_null($manufacturePartProducts->getModification()) === true) || $orderProduct->getModification()?->equals($manufacturePartProducts->getModification()));
                    });

                if(null === $orderProduct)
                {
                    $this->addFlash(
                        'page.add',
                        'Заказ № %s: не найдено соответствия с продуктом в заказе',
                        'ozon-package.package',
                        $OrderEvent->getOrderNumber(),
                    );

                    return $this->redirectToReferer();
                }

                /** Не добавляем, если заказ уже имеется в упаковке */
                if(
                    true === $existOrderInOzonPackageRepository
                        ->forOrder($OrderEvent->getMain())
                        ->forOrderProduct($orderProduct->getId())
                        ->isExist()
                )
                {
                    continue;
                }

                foreach($OrderEvent->getProduct() as $product)
                {
                    /** Добавляем заказ в упаковку  */
                    $OzonPackageOrderDTO = new OzonPackageOrderDTO()
                        ->setId($OrderEvent->getMain()) // идентификатор заказа
                        ->setProduct($product->getId()) // идентификатор продукта из заказа
                        ->setSort(time()); // сортировка по умолчанию

                    $OzonPackageDTO->addOrd($OzonPackageOrderDTO);
                }

                $i += $orderProduct->getTotal();
            }

            /**
             * Получаем складскую заявку и обновляем статус квитанции на «Готов к выдаче»
             */

            $invoices = $productStocksByOrderRepository
                ->onOrder($OrderEvent->getMain())
                ->findAll();

            if(false === $invoices)
            {
                $this->addFlash(
                    'page.add',
                    'Заказ № %s: Не найдено ни одной складской заявки',
                    'ozon-package.package',
                    $OrderEvent->getOrderNumber()
                );

                return $this->redirectToReferer();
            }

            /**
             * @var ProductStockEvent $ProductStockEvent
             * Изменяем статус складской заявки «Готов к выдаче»
             */
            foreach($invoices as $ProductStockEvent)
            {
                $ExtraditionProductStockDTO = new ExtraditionProductStockDTO();
                $ProductStockEvent->getDto($ExtraditionProductStockDTO);
                $ProductStock = $ExtraditionProductStockHandler->handle($ExtraditionProductStockDTO);

                if(false === ($ProductStock instanceof ProductStock))
                {
                    $this->addFlash(
                        'page.add',
                        'Ошибка при изменении статуса складской заявки на «Готов к выдаче»',
                        'ozon-package.package',
                        $ProductStock
                    );

                    return $this->redirectToReferer();
                }
            }

            if(true === $OzonPackageDTO->getOrd()->isEmpty())
            {
                $this->addFlash(
                    'page.add',
                    'Заказ № %s: Не найдено ни одного заказа для добавления в упаковку',
                    'ozon-package.package',
                    $OrderEvent->getOrderNumber()
                );

                return $this->redirectToReferer();
            }

            /** Сохраняем упаковку с имеющимися заказами */
            $OzonPackage = $OzonPackageHandler->handle($OzonPackageDTO);

            $this->addFlash
            (
                'page.add',
                $OzonPackage instanceof OzonPackage ? 'success.new' : 'danger.new',
                'ozon-package.package',
                $OzonPackage,
            );

            return $this->redirectToReferer();
        }

        return $this->render([
            'form' => $form->createView(),
            'card' => $productDetail
        ]);
    }

    public function errorController($code): Response
    {
        $this->addFlash
        (
            'page.add',
            'danger.add',
            'ozon-package.package',
            $code
        );

        return $this->redirectToRoute('ozon-package:admin.package.index');
    }
}