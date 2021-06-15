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
 * @copyright   2021 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Utils;

use Emerchantpay\Genesis\Core\Payment\Transaction\TransactionEntity;

/**
 * Helper for Simple Transaction Tree
 *
 * Class TransactionTree
 */
class TransactionTree
{
    /**
     * LEAF By Status
     */
    public const APPROVED_LEAF         = 'approved';

    /**
     * Structure Constants
     */
    public const BRANCH_NODE           = 'references';

    /**
     * Data Constants
     */
    public const DATA_STATUS           = 'status';
    public const DATA_UNIQUE_ID        = 'unique_id';
    public const DATA_TRANSACTION_ID   = 'transaction_id';
    public const DATA_REFERENCE_ID     = 'reference_id';
    public const DATA_TRANSACTION_TYPE = 'transaction_type';
    public const DATA_AMOUNT           = 'amount';
    public const DATA_CURRENCY         = 'currency';


    /**
     * Builds a tree from given array with transactions
     *      array(
     *              [0] => Transactions Model Object,
     *              ...
     *      )
     * Outputs array tree
     *     tree = array (
     * Root           array (
     *                  transaction_id => {data}
     *                  reference_id => {data}
     *                  transaction_type => {data}
     *                  ...
     *                  references => array (
     * Branch              array (
     *                        unique_id => {data}
     *                        transaction_id => {data}
     *                        reference_id => {data}
     *                        transaction_type => {data}
     *                        ...
     *                        references => array (
     *  Leaf                      array (
     *                                unique_id => {data}
     *                                transaction_id => {data}
     *                                reference_id => {data}
     *                                transaction_type => {data}
     *                                ...
     *                                references => array ()
     *                            )
     *                        )
     *                    )
     * Branch             array (
     *                        unique_id => {data}
     *                        transaction_id => {data}
     *                        reference_id => {data}
     *                        transaction_type => {data}
     *                        ...
     *                        references => array (
     * Leaf                       array (
     *                                [unique_id] => {data}
     *                                transaction_id => {data}
     *                                reference_id => {data}
     *                                transaction_type => {data}
     *                                ...
     *                                references => array ()
     *                            )
     * Leaf                       array (
     *                                unique_id => {data}
     *                                transaction_id => {data}
     *                                reference_id => {data}
     *                                transaction_type => {data}
     *                                ...
     *                                references => array ()
     *                            )
     *                        )
     *                    )
     *                )
     *            )
     *
     * @param string $uniqueId
     * @param array $transactions
     * @return array
     */
    public function buildTree($uniqueId, $transactions)
    {
        $tree = [];

        /** @var TransactionEntity $transaction */
        foreach ($transactions as $transaction) {
            if ($transaction->getUniqueId() !== $uniqueId) {
                continue;
            }

            $tree[] = $this->addNodeData($transaction);

            // Build Branches and Leaves
            $this->buildReferences($tree[self::arrayKeyLast($tree)], $transactions);
        }

        return $tree;
    }

    /**
     * Find the last approved Transaction Leaf in the transactionTree
     *
     * @param array $transactionTree
     * @param string $uniqueId
     * @return array
     */
    public function findLastApprovedLeaf($transactionTree, $uniqueId)
    {
        foreach ($transactionTree as $transaction) {
            if ($transaction[self::DATA_UNIQUE_ID] === $uniqueId) {
                if (!empty($transaction[self::BRANCH_NODE])) {
                    // Loop the references for that branch
                    return $this->loopBranches($transaction[self::BRANCH_NODE]);
                }

                return $transaction;
            }
        }

        return [];
    }

    /**
     * @param array $transactionTree
     * @param string $excludeTransactionType
     * @return array|mixed
     */
    public function findInitialTransaction(array $transactionTree, string $excludeTransactionType)
    {
        foreach ($transactionTree as $transaction) {
            if ($transaction[self::DATA_TRANSACTION_TYPE] !== $excludeTransactionType) {
                return $transaction;
            }

            if (!empty($transaction[self::BRANCH_NODE])) {
                return $this->findInitialTransaction($transaction[self::BRANCH_NODE], $excludeTransactionType);
            }
        }

        return [];
    }

    /**
     * @param array $transactionTree
     * @param array $states
     * @param array $transactionTypes
     * @return array|mixed
     */
    public function findReferenceTransaction(array $transactionTree, array $states, array $transactionTypes)
    {
        foreach ($transactionTree as $transaction) {
            if (in_array($transaction[self::DATA_STATUS], $states, true) &&
                in_array($transaction[self::DATA_TRANSACTION_TYPE], $transactionTypes, true)
            ) {
                return $transaction;
            }

            if (!empty($transaction[self::BRANCH_NODE])) {
                return $this->findReferenceTransaction($transaction[self::BRANCH_NODE], $states, $transactionTypes);
            }
        }

        return [];
    }

    /**
     * Build Branches and Leaves recursively
     *
     * @param array $node
     * @param array $transactions
     */
    private function buildReferences(&$node, $transactions)
    {
        /** @var TransactionEntity $transaction */
        foreach ($transactions as $transaction) {
            if ($transaction->getReferenceId() === $node[self::DATA_UNIQUE_ID]) {
                $node[self::BRANCH_NODE][] = $this->addNodeData($transaction);
                $this->buildReferences(
                    $node[self::BRANCH_NODE][self::arrayKeyLast($node[self::BRANCH_NODE])],
                    $transactions
                );
            }
        }
    }

    /**
     * @param TransactionEntity $transaction
     * @return array
     */
    private function addNodeData($transaction)
    {
        return [
            self::DATA_UNIQUE_ID        => $transaction->getUniqueId(),
            self::DATA_TRANSACTION_ID   => $transaction->getTransactionId(),
            self::DATA_REFERENCE_ID     => $transaction->getReferenceId(),
            self::DATA_STATUS           => $transaction->getStatus(),
            self::DATA_TRANSACTION_TYPE => $transaction->getTransactionType(),
            self::DATA_AMOUNT           => $transaction->getAmount(),
            self::DATA_CURRENCY         => $transaction->getCurrency(),

            // Create empty Branch for every node
            self::BRANCH_NODE  => []
        ];
    }

    /**
     * Walk through all the branches for the specified unique_id.
     * Return the last approved leaf. If approved leaf is missing then return empty array
     *
     * @param array $transactionBranches
     * @param string $status
     * @return array
     */
    private function loopBranches($transactionBranches, $status = self::APPROVED_LEAF)
    {
        foreach ($transactionBranches as $transaction) {
            if (empty($transaction[self::BRANCH_NODE]) && $transaction[self::DATA_STATUS] === $status) {
                return $transaction;
            }

            if (!empty($transaction[self::BRANCH_NODE])) {
                return $this->loopBranches($transaction[self::BRANCH_NODE]);
            }

            return $transaction;
        }

        return [];
    }

    /**
     * Helper for last array key
     *
     * @param $array
     * @return mixed|null
     */
    private static function arrayKeyLast($array)
    {
        if (!is_array($array) || empty($array)) {
            return null;
        }

        return array_keys($array)[count($array) - 1];
    }
}
