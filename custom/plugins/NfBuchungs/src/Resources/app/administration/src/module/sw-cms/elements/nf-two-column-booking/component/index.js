import template from './sw-cms-el-nf-two-column-booking.html.twig';
import './sw-cms-el-nf-two-column-booking.scss';

const { Mixin, Filter } = Shopware;

Shopware.Component.register('sw-cms-el-nf-two-column-booking', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            locationLeft: null,
            locationRight: null
        };
    },

    computed:
    {
        locationRepository() {
            return this.repositoryFactory.create('nf_booking_location');
        },

        configLeftId() {
            return this.element?.config?.locationIdLeft?.value;
        },

        configRightId() {
            return this.element?.config?.locationIdRight?.value;
        }
    },

    watch: {
        configLeftId: {
            handler(newId) {
                if (newId) {
                    this.locationRepository.get(newId, Shopware.Context.api).then(entity => {
                        this.locationLeft = entity;
                    });
                } else {
                    this.locationLeft = null;
                }
            },
            immediate: true
        },
        configRightId: {
            handler(newId) {
                if (newId) {
                    this.locationRepository.get(newId, Shopware.Context.api).then(entity => {
                        this.locationRight = entity;
                    });
                } else {
                    this.locationRight = null;
                }
            },
            immediate: true
        }
    }
});