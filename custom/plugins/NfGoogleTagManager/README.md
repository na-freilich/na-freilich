# Nf Google Tag Manager

The plugin fills the `dataLayer` and loads the shop's GTM container. GA4 event tags are not created in the shop. Import them once into each customer's container.

Merchants do not need the plugin source. The setup steps and the import file are in **Administration → Extensions → Nf Google Tag Manager Plugin** (plugin settings). The same files are also here for developers:

| | |
|---|---|
| Deutsch | [docs/gtm.de.md](docs/gtm.de.md) |
| English | [docs/gtm.en.md](docs/gtm.en.md) |
| Container import | [src/Resources/gtm/ga4-container.json](src/Resources/gtm/ga4-container.json) |

The import file has no customer container id and no real GA4 property id. Before publishing, replace the placeholder `G-XXXXXXXXXX` in the variable **NF - GA4 Measurement ID**.
