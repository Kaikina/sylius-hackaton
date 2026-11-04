<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Grid\Filter;

use Sylius\Bundle\GridBundle\Form\Type\Filter\StringFilterType;
use Sylius\Component\Grid\Attribute\AsFilter;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filtering\FilterInterface;

/**
 * Matches wishlists whose owner's first name, last name or email contains every searched word,
 * so "John Doe" finds John Doe while guest wishlists never match.
 */
#[AsFilter(
    formType: StringFilterType::class,
    type: 'evolutive_wishlist_customer',
    template: '@SyliusAdmin/shared/grid/filter/string.html.twig',
)]
final class CustomerFilter implements FilterInterface
{
    private const FIELDS = ['customer.firstName', 'customer.lastName', 'customer.email'];

    public function apply(DataSourceInterface $dataSource, string $name, $data, array $options): void
    {
        $value = is_array($data) ? ($data['value'] ?? null) : $data;
        if (!is_string($value)) {
            return;
        }

        $words = preg_split('/\s+/', trim($value), -1, \PREG_SPLIT_NO_EMPTY);
        if (false === $words || [] === $words) {
            return;
        }

        $expressionBuilder = $dataSource->getExpressionBuilder();
        $wordExpressions = [];
        foreach ($words as $word) {
            $pattern = '%' . addcslashes($word, '%_') . '%';
            $wordExpressions[] = $expressionBuilder->orX(
                ...array_map(static fn (string $field): mixed => $expressionBuilder->like($field, $pattern), self::FIELDS),
            );
        }

        $dataSource->restrict($expressionBuilder->andX(...$wordExpressions));
    }
}
