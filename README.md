# Direktori Batam - Minimal PHP CRUD

This is a minimal PHP CRUD app built for the `bd_directory` database (schema included as `schema.sql`). It's designed to run on XAMPP (Windows) using default settings.

## Files included
- `schema.sql` - Database schema + seed data (already present in your workspace).
- `config.php` - DB credentials (default XAMPP: `root` / no password)
- `db.php` - PDO connection and helper functions
- `header.php`, `footer.php` - page layout
- `index.php` - list and filter listings
- `listing_create.php` - create a listing
- `listing_edit.php` - edit a listing
- `listing_delete.php` - delete a listing
- `categories.php` - show categories tree
 - `api/listings.php` - API endpoint for AJAX listings
 - `assets/app.css`, `assets/app.js` - UI styling & client-side JS for AJAX and interactions

## Setup (XAMPP on Windows)
1. Place all files under `C:\xampp\htdocs\teskan` (already correct for this workspace).  
2. Start Apache and MySQL via the XAMPP Control Panel.  
3. Create the database and seed data using `schema.sql`:

   Option A (phpMyAdmin):
   - Open `http://localhost/phpmyadmin` -> Import -> Choose file `schema.sql` -> Import.

   Option B (MySQL CLI):
   Open PowerShell and run:

   ```powershell
   cd C:\xampp\htdocs\teskan
   mysql -u root < schema.sql
   ```

   If MySQL uses a password, add `-p` and you'll be prompted for it.

Migration: If you have already created the DB earlier without `map_link`, run the migration SQL to add the new column:

```powershell
mysql -u root bd_directory < migrations\add_map_link.sql
```

4. Visit the app in a browser: `http://localhost/teskan` or `http://localhost/teskan/index.php`.

## Configuration
- If your DB credentials differ, edit `config.php`.

## Notes / Next steps
- This is a minimal example focusing on listings & categories only.
- Additional improvements: user authentication, file/image uploads, richer validation, and controls for categories management.
 - The index now supports AJAX filtering and a modern responsive card grid. Use the search, category dropdown or click the category buttons in the top categories menu to show sub-categories. You can change the number of results displayed with the limit dropdown and the results will update without reloading the page.
 - Each card links to a modern detail view (`listing_view.php?id=...`) displaying a main photo and a gallery (if available), full description, category/subcategory, address, contact links, and an embedded Google Maps iframe based on coordinates or address.
 - Each card links to a modern detail view (`listing_view.php?id=...`) displaying a main photo and a gallery (if available), full description, category/subcategory, address, contact links, and an embedded Google Maps iframe based on coordinates or address.
 - You can now upload images during Add/Edit listing. Uploaded images are stored in `uploads/` and the path(s) are saved in the `thumbnail` column (comma-separated). When you replace images in Edit, previous local image files are deleted. Max 5MB per image; allowed formats: jpeg/png/webp.
 - You can now provide a Google Maps link for each listing (create/edit). The link is stored in the `map_link` column and will be used to show an embedded map on the listing detail page or as a quick 'Open in Google Maps' link. The app also falls back to lat/long or address if the map link isn't provided.
 - Sorting and AJAX filtering are supported on the homepage.
 - User registration (`register.php`) and login (`login.php`) are implemented. After registration, the user is automatically logged in.
 - Role-based access: Admins can create/edit/delete listings; normal users can only view.
 - For development, create an admin account by running `make_admin.php` once (creates admin@example.com / admin123):

```powershell
php make_admin.php
```

If you'd like, I can extend this to:
- Add user login & register pages that tie into the `users` table
- Add reviews UI using `reviews` table
- Create category management (add/edit/delete categories)

Tell me which features you'd like next — I'll implement them.