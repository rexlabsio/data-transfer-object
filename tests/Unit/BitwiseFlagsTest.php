<?php

namespace Rexlabs\DataTransferObject\Tests\Unit;

use PHPUnit\Framework\TestCase;

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
    public function flags_are_unique(): void
    {
        $flags = $this->getAllFlags();

        // Compare each flag to every other flag
        foreach ($flags as $i => $flag) {
            foreach ($flags as $j => $otherFlag) {
                // No need to compare to self
                if ($j === $i) {
                    continue;
                }

                self::assertNotEquals($flag, $otherFlag, 'Bitwise flags must be unique');
            }
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function flags_are_integers(): void
    {
        foreach ($this->getAllFlags() as $flag) {
            self::assertIsInt($flag, 'Bitwise flags must be ints');
        }
    }
}
