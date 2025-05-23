<?php

namespace spec\Genesis\Api\Traits;

use DateTime;
use Genesis\Exceptions\InvalidArgument;
use PhpSpec\ObjectBehavior;
use spec\Genesis\Api\Stubs\Traits\RestrictedSetterStub;
use spec\SharedExamples\Faker;

class RestrictedSetterSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beAnInstanceOf(RestrictedSetterStub::class);
    }

    public function it_should_not_fail_during_allowed_options_setter_with_proper_value()
    {
        $this->shouldNotThrow(InvalidArgument::class)->during(
            'publicAllowedOptionsSetter',
            [
                'test_field',
                ['val', 'val1', 'val2'],
                'val',
                'Error Message'
            ]
        );
    }

    public function it_should_set_property_with_value_during_allowed_options_setter()
    {
        $this->test_field = null;

        $this->publicAllowedOptionsSetter(
            'test_field',
            ['val', 'val1', 'val2'],
            'val',
            'Error Message'
        );

        $this->test_field->shouldBe('val');
    }

    public function it_should_fail_during_allowed_options_setter_with_not_proper_value()
    {
        $this->shouldThrow(InvalidArgument::class)->during(
            'publicAllowedOptionsSetter',
            [
                'test_field',
                ['val', 'val1', 'val2'],
                'val3',
                'Error Message'
            ]
        );
    }

    public function it_should_not_fail_during_limited_string_with_proper_max_value_and_without_min_value_length()
    {
        $this->shouldNotThrow(InvalidArgument::class)->during(
            'publicSetLimitedString',
            [
                'test_field',
                '******',
                null,
                6
            ]
        );
    }

    public function it_should_set_property_with_value_during_set_limted_string()
    {
        $this->test_field = null;

        $this->publicSetLimitedString(
            'test_field',
            '******'
        );

        $this->test_field->shouldBe('******');
    }

    public function it_should_not_fail_during_limited_string_with_proper_min_value_without_max_value_length()
    {
        $this->shouldNotThrow(InvalidArgument::class)->during(
            'publicSetLimitedString',
            [
                'test_field',
                '******',
                6,
                null
            ]
        );
    }

    public function it_should_not_fail_during_limited_string_with_proper_min_value_and_max_value_length()
    {
        $this->shouldNotThrow(InvalidArgument::class)->during(
            'publicSetLimitedString',
            [
                'test_field',
                '******',
                6,
                6
            ]
        );
    }

    public function it_should_fail_during_limited_string_with_invalid_min_value_length_without_max_value_length()
    {
        $this->shouldThrow(InvalidArgument::class)->during(
            'publicSetLimitedString',
            [
                'test_field',
                '******',
                7,
                null
            ]
        );
    }

    public function it_should_fail_during_limited_string_with_invalid_max_value_length_without_min_value_length()
    {
        $this->shouldThrow(InvalidArgument::class)->during(
            'publicSetLimitedString',
            [
                'test_field',
                '******',
                null,
                5
            ]
        );
    }

    public function it_should_fail_during_limited_string_with_invalid_max_and_min_value_length()
    {
        $this->shouldThrow(InvalidArgument::class)->during(
            'publicSetLimitedString',
            [
                'test_field',
                '******',
                1,
                5
            ]
        );
    }

    public function it_should_fail_during_parse_date_with_invalid_date_format()
    {
        $faker = Faker::getInstance();
        $this->shouldThrow(InvalidArgument::class)->during(
            'publicParseDate',
            [
                'test_field',
                ['Y-m-d'],
                $faker->dateTimeThisYear()->format('d-m-Y'),
                'Error Message'
            ]
        );
    }

    public function it_should_not_fail_during_parse_with_proper_date_format()
    {
        $faker = Faker::getInstance();
        $this->shouldNotThrow()->during(
            'publicParseDate',
            [
                'test_field',
                ['Y-m-d'],
                $faker->dateTimeThisYear()->format('Y-m-d'),
                'Error Message'
            ]
        );
    }

    public function it_should_set_property_with_date_time_instance_during_parse_date()
    {
        $this->test_field = null;

        $faker = Faker::getInstance();
        $this->publicParseDate(
            'test_field',
            ['Y-m-d'],
            $faker->dateTimeThisYear()->format('Y-m-d'),
            'Error Message'
        );
        $this->test_field->shouldHaveType(DateTime::class);
    }

    public function it_should_not_fail_with_more_than_one_valid_format_for_parse_date()
    {
        $faker = Faker::getInstance();
        $this->shouldNotThrow()->during(
            'publicParseDate',
            [
                'test_field',
                ['Y-m-d', 'd-m-Y', 'd/m/Y'],
                $faker->dateTimeThisYear()->format('d/m/Y'),
                'Error Message'
            ]
        );

        $this->shouldNotThrow()->during(
            'publicParseDate',
            [
                'test_field',
                ['Y-m-d', 'd-m-Y', 'd/m/Y'],
                $faker->dateTimeThisYear()->format('d-m-Y'),
                'Error Message'
            ]
        );
    }

    public function it_should_fail_during_parse_amount_with_invalid_value()
    {
        $this->shouldThrow(InvalidArgument::class)->during('publicParseAmount', ['test', 'aaa']);
        $this->shouldThrow(InvalidArgument::class)->during('publicParseAmount', ['test', -23]);
        $this->shouldThrow(InvalidArgument::class)->during('publicParseAmount', ['test', '23,76']);
    }

    public function it_should_not_fail_during_parse_amount_with_proper_value()
    {
        $faker = Faker::getInstance();
        $this->shouldNotThrow()->during(
            'publicParseAmount',
            [
                'test_field',
                $faker->numberBetween(1, PHP_INT_MAX)
            ]
        );
    }

    public function it_should_not_throw_when_parse_array_of_strings_with_valid_value()
    {
        $exampleArray = [1, '2'];

        $this->shouldNotThrow()->duringPublicParseArrayOfStrings('test_field', $exampleArray, 'Error message');

        $this->test_field->shouldBeArray();
        $this->test_field->shouldContainArrayOfStringValues();
    }

    public function it_should_throw_when_parse_array_of_strings_with_invalid_value()
    {
        $this
            ->shouldThrow(InvalidArgument::class)
            ->duringPublicParseArrayOfStrings('test_field', 'non_array_value', 'Error message');
    }

    public function getMatchers(): array
    {
        return array(
            'containArrayOfStringValues' => function ($subject) {
                foreach($subject as $value) {
                    if (!is_string($value)) {
                        return false;
                    }
                }

                return true;
            }
        );
    }
}
