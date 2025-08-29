# Implementation Notice

This module provides a Movie Rating system with the following implementation details:

## Implementation Overview

1. **Content Setup and Migration**

   - Created movie content types.
   - Prepared JSON-based migrations with dummy movie data for testing.

2. **Custom Data Storage**

   - Custom database table stores ratings: `id`, `movie_id`, `rating_value`, `user_id`/IP, and timestamp.
   - Integrated with Views via `hook_views_data()` for use in popular and highest-rated movies listings.

3. **Service Layer**

   - `MovieRatingService` manages rating submission, caching, and average calculation.
   - Methods include `submitRating()`, cache invalidation, and `getAverageRating()`.

4. **Rating Submission Form**

   - Form obtains `movieId` from current route node.
   - Displays radio buttons styled as stars.
   - Validates input and saves ratings through the service.
   - Honeypot service protects against bot submissions.

5. **Rating Block**

   - Displays average rating and rating submission form.
   - Implements cache contexts, tags, and max-age for performance.
   - Uses custom styling for star ratings.
   - Placed via block layout on movie pages.

6. **Views Integration**

   - Movie listing page with filters: categories, actors, directors, star ratings.
   - Custom Views filter plugin for the star ratings select list.
   - Views blocks for popular and highest-rated movies use aggregation and cache tagging.
   - Blocks placed in sidebar region.

7. **Bonus Features**

   - Honeypot integration for spam protection.
   - QR Code service generating and displaying trailer QR codes with YouTube URL validation.

---

## Setup and Testing Instructions

Execute the following commands in your local development environment:

```bash
ddev start
ddev composer install
ddev drush site:install --account-name=admin --account-pass=admin -y
ddev launch
```

---

### Configuration Import and UUID Mismatch Fix

After the site install, running configuration import may produce a UUID mismatch error due to the site already being installed but config having a different UUID.

To fix:

1. Obtain the UUID from your sync config at `config/sync/system.site.yml`.

2. Run:

```bash
ddev drush cset system.site uuid "44d8ad9e-a5ec-4a52-aa6a-f750294a3fe3"  # Replace with your actual UUID
ddev drush cim
ddev drush cr
```

---

### Import Dummy Content

Import dummy movie content for testing:

```bash
ddev drush migrate:import --group="movie_migration"
```

---

### Verify Content and Features

- Navigate to `/movies` to see the main movie listing with filters.
- Sidebar contains blocks for popular movies and highest rated movies.
- Visit individual movie pages to view ratings and submit your own rating via the rating form.

---

This setup guides through installing, importing configuration and content, and viewing the module’s functionality end-to-end.
