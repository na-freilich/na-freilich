import Plugin from 'src/plugin-system/plugin.class';

export default class NfBookingTimerPlugin extends Plugin {
    static options = {
        expiresAt: '',
        displaySelector: '.js-timer-display',
        criticalTime: 120,
        expirationText: 'Expired'
    };

    init() {
        const expiresAtValue = this.el.dataset.expiresAt || this.options.expiresAt;
        this._expiresAt = new Date(expiresAtValue).getTime();
        this._display = this.el.querySelector(this.options.displaySelector);
        if (isNaN(this._expiresAt)) {
            console.warn('[BookingTimer] Invalid expiration date');
            return;
        }

        this._isExpired = false;
        this._startTimer();
    }

    _startTimer() {
        this._updateTimer();
        this._interval = setInterval(() => this._updateTimer(), 1000);
    }

    _updateTimer() {
        const now = new Date().getTime();
        const distance = this._expiresAt - now;

        if (distance <= 0) {
            this._handleExpiration();
            return;
        }

        const secondsLeft = Math.floor(distance / 1000);

        if (secondsLeft <= this.options.criticalTime) {
            this._setCriticalState();
        }

        this._renderTime(distance);
    }

    _renderTime(distance) {
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        if (this._display) {
            this._display.innerHTML =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    _setCriticalState() {
        if (this.el.classList.contains('alert-info')) {
            this.el.classList.remove('alert-info');
            this.el.classList.add('alert-danger');
        }
    }

    _handleExpiration() {
        if (this._isExpired) return;
        this._isExpired = true;

        if (this._interval) {
            clearInterval(this._interval);
            this._interval = null;
        }

        if (this._display) {
            this._display.innerHTML = this.options.expirationText;
        }

        const event = new CustomEvent('nf-booking-timer:expired', {
            bubbles: true,
            detail: { expired: true }
        });

        this.el.dispatchEvent(event);
    }

    destroy() {
        clearInterval(this._interval);
    }
}