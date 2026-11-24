# 1.0.2
- `select_item` and `remove_from_cart` use standard Shopware nodes, without theme classes
- Listing `item_id` is the variant the product page opens; the parent number is `item_group_id`
- GA4 setup guide and `ga4-container.json` import file in the plugin settings, without opening the source

# 1.0.1
- Google Tag Manager loads only after the statistics cookie `nf-gtm-analytics`; the group is created when it is empty
- `analytics_storage` is set to granted after consent, advertising storage stays denied
- External consent loads the container immediately and requires the external tool to block the scripts
- Cart events: `add_to_cart` from the listing, `view_cart` without shipping, `remove_from_cart`, `select_item` and search
- `begin_checkout` once at checkout entry
- Item data with brand, category, variant, list, coupon and affiliation; four configurable property fields per shop
- Import file and instructions for the GA4 tags in the container, with no fixed container or property ids
