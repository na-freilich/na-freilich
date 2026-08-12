import template from './nf-booking-discount-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('nf-booking-discount-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            repository: null,
            item: null,
            isLoading: false,
            isSaveSuccessful: false
        };
    },

    created() {
        this.repository = this.repositoryFactory.create('nf_booking_series_discount');
        this.getDiscount();
    },

    methods: {
        getDiscount() {
            // Если в URL нет ID, значит мы создаем новую запись
            if (!this.$route.params.id) {
                this.item = this.repository.create(Shopware.Context.api);
                return;
            }

            this.isLoading = true;
            this.repository.get(this.$route.params.id, Shopware.Context.api).then((entity) => {
                this.item = entity;
                this.isLoading = false;
            });
        },

        onClickSave() {
            this.isLoading = true;

            this.repository.save(this.item, Shopware.Context.api).then(() => {
                this.getDiscount();
                this.isLoading = false;
                this.isSaveSuccessful = true;
                this.createNotificationSuccess({
                    title: 'Erfolg',
                    message: 'Der Rabatt wurde gespeichert.'
                });
            }).catch(() => {
                this.isLoading = false;
                this.createNotificationError({
                    title: 'Fehler',
                    message: 'Der Rabatt konnte nicht gespeichert werden.'
                });
            });
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        }
    }
});