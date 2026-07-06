<?php

declare(strict_types=1);

namespace App\Tests\Search;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Search\ProductSearch;
use App\Search\ProductSearchGateway;
use App\ValueObject\Money;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

final class ProductSearchTest extends TestCase
{
    public function testItReturnsProductsInTheOrderGivenByTheGateway(): void
    {
        $gateway = $this->createMock(ProductSearchGateway::class);
        $gateway->expects(self::once())->method('matchingIds')->with('coffee')->willReturn([3, 1]);

        $expected = [$this->product('Third'), $this->product('First')];

        $repository = $this->createMock(ProductRepository::class);
        $repository->expects(self::once())
            ->method('findActiveByIds')
            ->with([3, 1])
            ->willReturn($expected);

        $search = new ProductSearch($gateway, $repository, new NullLogger());

        self::assertSame($expected, $search->search('coffee'));
    }

    public function testItReturnsNothingForABlankTermWithoutQueryingTheGateway(): void
    {
        $gateway = $this->createMock(ProductSearchGateway::class);
        $gateway->expects(self::never())->method('matchingIds');

        $repository = $this->createMock(ProductRepository::class);
        $repository->expects(self::never())->method('findActiveByIds');

        $search = new ProductSearch($gateway, $repository, new NullLogger());

        self::assertSame([], $search->search('   '));
    }

    public function testItFallsBackToTheDatabaseWhenTheGatewayFails(): void
    {
        $gateway = $this->createMock(ProductSearchGateway::class);
        $gateway->expects(self::once())->method('matchingIds')->willThrowException(new \RuntimeException('cluster down'));

        $fallback = [$this->product('From database')];

        $repository = $this->createMock(ProductRepository::class);
        $repository->expects(self::once())
            ->method('searchByName')
            ->with('espresso')
            ->willReturn($fallback);

        $search = new ProductSearch($gateway, $repository, new NullLogger());

        self::assertSame($fallback, $search->search('espresso'));
    }

    private function product(string $name): Product
    {
        return new Product($name, strtolower($name), new Money(10000), new Category('Coffee', 'coffee'));
    }
}
