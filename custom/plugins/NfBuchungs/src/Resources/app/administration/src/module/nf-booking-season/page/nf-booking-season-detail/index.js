import template from './nf-booking-season-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('nf-booking-season-detail', {
    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
    ],

    data() {
        return {
            season: null,
            repository: null,
            isLoading: false,
            isSaveSuccessful: false,
            currentPriceRule: null,
            showSecondPicker: true,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        seasonRepository() {
            return this.repositoryFactory.create('nf_booking_season');
        },

        priceRuleRepository() {
            return this.repositoryFactory.create('nf_booking_price_rule');
        },

        isNew() {
            return this.season && this.season.isNew();
        },

        dayOptions() {
            return [
                { value: 1, label: this.$t('nf-booking.days.monday') },
                { value: 2, label: this.$t('nf-booking.days.tuesday') },
                { value: 3, label: this.$t('nf-booking.days.wednesday') },
                { value: 4, label: this.$t('nf-booking.days.thursday') },
                { value: 5, label: this.$t('nf-booking.days.friday') },
                { value: 6, label: this.$t('nf-booking.days.saturday') },
                { value: 7, label: this.$t('nf-booking.days.sunday') }
            ];
        },

        priceRuleColumns() {
            return [
                {
                    property: 'days',
                    label: this.$t('nf-booking.season.detail.columnDays'),
                    allowResize: true,
                },
                {
                    property: 'startTime',
                    label: this.$t('nf-booking.season.detail.columnStartTime'),
                    align: 'center',
                },
                {
                    property: 'endTime',
                    label: this.$t('nf-booking.season.detail.columnEndTime'),
                    align: 'center',
                },
                {
                    property: 'price',
                    label: this.$t('nf-booking.season.detail.columnPrice'),
                    align: 'right',
                },
                {
                    property: 'priceSubsequent',
                    label: this.$t('nf-booking.season.detail.columnPriceSubsequent'),
                    align: 'right',
                }

            ];
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            const seasonId = this.$route.params.id;

            if (seasonId) {
                this.loadEntityData(seasonId);
            } else {
                this.season = this.seasonRepository.create(Shopware.Context.api);
            }
        },

        onDateInput() {
            ['startDate', 'endDate'].forEach(field => {
                let val = String(this.season[field] || '').replace(/\D/g, '');

                if (val.length >= 2) {
                    const month = parseInt(val.substring(0, 2));
                    if (month > 12) val = '12' + val.substring(2);
                    if (month === 0 && val.length === 2) val = '01';
                }
                this.season[field] = val.slice(0, 4);
            });
        },

        loadEntityData(id) {
            this.isLoading = true;

            const criteria = new Shopware.Data.Criteria();
            criteria.addAssociation('priceRules');

            this.seasonRepository.get(id, Shopware.Context.api, criteria).then((entity) => {
                this.season = entity;
                this.isLoading = false;
            });
        },

        onChangeLanguage() {
            this.createdComponent();
        },

        onSave() {
            this.isLoading = true;

            this.seasonRepository.save(this.season, Shopware.Context.api).then(() => {
                this.loadEntityData(this.season.id);
                this.isSaveSuccessful = true;
                this.isLoading = false;
            }).catch(() => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$t('nf-booking.edit.titleSaveError'),
                    message: this.$t('nf-booking.edit.messageSaveError'),
                });
            });
        },

        onCancel() {
            this.$router.push({ name: 'nf.booking.season.list' });
        },

        onAddPriceRule() {
            this.currentPriceRule = this.priceRuleRepository.create(Shopware.Context.api);
            this.currentPriceRule.days = [];
            this.currentPriceRule.seasonId = this.season.id;
            this.currentPriceRule.days = [];
            this.currentPriceRule.startTime = '00:00';
            this.currentPriceRule.endTime = '23:59';
            this.currentPriceRule.price = 0.00;
            this.sourceRule = null;
        },

        onConfirmPriceRule() {
            if (!this.currentPriceRule) return;

            if (this.sourceRule) {
                Object.assign(this.sourceRule, this.currentPriceRule);
            } else {
                this.season.priceRules.add(this.currentPriceRule);
            }

            this.showSecondPicker = false;
            this.$nextTick(() => {
                setTimeout(() => {
                    this.currentPriceRule = null;
                    this.sourceRule = null;
                    this.showSecondPicker = true;
                }, 20);
            });
        },

        onCancelPriceRule() {
            this.showSecondPicker = false;

            this.$nextTick(() => {
                setTimeout(() => {
                    this.currentPriceRule = null;
                    this.sourceRule = null;
                    this.$nextTick(() => {
                        this.showSecondPicker = true;
                    });
                }, 50);
            });
        },

        onDeletePriceRule(item) {
            this.season.priceRules.remove(item.id);
        },

        getDayName(dayValue) {
            const option = this.dayOptions.find(o => o.value === dayValue);
            return option ? option.label : dayValue;
        },

        onEditPriceRule(item) {
            const selectedRule = item.item ? item.item : item;
            // this.showSecondPicker = false;
            this.currentPriceRule = Shopware.Utils.object.cloneDeep(selectedRule);
            this.sourceRule = selectedRule;
        },

        renderPrice(value) {
            if (!value && value !== 0) return '-';
            const currencyFilter = Shopware.Filter.getByName('currency');
            return currencyFilter(value, Shopware.Context.app.systemCurrencyISOCode);
        },
    }
});