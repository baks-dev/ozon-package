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
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Ozon\Orders\Messenger\ProcessOzonPackageStickers\ProcessOzonPackageStickersMessage;
use BaksDev\Ozon\Package\Entity\Package\OzonPackage;
use BaksDev\Ozon\Package\Messenger\Package\Print\PrintOzonPackageMessage;
use BaksDev\Ozon\Package\Repository\Package\OrdersByOzonPackage\OrdersByOzonPackageInterface;
use BaksDev\Ozon\Package\Repository\Package\OrdersByOzonPackage\OrdersByOzonPackageResult;
use BaksDev\Ozon\Products\Repository\Barcode\OzonBarcodeSettings\OzonBarcodeSettingsInterface;
use BaksDev\Ozon\Type\Id\OzonTokenUid;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByEventInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_OZON_PACKAGE_PRINT')]
final class PrintPackageController extends AbstractController
{
    /** Честные знаки для продуктов */
    private ?array $matrix = null;

    /** Штрихкоды продуктов */
    private ?array $barcodes = null;

    /** Продукты в упаковке */
    private ?array $products = null;

    /** Настройки штих-кода для Ozon */
    private ?array $settings = null;

    /** Упаковки */
    private ?array $packages = null;

    /** Заказы */
    private ?array $orders = null;

    /** Стикеры Ozon */
    private ?array $stickers = null;

    /**
     * Печать штрихкодов, Честных знаков и стикеров для УПАКОВКИ
     */
    #[Route('/admin/ozon/packages/print/pack/{id}', name: 'admin.package.print.pack', methods: ['GET', 'POST'])]
    public function printer(
        #[MapEntity] OzonPackage $OzonPackage,
        #[Target('ozonPackageLogger')] LoggerInterface $Logger,
        AppCacheInterface $cache,
        BarcodeWrite $BarcodeWrite,
        CentrifugoPublishInterface $CentrifugoPublish,
        MessageDispatchInterface $MessageDispatch,
        OrdersByOzonPackageInterface $orderByOzonPackageRepository,
        ProductDetailByEventInterface $productDetailByUidRepository,
        OzonBarcodeSettingsInterface $ozonBarcodeSettingsRepository,
    ): Response
    {
        $cache = $cache->init('order-sticker');

        /** Получаем все заказы в упаковке */
        $orders = $orderByOzonPackageRepository
            ->byPackageEvent($OzonPackage->getEvent())
            ->toArray();

        if(false === $orders)
        {
            return new Response('Заказов Ozon в упаковке не найдено', Response::HTTP_NOT_FOUND);
        }

        $OzonPackageUid = (string) $OzonPackage->getId();
        $this->packages[] = $OzonPackageUid;

        $Product = false;
        $isPrint = true;

        /** @var OrdersByOzonPackageResult $order */
        foreach($orders as $order)
        {
            $OrderUid = (string) $order->getOrderId();
            $this->orders[$OzonPackageUid][$order->getOrderNumber().':'.$order->getPostingNumber()] = $OrderUid;

            /**
             * Получаем стикеры Ozon
             */

            $key = $order->getPostingNumber();
            $ozonSticker = $cache->getItem($key)->get();

            /**
             * Если стикер не получен:
             * - пробуем получить заново
             * - если не удалось получить - не распечатываем всю упаковку
             */
            if(null === $ozonSticker)
            {
                $message = new ProcessOzonPackageStickersMessage(
                    new OzonTokenUid($order->getOrderToken()),
                    $order->getPostingNumber(),
                );

                /** @see ProcessOzonPackageStickersDispatcher */
                $MessageDispatch->dispatch(message: $message);

                $ozonSticker = $cache->getItem($key)->get();

                if(null !== $ozonSticker)
                {
                    $this->stickers[$order->getPostingNumber()] = base64_encode($ozonSticker);
                }
                else
                {
                    $isPrint = false;
                }
            }
            else
            {
                $this->stickers[$order->getPostingNumber()] = base64_encode($ozonSticker);
            }

            /**
             * Генерируем честный знак
             */

            if(false === isset($this->matrix[$OrderUid]) && true === $order->isExistCode())
            {
                $datamatrix = $BarcodeWrite
                    ->text($order->getCodeString())
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

                $render = $BarcodeWrite->render();
                $BarcodeWrite->remove();
                $render = strip_tags($render, ['path']);
                $render = trim($render);

                $this->matrix[$OrderUid] = $render;
            }

            /**
             * Получаем продукцию для штрихкода
             */
            if(false === $Product)
            {
                $Product = $productDetailByUidRepository
                    ->event($order->getProductEvent())
                    ->offer($order->getProductOffer())
                    ->variation($order->getProductVariation())
                    ->modification($order->getProductModification())
                    ->findResult();
            }
        }

        if(false === $Product)
        {
            $Logger->critical(
                'ozon-package: Продукция в упаковке не найдена',
                [$order, self::class.':'.__LINE__]
            );

            return new Response('Продукция в упаковке не найдена', Response::HTTP_NOT_FOUND);
        }

        if(empty($Product->getProductBarcode()))
        {
            $Logger->critical(
                'ozon-package: В продукции не указан штрихкод',
                [$Product, self::class.':'.__LINE__]
            );

            return new Response('В продукции не указан штрихкод', Response::HTTP_NOT_FOUND);
        }

        $this->products[$OzonPackageUid] = $Product;

        /**
         * Генерируем штрихкод продукции
         */
        $barcode = $BarcodeWrite
            ->text($Product->getProductBarcode())
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

        $this->barcodes[$OzonPackageUid] = $render;

        /**
         * Получаем настройки для штрих-кода Ozon
         */
        $this->settings[$OzonPackageUid] = $Product->getProductMain() ?
            $ozonBarcodeSettingsRepository->forProduct($Product->getProductMain())->find() :
            false;

        /** Скрываем у все пользователей упаковку для печати */
        $CentrifugoPublish
            ->addData(['identifier' => $OzonPackageUid]) // ID упаковки
            ->send('remove');

        $render = $this->render(
            [
                'packages' => $this->packages,
                'products' => $this->products,
                'orders' => $this->orders,

                'matrix' => $this->matrix,
                'barcodes' => $this->barcodes,
                'settings' => $this->settings,
                'stickers' => $this->stickers,
            ],
            'admin.package',
            '/print/print.html.twig'
        );

        /** Если были получены все стикеры ОЗОН - отмечаем распечатанным */
        if(true === $isPrint)
        {
            /** Отправляем сообщение в шину и отмечаем принт упаковки */
            $MessageDispatch->dispatch(
                message: new PrintOzonPackageMessage($OzonPackage->getId()),
                transport: 'ozon-package',
            );
        }

        return $render;
    }
}
