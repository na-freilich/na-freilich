import template from './clear-cache-button.html.twig';
import './clear-cache-button.scss';

const { Component, Mixin } = Shopware;

Component.register('clear-cache-button', {
    template,
    inject: [
        'cacheApiService',
        // 'feature',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    methods: {
        onCacheClear()
        {
            console.log("onCacheClear");
        },

        clearDataCache() {
            this.createNotificationInfo({
                message: this.$tc('sw-settings-cache.notifications.clearDataCache.started'),
            });

            // this.processes.normalClearCache = true;
            this.cacheApiService
                .clear()
                .then(() => {
                    // this.processSuccess.normalClearCache = true;

                    this.createNotificationSuccess({
                        message: this.$tc('sw-settings-cache.notifications.clearCache.success'),
                    });
                })
                .catch(() => {
                    // this.processSuccess.normalClearCache = false;

                    this.createNotificationError({
                        message: this.$tc('sw-settings-cache.notifications.clearCache.error'),
                    });
                })
                .finally(() => {
                    // this.processes.normalClearCache = false;
                });
        },
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    }
});
