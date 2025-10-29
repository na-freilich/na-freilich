import './sw-admin-menu.scss';

Shopware.Component.override('sw-admin-menu', {
    mounted() {
        this.$nextTick(() => {
            Shopware.Service('dynamicThemeSetter').applyAdminMenuStyles();
        });
    }
});

Shopware.Application.addServiceProvider('dynamicThemeSetter', () => {
    return {
        applyAdminMenuStyles: function() {
            const systemConfigApiService = Shopware.Service('systemConfigApiService');
            const configDomain = 'AdminPlugin.config';
            const bgColorKey = configDomain + '.sidebarColor';
            const textColorKey = configDomain + '.sidebarTextColor';
            systemConfigApiService.getValues(configDomain)
                .then(config => {
                    const customBgColor = config[bgColorKey];
                    const customTextColor = config[textColorKey];
                    const adminMenus = document.querySelectorAll('.sw-admin-menu');
                    adminMenus.forEach(menu => {
                        if (customBgColor) {
                            menu.style.setProperty('--sw-admin-menu-background-custom', customBgColor);
                         }
                        if (customTextColor) {
                            menu.style.setProperty('--sw-admin-menu-text-active-custom', customTextColor);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading color settings:', error);
                });
        }
    };
});
