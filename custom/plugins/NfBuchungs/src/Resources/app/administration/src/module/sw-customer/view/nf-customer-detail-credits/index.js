import template from './nf-customer-detail-credits.html.twig';

const { Component, Filter, Mixin } = Shopware;

Component.register('nf-customer-detail-credits', {
    template,

    props: {
        customer: {
            type: Object,
            required: true
        }
    },

    inject: ['repositoryFactory', 'feature'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            credits: null,
            isLoading: true,
            showModal: false,
            newCredit: null
        };
    },

    computed: {
        creditRepository() {
            return this.repositoryFactory.create('nf_booking_credit');
        },

        columns() {
            return [
                {
                    property: 'createdAt',
                    label: this.$t('nf-booking.credit.columnCreateAt'),
                    align: 'center'
                },
                {
                    property: 'totalSlots',
                    label: this.$t('nf-booking.credit.columnTotalSlots'),
                    align: 'center'
                },
                {
                    property: 'usedSlots',
                    label: this.$t('nf-booking.credit.columnUsedSlots'),
                    align: 'center'
                },
                {
                    property: 'active',
                    label: this.$t('nf-booking.credit.columnActive'),
                    align: 'center'
                },
                {
                    property: 'comment',
                    label: this.$t('nf-booking.credit.columnComment'),
                    allowResize: true,
                },
            ];
        },

        dateFilter() {
            return Filter.getByName('date');
        }
    },

    created() {
        this.loadCredits();
    },

    methods: {
        async onDeleteCredit(item) {
            const res = await this.creditRepository.delete(item.id, Shopware.Context.api);

            try {
                await this.loadCredits();

                this.createNotificationSuccess({
                        message: this.$tc('nf-booking.credit.delSuccess')
                });
            } catch (error) {
                this.createNotificationError({
                    message: this.$tc('nf-booking.credit.delError') + error.message
                });
            }
        },

        loadCredits() {
            this.isLoading = true;
            const criteria = new Shopware.Data.Criteria();
            criteria.addFilter(Shopware.Data.Criteria.equals('customerId', this.customer.id));

            this.creditRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.credits = result;
                this.isLoading = false;
            });
        },

        onAddCredit() {
            this.newCredit = {
                totalSlots: 1.0,
                active: true,
                customerId: this.customer.id
            };

            this.showModal = true;
        },

        onEditCredit(item) {
            this.newCredit = { ...item };
            this.showModal = true;
        },

        async saveCredit() {
            this.isLoading = true;
            let entity;
            try {
                if (this.newCredit.id) {
                    entity = await this.creditRepository.get(this.newCredit.id, Shopware.Context.api);
                } else {
                    entity = this.creditRepository.create(Shopware.Context.api);
                }

                Object.assign(entity, this.newCredit);

                await this.creditRepository.save(entity,  Shopware.Context.api);

                this.showModal = false;
                this.loadCredits();
                this.createNotificationSuccess({ message: this.$tc('nf-booking.credit.saveSuccess') });
            } catch (error) {
                console.log("error", error);
                this.createNotificationError({
                    message: this.$tc('nf-booking.credit.saveError'),
                });
            } finally {
                this.isLoading = false;
            }
        }
    }
});