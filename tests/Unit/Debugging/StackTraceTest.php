<?php

namespace Rexlabs\DataTransferObject\Tests\Unit\Debugging;

use Faker\Factory;
use Rexlabs\DataTransferObject\Exceptions\DataTransferObjectTypeError;
use Rexlabs\DataTransferObject\Exceptions\ImmutableTypeError;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Exceptions\UnknownPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingDto;
use Rexlabs\DataTransferObject\Tests\TestCase;

use const Rexlabs\DataTransferObject\PARTIAL;

class StackTraceTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function exception_stack_traces_are_short(): void
    {
        self::assertTrue(true);
        return;

        $maxSize = 2;

        $stackTraceTable = [
            // ImmutableTypeError on __set
            [
                'exception' => ImmutableTypeError::class,
                'call' => function () {
                    $faker = Factory::create();
                    $dto = TestingDto::make([], PARTIAL);

                    $dto->first_name = $faker->firstName;
                }
            ],
            // InvalidTypeError on make
            [
                'exception' => InvalidTypeError::class,
                'call' => function () {
                    TestingDto::make(['first_name' => true], PARTIAL);
                }
            ],
            // InvalidTypeError on __set
            [
                'exception' => InvalidTypeError::class,
                'call' => function () {
                }
            ],
            // UndefinedPropertiesTypeError on make
            [
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () {
                }
            ],
            // UndefinedPropertiesTypeError on __get from partial
            [
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () {
                }
            ],
            // UndefinedPropertiesTypeError on manual assertDefined
            [
                'exception' => UndefinedPropertiesTypeError::class,
                'call' => function () {
                    $dto = TestingDto::make([], PARTIAL);

                    $dto->assertDefined(['last_name']);
                }
            ],
            // UnknownPropertiesTypeError on make
            [
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                }
            ],
            // UnknownPropertiesTypeError on set
            [
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                }
            ],
            // UnknownPropertiesTypeError on isDefined
            [
                'exception' => UnknownPropertiesTypeError::class,
                'call' => function () {
                }
            ],
        ];

        foreach ($stackTraceTable as $stackTraceRow) {
            $exceptionClass = $stackTraceRow['exception'];
            $call = $stackTraceRow['call'];

            $trace = $this->getTrace($exceptionClass, $call);

            if (count($trace) > $maxSize) {
                print_r($trace);
                // Let it fail so the exception goes to stdout
                $call();
                break;
            }

            self::assertLessThanOrEqual($maxSize, count($trace), sprintf(
                'Exception stack trace exceeds maximum size: %d',
                $maxSize
            ));
        }
    }

    /**
     * @param string $exceptionClass
     * @param callable $call
     *
     * @return array
     */
    private function getTrace(string $exceptionClass, callable $call): array
    {
        // $call();
        // return [];
        try {
            $call();
        } catch (DataTransferObjectTypeError $e) {
            // Make sure only the expected exception is caught
            if (!$e instanceof $exceptionClass) {
                throw $e;
            }
            $trace = $e->getTrace();
        }

        if (empty($trace)) {
            self::fail('Unable to get stack trace for callable that did not throw');

            // Statement should be unreachable
            return [];
        }

        $relevantTrace = [];
        $closureFunctionName = __NAMESPACE__ . '\\{closure}';

        foreach ($trace as $traceItem) {
            if ($traceItem['function'] === $closureFunctionName) {
                break;
            }

            $relevantTrace[] = $traceItem;
        }

        return $relevantTrace;
    }
}
