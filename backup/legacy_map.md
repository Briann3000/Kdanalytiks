# Migration Mapping: Legacy to Laravel

This folder contains the original flat-file PHP implementation of KMSurveyTool. Use this as a reference for original business logic.

| Legacy File | New Laravel Location | Description |

| :--- | :--- | :--- |

| `config.php`, `rb.php` | `.env`, `config/database.php` | Database connection & RedBeanPHP setup moved to Eloquent. |

| `functions.php` | `app/Services/` | Global functions refactored into Service classes. |

| `admin-surveys.php` | `app/Http/Controllers/Admin/SurveyController.php` | Logic moved to Controller; UI moved to Blade templates. |

| `sql-schema.php` | `database/migrations/` | Manual SQL moved to Laravel Migrations. |

| `header.php`, `footer.php` | `resources/views/layouts/app.blade.php` | Global UI moved to a single Base Layout. |