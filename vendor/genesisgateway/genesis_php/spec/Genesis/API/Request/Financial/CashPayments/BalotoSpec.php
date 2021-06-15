<?php

namespace spec\Genesis\API\Request\Financial\CashPayments;

use Genesis\API\Request\Financial\CashPayments\Baloto;
use PhpSpec\ObjectBehavior;
use spec\SharedExamples\Genesis\API\Request\RequestExamples;

class BalotoSpec extends ObjectBehavior
{
    use RequestExamples;

    public function it_is_initializable()
    {
        $this->shouldHaveType(Baloto::class);
    }

    public function it_should_fail_when_country_not_co_param()
    {
        $this->setRequestParameters();
        $this->setBillingCountry('BG');
        $this->shouldThrow()->during('getDocument');
    }

    protected function setRequestParameters()
    {
        $this->setDefaultRequestParameters();

        $this->setConsumerReference('1234');
        $this->setNationalId('1234');
        $this->setCurrency('USD');
        $this->setBillingCountry('CO');
    }
}
