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
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Ozon\Package\Entity\Supply\OzonSupply;
use BaksDev\Ozon\Package\Repository\Supply\ExistOpenOzonSupplyProfile\ExistOzonSupplyInterface;
use BaksDev\Ozon\Package\UseCase\Supply\New\OzonSupplyNewDTO;
use BaksDev\Ozon\Package\UseCase\Supply\New\OzonSupplyNewHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_OZON_SUPPLY_NEW')]
final class NewController extends AbstractController
{
    /**
     * Открыть поставку
     */
    #[Route('/admin/ozon/supply/new', name: 'admin.supply.new', methods: ['GET', 'POST'])]
    public function controller(
        OzonSupplyNewHandler $ozonSupplyNewHandler,
        ExistOzonSupplyInterface $existOpenOzonSupplyRepository,
    ): Response
    {
        $OzonSupplyNewDTO = new OzonSupplyNewDTO($this->getProfileUid());

        $isExist = $existOpenOzonSupplyRepository
            ->forProfile($this->getProfileUid())
            ->isExistNewOzonSupply();

        /**
         * Проверяем, имеется ли открытая поставка у профиля
         */
        if(true === $isExist)
        {
            $this->addFlash(
                'danger',
                'danger.new',
                'ozon-package.supply',
                'у профиля уже есть открытая поставка',
            );

            return $this->redirectToReferer();
        }

        $handle = $ozonSupplyNewHandler->handle($OzonSupplyNewDTO);

        $this->addFlash
        (
            'page.new',
            $handle instanceof OzonSupply ? 'success.new' : 'danger.new',
            'ozon-package.supply',
            $handle
        );

        return $this->redirectToReferer();
    }
}