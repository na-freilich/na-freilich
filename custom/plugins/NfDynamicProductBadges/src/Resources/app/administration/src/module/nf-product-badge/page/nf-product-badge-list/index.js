const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('nf-product-badge-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification')
    ],

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
        badgeRepository() {
            return this.repositoryFactory.create('nf_custom_badge');
        },

        itemsCriteria() {
            const criteria = new Criteria();
            const params = this.getMainListingParams();

            params.sortBy = params.sortBy || 'name';
            params.sortDirection = params.sortDirection || 'ASC';

            criteria.addSorting(Criteria.sort(params.sortBy, params.sortDirection));

            if (typeof this.searchTerm === 'string' && this.searchTerm.length >= 2) {
                params.term = this.searchTerm;

                criteria.addQuery(Criteria.multi('or', [
                    Criteria.contains('name', params.term),
                ]));
            }

            return criteria;
        },

        columns() {
            return [
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc('nf-product-badge.common.labels.name'),
                    inlineEdit: 'string',
                    allowInlineEdit: false,
                    allowResize: true,
                    primary: true
                }, {
                    property: 'text',
                    dataIndex: 'text',
                    label: this.$tc('nf-product-badge.common.labels.text'),
                    inlineEdit: 'string',
                    allowResize: true,
                    align: 'center',
                    width: '100px'
                }
            ];
        }
    },

    methods: {
        onSearch(event) {
            this.searchTerm = event;

            this.getList();
        },

        getList() {
            this.isLoading = true;

            this.badgeRepository.search(this.itemsCriteria, Shopware.Context.api).then(items => {
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