# GA4-Tags im GTM-Container

Das Plugin schreibt E-Commerce-Ereignisse in den `dataLayer` und lädt den Container, der in den Plugin-Einstellungen steht. Die Tags, die diese Ereignisse an GA4 senden, liegen in Google Tag Manager. Dieselbe Importdatei gilt für jeden Shop. Container-ID und GA4-Property trägt man pro Kunde ein, nicht in diesem Repository.

Datei: `src/Resources/gtm/ga4-container.json`

Der Platzhalter `G-XXXXXXXXXX` und `GTM-XXXXXXX` sind keine echten Konten. GTM ersetzt die Container-IDs beim Import. Die Measurement-ID muss danach von Hand gesetzt werden.

## Im Shop

1. Plugin aktivieren.
2. Unter **GTM Container-ID** die ID dieses Shops eintragen, zum Beispiel `GTM-AAAAAAA`.
3. Die benötigten Ereignisse in den Plugin-Einstellungen eingeschaltet lassen.
4. Consent Mode lässt `analytics_storage` auf `denied`, bis die Cookie-Gruppe Statistik akzeptiert wurde. Nur wenn ein externes Consent-Tool die Skripte blockiert, **Einwilligung wird extern verwaltet** einschalten.
5. Cache leeren und das Storefront-Theme neu bauen, damit das Plugin-JavaScript im Theme liegt.

## Import

1. In [Google Tag Manager](https://tagmanager.google.com/) den Container dieses Shops öffnen.
2. **Verwaltung → Container importieren**.
3. `ga4-container.json` wählen.
4. Den Arbeitsbereich dieses Containers wählen.
5. **Zusammenführen** und bei Konflikten **Umbenennen**. **Überschreiben** löscht vorhandene Tags.
6. Import bestätigen. Die neuen Einträge liegen im Ordner **Nf Google Tag Manager**.

## Nach dem Import

1. Variable **NF - GA4 Measurement ID** öffnen.
2. `G-XXXXXXXXXX` durch die Measurement-ID der GA4-Property dieses Shops ersetzen. Mit dem Platzhalter gehen keine Hits ein.
3. Gibt es in dem Container schon ein Google-Tag für dieselbe Property, das Tag **NF - Google Tag** pausieren. Sonst entsteht ein zweites `page_view`. Die Ereignis-Tags nutzen **Measurement ID override** und senden weiter auf die ID aus der Variable.
4. In der Vorschau eine Kategorie, eine Produktdetailseite, Warenkorb und Suche prüfen. Zu jedem Ereignis aus der Tabelle unten muss genau ein Tag **NF - GA4 - …** feuern. `ecommerce` steht im `dataLayer`, davor setzt das Plugin `ecommerce: null`.
5. Container veröffentlichen. Erst die veröffentlichte Version gilt für den Shop.

## Ereignisse

| dataLayer-Ereignis | Tag | Inhalt |
|---|---|---|
| `view_item_list` | NF - GA4 - view_item_list | Liste, `item_list_id`, `item_list_name`, `index` |
| `select_item` | NF - GA4 - select_item | Klick aus der Liste |
| `view_item` | NF - GA4 - view_item | Produktdetail |
| `add_to_cart` | NF - GA4 - add_to_cart | Detailseite und Listing |
| `remove_from_cart` | NF - GA4 - remove_from_cart | Offcanvas und Warenkorbseite |
| `view_cart` | NF - GA4 - view_cart | Warenwert ohne Versand |
| `begin_checkout` | NF - GA4 - begin_checkout | einmal beim Einstieg in den Checkout (Register setzt die Marke, Confirm bleibt still; ohne Marke feuert Confirm) |
| `add_shipping_info` | NF - GA4 - add_shipping_info | `shipping_tier` |
| `add_payment_info` | NF - GA4 - add_payment_info | `payment_type` |
| `purchase` | NF - GA4 - purchase | `transaction_id`, `value`, `tax`, `shipping` |
| `add_to_wishlist` | NF - GA4 - add_to_wishlist | Merkzettel |
| `search` | NF - GA4 - search | `search_term`, ohne Ecommerce-Objekt |

Die Ereignis-Tags lesen das Objekt `ecommerce` aus dem Data Layer. Zusätzliche Variablen **NF - ecommerce.*** liegen für die Vorschau im Container. `search` liest **NF - search_term**.

Alle Tags warten auf die Consent-Art `analytics_storage`. `ad_storage` bleibt im Plugin auf `denied`, bis eine Marketing-Gruppe angebunden wird.

## In der GA4-Property

`page_view` kommt vom Google-Tag (`send_page_view`) und, falls eingeschaltet, zusätzlich von Enhanced Measurement. Eine der beiden Quellen für `page_view` genügt.

Als Key Event mindestens `purchase` markieren. `add_to_cart` und `begin_checkout` nach Bedarf.

`item_category2` bis `item_category5` und eigene Feldnamen kommen nur aus den vier Plätzen unter **Artikeleigenschaften**. Dort wählt der Shop eine Eigenschaftsgruppe und den Feldnamen. Ohne diese Einstellung bleiben zusätzliche Eigenschaften leer. In GA4 sichtbar werden sie, wenn dazu Item-Scoped Custom Dimensions angelegt sind.

Die ausgefüllte Measurement-ID bleibt im GTM-Konto des Kunden. Sie wird nicht in dieses Plugin zurückgeschrieben.
