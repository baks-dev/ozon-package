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

namespace BaksDev\Ozon\Package\Repository\Package\OrderInOzonPackageById;

use BaksDev\Materials\Sign\Type\Event\MaterialSignEventUid;
use BaksDev\Materials\Sign\Type\Status\MaterialSignStatus;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Products\Posting\OrderProductPosting;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;

/** @see WbPackageOrderResult */
final readonly class OrderInOzonPackageByIdResult
{
    public function __construct(
        private string $package,
        private string $order,
        private string $number,
        private ?string $token,

        private string $ord_product_event,
        private ?string $ord_product_offer,
        private ?string $ord_product_variation,
        private ?string $ord_product_modification,

        private ?string $status,
        private ?string $code_event,
        private ?string $code_string,

        private ?string $code_image,
        private ?string $code_ext,
        private bool|null $code_cdn,

        private string $posting_number,
    ) {}

    public function getPackage(): OzonPackageUid
    {
        return new OzonPackageUid($this->package);
    }

    public function getOrder(): OrderUid
    {
        return new OrderUid($this->order);
    }

    /** @see OrderInvariable */
    public function getOrdNumber(): string
    {
        return $this->number;
    }

    /** Идентификатор токена @see OrderInvariable */
    public function getOrdToken(): ?string
    {
        return $this->token;
    }

    public function getOrdProduct(): ProductEventUid
    {
        return new ProductEventUid($this->ord_product_event);
    }

    public function getOrdOffer(): ProductOfferUid|false
    {
        return $this->ord_product_offer ? new ProductOfferUid($this->ord_product_offer) : false;
    }

    public function getOrdVariation(): ProductVariationUid|false
    {
        return $this->ord_product_variation ? new ProductVariationUid($this->ord_product_variation) : false;
    }

    public function getOrdModification(): ProductModificationUid|false
    {
        return $this->ord_product_modification ? new ProductModificationUid($this->ord_product_modification) : false;
    }

    public function getSign(): MaterialSignEventUid|false
    {
        return $this->code_event ? new MaterialSignEventUid($this->code_event) : false;
    }

    public function getStatus(): MaterialSignStatus|false
    {
        return $this->status ? new MaterialSignStatus($this->status) : false;
    }

    public function isExistCode(): bool
    {
        return empty($this->code_string) === false;
    }

    /** @see MaterialSignCode */
    public function getCode(): string|false
    {
        if($this->isExistCode())
        {
            $subChar = "";
            preg_match_all('/\((\d{2})\)((?:(?!\(\d{2}\)).)*)/', $this->code_string, $matches, PREG_SET_ORDER);
            return $matches[0][1].$matches[0][2].$matches[1][1].$matches[1][2].$subChar.$matches[2][1].$matches[2][2].$subChar.$matches[3][1].$matches[3][2];
        }

        return false;
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
        return $this->code_cdn;
    }

    /** @see OrderProductPosting */
    public function getPostingNumber(): string
    {
        return $this->posting_number;
    }
}