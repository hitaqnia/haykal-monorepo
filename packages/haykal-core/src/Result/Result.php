<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Result;

use InvalidArgumentException;

/**
 * Typed outcome for operations that can fail in expected, non-exceptional ways.
 *
 * Use `Result::success($data)` for happy paths and `Result::failure(Error::make(...))`
 * for recoverable failures. Exceptions remain the right answer for truly unexpected
 * conditions (programmer error, infrastructure crashes).
 *
 * @template T
 */
final class Result
{
    /**
     * @param  T|null  $data
     */
    private function __construct(
        private readonly bool $isSuccess,
        private readonly mixed $data = null,
        private readonly ?Error $error = null,
    ) {
        if ($isSuccess && $error !== null) {
            throw new InvalidArgumentException('A successful Result cannot carry an Error.');
        }

        if (! $isSuccess && $error === null) {
            throw new InvalidArgumentException('A failed Result must carry an Error.');
        }
    }

    /**
     * @template TData
     *
     * @param  TData|null  $data
     * @return self<TData>
     */
    public static function success(mixed $data = null): self
    {
        return new self(isSuccess: true, data: $data);
    }

    /**
     * @template TData
     *
     * @return self<TData>
     */
    public static function failure(Error $error): self
    {
        return new self(isSuccess: false, error: $error);
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function isFailure(): bool
    {
        return ! $this->isSuccess;
    }

    /**
     * @return T|null
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    public function getError(): ?Error
    {
        return $this->error;
    }
}
