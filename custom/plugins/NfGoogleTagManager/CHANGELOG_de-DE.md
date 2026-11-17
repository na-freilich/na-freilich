# 1.0.1
- Google Tag Manager lädt erst nach dem Statistik-Cookie `nf-gtm-analytics`; die Gruppe wird angelegt, wenn sie leer ist
- `analytics_storage` wird nach der Einwilligung auf granted gesetzt, Werbe-Speicher bleibt denied
- Externes Consent lädt den Container sofort und setzt voraus, dass das externe Tool die Skripte blockiert
- Warenkorbreignisse: `add_to_cart` aus dem Listing, `view_cart` ohne Versandkosten, `remove_from_cart`, `select_item` und Suche
- `begin_checkout` einmal beim Einstieg in den Checkout
- Artikeldaten mit Marke, Kategorie, Variante, Liste, Gutschein und Zugehörigkeit; vier einstellbare Eigenschaftsfelder pro Shop
- Importvorlage und Anleitung für die GA4-Tags im Container, ohne feste Container- oder Property-IDs
