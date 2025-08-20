const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('product-views', {
    template,

    inject: [
        'repositoryFactory',
        'nfStatisticsProductViewsService'
    ],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification')
    ],


    metaInfo() {
        return {
            title: this.$createTitle(this.$tc('nf-statistics.general.title'))
        };
    },

    data() {
        return {
            languageId: null,
            repository: null,
            itemsCollection: null,
            isLoading: true,
            total: 0,
            searchTerm: "",
            searchTermPrev: "",
            dateFrom: "",
            dateTo: "",
            sortBy: "productNumber",
            sortDirection: "ASC",
            sortByPrev: "productNumber",
            sortDirectionPrev: "asc"
        };
    },

    computed: {
        nfStatProductViewRepository() {
            return this.repositoryFactory.create('nf_stat_product_views');
        },

        columns() {
            return [
                {
                    property: 'productNumber',
                    dataIndex: 'productNumber',
                    label: this.$tc('nf-statistics.common.labels.productNumber'),
                    allowInlineEdit: false,
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc('nf-statistics.common.labels.name'),
                    allowInlineEdit: false,
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'views',
                    dataIndex: 'views',
                    label: this.$tc('nf-statistics.common.labels.views'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                    width: '100px'
                },
            ];
        },
    },

    created() {
    },

    methods: {
        onChangeSort(column){
            if (column.dataIndex == this.sortByPrev)
            {
                if (this.sortDirectionPrev == "ASC")
                {
                    this.sortDirection = "DESC";
                    this.sortDirectionPrev = "DESC";
                }
                else
                {
                    this.sortDirection = "ASC";
                    this.sortDirectionPrev = "ASC";
                }
            }
            else
            {
                this.sortBy = column.dataIndex;
                this.sortByPrev = column.dataIndex;
                this.sortDirection = "ASC";
                this.sortDirectionPrev = "ASC";
            }
            this.getList();
        },

        onChangeLanguage(languageId) {
            this.languageId = languageId;
            this.getList();
        },

        onSearch(event) {
            if (this.searchTerm.length >= 3)
            {
                this.searchTermPrev = this.searchTerm;
                this.getList();
            }
            else if (this.searchTermPrev.length >= 3)
            {
                this.searchTermPrev = "";
                this.getList();
            }
        },

        dateIntervalSearch(event) {
            this.getList();
        },

        getList() {
            this.isLoading = true;

            this.nfStatisticsProductViewsService.getProductViewsList(1, 25, this.languageId, this.dateFrom, this.dateTo, this.searchTermPrev, this.sortBy, this.sortDirection).then((result) => {
                if ('success' === result.status) {
                    this.total = result.total;
                    this.itemsCollection = result.response;

                    this.isLoading = false;
                } else {
                    this.createNotificationError({
                        title: this.$tc('nf-statistics.common.labels.exception'),
                        message: result.message
                    });
                }

                this.isLoading = false;

            }).catch((exception) => {
                this.isLoading = false;

                this.createNotificationError({
                    title: this.$tc('nf-statistics.common.labels.exception'),
                    message: exception
                });
            });
/*
            this.nfStatProductViewRepository.search(this.itemsCriteria, Shopware.Context.api).then(items => {
                this.total = items.total;
                this.itemsCollection = items;
                for (var i in this.itemsCollection)
                {
                    if (typeof this.itemsCollection[i].parentProductId !== "undefined")
                    {
                        if (this.itemsCollection[i].parentProductId !== null)
                        {
                            this.itemsCollection[i].product.name = this.itemsCollection[i].parentProduct.name;
                        }
                    }
                }
                this.isLoading = false;
            });
*/
        },

        dateFormat(date) {
            return date.toLocaleDateString('de-DE');
        },

        dateClear() {
            this.dateFrom = "";
            this.dateTo = "";
        },

        updateTotal({ total }) {
            this.total = total;
        },
    }
});
