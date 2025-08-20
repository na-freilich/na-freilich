<?php declare(strict_types=1);

namespace Nf\NachlassFilter\Service;

use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\RangeAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FilterService
{
    const  maxPrc = 99999999999;
    const  productDiscountField = 'product.price.percentage.gross';

    public function __construct(
        private readonly SystemConfigService $systemConfigService
    )
    {
    }

    public function getFilter($filterValue): Filter
    {
        $filtered = (bool) $filterValue;

        if ($filtered)
            $curFilter = $this->getCurFilter($filterValue);
        else
            $curFilter = new RangeFilter('product.price.percentage.gross', [RangeFilter::GTE => 0]);

        $range = $this->getRange();
        return new Filter(
            'nachlass',
            $filtered,
            [new RangeAggregation('nachlass', 'product.price.percentage.gross', $range)],
            $curFilter,
            [
                'min' => null,
                'max' => null,
            ]
        );
    }

    private function getCurFilter($value){
        $ranges = explode('|', $value);
        if (count($ranges) == 1) {
            [$curRange[RangeFilter::GTE], $curRange[RangeFilter::LTE]] = $this->decodeDiscount($ranges[0]);
            return new RangeFilter('product.price.percentage.gross', $curRange);
        }
        else
        {
            $filter = new OrFilter();
            foreach ($ranges as $range) {
                [$curRange[RangeFilter::GTE], $curRange[RangeFilter::LTE]] = $this->decodeDiscount($range);
                $filter->addQuery(new RangeFilter(self::productDiscountField, $curRange));
            }
            return $filter;
        }
    }

    private function decodeDiscount($value):array
    {
        if (!$value)
            return [0, self::maxPrc];

        if(substr($value, 0, 3) == 'gte')
        {
            $from = 0;
            $to = (int) substr($value, 3);
        }

        elseif(str_contains($value, '-'))
        {
            $arr = explode('-', $value);
            $from = $arr[0];
            $to = $arr[1];
        }

        elseif(substr($value, 0, 3) == 'lte')
        {
            $from = substr($value, 3);
            $to = self::maxPrc;
        }
        else
            return [0, self::maxPrc];

        return [$from, $to];
    }

    private function normalizeRange (string $range): string
    {
        $range = trim($range);
        $range = str_replace('<', 'gte', $range);
        $range = str_replace('>', 'lte', $range);

        return $range;
    }

    private function getRange(): array
    {
        $config = $this->systemConfigService->get('NachlassFilter.config');
        $rangeString = $config['discountRange'];
        $ranges = [];
        if (!$rangeString)
            return $ranges;

        foreach(explode(';', $rangeString) as $range)
        {
            $range = $this->normalizeRange($range);

            [$from, $to] = $this->decodeDiscount($range);

            $ranges[] = [
                'from' => $from,
                'to' => $to,
                'key' => $range
            ];
        }
        return $ranges;
    }
}
