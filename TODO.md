## HIRA Recipes CMS Fix + Declutter + Save Flow Recovery

- [ ] Fix save flow in `admin/cms.php` (verify recipe row insert/update, then ingredients insert, then steps insert; skip empty arrays safely; improve error messages with stage context)
- [ ] Declutter Recipes listing UI in `admin/cms.php`
  - [ ] SECTION 1: compact KPI cards (Total/Published/Featured/Draft)
  - [ ] SECTION 2: single-row toolbar (Search, Category, Status, Featured, Add Recipe button)
  - [ ] SECTION 3: convert table rows into cleaner card/list rows with minimal CSS
  - [ ] Move Delete/Duplicate/Feature toggle into a single overflow (⋮) menu; keep Edit + Preview visible
- [ ] Fix “Add Recipe” experience inside CMS
  - [ ] Make prominent Add Recipe button top-right
  - [ ] Clear edit state (ensure listing + blank form when creating new)
  - [ ] Ensure accordion sections + dynamic Ingredients/Steps work as-is
  - [ ] Ensure image preview works
- [ ] QA end-to-end
  - [ ] Create new recipe with 2 ingredients + 2 steps + tips and save
  - [ ] Verify DB state: `recipes`, `recipe_ingredients`, `recipe_steps`
  - [ ] Verify frontend shows recipe immediately
  - [ ] Verify publish/draft + featured toggle
  - [ ] Verify errors are meaningful (no silent failures)
