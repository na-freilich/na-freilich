import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'order-upselling-timer',
    label: 'sw-cms.elements.order-upselling-timer.label',
    component: 'sw-cms-el-order-upselling-timer',
    configComponent: 'sw-cms-el-config-order-upselling-timer',
    previewComponent: 'sw-cms-el-preview-order-upselling-timer',
    defaultConfig: {
        title: { source: 'static', value: 'Passend zu Ihrer Buchung — exklusiv für Sie, heute:' },
        timerText: { source: 'static', value: 'Nur noch <span class="timer-time-highlight">%time%</span> — danach weg!' },
        durationMinutes: { source: 'static', value: 30 },
        discountAmount: {
            source: 'static',
            value: 10.0
        },
        crossSellingGroupNames: {
            source: 'static',
            value: []
        }
    }
});