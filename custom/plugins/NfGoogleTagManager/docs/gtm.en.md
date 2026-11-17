# GA4 tags in the GTM container

The plugin writes ecommerce events to the `dataLayer` and loads the container configured in the plugin settings. The tags that forward those events to GA4 live in Google Tag Manager. The same import file is used for every shop. The container id and the GA4 property are entered per customer, not in this repository.

File: `src/Resources/gtm/ga4-container.json`

`G-XXXXXXXXXX` and `GTM-XXXXXXX` are not real accounts. GTM remaps the container ids on import. The measurement id has to be set afterwards.

## In the shop

1. Activate the plugin.
2. Set **GTM Container ID** to this shop's container, for example `GTM-AAAAAAA`.
3. Leave the required events enabled in the plugin settings.
4. Consent mode keeps `analytics_storage` on `denied` until the statistics cookie group is accepted. Enable **Consent is managed externally** only when an external tool already blocks the scripts.
5. Clear the cache and rebuild the storefront theme so the plugin JavaScript is part of the theme.

## Import

1. Open this shop's container in [Google Tag Manager](https://tagmanager.google.com/).
2. Go to **Admin → Import Container**.
3. Choose `ga4-container.json`.
4. Choose a workspace of this container.
5. Select **Merge**, and **Rename** on conflict. **Overwrite** deletes existing tags.
6. Confirm the import. New entries are in the folder **Nf Google Tag Manager**.

## After the import

1. Open the variable **NF - GA4 Measurement ID**.
2. Replace `G-XXXXXXXXXX` with this shop's GA4 measurement id. Hits are dropped while the placeholder is still in place.
3. If this container already has a Google tag for the same property, pause **NF - Google Tag**. Otherwise the shop sends a second `page_view`. The event tags use **Measurement ID override** and keep sending to the id in the variable.
4. In preview, check a category, a product detail page, the cart, and search. Each event in the table below must fire exactly one **NF - GA4 - …** tag. `ecommerce` is on the `dataLayer`. The plugin pushes `ecommerce: null` before each event.
5. Publish the container. Only the published version runs on the shop.

## Events

| dataLayer event | Tag | Payload |
|---|---|---|
| `view_item_list` | NF - GA4 - view_item_list | Listing, `item_list_id`, `item_list_name`, `index` |
| `select_item` | NF - GA4 - select_item | Click from a list |
| `view_item` | NF - GA4 - view_item | Product detail |
| `add_to_cart` | NF - GA4 - add_to_cart | Detail page and listing |
| `remove_from_cart` | NF - GA4 - remove_from_cart | Offcanvas and cart page |
| `view_cart` | NF - GA4 - view_cart | Goods value, shipping excluded |
| `begin_checkout` | NF - GA4 - begin_checkout | once at checkout entry (register sets the mark, confirm stays silent; confirm fires when no mark is set) |
| `add_shipping_info` | NF - GA4 - add_shipping_info | `shipping_tier` |
| `add_payment_info` | NF - GA4 - add_payment_info | `payment_type` |
| `purchase` | NF - GA4 - purchase | `transaction_id`, `value`, `tax`, `shipping` |
| `add_to_wishlist` | NF - GA4 - add_to_wishlist | Wishlist |
| `search` | NF - GA4 - search | `search_term`, no ecommerce object |

Event tags read the `ecommerce` object from the data layer. The extra **NF - ecommerce.*** variables are there for preview. `search` reads **NF - search_term**.

Every tag waits for the consent type `analytics_storage`. The plugin leaves `ad_storage` on `denied` until a marketing cookie group is connected.

## In the GA4 property

`page_view` is sent by the Google tag (`send_page_view`) and, when enabled, again by Enhanced Measurement. One source for `page_view` is enough.

Mark at least `purchase` as a key event. Add `add_to_cart` and `begin_checkout` when those reports are needed.

`item_category2` through `item_category5` and any other field names come only from the four slots under **Item properties**. The shop picks a property group and the field name. Extra properties stay empty until a slot is filled. They show up in GA4 after matching item-scoped custom dimensions exist.

The filled-in measurement id stays in the customer's GTM account. It is not written back into this plugin.
