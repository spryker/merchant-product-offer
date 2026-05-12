<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\MerchantProductOffer\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\MerchantProductOfferConditionsTransfer;
use Generated\Shared\Transfer\MerchantProductOfferCriteriaTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\ShoppingListItemTransfer;
use ReflectionProperty;
use Spryker\Zed\MerchantProductOffer\Business\MerchantProductOfferReader\MerchantProductOfferReader;
use SprykerTest\Zed\MerchantProductOffer\MerchantProductOfferBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group MerchantProductOffer
 * @group Business
 * @group Facade
 * @group MerchantProductOfferFacadeTest
 * Add your own group annotations below this line
 */
class MerchantProductOfferFacadeTest extends Unit
{
    /**
     * @var \SprykerTest\Zed\MerchantProductOffer\MerchantProductOfferBusinessTester
     */
    protected MerchantProductOfferBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearMerchantProductOfferCollectionCache();
    }

    public function testPreloadProductOfferCollectionWarmsCache(): void
    {
        // Arrange
        $storeTransfer = $this->tester->haveStore();
        $merchantTransfer = $this->tester->haveMerchant();
        $productOfferTransfer = $this->tester->haveProductOffer([
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
        ]);
        $this->tester->haveProductOfferStore($productOfferTransfer, $storeTransfer);

        $merchantProductOfferCriteriaTransfer = (new MerchantProductOfferCriteriaTransfer())
            ->setMerchantProductOfferConditions(
                (new MerchantProductOfferConditionsTransfer())
                    ->setSkus([$productOfferTransfer->getConcreteSkuOrFail()])
                    ->setStoreIds([$storeTransfer->getIdStoreOrFail()]),
            );

        // Act - pre-warms the cache for given SKUs and store IDs
        $this->tester->getFacade()->preloadProductOfferCollection($merchantProductOfferCriteriaTransfer);

        // Assert - subsequent read returns data from the warmed cache
        $productOfferCollectionTransfer = $this->tester->getFacade()->getProductOfferCollection($merchantProductOfferCriteriaTransfer);

        $this->assertNotEmpty($productOfferCollectionTransfer->getProductOffers());
    }

    public function testPreloadProductOfferCollectionPreloadsEmptyCollectionWhenNoOffersMatch(): void
    {
        // Arrange
        $storeTransfer = $this->tester->haveStore();
        $nonExistentSku = sprintf('non-existent-sku-%s', uniqid());

        $merchantProductOfferCriteriaTransfer = (new MerchantProductOfferCriteriaTransfer())
            ->setMerchantProductOfferConditions(
                (new MerchantProductOfferConditionsTransfer())
                    ->setSkus([$nonExistentSku])
                    ->setStoreIds([$storeTransfer->getIdStoreOrFail()]),
            );

        // Act - pre-warms cache with no matching offers
        $this->tester->getFacade()->preloadProductOfferCollection($merchantProductOfferCriteriaTransfer);

        // Assert - cache is warmed with an empty collection; subsequent read returns empty
        $productOfferCollectionTransfer = $this->tester->getFacade()->getProductOfferCollection($merchantProductOfferCriteriaTransfer);

        $this->assertEmpty($productOfferCollectionTransfer->getProductOffers());
    }

    public function testPreloadProductOfferCollectionWarmsMultipleSkusAndEachReturnsItsOwnOffers(): void
    {
        // Arrange
        $storeTransfer = $this->tester->haveStore();
        $merchantTransfer = $this->tester->haveMerchant();

        $firstProductOfferTransfer = $this->tester->haveProductOffer([
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
        ]);
        $this->tester->haveProductOfferStore($firstProductOfferTransfer, $storeTransfer);

        $secondProductOfferTransfer = $this->tester->haveProductOffer([
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
        ]);
        $this->tester->haveProductOfferStore($secondProductOfferTransfer, $storeTransfer);

        $storeId = $storeTransfer->getIdStoreOrFail();

        $preloadCriteria = (new MerchantProductOfferCriteriaTransfer())
            ->setMerchantProductOfferConditions(
                (new MerchantProductOfferConditionsTransfer())
                    ->setSkus([
                        $firstProductOfferTransfer->getConcreteSkuOrFail(),
                        $secondProductOfferTransfer->getConcreteSkuOrFail(),
                    ])
                    ->setStoreIds([$storeId]),
            );

        // Act
        $this->tester->getFacade()->preloadProductOfferCollection($preloadCriteria);

        // Assert - each SKU returns only its own offer from the warmed cache
        $firstCollection = $this->tester->getFacade()->getProductOfferCollection(
            (new MerchantProductOfferCriteriaTransfer())
                ->setMerchantProductOfferConditions(
                    (new MerchantProductOfferConditionsTransfer())
                        ->setSkus([$firstProductOfferTransfer->getConcreteSkuOrFail()])
                        ->setStoreIds([$storeId]),
                ),
        );

        $secondCollection = $this->tester->getFacade()->getProductOfferCollection(
            (new MerchantProductOfferCriteriaTransfer())
                ->setMerchantProductOfferConditions(
                    (new MerchantProductOfferConditionsTransfer())
                        ->setSkus([$secondProductOfferTransfer->getConcreteSkuOrFail()])
                        ->setStoreIds([$storeId]),
                ),
        );

        $firstProductOfferReferences = array_map(
            fn (ProductOfferTransfer $productOffer): ?string => $productOffer->getProductOfferReference(),
            $firstCollection->getProductOffers()->getArrayCopy(),
        );

        $secondProductOfferReferences = array_map(
            fn (ProductOfferTransfer $productOffer): ?string => $productOffer->getProductOfferReference(),
            $secondCollection->getProductOffers()->getArrayCopy(),
        );

        $this->assertCount(1, $firstProductOfferReferences);
        $this->assertContains($firstProductOfferTransfer->getProductOfferReference(), $firstProductOfferReferences);

        $this->assertCount(1, $secondProductOfferReferences);
        $this->assertContains($secondProductOfferTransfer->getProductOfferReference(), $secondProductOfferReferences);
    }

    public function testGetProductOfferCollectionReturnsFilledCollection(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchant();

        $productOfferTransfer = $this->tester->haveProductOffer([
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
        ]);

        $merchantProductOfferCriteriaTransfer = (new MerchantProductOfferCriteriaTransfer())
            ->setMerchantReference($merchantTransfer->getMerchantReference())
            ->setSkus([$productOfferTransfer->getConcreteSku()])
            ->setIsActive(true);

        // Act
        $productOfferCollectionTransfer = $this->tester->getFacade()->getProductOfferCollection($merchantProductOfferCriteriaTransfer);

        // Assert
        $this->assertNotEmpty($productOfferCollectionTransfer->getProductOffers());
    }

    public function testCheckingShoppingListItemWithActiveAndApprovedMerchantSucceeds(): void
    {
        // Arrange
        $shoppingListItemTransfer = $this->createShoppingListItem(true, 'approved');

        // Act
        $shoppingListPreAddItemCheckResponseTransfer = $this->tester->getFacade()->checkShoppingListItem($shoppingListItemTransfer);

        // Assert
        $this->assertTrue($shoppingListPreAddItemCheckResponseTransfer->getIsSuccess());
        $this->assertEmpty($shoppingListPreAddItemCheckResponseTransfer->getMessages());
    }

    public function testCheckingShoppingListItemWithInactiveAndApprovedMerchantFails(): void
    {
        // Arrange
        $shoppingListItemTransfer = $this->createShoppingListItem(false, 'approved');

        // Act
        $shoppingListPreAddItemCheckResponseTransfer = $this->tester->getFacade()->checkShoppingListItem($shoppingListItemTransfer);

        // Assert
        $this->assertFalse($shoppingListPreAddItemCheckResponseTransfer->getIsSuccess());
        $this->assertCount(1, $shoppingListPreAddItemCheckResponseTransfer->getMessages());
    }

    public function testCheckingShoppingListItemWithActiveAndUnapprovedMerchantFails(): void
    {
        // Arrange
        $shoppingListItemTransfer = $this->createShoppingListItem(false, 'declined');

        // Act
        $shoppingListPreAddItemCheckResponseTransfer = $this->tester->getFacade()->checkShoppingListItem($shoppingListItemTransfer);

        // Assert
        $this->assertFalse($shoppingListPreAddItemCheckResponseTransfer->getIsSuccess());
        $this->assertCount(2, $shoppingListPreAddItemCheckResponseTransfer->getMessages());
    }

    public function testCheckingShoppingListItemWithInactiveAndUnapprovedMerchantFails(): void
    {
        // Arrange
        $shoppingListItemTransfer = $this->createShoppingListItem(false, 'declined');

        // Act
        $shoppingListPreAddItemCheckResponseTransfer = $this->tester->getFacade()->checkShoppingListItem($shoppingListItemTransfer);

        // Assert
        $this->assertFalse($shoppingListPreAddItemCheckResponseTransfer->getIsSuccess());
        $this->assertCount(2, $shoppingListPreAddItemCheckResponseTransfer->getMessages());
    }

    protected function createShoppingListItem(bool $isActiveStatus, string $merchantStatus): ShoppingListItemTransfer
    {
        $merchantTransfer = $this->tester->haveMerchant([
            MerchantTransfer::IS_ACTIVE => $isActiveStatus,
            MerchantTransfer::STATUS => $merchantStatus,
        ]);
        $productOfferTransfer = $this->tester->haveProductOffer([
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
        ]);

        return (new ShoppingListItemTransfer())
            ->setProductOfferReference($productOfferTransfer->getProductOfferReference());
    }

    protected function clearMerchantProductOfferCollectionCache(): void
    {
        $reflectionProperty = new ReflectionProperty(MerchantProductOfferReader::class, 'merchantProductOfferCollectionCache');
        $reflectionProperty->setValue(null, []);
    }
}
