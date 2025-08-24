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

namespace BaksDev\Ozon\Package\Repository\Package\AllOzonPackageOrders;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEvent;
use BaksDev\Manufacture\Part\Entity\Invariable\ManufacturePartInvariable;
use BaksDev\Manufacture\Part\Entity\Products\ManufacturePartProduct;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus\ManufacturePartStatusClosed;
use BaksDev\Manufacture\Part\Type\Status\ManufacturePartStatus\ManufacturePartStatusCompleted;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Ozon\Orders\Type\DeliveryType\TypeDeliveryDbsOzon;
use BaksDev\Ozon\Orders\Type\DeliveryType\TypeDeliveryFbsOzon;
use BaksDev\Ozon\Package\Entity\Package\Event\Orders\OzonPackageOrder;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Products\Stocks\Entity\Total\ProductStockTotal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\DBAL\ArrayParameterType;

final class AllOzonPackageOrdersRepository implements AllOzonPackageOrdersInterface
{
    private ?ProductFilterDTO $filter = null;

    private ?SearchDTO $search = null;

    private UserProfileUid|false $profile = false;

    private DeliveryUid|false $delivery = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage
    ) {}

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(ProductFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function profile(UserProfile|UserProfileUid $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    public function byTypeDeliveryFbsOzon(): self
    {
        $this->delivery = new DeliveryUid(TypeDeliveryFbsOzon::class);

        return $this;
    }

    /**
     * Метод возвращает список заказов готовых для добавления в поставку
     */
    public function findPaginator(): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(OrderInvariable::class, 'invariable');


        $dbal->join(
            'invariable',
            Order::class,
            'orders',
            'orders.id = invariable.main',
        );

        $dbal
            ->join(
                'orders',
                OrderEvent::class,
                'event',
                'event.id = orders.event AND event.status = :status'
            )
            ->setParameter(
                key: 'status',
                value: OrderStatusPackage::class, // Только заказа со статусом «На упаковке»
                type: OrderStatus::TYPE
            );


        $dbal
            ->leftJoin(
                'orders',
                OrderUser::class,
                'order_user',
                'order_user.event = orders.event'
            );

        $dbal
            ->addSelect('MIN(order_delivery.delivery_date) AS order_data')
            ->join(
                'order_user',
                OrderDelivery::class,
                'order_delivery',
                '
                    order_delivery.usr = order_user.id AND 
                    order_delivery.delivery IN (:delivery)
                '
            )
            ->setParameter(
                'delivery',
                [TypeDeliveryDbsOzon::TYPE, TypeDeliveryFbsOzon::TYPE],
                ArrayParameterType::STRING
            );

        $dbal
            ->leftJoin(
                'orders',
                OrderProduct::class,
                'order_product',
                'order_product.event = orders.event'
            );

        $dbal
            ->addSelect('SUM(order_product_price.total) AS order_total')
            ->addSelect('order_product.product')
            ->addSelect('order_product.offer')
            ->addSelect('order_product.variation')
            ->addSelect('order_product.modification')
            ->leftJoin(
                'order_product',
                OrderPrice::class,
                'order_product_price',
                'order_product_price.product = order_product.id'
            );

        $dbal
            ->leftJoin(
                'order_product',
                ProductEvent::class,
                'product_event',
                'product_event.id = order_product.product'
            );

        $dbal
            ->addSelect('product_info.article AS card_article')
            ->leftJoin(
                'order_product',
                ProductInfo::class,
                'product_info',
                'product_info.product = product_event.main '
            );

        if($this->filter?->getCategory())
        {
            $dbal->join('order_product',
                ProductCategory::class,
                'product_category',
                '
                    product_category.event = product_info.event AND 
                    product_category.category = :category AND 
                    product_category.root = true'
            );

            $dbal->setParameter(
                'category',
                $this->filter->getCategory(),
                CategoryProductUid::TYPE
            );
        }

        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                '
                    product_trans.event = product_event.id AND 
                    product_trans.local = :local'
            );

        /**
         * Торговое предложение
         */
        $dbal
            ->addSelect('product_offer.value AS product_offer_value')
            ->addSelect('product_offer.postfix AS product_offer_postfix')
            ->leftJoin(
                'product_event',
                ProductOffer::class,
                'product_offer',
                'product_offer.id = order_product.offer AND product_offer.event = product_event.id'
            );

        if($this->filter?->getOffer())
        {
            $dbal->andWhere('product_offer.value = :offer');
            $dbal->setParameter('offer', $this->filter->getOffer());
        }


        /* Тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );


        /**
         * Множественный вариант
         */


        $dbal
            ->addSelect('product_variation.value AS product_variation_value')
            ->addSelect('product_variation.postfix AS product_variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.id = order_product.variation AND product_variation.offer = product_offer.id'
            );


        /** ФИЛЬТР по множественным вариантам */
        if($this->filter?->getVariation())
        {
            $dbal
                ->andWhere('product_variation.value = :variation')
                ->setParameter(
                    'variation',
                    $this->filter->getVariation()
                );
        }


        /** Тип множественного варианта */
        $dbal
            ->addSelect('category_variation.reference AS product_variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = product_variation.category_variation'
            );


        /**
         * Модификации множественного варианта
         */

        $dbal
            ->addSelect('product_modification.value AS product_modification_value')
            ->addSelect('product_modification.postfix AS product_modification_postfix')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.id = order_product.modification AND product_modification.variation = product_variation.id'
            );


        /** ФИЛЬТР по модификациям множественного варианта */
        if($this->filter?->getModification())
        {
            $dbal
                ->andWhere('product_modification.value = :modification')
                ->setParameter(
                    'modification',
                    $this->filter->getModification()
                );
        }

        $dbal
            ->addSelect('category_modification.reference AS product_modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = product_modification.category_modification'
            );


        /** Фото продукта */

        $dbal->leftJoin(
            'product_event',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_image',
            'product_offer_image.offer = product_offer.id AND product_offer_image.root = true'
        );

        $dbal->leftJoin(
            'product_variation',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );

        $dbal->leftJoin(
            'product_modification',
            ProductModificationImage::class,
            'product_modification_image',
            'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
        );


        $dbal->addSelect(
            "
			CASE
			    WHEN product_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name)

			   WHEN product_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
			   
			   WHEN product_offer_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_image.name)
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
					
			   ELSE NULL
			END AS product_image
		"
        );

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
				WHEN product_modification_image.name IS NOT NULL 
			   THEN product_modification_image.ext
			   
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.ext
					
			   WHEN product_offer_image.name IS NOT NULL 
			   THEN product_offer_image.ext
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.ext
					
			   ELSE NULL
			END AS product_image_ext
		');

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			   WHEN product_modification_image.name IS NOT NULL 
			   THEN product_modification_image.cdn
			   
				WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.cdn
					
			   WHEN product_offer_image.name IS NOT NULL 
			   THEN product_offer_image.cdn
					
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
					
			   ELSE NULL
			END AS product_image_cdn
		');


        /** Артикул продукта */

        $dbal->addSelect('
            COALESCE(
                product_modification.article, 
                product_variation.article, 
                product_offer.article, 
                product_info.article
            ) AS product_article
		');


        /** Наличие на складе */

        $dbal
            ->addSelect('SUM(stock.total) AS stock_total')
            ->addSelect('(SUM(stock.total) - SUM(stock.reserve)) AS stock_available')
            ->join(
                'product_modification',
                ProductStockTotal::class,
                'stock',
                '
                    stock.profile = invariable.profile 
                    AND
                    stock.product = product_event.main 
                    AND
                    (
                        (product_offer.const IS NOT NULL AND stock.offer = product_offer.const) OR
                        (product_offer.const IS NULL AND stock.offer IS NULL)
                    )
                    AND
                    (
                        (product_variation.const IS NOT NULL AND stock.variation = product_variation.const) OR
                        (product_variation.const IS NULL AND stock.variation IS NULL)
                    )
                    AND
                    (
                        (product_modification.const IS NOT NULL AND stock.modification = product_modification.const) OR
                        (product_modification.const IS NULL AND stock.modification IS NULL)
                    )
                ');


        /** Только товары, которых нет в производстве */
        if($this->delivery instanceof DeliveryUid)
        {
            $this->deliveryExist($dbal);
        }
        else
        {
            $dbal->addSelect('FALSE AS exist_manufacture');
            //            $dbal->allGroupByExclude();
        }

        /** Только заказы, которых нет в упаковке */
        //        $dbal->join(
        //            'orders',
        //            OzonPackageOrder::class,
        //            'package',
        //            'package.id != orders.id'
        //        );

        $dbal
            ->where('invariable.profile = :profile OR invariable.profile IS NULL')
            ->setParameter(
                key: 'profile',
                value: $this->profile ?: $this->UserProfileTokenStorage->getProfile(),
                type: UserProfileUid::TYPE
            );

        $dbal->andWhereNotExists(
            OzonPackageOrder::class,
            'exist_package',
            '
                exist_package.id = orders.id AND exist_package.product = order_product.id'
        );

        $dbal->orderBy('order_data');
        $dbal->allGroupByExclude(['exist_part']);

        if($this->search && $this->search->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_variation.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_info.article')
                ->addSearchLike('product_trans.name');
        }

        return $this->paginator->fetchAllAssociative($dbal);
    }

    /** Только товары, которых нет в производстве */
    private function deliveryExist(DBALQueryBuilder $dbal): void
    {
        $dbalExist = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbalExist
            ->select('exist_part.number AS number')
            ->from(ManufacturePartProduct::class, 'exist_product');

        $dbalExist
            ->join(
                'exist_product',
                ManufacturePartInvariable::class,
                'exist_part',
                'exist_part.event = exist_product.event'
            );

        $dbalExist
            ->join('exist_part',
                ManufacturePartEvent::class,
                'exist_product_event',
                '
                        exist_product_event.id = exist_part.event AND
                        exist_product_event.complete = :complete AND
                        exist_product_event.status NOT IN (:status_part)
            ');

        /** Только продукция в процессе производства */
        $dbal
            ->setParameter(
                'status_part',
                [
                    ManufacturePartStatusClosed::STATUS,
                    ManufacturePartStatusCompleted::STATUS
                ],
                ArrayParameterType::STRING
            );

        /** Только продукция на указанный завершающий этап */
        $dbal->setParameter('complete', $this->delivery, DeliveryUid::TYPE);

        $dbalExist
            ->where('exist_product.product = order_product.product')
            ->andWhere('(exist_product.offer = order_product.offer)')
            ->andWhere('(exist_product.variation = order_product.variation)')
            ->andWhere('(exist_product.modification = order_product.modification)')
            ->setMaxResults(1);

        $dbal->addSelect('(SELECT ('.$dbalExist->getSQL().')) AS exist_manufacture');
    }
}
