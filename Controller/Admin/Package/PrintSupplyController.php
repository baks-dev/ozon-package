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
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Ozon\Orders\Messenger\ProcessOzonPackageStickers\ProcessOzonPackageStickersMessage;
use BaksDev\Ozon\Package\Messenger\Package\Print\PrintOzonPackageMessage;
use BaksDev\Ozon\Package\Repository\Package\OrdersByOzonPackage\OrdersByOzonPackageInterface;
use BaksDev\Ozon\Package\Repository\Package\OrdersByOzonPackage\OrdersByOzonPackageResult;
use BaksDev\Ozon\Package\Repository\Package\OzonPackageByOzonSupply\OzonPackageByOzonSupplyInterface;
use BaksDev\Ozon\Package\Repository\Package\OzonPackageByOzonSupply\OzonPackageByOzonSupplyResult;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Products\Repository\Barcode\OzonBarcodeSettings\OzonBarcodeSettingsInterface;
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
final class PrintSupplyController extends AbstractController
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
     * Печать штрихкодов, Честных знаков и стикеров для ВСЕХ УПАКОВОК
     */
    #[Route('/admin/ozon/packages/print/supply/{id}', name: 'admin.package.print.supply', methods: ['GET', 'POST'])]
    public function printers(
        #[ParamConverter(OzonSupplyUid::class)] OzonSupplyUid $ozonSupplyUid,
        #[Target('ozonPackageLogger')] LoggerInterface $Logger,
        AppCacheInterface $Cache,
        BarcodeWrite $BarcodeWrite,
        CentrifugoPublishInterface $CentrifugoPublish,
        MessageDispatchInterface $MessageDispatch,
        ProductDetailByEventInterface $productDetailRepository,
        OzonPackageByOzonSupplyInterface $ozonPackageByOzonSupplyRepository,
        OrdersByOzonPackageInterface $ordersByOzonPackageRepository,
        OzonBarcodeSettingsInterface $ozonBarcodeSettingsRepository,
    ): Response
    {
        $cache = $Cache->init('order-sticker');

        /** Получаем все НЕ РАСПЕЧАТАННЫЕ упаковки в поставке */
        $ozonPackages = $ozonPackageByOzonSupplyRepository
            ->forOzonSupply($ozonSupplyUid)
            ->onlyPrint()
            ->toArray();

        if(false === $ozonPackages)
        {
            return new Response('Package not found', Response::HTTP_NOT_FOUND);
        }

        $printers = null;

        /** @var OzonPackageByOzonSupplyResult $ozonPackage */
        foreach($ozonPackages as $ozonPackage)
        {
            $isPrint = true;

            $OzonPackageUid = (string) $ozonPackage->getId();
            $this->packages[] = $OzonPackageUid;

            /** Получаем все заказы в упаковке */
            $orders = $ordersByOzonPackageRepository
                ->byPackageEvent($ozonPackage->getEvent())
                ->findAll();

            /** Сбрасываем на каждую упаковку продукт */
            $Product = false;

            /** @var OrdersByOzonPackageResult $order */
            foreach($orders as $order)
            {
                $OrderUid = (string) $order->getOrderId();

                /** Составной ключ, так как по одному заказу может быть несколько продуктов  */
                $this->orders[$OzonPackageUid][$order->getOrderNumber().':'.$order->getPostingNumber()] = $OrderUid;

                /**
                 * Получаем стикеры OZON
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
                 * Получаем стикеры честных знаков на заказ
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
                    $render = strip_tags($render, ['path']);
                    $render = trim($render);
                    $BarcodeWrite->remove();

                    $this->matrix[$OrderUid] = $render;
                }

                if(false === $Product)
                {
                    // на каждую упаковку всегда один продукт
                    $Product = $productDetailRepository
                        ->event($order->getProductEvent())
                        ->offer($order->getProductOffer())
                        ->variation($order->getProductVariation())
                        ->modification($order->getProductModification())
                        ->findResult();

                    if(false === $Product)
                    {
                        $Logger->critical(
                            'ozon-package: Продукция в упаковке не найдена',
                            [$order, self::class.':'.__LINE__]
                        );

                        return new Response('Продукция в упаковке не найдена', Response::HTTP_NOT_FOUND);
                    }

                    $this->products[$OzonPackageUid] = $Product;
                }

                /**
                 * Генерируем штрихкод продукции
                 */

                if(false === isset($this->barcodes[$OzonPackageUid]))
                {
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
                    $render = strip_tags($render, ['path']);
                    $render = trim($render);
                    $BarcodeWrite->remove();

                    $this->barcodes[$OzonPackageUid] = $render;
                }

                /**
                 * Получаем настройки бокового стикера
                 */

                if(false === isset($this->settings[$OzonPackageUid]))
                {
                    /**
                     * Получаем настройки для штрих-кода Ozon
                     */
                    $this->settings[$OzonPackageUid] = $Product->getProductMain() ?
                        $ozonBarcodeSettingsRepository->forProduct($Product->getProductMain())->find() :
                        false;
                }
            }

            /** Скрываем у все пользователей упаковку для печати */
            $CentrifugoPublish
                ->addData(['identifier' => $OzonPackageUid]) // ID упаковки
                ->send('remove');

            $printers[$OzonPackageUid] = $isPrint;
        }

        $render = $this->render(
            [
                'packages' => $this->packages,
                'orders' => $this->orders,
                'stickers' => $this->stickers,
                'matrix' => $this->matrix,
                'barcodes' => $this->barcodes,
                'settings' => $this->settings,
                'products' => $this->products,
            ],
            'admin.package',
            '/print/print.html.twig'
        );

        /**
         * Отправляем сообщение в шину и отмечаем принт всех упаковок
         * @var array{string, bool} $printers
         */
        if(null !== $printers)
        {
            /** Отмечаем на печать только если были получены стикеры  */
            $forPrint = array_filter($printers, fn($v) => $v === true);

            foreach($forPrint as $package => $print)
            {
                $MessageDispatch->dispatch(
                    message: new PrintOzonPackageMessage(new OzonPackageUid($package)),
                    transport: 'ozon-package',
                );
            }
        }

        return $render;
    }
}
