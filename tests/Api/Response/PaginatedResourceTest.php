<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Api\Response;

use HiTaqnia\Haykal\Api\Response\PaginatedResource;
use HiTaqnia\Haykal\Tests\Api\ApiTestCase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginatedResourceTest extends ApiTestCase
{
    public function test_wraps_paginator_payload_in_items_and_pagination_keys(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [['id' => 1], ['id' => 2]],
            total: 42,
            perPage: 2,
            currentPage: 3,
        );

        $array = (new PaginatedResource($paginator))->toArray(Request::create('/'));

        $this->assertSame([
            'items' => [['id' => 1], ['id' => 2]],
            'pagination' => [
                'page' => 3,
                'per_page' => 2,
                'total' => 42,
            ],
        ], $array);
    }
}
