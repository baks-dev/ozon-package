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

namespace BaksDev\Ozon\Package\Controller\Admin\Supply;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Repository\Supply\OzonPackageOrdersByOzonSupply\OzonPackageOrdersByOzonSupplyInterface;
use BaksDev\Ozon\Package\Repository\Supply\OzonSupply\OzonSupplyInterface;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterForm;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_OZON_SUPPLY')]
final class DetailController extends AbstractController
{
    /**
     * Заказы в поставке
     */
    #[Route('/admin/ozon/supply/detail/{id}/{page<\d+>}', name: 'admin.supply.detail', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        #[MapEntity] OzonSupply $ozonSupply,
        OzonSupplyInterface $ozonSupplyRepository,
        OzonPackageOrdersByOzonSupplyInterface $OzonSupplyOrdersRepository,
        int $page = 0,
    ): Response
    {
        // Поиск
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('ozon-package:admin.supply.detail', ['id' => $ozonSupply->getId()])]
            )
            ->handleRequest($request);

        // Фильтр
        $filter = new ProductFilterDTO();

        $filterForm = $this
            ->createForm(
                type: ProductFilterForm::class,
                data: $filter,
                options: ['action' => $this->generateUrl('ozon-package:admin.supply.detail', ['id' => $ozonSupply->getId()])]
            )
            ->handleRequest($request);

        /** Получаем поставку Ozon */
        $ozonSupplyResult = $ozonSupplyRepository
            ->forSupply($ozonSupply)
            ->find();

        /** Получаем список заказов в поставке */
        $ozonSupplyOrders = $OzonSupplyOrdersRepository
            ->search($search)
            ->filter($filter)
            ->byOzonSupply($ozonSupply)
            ->findAll();

        return $this->render(
            [
                'query' => $ozonSupplyOrders,
                'supply' => $ozonSupplyResult,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
            ]
        );
    }
}
