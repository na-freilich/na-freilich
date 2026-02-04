const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';
import './styles.scss';

Component.register('order-day-create', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
    ],

    mixins: [Mixin.getByName('notification')],

    data() {
        return {
            loading: {
                categorySaving: false,
            },

            entity: null,
            entityId: null,
        };
    },

    created() {
        this.createdComponent();
    },

    watch: {
        '$route.params.id'() {
            this.createdComponent();
        }
    },

    computed: {
        isLoading() {
            return Object.values(this.loading).some((loadState) => loadState);
        },

        cosmoCategoryRepository() {
            return this.repositoryFactory.create('order_day');
        },

        categoryCriteria() {
            const criteria = new Criteria();

            return criteria;
        },
    },

    methods: {
        createdComponent() {
            if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                Shopware.State.commit('context/resetLanguageToDefault');
            }

            this.entity = this.cosmoCategoryRepository.create(Shopware.Context.api);
            this.entity.active = true;
            this.entity.name = "";
        },

        onSave() {
            this.loading.categorySaving = true;
console.log('onSave', this.entity);
            this.cosmoCategoryRepository
                .save(this.entity, Shopware.Context.api)
                .then((result) => {
                    this.loading.categorySaving = false;
                    this.$router.push({name: 'order.day.detail', params: {id: this.entity.id}});
                })
                .catch(exception => {
                    this.loading.categorySaving = false;

                    this.createNotificationError({
                        title: this.$tc('nf-order-day.common.labels.exception'),
                        message: exception
                    });
                });
        },
    },
});
