<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Core\Content\Flow\Rule;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Rule\FlowRule;
use Shopware\Core\Framework\Rule\RuleConfig;
use Shopware\Core\Framework\Rule\RuleConstraints;
use Shopware\Core\Framework\Rule\RuleScope;
use Shopware\Core\Content\Flow\Rule\FlowRuleScope;

#[Package('services-settings')]
class OrderCreatedByAdminRoleRule extends FlowRule
{
    final public const RULE_NAME = 'orderCreatedByAdminRole';

    /**
     * @internal
     */
    public function __construct(protected bool $shouldOrderBeCreatedByAdmin = true)
    {
//        dd('orderCreatedByAdminRole');
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {

        if (!$scope instanceof FlowRuleScope) {
            return false;
        }

//        dd($scope->getOrder()->getCreatedById());
        return $this->shouldOrderBeCreatedByAdmin === (bool) $scope->getOrder()->getCreatedById();
    }

    public function getConstraints(): array
    {
        return [
            'shouldOrderBeCreatedByAdminRole' => RuleConstraints::bool(true),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())->booleanField('shouldOrderBeCreatedByAdminRole');
    }
}
