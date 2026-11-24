import template from './nf-gtm-setup-guide.html.twig';
import './nf-gtm-setup-guide.scss';

const TEXTS = {
    de: {
        bannerTitle: 'Ohne Tags im Google-Konto kommt in GA4 nur page_view an',
        bannerBody: 'Das Plugin schreibt die Ereignisse in den dataLayer. Google Tag Manager sendet sie erst an GA4, wenn die Tags importiert und die Measurement-ID gesetzt sind.',
        shopTitle: 'Im Shop',
        shop1: 'Plugin aktivieren und die GTM-Container-ID dieses Shops eintragen.',
        shop2: 'Die gewünschten Ereignisse eingeschaltet lassen. Consent Mode gibt nur analytics_storage nach der Statistik-Cookie-Gruppe frei.',
        shop3: 'Cache leeren und das Storefront-Theme neu kompilieren.',
        importTitle: 'Im Google Tag Manager',
        import1: 'tagmanager.google.com öffnen und den Container dieses Shops wählen.',
        import2: 'Verwaltung → Container importieren.',
        import3: 'Die Datei ga4-container.json über den Button unten herunterladen und auswählen. Nicht aus dem Plugin-Code kopieren.',
        import4: 'Einen Arbeitsbereich dieses Containers wählen.',
        import5: 'Zusammenführen wählen, bei Konflikten Umbenennen. Überschreiben löscht vorhandene Tags.',
        import6: 'Import bestätigen. Die neuen Einträge liegen im Ordner Nf Google Tag Manager.',
        download: 'ga4-container.json herunterladen',
        afterTitle: 'Nach dem Import',
        after1: 'Variable NF - GA4 Measurement ID öffnen.',
        after2: 'G-XXXXXXXXXX durch die Measurement-ID der GA4-Property ersetzen, zum Beispiel G-E0NMWNH0LF. Mit dem Platzhalter gehen keine Hits ein.',
        after3: 'Wenn für dieselbe Property schon ein Google-Tag existiert, das Tag NF - Google Tag pausieren. Sonst entsteht ein zweites page_view.',
        after4: 'In der Vorschau Kategorie, Produktdetail, Warenkorb und Suche prüfen. Zu jedem dataLayer-Ereignis muss genau ein Tag NF - GA4 - … feuern.',
        after5: 'Container veröffentlichen. Erst die veröffentlichte Version gilt für den Shop.',
        ga4Title: 'In der GA4-Property',
        ga41: 'purchase als Key Event markieren. add_to_cart und begin_checkout nach Bedarf.',
        ga42: 'Zusätzliche Item-Felder aus Artikeleigenschaften (zum Beispiel size) brauchen in GA4 eine Custom Dimension mit Item-Gültigkeitsbereich und demselben Namen.',
        ga43: 'item_category2 bis item_category5 sind Standardfelder. Dafür ist keine Custom Dimension nötig.',
    },
    en: {
        bannerTitle: 'Without tags in the Google account, GA4 only receives page_view',
        bannerBody: 'The plugin writes events to the dataLayer. Google Tag Manager forwards them to GA4 only after the tags are imported and the measurement id is set.',
        shopTitle: 'In the shop',
        shop1: 'Activate the plugin and enter this shop\'s GTM container id.',
        shop2: 'Leave the required events enabled. Consent mode grants analytics_storage only after the statistics cookie group is accepted.',
        shop3: 'Clear the cache and compile the storefront theme.',
        importTitle: 'In Google Tag Manager',
        import1: 'Open tagmanager.google.com and select this shop\'s container.',
        import2: 'Go to Admin → Import Container.',
        import3: 'Download ga4-container.json with the button below and choose that file. Do not copy it from the plugin source.',
        import4: 'Choose a workspace of this container.',
        import5: 'Select Merge, and Rename on conflict. Overwrite deletes existing tags.',
        import6: 'Confirm the import. New entries are in the folder Nf Google Tag Manager.',
        download: 'Download ga4-container.json',
        afterTitle: 'After the import',
        after1: 'Open the variable NF - GA4 Measurement ID.',
        after2: 'Replace G-XXXXXXXXXX with this shop\'s GA4 measurement id, for example G-E0NMWNH0LF. Hits are dropped while the placeholder is still in place.',
        after3: 'If a Google tag for the same property already exists, pause NF - Google Tag. Otherwise the shop sends a second page_view.',
        after4: 'In preview, check a category, a product page, the cart, and search. Each dataLayer event must fire exactly one NF - GA4 - … tag.',
        after5: 'Publish the container. Only the published version runs on the shop.',
        ga4Title: 'In the GA4 property',
        ga41: 'Mark purchase as a key event. Add add_to_cart and begin_checkout when those reports are needed.',
        ga42: 'Extra item fields from Item properties (for example size) need an item-scoped custom dimension in GA4 with the same name.',
        ga43: 'item_category2 through item_category5 are standard fields. They do not need a custom dimension.',
    },
};

Shopware.Component.register('nf-gtm-setup-guide', {
    template,

    props: {
        value: {
            required: false,
            default: null,
        },
    },

    computed: {
        texts() {
            return this.isGerman ? TEXTS.de : TEXTS.en;
        },

        isGerman() {
            const locale = this.currentLocale;

            return typeof locale === 'string' && locale.toLowerCase().startsWith('de');
        },

        currentLocale() {
            try {
                if (Shopware.Store && typeof Shopware.Store.get === 'function') {
                    return Shopware.Store.get('session')?.currentLocale
                        || Shopware.Store.get('session')?.languageId
                        || '';
                }
            } catch (e) {
                // Shopware 6.7 uses Store. Fall through for older session state.
            }

            try {
                return Shopware.State.get('session')?.currentLocale || '';
            } catch (e) {
                return 'de-DE';
            }
        },

        jsonUrl() {
            return '/bundles/nfgoogletagmanager/documentation/ga4-container.json';
        },
    },
});
