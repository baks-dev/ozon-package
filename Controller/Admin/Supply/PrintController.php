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

namespace BaksDev\Ozon\Package\Controller\Admin\Supply;

use BaksDev\Barcode\Writer\BarcodeFormat;
use BaksDev\Barcode\Writer\BarcodeType;
use BaksDev\Barcode\Writer\BarcodeWrite;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Ozon\Package\Entity\Supply\Event\Identifier\OzonSupplyIdentifier;
use BaksDev\Ozon\Package\Repository\Package\PrintOzonPackageOrders\PrintOzonPackageOrdersInterface;
use BaksDev\Ozon\Package\Repository\Supply\OzonPackageOrdersByOzonSupply\OzonPackageOrdersByOzonSupplyInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_OZON_SUPPLY_PRINT')]
final class PrintController extends AbstractController
{
    /**
     * QR код поставки Ozon
     */
    #[Route('/admin/ozon/supply/print/{id}', name: 'admin.supply.print.qrcode', methods: ['GET', 'POST'])]
    public function printer(
        Request $request,
        #[MapEntity] OzonSupplyIdentifier $ozonSupplyIdentifier,
        OzonPackageOrdersByOzonSupplyInterface $ozonPackageOrdersByOzonSupplyRepository,
        PrintOzonPackageOrdersInterface $printOzonPackageOrders,
        BarcodeWrite $barcodeWrite,
    ): Response
    {
        $data = $ozonSupplyIdentifier->getIdentifier();

        $orders = $ozonPackageOrdersByOzonSupplyRepository
            ->byOzonSupply($ozonSupplyIdentifier->getMain())
            ->findAll()
            ->getData();

        $barcode = $barcodeWrite
            ->text($data)
            ->type(BarcodeType::QRCode)
            ->format(BarcodeFormat::SVG)
            ->generate(implode(DIRECTORY_SEPARATOR, ['barcode', 'test']));

        if($barcode === false)
        {
            /**
             * Проверить права на исполнение
             * chmod +x /home/DOMAIN/vendor/baks-dev/barcode/Writer/Generate
             * chmod +x /home/DOMAIN/vendor/baks-dev/barcode/Reader/Decode
             * */

            throw new RuntimeException('Barcode write error');
        }

        $render = $barcodeWrite->render();
        $render = strip_tags($render, ['path']);
        $render = trim($render);
        $barcodeWrite->remove();

        return $this->render(
            [
                'qrcode' => $render,
                'number' => $data,
                'count' => count($orders),
            ]
        );
    }
}
