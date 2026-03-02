<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOffer\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\MerchantProductOffer\Business\Checker\MerchantProductOfferChecker;
use Spryker\Zed\MerchantProductOffer\Business\Checker\MerchantProductOfferCheckerInterface;
use Spryker\Zed\MerchantProductOffer\Business\Expander\ProductConcreteOfferExpander;
use Spryker\Zed\MerchantProductOffer\Business\Expander\ProductConcreteOfferExpanderInterface;
use Spryker\Zed\MerchantProductOffer\Business\Expander\ShoppingListItemExpander;
use Spryker\Zed\MerchantProductOffer\Business\Expander\ShoppingListItemExpanderInterface;
use Spryker\Zed\MerchantProductOffer\Business\Hydrator\CartReorderItemHydrator;
use Spryker\Zed\MerchantProductOffer\Business\Hydrator\CartReorderItemHydratorInterface;
use Spryker\Zed\MerchantProductOffer\Business\MerchantProductOfferReader\MerchantProductOfferReader;
use Spryker\Zed\MerchantProductOffer\Business\MerchantProductOfferReader\MerchantProductOfferReaderInterface;
use Spryker\Zed\MerchantProductOffer\Dependency\Facade\MerchantProductOfferToMerchantFacadeInterface;
use Spryker\Zed\MerchantProductOffer\Dependency\Facade\MerchantProductOfferToProductOfferFacadeInterface;
use Spryker\Zed\MerchantProductOffer\MerchantProductOfferDependencyProvider;

/**
 * @method \Spryker\Zed\MerchantProductOffer\MerchantProductOfferConfig getConfig()
 * @method \Spryker\Zed\MerchantProductOffer\Persistence\MerchantProductOfferRepositoryInterface getRepository()
 */
class MerchantProductOfferBusinessFactory extends AbstractBusinessFactory
{
    public function createMerchantProductOfferReader(): MerchantProductOfferReaderInterface
    {
        return new MerchantProductOfferReader(
            $this->getProductOfferFacade(),
            $this->getRepository(),
        );
    }

    public function getProductOfferFacade(): MerchantProductOfferToProductOfferFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferDependencyProvider::FACADE_PRODUCT_OFFER);
    }

    public function createProductConcreteOfferExpander(): ProductConcreteOfferExpanderInterface
    {
        return new ProductConcreteOfferExpander(
            $this->getProductOfferFacade(),
            $this->getMerchantFacade(),
        );
    }

    public function createShoppingListItemExpander(): ShoppingListItemExpanderInterface
    {
        return new ShoppingListItemExpander(
            $this->getProductOfferFacade(),
        );
    }

    public function createCartReorderItemHydrator(): CartReorderItemHydratorInterface
    {
        return new CartReorderItemHydrator();
    }

    public function getMerchantFacade(): MerchantProductOfferToMerchantFacadeInterface
    {
        return $this->getProvidedDependency(MerchantProductOfferDependencyProvider::FACADE_MERCHANT);
    }

    public function createMerchantProductOfferChecker(): MerchantProductOfferCheckerInterface
    {
        return new MerchantProductOfferChecker(
            $this->getProductOfferFacade(),
            $this->getMerchantFacade(),
        );
    }
}
