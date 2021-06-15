<?php declare(strict_types=1);
/*
 * Copyright (C) 2021 emerchantpay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      emerchantpay
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Utils\Data\Base;

use Genesis\Utils\Common as SdkCommonUtils;

/**
 * Class DataAdapter
 */
abstract class DataAdapter
{
    /**
     * Contains all the data
     *
     * @var array
     */
    private $data = [];

    /**
     * DataAdapter constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->loadFromArray($data);
    }

    /**
     * Magic method used for handling the setters and the getters
     * Uses the fields as method names in CamelCase
     *
     * @param $name
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return $this|mixed
     */
    public function __call($name, $arguments)
    {
        $method = substr($name, 0, 3);
        $property = substr($name, 3);

        if ($method === 'get') {
            return $this->getProperty($property);
        }

        if ($method === 'set') {
            return $this->setProperty($property, $arguments[0]);
        }

        throw new \Exception('You are trying to call non-existing method');
    }

    /**
     * Defined key values for the specific data
     * Every key is representation of magic method.
     * Key is in snake_case, methods are transformed in CamelCase via the Magic Methods
     *
     * @return array
     */
    abstract public function getFields();

    /**
     * Returns all properties and their values as associative array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getData();
    }

    /**
     * Return all properties and their values as HTTP query parameters
     *
     * @return string
     */
    public function toHttpQuery()
    {
        return http_build_query($this->getData());
    }

    /**
     * @param string $name
     *
     * @throws \Exception
     *
     * @return $this
     */
    protected function setProperty($name, $value)
    {
        $field = SdkCommonUtils::pascalToSnakeCase($name);
        if (!in_array($field, $this->getFields(), true)) {
            throw new \Exception('You are trying to call non-existing method');
        }

        $this->data[$field] = $value;

        return $this;
    }

    /**
     * @param $name
     *
     * @throws \Exception
     *
     * @return mixed|null
     */
    protected function getProperty($name)
    {
        $field = SdkCommonUtils::pascalToSnakeCase($name);
        if (!in_array($field, $this->getFields(), true)) {
            throw new \Exception('You are trying to call non-existing method');
        }

        return array_key_exists($field, $this->data) ? $this->data[$field] : null;
    }

    /**
     * Returns the data array
     *
     * @return array
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Load the data from array
     *
     * @param array $data
     */
    private function loadFromArray($data): void
    {
        foreach ($data as $field => $value) {
            if (in_array($field, $this->getFields(), true)) {
                $method = 'set' . SdkCommonUtils::snakeCaseToCamelCase($field);
                $this->{$method}($value);
            }
        }
    }
}
