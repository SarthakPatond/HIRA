# TODO (Lead + WhatsApp integration)

- [ ] Add DB columns for backward-compatible lead metadata (source, city, business_details)
- [ ] Update backend submitContact.php to save Contact source + new fields (nulls)
- [ ] Update backend submitLead.php to save Distributor source + city + business_details mapping (without breaking old columns)
- [ ] Update admin/leads.php to display Source (and optionally city/business details columns safely)
- [ ] Update ContactPage.jsx: after successful save, open WhatsApp in new tab with prefills (Name, Phone, Message)
- [ ] Update DistributorPage.jsx: add City input + rename textarea label to Business Details; after save, open WhatsApp with prefills (Name, Phone, City, Business Details)
- [ ] Smoke test: submit both forms, verify Admin Leads shows new rows, then WhatsApp opens only after success

