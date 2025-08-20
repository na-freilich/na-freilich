import nfStatisticsVisitorsService from "../../service/visitors.api.service";

const { Component, Mixin,Filter } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('nf-conversion', {
    template,

    inject: [
        'repositoryFactory',
        'nfStatisticsVisitorsService'
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
            limit: 25,
            page: 1,
            searchTerm: "",
            searchTermPrev: "",
            getFromDateFieldId: "nf-stat-date-from-search",
            getToDateFieldId: "nf-stat-date-to-search",
            dateFrom: "",
            dateTo: "",
            dateConfig: {
                altInput: true,
                altFormat: "d.m.Y",
                dateFormat: "Y-m-d",
            },
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
                    property: 'date',
                    dataIndex: 'date',
                    label: this.$tc('nf-statistics.common.labels.date'),
                    allowInlineEdit: false,
                    allowResize: true,
                    primary: true
                },

                {
                    property: 'all_visits',
                    dataIndex: 'all_visits',
                    label: this.$tc('nf-statistics.common.labels.visitors'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'page_views',
                    dataIndex: 'page_views',
                    label: this.$tc('nf-statistics.common.labels.page_views'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'order_cnt',
                    dataIndex: 'order_cnt',
                    label: this.$tc('nf-statistics.common.labels.order_cnt'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'order_sum',
                    dataIndex: 'order_sum',
                    label: this.$tc('nf-statistics.common.labels.order_sum'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'order_conversion',
                    dataIndex: 'order_conversion',
                    label: this.$tc('nf-statistics.common.labels.order_conversion'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'conversion_rate',
                    dataIndex: 'conversion_rate',
                    label: this.$tc('nf-statistics.common.labels.conversion_rate'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
            ];
        },

        dateFilter() {
            return Filter.getByName('date');
        },
    },

    created() {
    },

    methods: {
        onChangeLanguage(languageId) {
            this.languageId = languageId;
            this.getList();
        },

        async onPageChange(page) {
            this.page = page.page;
            this.limit = page.limit;
            await this.getList();
        },

        dateIntervalSearch(event) {
            this.dateFrom = document.getElementById("nf-stat-date-from-search").value;
            this.dateTo = document.getElementById("nf-stat-date-to-search").value;
            this.getList();
        },

        getList() {
            this.isLoading = true;

            this.nfStatisticsVisitorsService.getConversionList(this.page, this.limit, this.languageId, this.dateFrom, this.dateTo, this.searchTermPrev, this.sortBy, this.sortDirection).then((result) => {
                if ('success' === result.status) {
                    console.log("result", result);
                    this.total = result.total;
                    // this.total =25;
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

        onRefresh(){
            this.getList();
        },

        updateTotal({ total }) {
            this.total = total;
        },

        formatDate(dateTime) {
            return this.dateFilter(dateTime, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: undefined,
                minute: undefined,
                second: undefined
            });
        },

        formatNumber(num){
            return Math.round(num * 100) / 100;
        }
    }
});
