<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Core\Content\Cms\SalesChannel;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Checkout\Promotion\Util\PromotionCodeService;
use Shopware\Core\Framework\Context;

readonly class OrderPromotionResolver
{
    public function __construct(
        private EntityRepository $promotionRepository,
        private EntityRepository $individualCodeRepository,
        private PromotionCodeService $codeService,
        private EntityRepository $promotionOrderCodeRepository)
    {
    }

    public function getType(): string
    {
        return 'order-promotion';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?ElementDataCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $request = $resolverContext->getRequest();
        $context = $resolverContext->getSalesChannelContext()->getContext();

        $orderId = $request->get('orderId')
            ?? $request->attributes->get('orderId')
            ?? $request->query->get('orderId');

        $customerId = $resolverContext->getSalesChannelContext()->getCustomerId();

        if (!$orderId or !$customerId)
            return;

        $existingCodes = $this->getExistingCodes($orderId, $context);
        if ($existingCodes) {
            $friendCode = $existingCodes['friendCode'];
            $ownerCode  = $existingCodes['ownerCode'];
        }
        else{
            $friendPromo = $this->getPromotionByTechnicalTag('friend_promo', $context);
            $ownerPromo = $this->getPromotionByTechnicalTag('owner_promo', $context);

            if (!$friendPromo || !$ownerPromo) {
                return;
            }

            $friendCode =  $this->generateCode($friendPromo, $context);
            $ownerCode = $this->generateCode($ownerPromo, $context);

            $this->promotionOrderCodeRepository->create([
                [
                    'orderId' => $orderId,
                    'customerId' => $customerId,
                    'friendCode' => $friendCode,
                    'ownerCode' => $ownerCode,
                ]
            ], $context);
        }

        $slot->setData(new ArrayStruct([
            'referralCodeFriend' => $friendCode,
            'referralCodeOwner' => $ownerCode
        ]));
    }

    private function getExistingCodes(string $orderId, Context $context): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->setLimit(1);

        $result = $this->promotionOrderCodeRepository->search($criteria, $context)->first();

        if (!$result) {
            return null;
        }

        return [
            'friendCode' => $result->get('friendCode'),
            'ownerCode' => $result->get('ownerCode'),
        ];
    }
    private function generateCode(PromotionEntity $promotion, Context $context): string
    {

        $pattern = $promotion->getIndividualCodePattern();

        try {
            $codes = $this->codeService->generateIndividualCodes($pattern, 1);
            $newCode = $codes[0];

            $this->individualCodeRepository->create([
                [
                    'promotionId' => $promotion->getId(),
                    'code' => $newCode,
                ]
            ], $context);

            return $newCode;

        } catch (\Exception $e) {
            return 'GENERATION-FAILED';
        }
    }
    private function getPromotionByTechnicalTag(string $tag, Context $context): ?PromotionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.referral_type', $tag));
        $criteria->setLimit(1);

        return $this->promotionRepository
            ->search($criteria, $context)
            ->first();
    }

}