<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOffer\Business\Hydrator;

use Generated\Shared\Transfer\CartReorderTransfer;

interface CartReorderItemHydratorInterface
{
    public function hydrate(CartReorderTransfer $cartReorderTransfer): CartReorderTransfer;
}
