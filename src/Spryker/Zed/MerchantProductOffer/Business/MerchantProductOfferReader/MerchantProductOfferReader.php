<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOffer\Business\MerchantProductOfferReader;

use Generated\Shared\Transfer\MerchantProductOfferConditionsTransfer;
use Generated\Shared\Transfer\MerchantProductOfferCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferCriteriaTransfer;
use Spryker\Zed\MerchantProductOffer\Dependency\Facade\MerchantProductOfferToProductOfferFacadeInterface;
use Spryker\Zed\MerchantProductOffer\Persistence\MerchantProductOfferRepositoryInterface;

class MerchantProductOfferReader implements MerchantProductOfferReaderInterface
{
    /**
     * @var \Spryker\Zed\MerchantProductOffer\Dependency\Facade\MerchantProductOfferToProductOfferFacadeInterface
     */
    protected $productOfferFacade;

    /**
     * @var \Spryker\Zed\MerchantProductOffer\Persistence\MerchantProductOfferRepositoryInterface
     */
    protected $merchantProductOfferRepository;

    /**
     * @var array<string, array<int, \Generated\Shared\Transfer\ProductOfferCollectionTransfer>>
     */
    protected static array $merchantProductOfferCollectionCache = [];

    public function __construct(
        MerchantProductOfferToProductOfferFacadeInterface $productOfferFacade,
        MerchantProductOfferRepositoryInterface $merchantProductOfferRepository
    ) {
        $this->productOfferFacade = $productOfferFacade;
        $this->merchantProductOfferRepository = $merchantProductOfferRepository;
    }

    public function preloadProductOfferCollection(
        MerchantProductOfferCriteriaTransfer $merchantProductOfferCriteriaTransfer
    ): void {
        $this->preloadToCache($merchantProductOfferCriteriaTransfer);
    }

    public function getMerchantProductOfferCollection(
        MerchantProductOfferCriteriaTransfer $merchantProductOfferCriteriaTransfer
    ): ProductOfferCollectionTransfer {
        $cachedResult = $this->findInCache($merchantProductOfferCriteriaTransfer);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $productOfferIds = $this->merchantProductOfferRepository->getProductOfferIds($merchantProductOfferCriteriaTransfer);

        if (!$productOfferIds) {
            return new ProductOfferCollectionTransfer();
        }

        $productOfferCriteriaTransfer = (new ProductOfferCriteriaTransfer())
            ->setProductOfferIds($productOfferIds);

        return $this->productOfferFacade->get($productOfferCriteriaTransfer);
    }

    protected function preloadToCache(MerchantProductOfferCriteriaTransfer $merchantProductOfferCriteriaTransfer): void
    {
        $conditions = $merchantProductOfferCriteriaTransfer->getMerchantProductOfferConditions();

        $skus = $conditions !== null ? $conditions->getSkus() : $merchantProductOfferCriteriaTransfer->getSkus();
        $storeIds = $conditions !== null ? $conditions->getStoreIds() : $merchantProductOfferCriteriaTransfer->getStoreIds();

        foreach ($storeIds as $storeId) {
            $skusToFetch = array_values(array_filter(
                $skus,
                fn (string $sku) => !isset(static::$merchantProductOfferCollectionCache[$sku][$storeId]),
            ));

            if ($skusToFetch === []) {
                continue;
            }

            // Pre-populate with empty collections so cache misses are not re-queried
            foreach ($skusToFetch as $sku) {
                static::$merchantProductOfferCollectionCache[$sku][$storeId] = new ProductOfferCollectionTransfer();
            }

            $criteria = (new MerchantProductOfferCriteriaTransfer())
                ->setMerchantProductOfferConditions(
                    (new MerchantProductOfferConditionsTransfer())
                        ->setSkus($skusToFetch)
                        ->addIdStore($storeId),
                );

            $productOfferIds = $this->merchantProductOfferRepository->getProductOfferIds($criteria);

            if (!$productOfferIds) {
                continue;
            }

            $productOfferCollectionTransfer = $this->productOfferFacade->get(
                (new ProductOfferCriteriaTransfer())->setProductOfferIds($productOfferIds),
            );

            foreach ($productOfferCollectionTransfer->getProductOffers() as $productOfferTransfer) {
                $concreteSku = $productOfferTransfer->getConcreteSku();

                if ($concreteSku === null) {
                    continue;
                }

                static::$merchantProductOfferCollectionCache[$concreteSku][$storeId]->addProductOffer($productOfferTransfer);
            }
        }
    }

    protected function findInCache(MerchantProductOfferCriteriaTransfer $criteria): ?ProductOfferCollectionTransfer
    {
        $conditions = $criteria->getMerchantProductOfferConditions();

        $skus = $conditions !== null ? $conditions->getSkus() : $criteria->getSkus();
        $storeIds = $conditions !== null ? $conditions->getStoreIds() : $criteria->getStoreIds();

        if (count($skus) !== 1 || count($storeIds) !== 1) {
            return null;
        }

        return static::$merchantProductOfferCollectionCache[$skus[0]][$storeIds[0]] ?? null;
    }
}
