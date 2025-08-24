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

namespace BaksDev\Ozon\Package\Repository\Package\ExistOrderInOzonPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Ozon\Package\Entity\Package\Event\Orders\OzonPackageOrder;
use BaksDev\Ozon\Package\Entity\Package\OzonPackage;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus\OzonPackageStatusError;
use InvalidArgumentException;

final class ExistOrderInOzonPackageRepository implements ExistOrderInOzonPackageInterface
{
    private OrderUid|false $order = false;

    private OrderProductUid|false $product = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forOrder(Order|OrderUid $order): self
    {
        if($order instanceof Order)
        {
            $order = $order->getId();
        }

        $this->order = $order;

        return $this;
    }

    public function forOrderProduct(OrderProduct|OrderProductUid $product): self
    {

        if($product instanceof OrderProduct)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    /**
     * Метод проверяет, имеется ли заказ в упаковке (без статуса ERROR)
     */
    public function isExist(): bool
    {
        if(false === ($this->order instanceof OrderUid))
        {
            throw new InvalidArgumentException('Invalid Argument Order');
        }

        if(false === ($this->product instanceof OrderProductUid))
        {
            throw new InvalidArgumentException('Invalid Argument OrderProductUid');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(OzonPackageOrder::class, 'ozon_order');

        $dbal
            ->where('ozon_order.id = :order')
            ->setParameter(
                key: 'order',
                value: $this->order,
                type: OrderUid::TYPE
            );

        $dbal
            ->andWhere('ozon_order.product = :product')
            ->setParameter(
                key: 'product',
                value: $this->product,
                type: OrderProductUid::TYPE
            );

        $dbal
            ->andWhere('ozon_order.status != :status')
            ->setParameter(
                key: 'status',
                value: new OzonPackageStatus(OzonPackageStatusError::class),
                type: OzonPackageStatus::TYPE
            );

        $dbal->join(
            'ozon_order',
            OzonPackage::class,
            'package',
            'package.event = ozon_order.event'
        );

        return $dbal->fetchExist();
    }
}