<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
class UpsellPriceService
{
    private array $cache = [];

    public function __construct(
        private readonly EntityRepository $nfTimerRepository
    ) {}

    public function getActiveDiscount(string $productId, SalesChannelContext $context): ?ArrayStruct
    {
        $customer = $context->getCustomer();
        if (!$customer) {
            return null;
        }

        $cacheKey = $productId . '_' . $customer->getId();
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('products.id', $productId));
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new RangeFilter('endedAt', [
            RangeFilter::GT => (new \DateTime())->format(\Shopware\Core\Defaults::STORAGE_DATE_TIME_FORMAT)
        ]));

        /** @var ArrayEntity|null $timer */
        $timer = $this->nfTimerRepository->search($criteria, $context->getContext())->first();

        $result = null;
        if ($timer) {
            $result = new ArrayStruct([
                'amount' => (float)$timer->get('discountAmount'),
                'endedAt' => $timer->get('endedAt')
            ]);
        }

        return $this->cache[$cacheKey] = $result;
    }
}