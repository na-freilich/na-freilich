import template from './sw-cms-el-order-upselling-timer.html.twig';
import './sw-cms-el-order-upselling-timer.scss';

Shopware.Component.register('sw-cms-el-order-upselling-timer', {
    template,

    mixins: [
        'cms-element'
    ],

    computed: {
        displayTime() {
            const duration = this.element.config.durationMinutes.value || 30;
            return `${duration}:00`;
        },

        finalTimerText() {
            const text = this.element.config.timerText.value || '';
            const time = this.displayTime;

            return text.replace('%time%', time);
        },

        title() {
            return this.element.config.title.value;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('order-upselling-timer');
        }
    }
});