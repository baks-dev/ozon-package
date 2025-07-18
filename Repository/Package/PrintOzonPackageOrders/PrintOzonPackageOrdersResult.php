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

namespace BaksDev\Ozon\Package\Repository\Package\PrintOzonPackageOrders;

use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

/** @see PrintOzonPackageOrdersRepository */
final readonly class PrintOzonPackageOrdersResult
{
    public function __construct(
        private string $package_id,
        private int $product_total,
        private bool $part,
        private string $supply,

        private string $product_event,
        private string $product_name,
        private string $product_article,

        private ?string $product_offer_uid,
        private ?string $product_offer_value,
        private ?string $product_offer_postfix,
        private ?string $product_offer_barcode,
        private ?string $product_offer_detail_name,
        private ?string $product_offer_reference,
        private ?string $product_offer_name,
        private ?string $product_offer_name_postfix,

        private ?string $product_variation_uid,
        private ?string $product_variation_value,
        private ?string $product_variation_postfix,
        private ?string $product_variation_barcode,
        private ?string $product_variation_reference,
        private ?string $product_variation_name,
        private ?string $product_variation_name_postfix,

        private ?string $product_modification_uid,
        private ?string $product_modification_value,
        private ?string $product_modification_postfix,
        private ?string $product_modification_barcode,
        private ?string $product_modification_reference,
        private ?string $product_modification_name,
        private ?string $product_modification_name_postfix,

        private string $barcode,

        private string $product_image,
        private string $product_image_ext,
        private bool $product_image_cdn,
    ) {}

    public function getPackageId(): OzonPackageUid
    {
        return new OzonPackageUid($this->package_id);
    }

    public function getProductTotal(): int
    {
        return $this->product_total;
    }

    public function isPart(): bool
    {
        return $this->part;
    }

    public function getSupply(): OzonSupplyUid
    {
        return new OzonSupplyUid($this->supply);
    }

    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->product_event);
    }

    public function getProductName(): string
    {
        return $this->product_name;
    }

    public function getProductArticle(): string
    {
        return $this->product_article;
    }

    public function getProductOfferUid(): ?ProductOfferUid
    {
        return $this->product_offer_uid ? new ProductOfferUid($this->product_offer_uid) : null;
    }

    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    public function getProductOfferPostfix(): ?string
    {
        return $this->product_offer_postfix;
    }

    public function getProductOfferBarcode(): ?string
    {
        return $this->product_offer_barcode;
    }

    public function getProductOfferDetailName(): ?string
    {
        return $this->product_offer_detail_name;
    }

    public function getProductOfferReference(): ?string
    {
        return $this->product_offer_reference;
    }

    public function getProductOfferName(): ?string
    {
        return $this->product_offer_name;
    }

    public function getProductOfferNamePostfix(): ?string
    {
        return $this->product_offer_name_postfix;
    }

    public function getProductVariationUid(): ?ProductVariationUid
    {
        return $this->product_variation_uid ? new ProductVariationUid($this->product_variation_uid) : null;
    }

    public function getProductVariationValue(): ?string
    {
        return $this->product_variation_value;
    }

    public function getProductVariationPostfix(): ?string
    {
        return $this->product_variation_postfix;
    }

    public function getProductVariationBarcode(): ?string
    {
        return $this->product_variation_barcode;
    }

    public function getProductVariationReference(): ?string
    {
        return $this->product_variation_reference;
    }

    public function getProductVariationName(): ?string
    {
        return $this->product_variation_name;
    }

    public function getProductVariationNamePostfix(): ?string
    {
        return $this->product_variation_name_postfix;
    }

    public function getProductModificationUid(): ?ProductModificationUid
    {
        return $this->product_modification_uid ? new ProductModificationUid($this->product_modification_uid) : null;
    }

    public function getProductModificationValue(): ?string
    {
        return $this->product_modification_value;
    }

    public function getProductModificationPostfix(): ?string
    {
        return $this->product_modification_postfix;
    }

    public function getProductModificationBarcode(): ?string
    {
        return $this->product_modification_barcode;
    }

    public function getProductModificationReference(): ?string
    {
        return $this->product_modification_reference;
    }

    public function getProductModificationName(): ?string
    {
        return $this->product_modification_name;
    }

    public function getProductModificationNamePostfix(): ?string
    {
        return $this->product_modification_name_postfix;
    }

    public function getBarcode(): string
    {
        return $this->barcode;
    }

    public function getProductImage(): string
    {
        return $this->product_image;
    }

    public function getProductImageExt(): string
    {
        return $this->product_image_ext;
    }

    public function isProductImageCdn(): bool
    {
        return $this->product_image_cdn;
    }
}