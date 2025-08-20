const { Application } = Shopware;

Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('orderCreatedByAdminRole', {
        component: 'sw-condition-generic',
        label: 'nf-admin.condition.orderCreatedByAdminRoleRule',
        scopes: ['order'],
        group: 'order',
    });

    return ruleConditionService;
});
