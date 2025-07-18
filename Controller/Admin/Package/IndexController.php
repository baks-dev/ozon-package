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

use BaksDev\Centrifugo\Services\Token\TokenUserGenerator;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Ozon\Package\Repository\Package\AllOzonPackageOrders\AllOzonPackageOrdersInterface;
use BaksDev\Ozon\Package\Repository\Package\PrintOzonPackageOrders\PrintOzonPackageOrdersInterface;
use BaksDev\Ozon\Package\Repository\Supply\LastOzonSupplyIdentifier\LastOzonSupplyInterface;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus\OzonSupplyStatusClose;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_OZON_PACKAGE')]
final class IndexController extends AbstractController
{
    /**
     * Упаковка заказов Ozon
     */
    #[Route('/admin/ozon/packages/{page<\d+>}', name: 'admin.package.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TokenUserGenerator $tokenUserGenerator,
        PrintOzonPackageOrdersInterface $printOzonPackageOrdersRepository,
        AllOzonPackageOrdersInterface $allOzonPackageOrdersRepository,
        LastOzonSupplyInterface $lastOzonSupplyRepository,
        int $page = 0,
    ): Response
    {
        // Поиск
        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search = new SearchDTO(),
                options: ['action' => $this->generateUrl('ozon-package:admin.package.index')]
            )
            ->handleRequest($request);

        // Фильтр продукции
        $filterForm = $this
            ->createForm(
                type: ProductFilterForm::class,
                data: $filter = new ProductFilterDTO(),
                options: ['action' => $this->generateUrl('ozon-package:admin.package.index')]
            )
            ->handleRequest($request);

        $print = null;

        /** Получаем ПОСЛЕДНЮЮ поставку профиля пользователя с любым статусом */
        $openOzonSupply = $lastOzonSupplyRepository->find();

        /** Статус не Close */
        if(false !== $openOzonSupply && false === $openOzonSupply->getStatus()->equals(OzonSupplyStatusClose::STATUS))
        {
            $supply = new OzonSupplyUid($openOzonSupply->getId());

            /** Получаем заказы, которые НЕ БЫЛИ напечатаны */
            $print = $printOzonPackageOrdersRepository
                ->byOzonSupply($supply)
                ->findAll();

            if(false !== $print)
            {
                $print = iterator_to_array($print);
            }
        }

        /**
         * Получаем список заказов Ozon
         */
        $ozonOrders = $allOzonPackageOrdersRepository
            ->search($search)
            ->filter($filter)
            ->byTypeDeliveryFbsOzon()
            ->findPaginator();

        return $this->render(
            [
                'opens' => $openOzonSupply,
                'print' => $print,
                'query' => $ozonOrders,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
                'token' => $tokenUserGenerator->generate($this->getUsr()),
            ]
        );
    }
}
