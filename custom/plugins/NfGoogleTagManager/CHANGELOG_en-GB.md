# 1.0.1
- Google Tag Manager loads only after the statistics cookie `nf-gtm-analytics`; the group is created when it is empty
- `analytics_storage` is set to granted after consent, advertising storage stays denied
- External consent loads the container immediately and requires the external tool to block the scripts
- Cart events: `add_to_cart` from the listing, `view_cart` without shipping, `remove_from_cart`, `select_item` and search
- `begin_checkout` once at checkout entry
- Item data with brand, category, variant, list, coupon and affiliation; four configurable property fields per shop
- Import file and instructions for the GA4 tags in the container, with no fixed container or property ids
