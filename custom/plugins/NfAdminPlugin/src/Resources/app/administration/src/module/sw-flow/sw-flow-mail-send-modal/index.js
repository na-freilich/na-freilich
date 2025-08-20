import template from './sw-flow-mail-send-modal.html.twig';

const { Criteria } = Shopware.Data;



Shopware.Component.override('sw-flow-mail-send-modal', {
    template,

    inject: ['systemConfigApiService'],

    data() {
        return {
            adminRoles: []
        };
    },

    created() {
        this.loadRoles();
    },

    computed:{
        recipientAdmin() {
            let recipient = this.$super('recipientAdmin');

            if (this.adminRoles)
                for (const item of this.adminRoles) {
                    recipient.push({
                        value: 'role_'.concat(item.id),
                        label: item.name,
                    });
                }

            return recipient;
        },
    },

    methods: {
        async loadRoles() {
            const response = await this.systemConfigApiService.getValues('AdminPlugin.config');
            const recipientRoleIds = response['AdminPlugin.config.recipientRoles'];

            const criteria = new Shopware.Data.Criteria(1, 100);
            criteria.addFilter(
                Criteria.multi(
                    'AND',
                    [
                        // Criteria.equals('app.id', null),
                        Criteria.equals('deletedAt', null),
                        Criteria.equalsAny('id', recipientRoleIds)
                    ])
            );

            this.adminRoles =await Shopware.Service('repositoryFactory')
                .create('acl_role')
                .search(criteria);
        }
    },
});
