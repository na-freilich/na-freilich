import template from './nf-booking-location-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('nf-booking-location-detail', {
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
            location: null,
            repository: null,
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        locationRepository() {
            return this.repositoryFactory.create('nf_booking_location');
        },

        isNew() {
            return this.location && this.location.isNew();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            const locationId = this.$route.params.id;

            if (locationId) {
                this.loadEntityData(locationId);
            } else {
                this.location = this.locationRepository.create(Shopware.Context.api);
            }
        },

        loadEntityData(id) {
            this.isLoading = true;

            const criteria = new Shopware.Data.Criteria();

            this.locationRepository.get(id, Shopware.Context.api, criteria).then((entity) => {
                this.location = entity;
                this.isLoading = false;
            });
        },

        onChangeLanguage() {
            this.createdComponent();
        },

        onSave() {
            this.isLoading = true;

            this.locationRepository.save(this.location, Shopware.Context.api).then(() => {
                this.loadEntityData(this.location.id);
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
            this.$router.push({ name: 'nf.booking.location.list' });
        }
    }
});