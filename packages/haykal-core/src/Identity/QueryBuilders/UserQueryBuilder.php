<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\QueryBuilders;

use HiTaqnia\Haykal\Core\Identity\Models\BaseHuwiyaUser;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<BaseHuwiyaUser>
 */
final class UserQueryBuilder extends Builder
{
    public function wherePhoneNumber(string $phone): self
    {
        return $this->where('phone', (new PhoneNumber($phone))->getInternational());
    }

    public function getByPhoneNumber(string $phone): ?BaseHuwiyaUser
    {
        return $this->wherePhoneNumber($phone)->first();
    }
}
