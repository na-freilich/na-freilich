import template from './sw-cms-el-config-nf-two-column-booking.html.twig';

const { Context, Mixin } = Shopware;

Shopware.Component.register('sw-cms-el-config-nf-two-column-booking', {
    template,

    inject: ['repositoryFactory', 'cmsService'],

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    computed: {
        locationRepository() {
            return this.repositoryFactory.create('nf_booking_location');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('nf-two-column-booking');
        },

        onLocationLeftChange(id) {
            this.element.config.locationIdLeft.value = id;
            this.$emit('element-update', this.element);
        },

        onLocationRightChange(id) {
            this.element.config.locationIdRight.value = id;
            this.$emit('element-update', this.element);
        }
    }
});