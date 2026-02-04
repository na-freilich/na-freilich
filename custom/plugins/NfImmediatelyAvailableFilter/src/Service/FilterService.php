<?php declare(strict_types=1);

namespace Nf\ImmediatelyAvailableFilter\Service;

use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FilterService
{

    public function __construct(
        private readonly SystemConfigService $systemConfigService
    )
    {
    }

    public function getFilter($filterValue): Filter
    {
        $filtered = (bool) $filterValue;

        $config = $this->systemConfigService->get('ImmediatelyAvailableFilter.config');
        $deliveryTimeIds = $config['ImmediatelyDeliveryTime'];

        return new Filter(
            'immediately-available',
            $filtered === true,
            [
                new FilterAggregation(
                    'immediately-available-filter',
                    new MaxAggregation('immediately-available', 'product.deliveryTimeId'),
                    [new EqualsAnyFilter('product.deliveryTimeId', $deliveryTimeIds)]
                ),
            ],
            new EqualsAnyFilter('product.deliveryTimeId', $deliveryTimeIds),
            $filtered
        );
    }


}
