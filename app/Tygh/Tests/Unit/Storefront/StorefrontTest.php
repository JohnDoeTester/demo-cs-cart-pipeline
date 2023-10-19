<?php

namespace Tygh\Tests\Unit\Storefront;

use Tygh\Enum\StorefrontStatuses;
use Tygh\Storefront\RelationsManager;
use Tygh\Storefront\Storefront;
use Tygh\Tests\Unit\ATestCase;

class StorefrontTest extends ATestCase
{
    /**
     * @var \Tygh\Storefront\Storefront
     */
    protected $storefront;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $relations_manager;

    public function setUp(): void
    {
        $relations_manager = $this->createMock(RelationsManager::class);
        $relations_manager->method('getRelations')->willReturn(['r1', 'r2']);
        $relations_manager->method('resolveName')->will($this->returnArgument(0));
        $this->relations_manager = $relations_manager;
    }

    public function getStorefront()
    {
        return new Storefront(
            0,
            'http://example.com',
            true,
            false,
            StorefrontStatuses::OPEN,
            '',
            $this->relations_manager,
            'Main',
            'basic',
            [
                'r1' => [1, 2, 3, 4],
                'r2' => [],
            ]
        );
    }

    public function testRemoveRelationValues()
    {
        $this->assertEquals(
            [1, 2, 4],
            $this->getStorefront()->removeRelationValues('r1', 3)->getRelationValue('r1')
        );

        $this->assertEquals(
            [],
            $this->getStorefront()->removeRelationValues('r1', ['4', '3', '2', '1'])->getRelationValue('r1')
        );

        $this->assertEquals(
            [1, 2, 3, 4],
            $this->getStorefront()->removeRelationValues('r1', [])->getRelationValue('r1')
        );
    }

    public function testAddRelationValues()
    {
        $this->assertEquals(
            [1, 2, 3, 4, 5],
            $this->getStorefront()->addRelationValues('r1', 5)->getRelationValue('r1')
        );

        $this->assertEquals(
            [1, 2, 3, 4],
            $this->getStorefront()->addRelationValues('r1', [4, 3, 2, 1])->getRelationValue('r1')
        );

        $this->assertEquals(
            [1, 2, 3, 4, 5],
            $this->getStorefront()->addRelationValues('r1', [4, 3, 2, 1, 5])->getRelationValue('r1')
        );

        $this->assertEquals(
            [1, 2, 3, 4],
            $this->getStorefront()->addRelationValues('r1', [])->getRelationValue('r1')
        );

        $this->assertEquals(
            [1],
            $this->getStorefront()->addRelationValues('r2', 1)->getRelationValue('r2')
        );

        $this->assertEquals(
            [1, 2],
            $this->getStorefront()->addRelationValues('r2', [1, 2])->getRelationValue('r2')
        );

        $this->assertEquals(
            [],
            $this->getStorefront()->addRelationValues('r2', [])->getRelationValue('r2')
        );
    }
}
