const { Module } = Shopware;

import './page/nf-product-badge-list';
import './view/nf-product-badge-create';
import './view/nf-product-badge-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('nf-product-badge', {
    type: 'core',
    name: 'nf-product-badge.general.mainMenuItemGeneral',
    title: 'nf-product-badge.general.mainMenuItemGeneral',
    description: 'nf-product-badge.general.description',
    icon: 'regular-file-edit',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'nf-product-badge-list',
            path: 'list'
        },
        create: {
            component: 'nf-product-badge-create',
            path: 'create'
        },
        detail: {
            component: 'nf-product-badge-detail',
            path: 'detail/:id'
        }
    },

    navigation: [{
        label: 'nf-product-badge.general.mainMenuItemGeneral',
        path: 'nf.product.badge.list',
        parent: 'sw-catalogue',
        position: 100
    }]
});