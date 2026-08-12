const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './template.html.twig';

Component.register('nf-product-badge-detail', {
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
            badge: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.$tc('nf-product-badge.common.actions.edit'))
        };
    },

    computed: {
        badgeRepository() {
            return this.repositoryFactory.create('nf_custom_badge');
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
            this.badgeId = this.$route.params.id;
            this.badgeRepository.get(
                this.badgeId,
                Shopware.Context.api,
                this.defaultCriteria,
            ).then((badge) => {
                this.badge = badge;
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

            return this.badgeRepository.save(this.badge).then(() => {
                this.isSaveSuccessful = true;
                this.createNotificationSuccess({
                    message: this.$tc('nf-product-badge.common.labels.savedSuccess'),
                });
                this.isLoading = false;
            }).catch((exception) => {
                this.createNotificationError({
                    message: this.$tc('nf-product-badge.common.labels.exception'),
                });
                this.isLoading = false;
                throw exception;
            });
        },

    },
});