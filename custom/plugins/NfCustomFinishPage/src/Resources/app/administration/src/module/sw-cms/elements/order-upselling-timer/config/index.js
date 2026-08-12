import template from './sw-cms-el-config-order-upselling-timer.html.twig';

const { EntityCollection } = Shopware.Data;

Shopware.Component.register('sw-cms-el-config-order-upselling-timer', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            groupCollection: null,
            uniqueGroupOptions: [],
            isLoading: false
        };
    },

    computed: {
        crossSellingRepository() {
            return this.repositoryFactory.create('product_cross_selling');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('upselling-timer');

            this.loadUniqueGroupNames();
        },


        loadUniqueGroupNames() {
            this.isLoading = true;
            const repository = this.repositoryFactory.create('product_cross_selling');
            const criteria = new Shopware.Data.Criteria();

            criteria.addSorting(Shopware.Data.Criteria.sort('name', 'ASC'));
            criteria.setLimit(500);

            repository.search(criteria, Shopware.Context.api).then((result) => {
                const allNames = result.map(item => item.name).filter(Boolean);

                const uniqueNames = [...new Set(allNames)];

                this.uniqueGroupOptions = uniqueNames.map(name => ({
                    value: name,
                    label: name
                }));

                this.isLoading = false;
            });
        },

        onSelectionChange(value) {
            this.element.config.crossSellingGroupNames.value = value;
            this.$emit('element-update', this.element);
        }
    },

    mixins: [
        Shopware.Mixin.getByName('cms-element')
    ]
});