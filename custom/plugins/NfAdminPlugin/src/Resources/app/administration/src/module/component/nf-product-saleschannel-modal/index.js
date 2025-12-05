import template from './nf-product-saleschannel-modal.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Component.register('nf-product-saleschannel-modal', {
    template,

    inject: [
        'repositoryFactory',
    ],

    emits: ['modal-close'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        product: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            salesChannelDomains: [],
        };
    },

    computed: {
        modalTitle() {
            return this.$tc('sw-product.detail.productLinkModalTitle', {});
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        currentUser() {
            return Shopware.Store.get('session').currentUser;
        },

        salesChannelDomainCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('salesChannel');
            criteria.addFilter(Criteria.equals('salesChannel.typeId', Shopware.Defaults.storefrontSalesChannelTypeId));
            criteria.addFilter(Criteria.equals('salesChannel.active', true));
            criteria.addSorting(Criteria.sort('salesChannel.name', 'ASC'));
            criteria.addSorting(Criteria.sort('languageId', 'DESC'));

            return criteria;
        },

        hasSalesChannelDomains() {
            return this.salesChannelDomains !== null && this.salesChannelDomains.length > 0;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.fetchSalesChannelDomains();
        },

        onSalesChannelDomainMenuItemClick(salesChannelId, salesChannelDomainUrl) {
            this.redirectToSalesChannelProductUrl(salesChannelDomainUrl, this.product.id);
        },

        onCancel() {
            this.$emit('modal-close');
        },

        fetchSalesChannelDomains() {
            this.salesChannelDomainRepository
                .search(this.salesChannelDomainCriteria, Shopware.Context.api)
                .then((loadedDomains) => {
                    this.salesChannelDomains = loadedDomains;
                });
        },

        redirectToSalesChannelProductUrl(salesChannelDomainUrl, productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${salesChannelDomainUrl}/detail/${productId}`;
            form.target = '_blank';
            document.body.appendChild(form);

            form.submit();
            form.remove();
        }
    },
});
