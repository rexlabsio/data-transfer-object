<?php

namespace Rexlabs\DataTransferObject\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Rexlabs\DataTransferObject\DataTransferObject;
use Rexlabs\DataTransferObject\Factory\Factory;

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

        // DataTransferObject stores a static reference to a Factory that caches
        // class metadata. Clear the factory each test so that the cache is
        // empty and no class metadata leaks from test to test.
        //
        // Also I'm sorry for caching static data
        DataTransferObject::setFactory(null);

        $this->factory = DataTransferObject::getFactory();
    }
}
