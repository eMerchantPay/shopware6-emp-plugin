<?php declare(strict_types=1);
/*
 * Copyright (C) 2025 emerchantpay Ltd.
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
 * @copyright   2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Core\Payment\Transaction;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TransactionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'emerchantpay_genesis_payment_entity';
    public const MAX_LENGTH_IDENTIFIERS = 255;
    public const MAX_LENGTH_METHOD = 64;
    public const MAX_LENGTH_STATUS = 32;
    public const MAX_LENGTH_TRANSACTION_TYPE = 128;
    public const MAX_LENGTH_CURRENCY = 6;
    public const MAX_LENGTH_MODE = 6;

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return TransactionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return TransactionCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField(
                'transaction_id',
                'transaction_id',
                self::MAX_LENGTH_IDENTIFIERS
            ))->addFlags(new Required()),
            (new StringField(
                'unique_id',
                'unique_id',
                self::MAX_LENGTH_IDENTIFIERS
            )),
            (new StringField(
                'reference_id',
                'reference_id',
                self::MAX_LENGTH_IDENTIFIERS
            )),
            (new StringField(
                'payment_method',
                'payment_method',
                self::MAX_LENGTH_METHOD
            ))->addFlags(new Required()),
            (new StringField(
                'terminal_token',
                'terminal_token',
                self::MAX_LENGTH_IDENTIFIERS
            )),
            (new StringField(
                'status',
                'status',
                self::MAX_LENGTH_STATUS
            ))->addFlags(new Required()),
            (new StringField(
                'order_id',
                'order_id',
                self::MAX_LENGTH_IDENTIFIERS
            )),
            (new StringField(
                'transaction_type',
                'transaction_type',
                self::MAX_LENGTH_TRANSACTION_TYPE
            )),
            (new IntField('amount', 'amount'))->addFlags(new Required()),
            (new StringField('currency', 'currency', self::MAX_LENGTH_CURRENCY))
                ->addFlags(new Required()),
            (new StringField('mode', 'mode', self::MAX_LENGTH_MODE))
                ->addFlags(new Required()),
            (new LongTextField('message', 'message')),
            (new LongTextField('technical_message', 'technical_message')),
            (new LongTextField('request', 'request')),
            (new LongTextField('response', 'response')),
            (new DateTimeField('created_at', 'created_at')),
            (new DateTimeField('updated_at', 'updated_at')),
            (new StringField(
                'shopware_payment_id',
                'shopware_payment_id',
                self::MAX_LENGTH_IDENTIFIERS
            ))
        ]);
    }
}
