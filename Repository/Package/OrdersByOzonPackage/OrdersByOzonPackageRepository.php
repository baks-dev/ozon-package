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

namespace BaksDev\Ozon\Package\Repository\Package\OrdersByOzonPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Sign\BaksDevMaterialsSignBundle;
use BaksDev\Materials\Sign\Entity\Code\MaterialSignCode;
use BaksDev\Materials\Sign\Entity\Event\MaterialSignEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Posting\OrderProductPosting;
use BaksDev\Ozon\Package\Entity\Package\Event\Orders\OzonPackageOrder;
use BaksDev\Ozon\Package\Entity\Package\Event\OzonPackageEvent;
use BaksDev\Ozon\Package\Entity\Package\Event\Supply\OzonPackageSupply;
use BaksDev\Ozon\Package\Entity\Supply\Event\Identifier\OzonSupplyIdentifier;
use BaksDev\Ozon\Package\Type\Package\Event\OzonPackageEventUid;
use Generator;
use InvalidArgumentException;

final class OrdersByOzonPackageRepository implements OrdersByOzonPackageInterface
{
    /** Идентификатор упаковки */
    private OzonPackageEventUid|false $event = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder
    ) {}

    public function byPackageEvent(OzonPackageEvent|OzonPackageEventUid|string $event): self
    {
        if(is_string($event))
        {
            $event = new OzonPackageEventUid($event);
        }

        if($event instanceof OzonPackageEvent)
        {
            $event = $event->getId();
        }

        $this->event = $event;

        return $this;
    }

    public function findAll(): Generator|false
    {
        if(false === ($this->event instanceof OzonPackageEventUid))
        {
            throw new InvalidArgumentException('Invalid Argument OzonPackageEvent');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('ozon_package_order.status AS package_status')
            ->from(OzonPackageOrder::class, 'ozon_package_order');

        $dbal
            ->addSelect('ozon_package_event.total')
            ->leftJoin(
                'ozon_package_order',
                OzonPackageEvent::class,
                'ozon_package_event',
                'ozon_package_event.id = ozon_package_order.event'
            );

        $dbal
            ->leftJoin(
                'ozon_package_event',
                OzonPackageSupply::class,
                'ozon_package_supply',
                'ozon_package_supply.main = ozon_package_event.main'
            );

        $dbal
            ->addSelect('ozon_supply_identifier.identifier AS supply_number')
            ->leftJoin(
                'ozon_package_supply',
                OzonSupplyIdentifier::class,
                'ozon_supply_identifier',
                'ozon_supply_identifier.main = ozon_package_supply.supply'
            );

        $dbal
            ->addSelect('ord.id AS order')
            ->join(
                'ozon_package_order',
                Order::class,
                'ord',
                'ord.id = ozon_package_order.id'
            );

        $dbal
            ->addSelect('invariable.token AS order_token')
            ->addSelect('invariable.number AS order_number')
            ->join(
                'ozon_package_order',
                OrderInvariable::class,
                'invariable',
                '
                    invariable.main = ord.id 
                    '
            // AND invariable.token != NULL // @TODO
            );

        $dbal
            ->addSelect('ord_product.id AS order_product')
            ->addSelect('ord_product.product AS order_product_event')
            ->addSelect('ord_product.offer AS order_product_offer')
            ->addSelect('ord_product.variation AS order_product_variation')
            ->addSelect('ord_product.modification AS order_product_modification')
            ->join(
                'ord',
                OrderProduct::class,
                'ord_product',
                '
                    ord_product.event = ord.event AND 
                    ord_product.id = ozon_package_order.product'
            );

        /** Отдельные отправления - должен быть хотя бы один элемент */
        $dbal
            ->addSelect('ord_product_posting.number AS order_product_posting')
            ->join(
                'ord_product',
                OrderProductPosting::class,
                'ord_product_posting',
                'ord_product_posting.product = ord_product.id'
            );

        if(class_exists(BaksDevMaterialsSignBundle::class))
        {
            $dbal
                ->addSelect("sign_event.id AS code_event")
                ->addSelect('sign_event.status AS code_status')
                ->leftOneJoin(
                    'ozon_package_order',
                    MaterialSignEvent::class,
                    'sign_event',
                    'sign_event.ord = ozon_package_order.id',
                );

            $dbal
                ->addSelect("
                    CASE
                       WHEN code.name IS NOT NULL 
                       THEN CONCAT ( '/upload/".$dbal->table(MaterialSignCode::class)."' , '/', code.name)
                       ELSE NULL
                    END AS code_image
                    "
                )
                ->addSelect("code.ext AS code_ext")
                ->addSelect("code.cdn AS code_cdn")
                ->addSelect("code.code AS code_string")
                ->leftJoin(
                    'sign_event',
                    MaterialSignCode::class,
                    'code',
                    'code.main = sign_event.main'
                );
        }

        $dbal
            ->where('ozon_package_order.event = :event')
            ->setParameter(
                key: 'event',
                value: $this->event,
                type: OzonPackageEventUid::TYPE
            );

        $dbal->enableCache('ozon-package');

        return $dbal->fetchAllHydrate(OrdersByOzonPackageResult::class);
    }

    /**
     * @return array{int, OrdersByOzonPackageResult}|false
     */
    public function toArray(): array|false
    {
        $Generator = $this->findAll();

        return (false === $Generator || false === $Generator->valid()) ? false : iterator_to_array($Generator);
    }
}