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

            systemConfigApiService.getValues(configDomain)
                .then(config => {
                    const root = document.documentElement;
                    const colors = {
                        '--sw-admin-menu-background-custom': config[configDomain + '.sidebarColor'],
                        '--sw-admin-menu-text-active-custom': config[configDomain + '.sidebarTextColor'],
                        '--sw-admin-menu-hover-background-custom': config[configDomain + '.sidebarHoverColor'],
                        '--sw-admin-menu-hover-text-custom': config[configDomain + '.sidebarHoverTextColor'],
                    };

                    Object.entries(colors).forEach(([name, value]) => {
                        if (value) {
                            root.style.setProperty(name, value);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading color settings:', error);
                });
        }
    };
});
