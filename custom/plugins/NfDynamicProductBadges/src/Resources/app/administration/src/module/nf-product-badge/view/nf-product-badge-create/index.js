const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';

Component.register('nf-product-badge-create', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
    ],

    mixins: [Mixin.getByName('notification')],

    data() {
        return {
            loading: {
                badgeSaving: false,
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

        badgeRepository() {
            return this.repositoryFactory.create('nf_custom_badge');
        },

        badgeCriteria() {
            const criteria = new Criteria();

            return criteria;
        },
    },

    methods: {
        createdComponent() {
            if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                Shopware.State.commit('context/resetLanguageToDefault');
            }

            this.entity = this.badgeRepository.create(Shopware.Context.api);
            this.entity.active = true;
            this.entity.name = "";
        },

        onSave() {
            this.loading.badgeSaving = true;
            this.badgeRepository
                .save(this.entity, Shopware.Context.api)
                .then((result) => {
                    this.loading.badgeSaving = false;
                    this.$router.push({name: 'nf.product.badge.detail', params: {id: this.entity.id}});
                })
                .catch(exception => {
                    this.loading.badgeSaving = false;

                    this.createNotificationError({
                        title: this.$tc('nf-product-badge.common.labels.exception'),
                        message: exception
                    });
                });
        }
    }
});