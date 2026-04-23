<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\QueryBuilders;

use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<User>
 */
final class UserQueryBuilder extends Builder
{
    public function wherePhoneNumber(string $phone): self
    {
        return $this->where('phone', (new PhoneNumber($phone))->getInternational());
    }

    public function getByPhoneNumber(string $phone): ?User
    {
        return $this->wherePhoneNumber($phone)->first();
    }
}
