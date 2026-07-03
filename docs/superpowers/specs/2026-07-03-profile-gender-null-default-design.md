# Profile Gender Null Default

## Goal

Profiles created without a valid gender must store `null` instead of defaulting to `male`.

## Behavior

- Missing, `null`, empty, or unsupported gender values are normalized to `null` when a profile is created.
- `male` and `female` remain the only stored non-null values.
- Valid values are normalized to lowercase, preserving the current case-insensitive behavior.
- Existing profile records are not migrated or modified.

## Implementation

Update the `Profile` model's `creating` callback so its fallback is `null` rather than `male`. Keep the database schema unchanged because the gender column is already nullable.

## Tests

Add focused model tests proving that:

- A profile created without gender stores `null`.
- An unsupported gender stores `null`.
- Valid mixed-case values are stored in lowercase.

Run the focused test first, then the relevant test suite.
