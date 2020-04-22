<?php

/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */

declare(strict_types=1);

namespace Wizaplace\SDK\Vendor\Promotion\Rules;

use Wizaplace\SDK\Vendor\Promotion\BasketRuleType;

/**
 * Interface BasketRule
 * @package Wizaplace\SDK\Vendor\Promotion\Rules
 */
interface BasketRule
{
    /**
     * @internal for serialization purposes only
     */
    public function getType(): BasketRuleType;
}
