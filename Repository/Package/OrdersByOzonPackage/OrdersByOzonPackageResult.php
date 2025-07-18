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

use BaksDev\Materials\Sign\Type\Event\MaterialSignEventUid;
use BaksDev\Materials\Sign\Type\Status\MaterialSignStatus;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

/** @see OrdersByOzonPackageRepository */
final readonly class OrdersByOzonPackageResult
{
    public function __construct(
        private string $package_status,
        private int $total,
        private string $supply_number,

        private string $order,
        private string $order_number,
        private ?string $order_token,
        private string $order_product,
        private string $order_product_event,
        private ?string $order_product_offer,
        private ?string $order_product_variation,
        private ?string $order_product_modification,

        private ?string $code_event = null,
        private ?string $code_status = null,
        private ?string $code_image = null,
        private ?string $code_ext = null,
        private ?bool $code_cdn = null,
        private ?string $code_string = null,

        private string $order_product_posting,
    ) {}

    /** Статус упаковки @see OzonPackageOrder */
    public function getPackageStatus(): OzonPackageStatus
    {
        return new OzonPackageStatus($this->package_status);
    }

    /**
     * Идентификатор поставки @see OzonPackageSupply
     */
    public function getSupply(): string
    {
        return $this->supply_number;
    }

    /** Общее количество заказов в упаковке @see OzonPackageEvent */
    public function getTotal(): int
    {
        return $this->total;
    }

    /** Идентификатор заказа @see Order */
    public function getOrderId(): OrderUid
    {
        return new OrderUid($this->order);
    }

    /** Идентификатор поставки на Озон @see OrderProductPosting */
    public function getPostingNumber(): string
    {
        return $this->order_product_posting;
    }

    /** Идентификатор продукта в заказе @see OrderProduct */
    public function getOrderProduct(): OrderProductUid
    {
        return new OrderProductUid($this->order_product);
    }

    /**
     * Идентификатор заказа Ozon @see OrderInvariable
     */
    public function getOrderNumber(): string
    {
        return str_replace('O-', '', $this->order_number);
    }

    /** Идентификатор токена @see OrderInvariable */
    public function getOrderToken(): ?string
    {
        return $this->order_token;
    }

    /** Идентификатор события продукта @see OrderProduct */
    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->order_product_event);
    }

    /** Идентификатор торгового предложения @see OrderProduct */
    public function getProductOffer(): ProductOfferUid|false
    {
        return $this->order_product_offer ? new ProductOfferUid($this->order_product_offer) : false;
    }

    /** Идентификатор множественного варианта торгового предложения @see OrderProduct */
    public function getProductVariation(): ProductVariationUid|false
    {
        return $this->order_product_variation ? new ProductVariationUid($this->order_product_variation) : false;
    }

    /** Идентификатор модификации множественного варианта торгового предложения @see OrderProduct */
    public function getProductModification(): ProductModificationUid|false
    {
        return $this->order_product_modification ? new ProductModificationUid($this->order_product_modification) : false;
    }

    /**
     * Честный знак
     */

    /** @see MaterialSignEvent */
    public function getCodeEvent(): MaterialSignEventUid|false
    {
        return $this->code_event ? new MaterialSignEventUid($this->code_event) : false;
    }

    /** @see MaterialSignEvent */
    public function getCodeStatus(): MaterialSignStatus|false
    {
        return $this->code_status ? new MaterialSignStatus($this->code_status) : false;
    }

    /** @see MaterialSignCode */
    public function getCodeImage(): ?string
    {
        return $this->code_image;
    }

    /** @see MaterialSignCode */
    public function getCodeExt(): ?string
    {
        return $this->code_ext;
    }

    /** @see MaterialSignCode */
    public function getCodeCdn(): bool
    {
        return $this->code_cdn === true;
    }

    /** @see MaterialSignCode */
    public function getCodeString(): string|false
    {
        if($this->isExistCode())
        {
            $subChar = "";
            preg_match_all('/\((\d{2})\)((?:(?!\(\d{2}\)).)*)/', $this->code_string, $matches, PREG_SET_ORDER);
            return $matches[0][1].$matches[0][2].$matches[1][1].$matches[1][2].$subChar.$matches[2][1].$matches[2][2].$subChar.$matches[3][1].$matches[3][2];
        }

        return false;
    }

    public function isExistCode(): bool
    {
        return empty($this->code_string) === false;
    }
}