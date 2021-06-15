<?php

namespace spec\Genesis\Parsers;

use Genesis\Parser;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class XMLSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Genesis\Parsers\XML');
    }

    public function it_should_parse_response()
    {
        $xml = file_get_contents('spec/fixtures/XML/gate_authorize_request.xml');
        $this->skipRootNode();

        $this->parseDocument($xml);

        $this->getObject()->transaction_type->shouldBe('authorize');
        $this->getObject()->amount->shouldBe('5000');
        $this->getObject()->technical_message->shouldBe('Transaction successful!');
        $this->getObject()->sent_to_acquirer->shouldBe(true);
    }

    public function it_should_parse_urls()
    {
        $xml = file_get_contents('spec/fixtures/XML/gate_apm_request.xml');

        $this->skipRootNode();

        $this->parseDocument($xml);

        $this->getObject()->status->shouldBe('pending_async');

        $this->getObject()->redirect_url->shouldBe(
            'https://staging.gate.x.net/redirect/to_acquirer/xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
        );
    }

    public function it_should_parse_multinodes()
    {
        $xml = file_get_contents('spec/fixtures/XML/wpf_request.xml');

        $this->skipRootNode();

        $this->parseDocument($xml);

        $this->getObject()->billing_address->first_name->shouldBe('John');

        $this->getObject()->transaction_types->transaction_type->shouldHaveType('\ArrayObject');

        $this->getObject()->transaction_types->transaction_type->count()->shouldBe(5);
    }
}
