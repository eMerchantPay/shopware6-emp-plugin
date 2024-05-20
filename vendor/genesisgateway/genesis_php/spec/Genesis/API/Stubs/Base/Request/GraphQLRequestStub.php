<?php

namespace spec\Genesis\API\Stubs\Base\Request;

use Genesis\API\Request\Base\GraphQLRequest;

class GraphQLRequestStub extends GraphQLRequest
{
    public function __construct()
    {
        parent::__construct('test', 'test_name');
    }

    public function publicGenerateGraphQLCode($elements, $element_name, $glue, $format)
    {
        return $this->generateGraphQLCode($elements, $element_name, $glue, $format);
    }

    protected function initGraphqlToken()
    {
        return 'token';
    }

    protected function getRequestFilters()
    {
        return 'filter';
    }

    protected function getRequestStructure()
    {
        return 'structure';
    }

    protected function getResponseFieldsAllowedValues()
    {
        return ['response_field'];
    }
}
