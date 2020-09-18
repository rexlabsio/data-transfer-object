<?php

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory;

class TestCase extends BaseTestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Clear cached static data
        // Also I'm sorry for caching static data
        $this->factory = new Factory([]);
        DataTransferObject::setFactory($this->factory);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        // Clear cached static data
        // Also I'm sorry for caching static data
        DataTransferObject::setFactory(null);
    }
}
