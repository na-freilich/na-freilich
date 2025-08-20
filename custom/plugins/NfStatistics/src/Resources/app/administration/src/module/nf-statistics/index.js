const { Module } = Shopware;
const mainColor = '#31a3dd';

import './service/product-views.api.service';
import './service/pdp-analysis.api.service';
import './service/visitors-online.api.service';
import './service/visitors.api.service';
import './page/product-views';
import './page/pdp-analysis';
import './page/visitors';
import './page/conversion';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('nf-statistics', {
    type: 'plugin',
    name: 'nf-statistics.general.name',
    title: 'nf-statistics.general.title',
    description: 'nf-statistics.general.description',
    color: mainColor,
    icon: 'regular-file-edit',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    navigation: [
        { // level 1
            id: 'nf-statistics-main',
            label: 'nf-statistics.general.title',
            color: mainColor,
            icon: 'regular-file-edit',
            position: 40,
            path: 'nf.statistics.productViews',
            parent: 'sw-marketing'
        },
        { // level 2
            id: 'nf-statistics-product-views',
            label: 'nf-statistics.product-views.title',
            color: mainColor,
            icon: 'regular-file-edit',
            position: 10,
            path: 'nf.statistics.productViews',
            parent: 'nf-statistics-main'
        },
        { // level 2
            id: 'nf-statistics-pdp-analysis',
            label: 'nf-statistics.pdp-analysis.title',
            color: mainColor,
            icon: 'regular-file-edit',
            position: 10,
            path: 'nf.statistics.pdpAnalysis',
            parent: 'nf-statistics-main'
        },
        { // level 2
            id: 'nf-statistics-visitors',
            label: 'nf-statistics.visitors.title',
            color: mainColor,
            icon: 'regular-file-edit',
            position: 10,
            path: 'nf.statistics.visitors',
            parent: 'nf-statistics-main'
        },
        { // level 2
            id: 'nf-statistics-conversion',
            label: 'nf-statistics.conversion.title',
            color: mainColor,
            icon: 'regular-file-edit',
            position: 10,
            path: 'nf.statistics.conversion',
            parent: 'nf-statistics-main'
        },
    ],

    routes: {
        productViews: {
            component: 'product-views',
            path: 'product-views'
        },
        pdpAnalysis: {
            component: 'pdp-analysis',
            path: 'pdp-analysis'
        },
        visitors: {
            component: 'nf-visitors-stat',
            path: 'visitors'
        },
        conversion: {
            component: 'nf-conversion',
            path: 'conversion'
        },
    }
});