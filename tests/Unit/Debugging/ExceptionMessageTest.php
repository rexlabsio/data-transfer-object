<?php

namespace Rexlabs\DataTransferObject\Tests\Unit\Debugging;

use Rexlabs\DataTransferObject\DTOMetadata;
use Rexlabs\DataTransferObject\Exceptions\InvalidTypeError;
use Rexlabs\DataTransferObject\Tests\Support\TestDataTransferObject;
use Rexlabs\DataTransferObject\Tests\TestCase;

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
                    '/\bfirst_name\b/', // Property name
                    '/\bNULL\b.*\bstring\b/', // null then string type names
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
                    '/\bphone\b/', // Property name
                    '/\bboolean\b.*\bnull\|string\b/', // null then string type names
                ],
            ],
            // array type
            // non dto object type
            // dto object type
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
}
