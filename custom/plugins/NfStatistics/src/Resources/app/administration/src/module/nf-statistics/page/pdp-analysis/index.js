const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('pdp-analysis', {
    template,

    inject: [
        'repositoryFactory',
        'nfStatisticsPdpAnalysisService'
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
            sortBy: "layoutName",
            sortDirection: "ASC",
            sortByPrev: "layoutName",
            sortDirectionPrev: "asc"
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'layoutName',
                    dataIndex: 'layoutName',
                    label: this.$tc('nf-statistics.common.labels.layoutName'),
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
                {
                    property: 'orders',
                    dataIndex: 'orders',
                    label: this.$tc('nf-statistics.common.labels.orders'),
                    allowInlineEdit: false,
                    allowResize: true,
                    primary: true
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

            this.nfStatisticsPdpAnalysisService.getPdpAnalysisList(1, 25, this.languageId, this.dateFrom, this.dateTo, this.searchTermPrev, this.sortBy, this.sortDirection).then((result) => {
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
        },

        updateTotal({ total }) {
            this.total = total;
        },

        dateFormat(date) {
            return date.toLocaleDateString('de-DE');
        },

        dateClear() {
            this.dateFrom = "";
            this.dateTo = "";
        },
    }
});
