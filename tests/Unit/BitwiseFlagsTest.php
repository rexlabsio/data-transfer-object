<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use Rexlabs\DataTransferObject\Tests\TestCase;

class BitwiseFlagsTest extends TestCase
{
    /**
     * @return array
     */
    private function getAllFlags(): array
    {
        $userConstants = get_defined_constants(true)['user'];

        return array_reduce(
            array_keys($userConstants),
            function (array $carry, $name) use ($userConstants): array {
                if (strpos($name, 'Rexlabs\DataTransferObject') !== false) {
                    $carry[] = $userConstants[$name];
                }

                return $carry;
            },
            []
        );
    }

    /**
     * Safeguards against accidentally breaking the bitwise flag values.
     * As long as each value is unique the flags will work.
     *
     * @test
     *
     * @return void
     */
    public function flags_are_incrementally_shifting(): void
    {
        $flags = $this->getAllFlags();

        $expected = [];

        // Check each flag is shifted up from the last
        // The first NONE flag is treated specially
        foreach ($flags as $i => $flag) {
            $expected[] = $i === 0
                ? 0
                : 1 << $i - 1;
        }

        self::assertEquals($expected, $flags);
    }

    /**
     * @test
     *
     * @return void
     */
    public function flags_are_integers(): void
    {
        foreach ($this->getAllFlags() as $flag) {
            self::assertIsInt($flag, 'Bitwise flags must be integers');
        }
    }
}
