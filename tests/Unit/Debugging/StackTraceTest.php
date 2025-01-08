<?php

namespace Rexlabs\DataTransferObject\Tests\Unit\Debugging;

use Faker\Factory;
use Rexlabs\DataTransferObject\Exceptions\DataTransferObjectTypeError;
use Rexlabs\DataTransferObject\Exceptions\ImmutableTypeError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\MUTABLE;
use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class StackTraceTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function exception_stack_traces_are_short(): void
    {
        $maxSize = 1;

        $this->factory->setClassMetadata(
            TestDataTransferObject::class,
            [
                'first_name' => ['string'],
                'last_name' => ['string'],
                'email' => ['string'],
                'phone' => ['null', 'string'],
                'parent' => ['null', TestDataTransferObject::class],
                'children' => [TestDataTransferObject::class . '[]'],
            ]
        );

        $faker = Factory::create();

        $stackTraceTable = [
            [
                'message' => 'ImmutableTypeError on __set',
                'exception' => ImmutableTypeError::class,
                'call' => function () use ($faker) {
                    $dto = TestDataTransferObject::make([], PARTIAL);
                    $dto->__set('first_name', $faker->firstName);
                },
            ],
            [
                'message' => 'InvalidTypeError on make',
                'exception' => InvalidTypeError::class,
                'call' => function () {
                    TestDataTransferObject::make(['first_name' => null], PARTIAL);
                },
            ],
            [
                'message' => 'InvalidTypeError on __set',
                'exception' => InvalidTypeError::class,
                'call' => function () {
                    $dto = TestDataTransferObject::make([], PARTIAL | MUTABLE);
                    $dto->__set('first_name', null);
                },
            ],
            [
                'message' => 'UndefinedPropertiesTypeError on make',
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () {
                    TestDataTransferObject::make([], NONE);
                },
            ],
            [
                'message' => 'UndefinedPropertiesTypeError on __get from partial',
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () {
                    $dto = TestDataTransferObject::make([], PARTIAL);
                    $dto->__get('first_name');
                },
            ],
            [
                'message' => 'UndefinedPropertiesTypeError on manual assertDefined',
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () {
                    $dto = TestDataTransferObject::make([], PARTIAL);
                    $dto->assertDefined('first_name');
                },
            ],
            [
                'message' => 'UnknownPropertiesTypeError on make',
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                    TestDataTransferObject::make(['fake_name' => 'fake_value'], PARTIAL);
                },
            ],
            [
                'message' => 'UnknownPropertiesTypeError on set',
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                    $dto = TestDataTransferObject::make([], PARTIAL);
                    $dto->__set('fake_name', 'fake_value');
                },
            ],
            [
                'message' => 'UnknownPropertiesTypeError on isDefined',
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                    $dto = TestDataTransferObject::make([], PARTIAL);
                    $dto->isDefined('fake_name');
                },
            ],
            // Make nested property
            [
                'message' => 'InvalidTypeError on make nested property',
                'exception' => InvalidTypeError::class,
                'call' => function () {
                    TestDataTransferObject::make(
                        [
                            'parent' => [
                                'first_name' => null,
                            ],
                        ],
                        PARTIAL
                    );
                },
                // 'debug' => true,
            ],
            [
                'message' => 'UndefinedPropertiesTypeError on make nested property',
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () use ($faker) {
                    TestDataTransferObject::make(
                        [
                            'first_name' => $faker->firstName,
                            'last_name' => $faker->lastName,
                            'email' => $faker->email,
                            'phone' => $faker->phoneNumber,
                            'parent' => [
                                'first_name' => $faker->firstName,
                                'last_name' => $faker->lastName,
                            ],
                            'children' => [],
                        ],
                        NONE
                    );
                },
                // 'debug' => true,
            ],
            [
                'message' => 'UnknownPropertiesTypeError on make nested property',
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                    TestDataTransferObject::make(
                        [
                            'parent' => [
                                'fake_prop' => 'fake_value',
                                'fake_prop_2' => 'fake_value_2',
                            ],
                        ],
                        PARTIAL
                    );
                },
                // 'debug' => true,
            ],
            // Make nested property collection
            [
                'message' => 'InvalidTypeError on make nested property collection',
                'exception' => InvalidTypeError::class,
                'call' => function () {
                    TestDataTransferObject::make(
                        [
                            'children' => [
                                [
                                    'first_name' => null,
                                ],
                            ],
                        ],
                        PARTIAL
                    );
                },
                // 'debug' => true,
            ],
            [
                'message' => 'UndefinedPropertiesTypeError on make nested property collection',
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () use ($faker) {
                    TestDataTransferObject::make(
                        [
                            'first_name' => $faker->firstName,
                            'last_name' => $faker->lastName,
                            'email' => $faker->email,
                            'phone' => $faker->phoneNumber,
                            'parent' => null,
                            'children' => [
                                [
                                    'first_name' => $faker->firstName,
                                    'last_name' => $faker->lastName,
                                ],
                            ],
                        ],
                        NONE
                    );
                },
                // 'debug' => true,
            ],
            [
                'message' => 'UnknownPropertiesTypeError on make nested property collection',
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                    TestDataTransferObject::make(
                        [
                            'children' => [
                                [
                                    'parent' => [
                                        'fake_prop' => 'fake_value',
                                        'fake_prop_2' => 'fake_value_2',
                                    ],
                                ],
                            ],
                        ],
                        PARTIAL
                    );
                },
                // 'debug' => true,
            ],
        ];

        foreach ($stackTraceTable as $stackTraceRow) {
            $message = $stackTraceRow['message'];
            $exceptionClass = $stackTraceRow['exception'];
            $call = $stackTraceRow['call'];

            try {
                $call();
                self::fail(
                    sprintf(
                        'Unable to get stack trace for callable that did not throw on: %s',
                        $message
                    )
                );
                break;
            } catch (DataTransferObjectTypeError $e) {
                // Make sure only the expected exception is caught
                if (!$e instanceof $exceptionClass || ($stackTraceRow['debug'] ?? false)) {
                    throw $e;
                }
                $trace = $e->getTrace();
            }

            self::assertNotEmpty(
                $trace,
                sprintf(
                    'Unable to get stack trace for callable that did not throw on: %s',
                    $message
                )
            );

            $relevantTrace = [];

            foreach ($trace as $traceItem) {
                // PHP 8.4 changed the closure format in stack traces
                // https://tideways.com/profiler/blog/php-8-4-improves-closure-naming-for-simplified-debugging
                if (PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION >= 4) {
                    if (str_starts_with($traceItem['function'], '{closure:' . __METHOD__)) {
                        break;
                    }
                } elseif ($traceItem['function'] === __NAMESPACE__ . '\\{closure}') {
                    break;
                }

                $relevantTrace[] = $traceItem;
            }

            $message = sprintf(
                'Exception stack trace exceeds maximum size: %d for: "%s"',
                $maxSize,
                $message
            );

            if (count($relevantTrace) > $maxSize) {
                echo $message;
                throw $e;
            }
        }
    }
}
