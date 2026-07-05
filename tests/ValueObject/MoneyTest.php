<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testFromMajorUnitsConvertsToMinorUnits(): void
    {
        $money = Money::fromMajorUnits(199.90);

        self::assertSame(19990, $money->amount());
        self::assertSame('CZK', $money->currency());
    }

    public function testMultiply(): void
    {
        $money = new Money(1500, 'CZK');

        self::assertSame(4500, $money->multiply(3)->amount());
    }

    public function testAddSameCurrency(): void
    {
        $sum = (new Money(1000))->add(new Money(250));

        self::assertSame(1250, $sum->amount());
    }

    public function testAddDifferentCurrencyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Money(1000, 'CZK'))->add(new Money(1000, 'EUR'));
    }

    public function testNegativeAmountThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Money(-1);
    }

    public function testIsImmutable(): void
    {
        $money = new Money(1000);
        $money->multiply(2);

        self::assertSame(1000, $money->amount());
    }

    public function testStringRepresentation(): void
    {
        self::assertSame('1 234.50 CZK', (string) new Money(123450));
    }
}
