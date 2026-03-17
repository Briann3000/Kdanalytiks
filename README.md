# KMSurveyTool: Phased Migration to Laravel

KMSurveyTool is a state-of-the-art, role-based survey and data collection platform specifically designed for the Kenyan research ecosystem. Originally built as a flat PHP project, this version represents a full migration to **Laravel 10.50.2**, incorporating modern UI/UX principles, AI-driven analytics, and robust security.

## 🚀 Key Features

- **AI Survey Architect**: Integrated with OpenAI to generate complete survey schemas from natural language prompts.
- **Advanced Sentiment Analysis**: Automatic sentiment tagging and executive summaries for all survey responses.
- **Multitenant Role Architecture**: Tailored dashboards for Administrators, Organizations, Independent Researchers, and Respondents.
- **Modern UI**: Fully responsive interface built with Tailwind CSS, featuring glassmorphism elements and premium typography.
- **SurveyJS Integration**: Utilizing the SurveyJS engine for dynamic, complex questionnaire rendering and participation.
- **Secure Infrastructure**: Password visibility toggles, CAPTCHA verification on public submissions, and role-based middleware.

## 📁 Project Structure (Post-Migration)

- **`app/Http/Controllers/`**: Contains role-specific controllers (Admin, Org, Independent) and the AI logic.
- **`app/Models/`**: Eloquent models with defined relationships for a relational survey database.
- **`resources/views/`**: Modern Blade templates organized by role and feature.
- **`docs/`**: Project documentation, including implementation plans and task lists.
- **`backup/`**: An archive of the legacy PHP files and database dumps for historical reference.
- **`database/migrations/`**: A chronological record of the schema evolution.

## 🛠 Setup & Installation

1.  **Clone the Repository**:
    ```bash
    git clone -b laravel-migration <repo-url>
    ```
2.  **Environment Configuration**:
    - Copy `.env.example` to `.env`.
    - Set your `OPENAI_API_KEY` for the AI Architect features.
    - Configure the SQLite database path in `.env`.
3.  **Dependency Installation**:
    ```bash
    composer install
    npm install
    npm run dev
    ```
4.  **Database Seeding**:
    ```bash
    php artisan migrate --seed
    ```
    *This populates the platform with initial Kenyan institutional data.*

## 📈 Current Migration Status

The project is currently in the "Release Candidate" phase. High-fidelity views and the AI core have been fully implemented and verified.

---

*Built with ❤️ for the Kenyan research community.*
