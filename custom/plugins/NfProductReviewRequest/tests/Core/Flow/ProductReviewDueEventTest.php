<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Tests\Core\Flow;

use Nf\ProductReviewRequest\Core\Flow\ProductReviewDueEvent;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Event\OrderAware;

class ProductReviewDueEventTest extends TestCase
{
    public function testExposesProductReviewLinksToTheMailPreview(): void
    {
        $data = ProductReviewDueEvent::getAvailableData()->toArray();

        self::assertSame(OrderDefinition::class, $data[OrderAware::ORDER]['entityClass']);
        self::assertSame([
            'type' => 'array',
            'of' => [
                'type' => 'object',
                'data' => [
                    'label' => ['type' => 'string'],
                    'url' => ['type' => 'string'],
                ],
            ],
        ], $data[ProductReviewDueEvent::PRODUCT_REVIEW_LINKS]);
    }
}
