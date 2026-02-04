const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('order-day-detail', {
    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            orderDay: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.$tc('nf-order-day.common.actions.editDay'))
        };
    },

    computed: {
        orderDayRepository() {
            return this.repositoryFactory.create('order_day');
        },

        defaultCriteria() {
            const criteria = new Criteria();

            return criteria;
        },

    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = false;
            this.orderDayId = this.$route.params.id;
            this.orderDayRepository.get(
                this.orderDayId,
                Shopware.Context.api,
                this.defaultCriteria,
            ).then((orderDay) => {
                this.orderDay = orderDay;
                this.isLoading = false;
            });
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.createdComponent();
            this.isLoading = false;
        },

        async onSave() {
            this.isLoading = true;

            this.isSaveSuccessful = false;

            return this.orderDayRepository.save(this.orderDay).then(() => {
                this.isSaveSuccessful = true;
                this.createNotificationSuccess({
                    message: this.$tc('nf-order-day.common.labels.savedSuccess'),
                });
                this.isLoading = false;
            }).catch((exception) => {
                this.createNotificationError({
                    message: this.$tc('nf-order-day.common.labels.exception'),
                });
                this.isLoading = false;
                throw exception;
            });
        },

        abortOnLanguageChange() {
            return this.orderDayRepository.hasChanges(this.orderDay);
        },

        saveOnLanguageChange() {
            return this.onSave();
        },

        onChangeLanguage(languageId) {
            Shopware.State.commit('context/setApiLanguageId', languageId);
            this.createdComponent();
        },
    },
});
