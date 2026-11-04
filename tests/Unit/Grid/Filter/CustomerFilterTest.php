<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Grid\Filter;

use Evolutive\SyliusWishlistPlugin\Grid\Filter\CustomerFilter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\ExpressionBuilderInterface;

final class CustomerFilterTest extends TestCase
{
    public function test_every_word_must_match_the_owner_name_or_email(): void
    {
        $expressionBuilder = $this->createMock(ExpressionBuilderInterface::class);
        $expressionBuilder->method('like')->willReturnCallback(static fn (string $field, string $pattern): string => sprintf('%s LIKE %s', $field, $pattern));
        $expressionBuilder->method('orX')->willReturnCallback(static fn (string ...$expressions): string => '(' . implode(' OR ', $expressions) . ')');
        $expressionBuilder->method('andX')->willReturnCallback(static fn (string ...$expressions): string => implode(' AND ', $expressions));

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getExpressionBuilder')->willReturn($expressionBuilder);
        $dataSource->expects(self::once())->method('restrict')->with(
            '(customer.firstName LIKE %John% OR customer.lastName LIKE %John% OR customer.email LIKE %John%)'
            . ' AND (customer.firstName LIKE %Doe% OR customer.lastName LIKE %Doe% OR customer.email LIKE %Doe%)',
        );

        (new CustomerFilter())->apply($dataSource, 'customer', ['value' => '  John   Doe '], []);
    }

    public function test_blank_search_does_not_restrict(): void
    {
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects(self::never())->method('restrict');

        $filter = new CustomerFilter();
        $filter->apply($dataSource, 'customer', ['value' => '   '], []);
        $filter->apply($dataSource, 'customer', ['type' => 'contains'], []);
        $filter->apply($dataSource, 'customer', '', []);
    }
}
