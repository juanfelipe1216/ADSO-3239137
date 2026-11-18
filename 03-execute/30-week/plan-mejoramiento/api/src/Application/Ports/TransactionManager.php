<?php

declare(strict_types=1);

namespace Lab\Application\Ports;

interface TransactionManager
{
    /**
     * Begin a transaction and serialize this researcher's operations before invoking the callback.
     * All participating repositories must share it. Commit on success, roll back any Throwable,
     * and release the researcher lock on either outcome. Return the callback's result unchanged.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function forResearcher(string $researcherId, callable $operation): mixed;
}
