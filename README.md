# Hira FMCG Website

A complete FMCG website with:

- React frontend with Tailwind CSS
- PHP backend APIs for products, CMS, and leads
- PHP admin dashboard with session-based authentication
- MySQL schema for products, editable page content, leads, and admin users

## Structure

- `frontend` - React storefront
- `backend` - PHP API layer, database bootstrap, upload handling
- `admin` - PHP admin control panel
- `database/schema.sql` - MySQL database setup

## Default Admin Login

- Username: `admin`
- Password: `admin123`

The default admin user is automatically created on first run if the `admin_users` table is empty.

## XAMPP Setup

1. Place the project inside `htdocs` so it resolves as `http://localhost/HIRA`.
2. Start Apache and MySQL from XAMPP.
3. Import [`database/schema.sql`](/c:/Users/sarth/Desktop/HIRA/database/schema.sql).
4. Confirm `backend/config/database.php` matches your local MySQL credentials.
5. Open `http://localhost/HIRA/admin` for the admin panel.
6. Run the React app from `frontend`.

## Frontend Commands

From `frontend`:

```bash
npm install
npm run dev
```

For production build:

```bash
npm run build
```

If you want to deploy the React build through Apache, serve the generated `frontend/dist` output separately or connect it to your preferred static hosting setup while keeping the PHP backend and admin panel in XAMPP.

## API Endpoints

- `GET /backend/api/products.php`
- `GET /backend/api/getProduct.php?id=1`
- `POST /backend/api/addProduct.php`
- `POST /backend/api/updateProduct.php`
- `POST /backend/api/deleteProduct.php`
- `GET /backend/api/getPage.php?page=home`
- `POST /backend/api/updatePage.php`
- `POST /backend/api/submitLead.php`
- `POST /backend/api/submitContact.php`
- `GET /backend/api/getLeads.php`

## Notes

- Uploaded product images are stored in `backend/uploads`.
- CMS content is stored as JSON in `cms_pages`.
- The site seeds default page content, a starter catalog, and the first admin user automatically once the schema exists.
