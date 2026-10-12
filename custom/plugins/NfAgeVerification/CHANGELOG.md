# 1.0.2
- Altersprüfung läuft als Overlay im Storefront. Der Seiteninhalt bleibt im HTML und ist für Crawler lesbar
- Kein serverseitiges Ausblenden der Produkte und kein Redirect mehr
- Cookie `nf_is_adult` wird nicht mehr in den HTTP-Cache-Key geschrieben und beim ersten Request nicht gesetzt
- Nach „Nein, ich bin jünger“ werden betroffene Produkte nur visuell verborgen, nicht aus dem DOM entfernt
- Kauf altersbeschränkter Produkte wird im Warenkorb und Checkout blockiert
