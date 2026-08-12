// index.js
import template from './sw-customer-detail.html.twig';

const { Component } = Shopware;

Component.override('sw-customer-detail', {
    template,

});