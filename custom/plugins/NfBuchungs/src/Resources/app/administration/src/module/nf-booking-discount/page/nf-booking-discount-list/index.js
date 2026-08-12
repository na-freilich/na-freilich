import template from './nf-booking-discount-list.html.twig';

const { Component, Mixin } = Shopware;

Shopware.Component.register('nf-booking-discount-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            repository: null,
            items: null,
            isLoading: true
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'minCount',
                    label: this.$t('nf-booking.discount.list.columnMinCount'),
                    routerLink: 'nf.booking.discount.detail',
                    inlineEdit: 'number',
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'discountPercentage',
                    label: this.$t('nf-booking.discount.list.columnDiscountPercentage'),
                    inlineEdit: 'number',
                    allowResize: true
                },
                {
                    property: 'active',
                    label: this.$t('nf-booking.discount.list.columnActive'),
                    inlineEdit: 'boolean',
                    allowResize: true
                }
            ];
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('nf_booking_series_discount');
        this.getList();
    },

    methods: {
        onDelete(item) {
            console.log("onDelete", item);

            if (!item || !item.id) return;

            this.repository.delete(item.id, Shopware.Context.api).then(() => {
                this.getList();
                this.createNotificationSuccess({
                    title: this.$t('global.default.success'),
                    message: this.$t('nf-booking.discount.list.deleteSuccess')
                });
            }).catch(() => {
                this.createNotificationError({
                    title: this.$t('global.default.error'),
                    message: 'Löschen fehlgeschlagen.'
                });
            });
        },

        getList() {
            this.isLoading = true;

            if (!this.repository) {
                return;
            }

            const criteria = new Shopware.Data.Criteria();
            criteria.addSorting(Shopware.Data.Criteria.sort('minCount', 'ASC'));

            this.repository.search(criteria, Shopware.Context.api).then((result) => {
                this.items = result;
                this.isLoading = false;
            }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    title: 'Fehler',
                    message: 'Daten konnten nicht geladen werden.'
                });
            });
        }
    }
});