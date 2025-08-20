import nfStatisticsVisitorsService from "../../service/visitors.api.service";

const { Component, Mixin,Filter } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('nf-visitors-stat', {
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
                    property: 'desktop_visits',
                    dataIndex: 'desktop_visits',
                    label: this.$tc('nf-statistics.common.labels.desktop_visits'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'mobile_visits',
                    dataIndex: 'mobile_visits',
                    label: this.$tc('nf-statistics.common.labels.mobile_visits'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'tablet_visits',
                    dataIndex: 'tablet_visits',
                    label: this.$tc('nf-statistics.common.labels.tablet_visits'),
                    allowResize: true,
                    allowInlineEdit: false,
                    align: 'center',
                },
                {
                    property: 'all_visits',
                    dataIndex: 'all_visits',
                    label: this.$tc('nf-statistics.common.labels.all_visits'),
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

            this.nfStatisticsVisitorsService.getVisitorsList(this.page, this.limit, this.languageId, this.dateFrom, this.dateTo, this.searchTermPrev, this.sortBy, this.sortDirection).then((result) => {
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
    }
});
