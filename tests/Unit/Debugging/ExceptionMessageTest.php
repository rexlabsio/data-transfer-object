<?php

namespace Rexlabs\DataTransferObject\Tests\Unit\Debugging;

use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Factory;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

use stdClass;

use const Rexlabs\DataTransferObject\NONE;
use const Rexlabs\DataTransferObject\PARTIAL;

class ExceptionMessageTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function invalid_type_exception_includes_readable_type_names(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'first_name' => ['string'],
                'last_name' => ['string'],
                'email' => ['string'],
                'phone' => ['null', 'string'],
                'parent' => ['null', TestDataTransferObject::class],
                'children' => [TestDataTransferObject::class . '[]'],
            ]),
            NONE
        ));

        $testTable = [
            [
                'label' => 'Null value for string type',
                'call' => function () {
                    TestDataTransferObject::make([
                        'first_name' => null,
                    ], PARTIAL);
                },
                'patterns' => [
                    '/\bfirst_name\b/',
                    '/\bnull\b.*\bstring\b/',
                ],
            ],
            [
                'label' => 'bool value for string|null type',
                'call' => function () {
                    TestDataTransferObject::make([
                        'phone' => true,
                    ], PARTIAL);
                },
                'patterns' => [
                    '/\bphone\b/',
                    '/\bboolean\b.*\bnull\|string\b/',
                ],
            ],
            [
                'label' => 'array value for string type',
                'call' => function () {
                    TestDataTransferObject::make([
                        'first_name' => [1, 2],
                    ], PARTIAL);
                },
                'patterns' => [
                    '/\bfirst_name\b/',
                    '/\barray\b.*\bstring\b/',
                ],
            ],
            [
                'label' => 'object value for string type',
                'call' => function () {
                    TestDataTransferObject::make([
                        'parent' => new Factory([]),
                    ], PARTIAL);
                },
                'patterns' => [
                    '/\bparent\b/',
                    '/\bRexlabs\\\\DataTransferObject\\\\Factory\b.*\bnull\|'
                    . 'Rexlabs\\\\DataTransferObject\\\\Tests\\\\Support\\\\'
                    . 'TestDataTransferObject\b/',
                ],
            ],
        ];

        foreach ($testTable as $testRow) {
            $label = $testRow['label'];
            $call = $testRow['call'];
            $patterns = $testRow['patterns'];

            try {
                $call();
                self::fail(sprintf('%s did not throw invalid type exception', $label));
                break;
            } catch (InvalidTypeError $e) {
                $message = $e->getMessage();
            }

            foreach ($patterns as $pattern) {
                self::assertRegExp($pattern, $message, $label);
            }
        }
    }

    /**
     * @test
     * @return void
     */
    public function multiple_invalid_types_reported_in_exception_message(): void
    {
        $this->factory->setClassMetadata(new DTOMetadata(
            TestDataTransferObject::class,
            $this->factory->makePropertyTypes([
                'first_name' => ['string'],
                'last_name' => ['string'],
                'email' => ['string'],
                'phone' => ['null', 'string'],
                'parent' => ['null', TestDataTransferObject::class],
                'children' => [TestDataTransferObject::class . '[]'],
            ]),
            NONE
        ));

        $parameters = [
            'first_name' => false,
            'last_name' => null,
            'email' => new Factory([]),
            'phone' => 1234,
            'parent' => new stdClass(),
            'children' => [
                123,
                '456'
            ],
        ];

        try {
            TestDataTransferObject::make($parameters);
            self::fail('Expected invalid type exception');
            return;
        } catch (InvalidTypeError $e) {
            $message = $e->getMessage();
        }

        foreach (array_keys($parameters) as $name) {
            self::assertRegExp(
                '/Property\h' . $name . '/',
                $message,
                'Expected invalid type for ' . $name
            );
        }
    }
}
