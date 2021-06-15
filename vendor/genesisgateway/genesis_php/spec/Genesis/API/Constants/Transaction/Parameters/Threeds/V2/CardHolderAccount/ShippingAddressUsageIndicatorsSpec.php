<?php

namespace spec\Genesis\API\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount;

use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\ShippingAddressUsageIndicators;
use PhpSpec\ObjectBehavior;

class ShippingAddressUsageIndicatorsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ShippingAddressUsageIndicators::class);
    }

    public function it_should_be_array()
    {
        $this->getAll()->shouldBeArray();
    }
}
