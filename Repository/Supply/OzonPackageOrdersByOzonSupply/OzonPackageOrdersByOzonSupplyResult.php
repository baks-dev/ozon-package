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

namespace BaksDev\Ozon\Package\Repository\Supply\OzonPackageOrdersByOzonSupply;

use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Ozon\Package\Entity\Package\Event\Orders\OzonPackageOrder;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

final readonly class OzonPackageOrdersByOzonSupplyResult
{
    public function __construct(
        private bool $print,
        private string $order_id,
        private string $product_id,

        private string $status,

        private string $order_number,
        private string $order_data,

        private string $ord_product_event,
        private string|null $ord_product_offer,
        private string|null $ord_product_variation,
        private string|null $ord_product_modification,

        private string $card_article,
        private string $product_name,

        private string|null $product_offer_value,
        private string|null $product_offer_postfix,
        private string|null $product_offer_reference,

        private string|null $product_variation_value,
        private string|null $product_variation_postfix,
        private string|null $product_variation_reference,

        private string|null $product_modification_value,
        private string|null $product_modification_postfix,
        private string|null $product_modification_reference,

        private string $product_article,

        private string|null $product_image,
        private string|null $product_image_ext,
        private bool $product_image_cdn,
    ) {}

    /** @see OzonPackageSupply */
    public function isPrint(): bool
    {
        return $this->print;
    }

    /** @see OzonPackageOrder */
    public function getOrderId(): OrderUid
    {
        return new OrderUid($this->order_id);
    }

    /** @see OzonPackageOrder */
    public function getProductId(): OrderProductUid
    {
        return new OrderProductUid($this->product_id);
    }

    /** @see OzonPackageOrder */
    public function getStatus(): OzonPackageStatus
    {
        return new OzonPackageStatus($this->status);
    }

    /** @see OrderInvariable */
    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    /** @see OrderEvent */
    public function getOrderData(): string
    {
        return $this->order_data;
    }

    /** @see OrderProduct */
    public function getOrdProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->ord_product_event);
    }

    /** @see OrderProduct */
    public function getOrdProductOffer(): ?ProductOfferUid
    {
        return false === is_null($this->ord_product_offer) ? new ProductOfferUid($this->ord_product_offer) : null;
    }

    /** @see OrderProduct */
    public function getOrdProductVariation(): ?ProductVariationUid
    {
        return false === is_null($this->ord_product_variation) ? new ProductVariationUid($this->ord_product_variation) : null;
    }

    /** @see OrderProduct */
    public function getOrdProductModification(): ?ProductModificationUid
    {
        return false === is_null($this->ord_product_modification) ? new ProductModificationUid($this->ord_product_modification) : null;
    }

    /** @see ProductInfo */
    public function getCardArticle(): string
    {
        return $this->card_article;
    }

    /** @see ProductTrans */
    public function getProductName(): string
    {
        return $this->product_name;
    }

    /** @see ProductOffer */
    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    /** @see ProductOffer */
    public function getProductOfferPostfix(): ?string
    {
        return $this->product_offer_postfix;
    }

    /** @see CategoryProductOffers */
    public function getProductOfferReference(): ?string
    {
        return $this->product_offer_reference;
    }

    /** @see ProductVariation */
    public function getProductVariationValue(): ?string
    {
        return $this->product_variation_value;
    }

    /** @see ProductVariation */
    public function getProductVariationPostfix(): ?string
    {
        return $this->product_variation_postfix;
    }

    /** @see CategoryProductVariation */
    public function getProductVariationReference(): ?string
    {
        return $this->product_variation_reference;
    }

    /** @see ProductModification */
    public function getProductModificationValue(): ?string
    {
        return $this->product_modification_value;
    }

    /** @see ProductModification */
    public function getProductModificationPostfix(): ?string
    {
        return $this->product_modification_postfix;
    }

    /** @see CategoryProductModification */
    public function getProductModificationReference(): ?string
    {
        return $this->product_modification_reference;
    }

    public function getProductArticle(): string
    {
        return $this->product_article;
    }

    public function getProductImage(): ?string
    {
        return $this->product_image;
    }

    public function getProductImageExt(): ?string
    {
        return $this->product_image_ext;
    }

    public function isProductImageCdn(): bool
    {
        return $this->product_image_cdn;
    }
}