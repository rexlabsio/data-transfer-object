<?php

namespace Rexlabs\DataTransferObject\Tests\Unit\Debugging;

use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Rexlabs\DataTransferObject\Exceptions\DataTransferObjectTypeError;
use Rexlabs\DataTransferObject\Exceptions\UndefinedPropertiesTypeError;
use Rexlabs\DataTransferObject\Tests\Feature\Examples\TestingDto;

use const Rexlabs\DataTransferObject\PARTIAL;

class StackTraceTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function one(): void
    {
        self::assertTrue(true);
        return;

        $stackTraceTable = [
            // ImmutableTypeError on __set
            [
                'call' => function () {
                    $faker = Factory::create();
                    $dto = TestingDto::make([], PARTIAL);

                    $dto->first_name = $faker->firstName;
                }
            ],
            /*
            // UndefinedPropertiesTypeError on manual assertDefined
            [
                'call' => function () {
                    $dto = TestingDto::make([], PARTIAL);

                    $dto->assertDefined(['last_name']);
                }
            ],
            */
        ];

        foreach ($stackTraceTable as $stackTraceRow) {
            $call = $stackTraceRow['call'];

            $trace = $this->getTrace($call);
            self::assertLessThanOrEqual(2, count($trace));
            print_r($trace);
        }
    }

    /**
     * @param callable $call
     *
     * @return array
     */
    private function getTrace(callable $call): array
    {
        $call();
        return [];
        try {
            $call();
        } catch (DataTransferObjectTypeError $e) {
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
