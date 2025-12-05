document.addEventListener('DOMContentLoaded', () => {
    if (location.hash.startsWith('#/login')) {

        const observer = new MutationObserver(() => {
            const headline = document.querySelector('.sw-login__headline');
            const subHeadline = document.querySelector('.sw-login__sub-headline');
            const contentHeadline = document.querySelector('.sw-login__content-headline');
            const loginImage = document.querySelector('.sw-login__image');
            const customLoginBackgroundImg = window.nfAdminPluginConfig.loginBackgroundImg;

            if (headline && window.nfAdminPluginConfig.loginHeadline) {
                observer.disconnect();
                headline.textContent = window.nfAdminPluginConfig.loginHeadline;
            }

            if (subHeadline && window.nfAdminPluginConfig.loginSubHeadline) {
                observer.disconnect();
                subHeadline.textContent = window.nfAdminPluginConfig.loginSubHeadline;
            }

            if (contentHeadline && window.nfAdminPluginConfig.loginContentHeadline) {
                observer.disconnect();
                contentHeadline.textContent = window.nfAdminPluginConfig.loginContentHeadline;
            }

            if (loginImage && customLoginBackgroundImg) {
                loginImage.style.backgroundImage = `url("${customLoginBackgroundImg}")`;
                loginImage.style.backgroundSize = 'cover';
                loginImage.style.backgroundPosition = 'center';
                console.log("Login background replaced");
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }
});
