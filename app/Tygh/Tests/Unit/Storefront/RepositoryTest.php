<?php

namespace Tygh\Tests\Unit\Storefront;

use Tygh\Common\Robots;
use Tygh\Database\Connection;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Storefront\Factory;
use Tygh\Storefront\Normalizer;
use Tygh\Storefront\RelationsManager;
use Tygh\Storefront\Repository;
use Tygh\Storefront\Storefront;
use Tygh\Tests\Unit\ATestCase;

class RepositoryTest extends ATestCase
{
    /** @var Repository */
    protected $repository;

    public function setUp(): void
    {
        $this->repository = new Repository(
            $this->createMock(Connection::class),
            $this->createMock(Factory::class),
            $this->createMock(Normalizer::class),
            $this->createMock(RelationsManager::class),
            $this->createMock(Robots::class)
        );
    }

    /**
     * @dataProvider dpFindClosestMatchingByPath
     */
    public function testFindClosestMatchingByPath($path, $storefronts, $expected)
    {
        $actual = $this->repository->findClosestMatchingByPath($path, $storefronts);

        $this->assertEquals($expected, $actual);
    }

    public function dpFindClosestMatchingByPath()
    {
        $relations_manager = $this->createMock(RelationsManager::class);
        $status = StorefrontStatuses::OPEN;

        $storefront = new Storefront(
            0,
            'example.com',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_1 = new Storefront(
            1,
            'example.com/store1',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_1_1 = new Storefront(
            11,
            'example.com/store1/substore1',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_1_2 = new Storefront(
            12,
            'example.com/store1/substore2',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_2 = new Storefront(
            2,
            'example.com/store2',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_2_1 = new Storefront(
            21,
            'example.com/store2/substore1',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_2_2 = new Storefront(
            22,
            'example.com/store2/substore2',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_3_1 = new Storefront(
            1,
            'example1.com/storefront/substore1',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_3_2 = new Storefront(
            2,
            'example1.com/storefront/substore2',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_3_3 = new Storefront(
            3,
            'example1.com/storefront',
            true,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );
        $storefront_3_4 = new Storefront(
            4,
            'example1.com/storefront/substore3',
            false,
            false,
            $status,
            '',
            $relations_manager,
            '',
            '',
            []
        );

        $storefronts = [
            $storefront,
            $storefront_1,
            $storefront_1_1,
            $storefront_1_2,
            $storefront_2,
            $storefront_2_1,
            $storefront_2_2,
        ];
        $storefronts_2 = [
            $storefront,
            $storefront_3_1,
            $storefront_3_2,
            $storefront_3_3,
            $storefront_3_4,
        ];

        return [
            [
                '',
                [],
                null,
            ],
            [
                '',
                $storefronts,
                $storefront,
            ],
            [
                '/',
                $storefronts,
                $storefront,
            ],
            [
                '/electronics/tv/lg.html',
                $storefronts,
                $storefront,
            ],
            [
                '/store1',
                $storefronts,
                $storefront_1,
            ],
            [
                '/store1/electronics/tv/lg.html',
                $storefronts,
                $storefront_1,
            ],
            [
                '/store1/substore1',
                $storefronts,
                $storefront_1_1,
            ],
            [
                '/store1/substore1/electronics/tv/lg.html',
                $storefronts,
                $storefront_1_1,
            ],
            [
                '/storefront',
                $storefronts_2,
                $storefront_3_3,
            ],
        ];
    }
}
