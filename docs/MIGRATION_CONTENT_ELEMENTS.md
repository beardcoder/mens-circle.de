# Content Element Migration Guide

This document describes the migration path for existing content elements after the TYPO3 v14 modernization.

## Overview

All custom content elements have been modernized to use standard TYPO3 fields instead of custom fields. This provides better compatibility, maintainability, and follows TYPO3 best practices.

## Field Mapping

### Global Field Changes

| Old Field | New Field | Notes |
|-----------|-----------|-------|
| `tx_sitepackage_eyebrow` | `tx_sitepackage_subheader` | Renamed for clarity |
| `tx_sitepackage_title` | `header` | Standard TYPO3 field |
| `tx_sitepackage_text` | `bodytext` | Standard TYPO3 field |
| `tx_sitepackage_background_image` | `assets` | Standard TYPO3 field |
| `tx_sitepackage_photo` | `assets` | Standard TYPO3 field |

### Content Element Specific Changes

#### Hero (mc_hero)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `tx_sitepackage_text` → `bodytext` (description)
- `tx_sitepackage_background_image` → `assets`

#### Text Section (mc_text_section)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `bodytext` → `bodytext` (unchanged, but now with rich text)

#### Call to Action (mc_cta)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `tx_sitepackage_text` → `bodytext`

#### Intro Section (mc_intro)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `tx_sitepackage_text` → `bodytext` (intro text)
- `tx_sitepackage_quote` → `header_link` (quote)

#### FAQ (mc_faq)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `tx_sitepackage_text` → `bodytext` (intro)

#### Journey Steps (mc_journey)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `tx_sitepackage_subtitle` → `bodytext`

#### Value Items (mc_values)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`

#### Moderator (mc_moderator)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_name` → `header` (moderator name)
- `bodytext` → `bodytext` (biography, now with rich text)
- `tx_sitepackage_quote` → `header_link` (quote)
- `tx_sitepackage_photo` → `assets`

#### Newsletter (mc_newsletter)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `tx_sitepackage_text` → `bodytext`

#### WhatsApp Community (mc_whatsapp)
- `tx_sitepackage_eyebrow` → `tx_sitepackage_subheader`
- `tx_sitepackage_title` → `header`
- `bodytext` → `bodytext` (unchanged, now with rich text)
- `tx_sitepackage_text` (hint) → `header_link` (disclaimer text)

#### Testimonials (mc_testimonials)
- No fields changed (uses database query)

## Migration Steps

### 1. Database Schema Update

After deploying the changes, run the TYPO3 database compare:

```bash
# Via CLI
php vendor/bin/typo3 database:updateschema

# Or via Backend
Admin Tools → Maintenance → Analyze Database Structure
```

### 2. Data Migration SQL

Run this SQL to migrate existing content element data:

```sql
-- Migrate hero elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    bodytext = tx_sitepackage_text
WHERE CType = 'mc_hero' AND header = '';

-- Move background images to assets for hero
UPDATE tt_content t
INNER JOIN sys_file_reference r ON r.uid_foreign = t.uid 
    AND r.tablenames = 'tt_content' 
    AND r.fieldname = 'tx_sitepackage_background_image'
SET r.fieldname = 'assets'
WHERE t.CType = 'mc_hero';

-- Migrate text section elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow
WHERE CType = 'mc_text_section' AND header = '';

-- Migrate CTA elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    bodytext = tx_sitepackage_text
WHERE CType = 'mc_cta' AND header = '';

-- Migrate intro elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    bodytext = tx_sitepackage_text,
    header_link = tx_sitepackage_quote
WHERE CType = 'mc_intro' AND header = '';

-- Migrate FAQ elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    bodytext = tx_sitepackage_text
WHERE CType = 'mc_faq' AND header = '';

-- Migrate journey elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    bodytext = tx_sitepackage_subtitle
WHERE CType = 'mc_journey' AND header = '';

-- Migrate value elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow
WHERE CType = 'mc_values' AND header = '';

-- Migrate moderator elements
UPDATE tt_content SET 
    header = tx_sitepackage_name,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    header_link = tx_sitepackage_quote
WHERE CType = 'mc_moderator' AND header = '';

-- Move moderator photos to assets
UPDATE tt_content t
INNER JOIN sys_file_reference r ON r.uid_foreign = t.uid 
    AND r.tablenames = 'tt_content' 
    AND r.fieldname = 'tx_sitepackage_photo'
SET r.fieldname = 'assets'
WHERE t.CType = 'mc_moderator';

-- Migrate newsletter elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    bodytext = tx_sitepackage_text
WHERE CType = 'mc_newsletter' AND header = '';

-- Migrate WhatsApp elements
UPDATE tt_content SET 
    header = tx_sitepackage_title,
    tx_sitepackage_subheader = tx_sitepackage_eyebrow,
    header_link = tx_sitepackage_text
WHERE CType = 'mc_whatsapp' AND header = '';
```

### 3. Clear All Caches

After migration, clear all TYPO3 caches:

```bash
# Via CLI
php vendor/bin/typo3 cache:flush

# Or via Backend
Admin Tools → Flush Caches → Flush all caches
```

### 4. Manual Verification

1. Check a few content elements of each type in the backend
2. Verify the content displays correctly on the frontend
3. Test editing content elements to ensure all fields work

## Rollback Procedure

If issues occur, you can rollback by:

1. Revert the git commit
2. Run database compare to restore old schema
3. Deploy the previous version

## Post-Migration Cleanup (Optional)

After successful migration and verification, you can remove old database columns:

```sql
-- Remove old columns (ONLY after successful migration and verification!)
ALTER TABLE tt_content 
    DROP COLUMN tx_sitepackage_eyebrow,
    DROP COLUMN tx_sitepackage_title,
    DROP COLUMN tx_sitepackage_text,
    DROP COLUMN tx_sitepackage_quote,
    DROP COLUMN tx_sitepackage_subtitle,
    DROP COLUMN tx_sitepackage_name,
    DROP COLUMN tx_sitepackage_background_image,
    DROP COLUMN tx_sitepackage_photo;
```

**⚠️ Warning:** Only run cleanup after thorough testing in production!

## Benefits After Migration

1. **Better TYPO3 Integration**: Using standard fields means better compatibility with TYPO3 core features
2. **Improved Editor Experience**: Context-specific labels and descriptions
3. **Modern TCA Structure**: Full tab structure with proper organization
4. **Future-Proof**: Follows TYPO3 v14 best practices
5. **Cleaner Database**: Reduced custom fields
6. **Better Searchability**: Standard fields are indexed by TYPO3

## Support

If you encounter issues during migration:

1. Check the database migration logs
2. Verify all SQL statements completed successfully
3. Review TYPO3 error logs
4. Test in a staging environment first

## Questions?

For questions or issues, please create an issue in the repository.
