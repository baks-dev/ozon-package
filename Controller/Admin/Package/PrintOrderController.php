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

use BaksDev\Barcode\Writer\BarcodeFormat;
use BaksDev\Barcode\Writer\BarcodeType;
use BaksDev\Barcode\Writer\BarcodeWrite;
use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Ozon\Orders\Messenger\ProcessOzonPackageStickers\ProcessOzonPackageStickersMessage;
use BaksDev\Ozon\Package\Repository\Package\OrderInOzonPackageById\OrderInOzonPackageByIdInterface;
use BaksDev\Ozon\Products\Repository\Barcode\OzonBarcodeSettings\OzonBarcodeSettingsInterface;
use BaksDev\Ozon\Products\Repository\Barcode\OzonBarcodeSettings\OzonBarcodeSettingsResult;
use BaksDev\Ozon\Type\Id\OzonTokenUid;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByEventInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_OZON_PACKAGE_PRINT')]
final class PrintOrderController extends AbstractController
{
    /** Честные знаки для продуктов */
    private ?array $matrix = null;

    /** Штрихкоды продуктов */
    private ?array $barcodes = null;

    /** Продукты в упаковке */
    private ?array $products = null;

    /** Настройки штих-кода для Ozon */
    private OzonBarcodeSettingsResult|null $settings = null;

    /** Упаковки */
    private ?array $packages = null;

    /** Заказы */
    private ?string $order = null;

    /** Стикеры Ozon */
    private ?array $stickers = null;

    /**
     * Печать штрихкодов, Честных знаков и стикеров для ЗАКАЗОВ
     */
    #[Route('/admin/ozon/packages/print/order/{id}', name: 'admin.package.print.order', methods: ['GET', 'POST'])]
    public function printer(
        #[ParamConverter(OrderUid::class, key: 'id')] OrderUid $OrderUid,
        #[Target('ozonPackageLogger')] LoggerInterface $Logger,
        AppCacheInterface $Cache,
        BarcodeWrite $BarcodeWrite,
        MessageDispatchInterface $MessageDispatch,
        OrderInOzonPackageByIdInterface $OzonPackageOrderRepository,
        ProductDetailByEventInterface $productDetailRepository,
        OzonBarcodeSettingsInterface $barcodeSettingsRepository,
    ): Response
    {
        $cache = $Cache->init('order-sticker');

        /**
         * Получаем заказы в упаковке
         */
        $ozonPackageOrderResults = $OzonPackageOrderRepository
            ->forOrder($OrderUid)
            ->findAll();

        if(false === $ozonPackageOrderResults)
        {
            $Logger->critical(
                'ozon-package: Не найдены заказы в упаковке',
                [
                    self::class.':'.__LINE__,
                    var_export($OrderUid, true)
                ]
            );

            return new Response('Заказов Ozon в упаковке не найдено', Response::HTTP_NOT_FOUND);
        }

        foreach($ozonPackageOrderResults as $ozonPackageOrderResult)
        {
            /** Идентификатор упаковки OzonPackage */
            $ozonPackageUid = (string) $ozonPackageOrderResult->getPackage();
            $this->packages[] = $ozonPackageUid;

            /** Номер заказа - всегда один */
            $this->order = $ozonPackageOrderResult->getOrdNumber();

            /**
             * Получаем стикеры Ozon
             */

            $key = $ozonPackageOrderResult->getPostingNumber();
            $ozonSticker = $cache->getItem($key)->get();

            /**
             * Если стикер не получен:
             * - пробуем получить заново
             * - если не удалось получить - не распечатываем всю упаковку
             */
            if(null === $ozonSticker)
            {
                $message = new ProcessOzonPackageStickersMessage(
                    new OzonTokenUid($ozonPackageOrderResult->getOrdToken()),
                    $ozonPackageOrderResult->getPostingNumber(),
                );

                /** @see ProcessOzonPackageStickersDispatcher */
                $MessageDispatch->dispatch(message: $message);

                $ozonSticker = $cache->getItem($key)->get();

                if(null !== $ozonSticker)
                {
                    $this->stickers[$ozonPackageUid] = base64_encode($ozonSticker);
                }
            }
            else
            {
                $this->stickers[$ozonPackageUid] = base64_encode($ozonSticker);
            }

            /**
             * Генерируем Честный знак
             */

            if(true === $ozonPackageOrderResult->isExistCode())
            {
                $datamatrix = $BarcodeWrite
                    ->text($ozonPackageOrderResult->getCode())
                    ->type(BarcodeType::DataMatrix)
                    ->format(BarcodeFormat::SVG)
                    ->generate();

                if($datamatrix === false)
                {
                    $Logger->critical(
                        'ozon-package: Проверить права на исполнение:  ',
                        [
                            self::class.':'.__LINE__,
                            'chmod +x /home/PROJECT_DIR/vendor/baks-dev/barcode/Writer/Generate',
                            'chmod +x /home/PROJECT_DIR/vendor/baks-dev/barcode/Reader/Decode'
                        ]
                    );

                    throw new RuntimeException('Datamatrix write error');
                }

                /** Генерируем «Честный знак» в формате SVG */
                $render = $BarcodeWrite->render();
                $BarcodeWrite->remove();
                $render = strip_tags($render, ['path']);
                $render = trim($render);

                $this->matrix[$ozonPackageUid] = $render;
            }

            /**
             * Получаем информацию о продукте
             */
            $product = $productDetailRepository
                ->event($ozonPackageOrderResult->getOrdProduct())
                ->offer($ozonPackageOrderResult->getOrdOffer())
                ->variation($ozonPackageOrderResult->getOrdVariation())
                ->modification($ozonPackageOrderResult->getOrdModification())
                ->findResult();

            if(false === $product)
            {
                $Logger->critical(
                    'ozon-package: Продукция в упаковке не найдена',
                    [self::class.':'.__LINE__]
                );

                return new Response('Продукция в упаковке не найдена', Response::HTTP_NOT_FOUND);
            }

            $this->products[$ozonPackageUid] = $product;

            /**
             * Получаем настройки для штрих-кода Ozon
             */
            $this->settings = $product->getProductMain() ? $barcodeSettingsRepository
                ->forProduct($product->getProductMain())
                ->find() : false;

            /** Генерируем штрихкод в формате SVG */
            $barcode = $BarcodeWrite
                ->text($product->getProductBarcode())
                ->type(BarcodeType::Code128)
                ->format(BarcodeFormat::SVG)
                ->generate();

            if($barcode === false)
            {
                $Logger->critical(
                    'ozon-package: Проверить права на исполнение:  ',
                    [
                        self::class.':'.__LINE__,
                        'chmod +x /home/PROJECT_DIR/vendor/baks-dev/barcode/Writer/Generate',
                        'chmod +x /home/PROJECT_DIR/vendor/baks-dev/barcode/Reader/Decode'
                    ]
                );

                throw new RuntimeException('Barcode write error');
            }

            $render = $BarcodeWrite->render();
            $BarcodeWrite->remove();
            $render = strip_tags($render, ['path']);
            $render = trim($render);

            $this->barcodes[$ozonPackageUid] = trim($render);
        }

        return $this->render(
            [
                'matrix' => $this->matrix,
                'barcodes' => $this->barcodes,
                'products' => $this->products,
                'packages' => $this->packages,
                'settings' => $this->settings,
                'order' => $this->order,
                'stickers' => $this->stickers,
            ],
            'admin.package',
            '/print/order.html.twig'
        );
    }
}
