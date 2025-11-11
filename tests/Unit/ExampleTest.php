<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_basic_arithmetic(): void
    {
        $operandA = 2;
        $operandB = 3;

        $this->assertSame(5, $operandA + $operandB);
    }
}
