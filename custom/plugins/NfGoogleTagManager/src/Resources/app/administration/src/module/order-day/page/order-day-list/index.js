const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('order-day-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification')
    ],


    metaInfo() {
        return {
            title: this.$createTitle(this.$tc('nf-order-day.list.title'))
        };
    },

    data() {
        return {
            repository: null,
            itemsCollection: null,
            isLoading: true,
            total: 0,
            searchTerm: null,
        };
    },

    computed: {
        orderDayRepository() {
            return this.repositoryFactory.create('order_day');
        },

        columns() {
            return [
                {
                    property: 'date',
                    dataIndex: 'date',
                    label: this.$tc('nf-order-day.common.labels.date'),
                    inlineEdit: 'string',
                    allowInlineEdit: false,
                    allowResize: true,
                    primary: true
                }, {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc('nf-order-day.common.labels.active'),
                    inlineEdit: 'boolean',
                    allowResize: true,
                    align: 'center',
                    width: '100px'
                }
            ];
        },

        itemsCriteria() {
            const criteria = new Criteria();
            const params = this.getMainListingParams();

            params.sortBy = params.sortBy || 'date';
            params.sortDirection = params.sortDirection || 'ASC';

            criteria.addSorting(Criteria.sort(params.sortBy, params.sortDirection));

            return criteria;
        },
    },

    created() {
    },

    methods: {
        onChangeLanguage(languageId) {
            this.getList(languageId);
        },

        onSearch(event) {
            this.searchTerm = event;

            this.getList();
        },

        getList() {
            this.isLoading = true;

            this.orderDayRepository.search(this.itemsCriteria, Shopware.Context.api).then(items => {
                this.total = items.total;
                this.itemsCollection = items;
                this.isLoading = false;
            });
        },

        updateTotal({ total }) {
            this.total = total;
        },
    }
});
