import Plugin from 'src/plugin-system/plugin.class';

export default class OrderPromotionPlugin extends Plugin {
    static options = {
        copyButtonSelector: '.btn-copy',
        codeSelector: '.referral-code-value',
        successClass: 'is-copied',
        timeout: 2000
    };

    init() {
        this._registerEvents();
    }

    _registerEvents() {
        this.el.querySelectorAll(this.options.copyButtonSelector).forEach(btn => {
            btn.addEventListener('click', this._onCopy.bind(this));
        });
    }

    _onCopy(event) {
        const btn = event.currentTarget;
        const targetId = btn.dataset.target;
        const codeElement = document.getElementById(targetId);

        if (!codeElement) return;

        const textToCopy = codeElement.innerText.trim();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy)
                .then(() => this._showSuccess(btn))
                .catch(() => this._fallbackCopy(textToCopy, btn));
        } else {
            this._fallbackCopy(textToCopy, btn);
        }
    }

    _showSuccess(btn) {
        const originalContent = btn.innerHTML;
        const successText = btn.dataset.successText || 'Copied!';

        btn.classList.add(this.options.successClass);
        btn.innerText = successText;

        setTimeout(() => {
            btn.classList.remove(this.options.successClass);
            btn.innerHTML = originalContent;
        }, this.options.timeout);
    }

    _fallbackCopy(text, btn) {
        const textArea = document.createElement("textarea");
        textArea.value = text;

        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        document.body.appendChild(textArea);

        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                this._showSuccess(btn);
            }
        } catch (err) {
            console.error('Fallback: Error', err);
        }

        document.body.removeChild(textArea);
    }
}