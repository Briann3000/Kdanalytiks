<div align="center">

# KDAnalytiks

### Survey Intelligence Platform for African Research

**Build surveys. Collect data. Generate full academic reports — powered by AI.**

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![License](https://img.shields.io/badge/License-Proprietary-blue?style=flat)](https://kdanalytiks.com)
[![Powered by KENPRO](https://img.shields.io/badge/Powered%20by-KENPRO-4F46E5?style=flat)](https://kenpro.org)

[Live App](https://kdanalytiks.com) · [Contact](mailto:infokdanalytiks@gmail.com) · [KENPRO](https://kenpro.org)

</div>

---

## What is KDAnalytiks?

KDAnalytiks is a multi-tenant research data platform built specifically for the Kenyan and broader African academic ecosystem. It goes far beyond form building,it is the only platform in the region that takes a researcher from blank page to a **fully formatted, automated, 5-chapter academic report** in a single workflow.

Designed for universities, NGOs, government agencies, independent researchers and student respondents, KDAnalytiks combines enterprise-grade survey infrastructure with cutting-edge AI research tools typically only available via expensive Western SaaS tools.

---

## ✨ Standout Features

### 🧠 Survey-to-Thesis Pipeline
The flagship feature. Select any live survey with collected responses and KDAnalytiks generates a **complete, publishable-quality academic research report** in minutes:

- Structured across **5 university-standard chapters**: Introduction, Literature Review, Methodology, Results & Discussion, Conclusions & Recommendations.
- Chapter generation runs in **parallel AI waves** (Chapters 1+2 and Chapters 3+4+5 simultaneously) via HTTP connection pooling — cutting generation time significantly compared to sequential generation.
- Every quantitative survey question automatically generates a **frequency distribution table (f, %)** AND a **branded bar chart** (via QuickChart.io, coloured to the org's brand colour) embedded inline in the report.
- Reports export as fully formatted **DOCX** (PhpWord) or **PDF** (mPDF). Free-tier exports carry a KDAnalytiks watermark. Pro/Enterprise exports are **fully white-labelled** — the org's logo, name and brand colour replace all platform branding at the document level.
- Supports **7 academic citation styles** chosen at generation time: APA7, MLA9, Harvard, Chicago, IEEE, Vancouver and OSCOLA.
- After generation, the entire report can be **auto-translated** into Swahili, French, German, Spanish, Arabic, or Chinese using batched parallel AI translation calls that preserve document structure via ID-tagging.

---

### 🤖 Socius — Your Contextual Research AI Assistant
An embedded AI chat companion (powered by Groq LLaMA 3.1 and Google Gemini) that already knows your survey, your data and your research context before you type a single word:

- **Real-time streaming responses** via Server-Sent Events — no waiting for the full reply, it streams token by token like a professional AI tool.
- **Survey-context injection** — Toggle a switch and Socius reads your entire survey schema and all collected response data before answering. Ask *"What does the data say about question 5?"* and get a data-grounded answer.
- **Web search grounding** — A second toggle fires a live web search (via Serper API) and injects current results as context, letting Socius cite up-to-date sources in its answers.
- **Long-term project memory** — After each session, Socius automatically extracts and persists key facts about your project that carry forward into every future conversation.
- **File & image understanding** — Attach PDFs, DOCX, CSV and image files directly to messages. Text is extracted and sent as context; images are processed via Groq's vision model.
- **Supervisor Review Mode** — Upload a report draft alongside supervisor comment notes. Socius acts as an editor and rewrites the flagged sections to address every comment.
- **Multi-thread management** — Create, rename, pin and delete separate conversation threads per survey, keeping different research tracks cleanly separated.
- **Export conversations** in 4 formats: PDF, DOCX, Excel, or Markdown.

---

### 📊 Inferential Statistical Analysis with AI Interpretation
Not just cross-tabs and pie charts. KDAnalytiks computes and **interprets** six statistical tests with the rigour of a trained analyst:

| Test | What It Computes |
|------|-----------------|
| Chi-Square | Cross-tabulation matrix + expected values + p-value |
| Independent T-Test | Group means, mean difference, standard error, significance |
| Pearson Correlation | r, r², t-value, p-value |
| One-Way ANOVA | F-value, SS between/within groups, group descriptives |
| Simple Linear Regression | R, R², adjusted R², coefficient table, ANOVA fit |
| Multiple Linear Regression | Full equation, multiple predictor coefficients, model summary |

After computing the test, the AI writes a **strategic narrative interpretation** (not just restating numbers — it identifies what the findings mean for the research). Researchers can then send **follow-up prompts** (*"swap the predictor variables"*, *"add the likelihood ratio"*) and the AI returns a revised interpretation **plus** a JSON code block with updated metric values that re-render the statistical table dynamically on the page.

---

### 🛡️ AI-Powered Response Fraud & Quality Detection
Every submitted survey response passes through a 4-checkpoint quality scoring pipeline (0–100 score) before being stored:

- **Speed Trap**: Compares completion time against the statistical median of prior responses. Completes faster than 20% of median? Flagged. Falls back to an 8-second-per-question heuristic for new surveys.
- **Device Fingerprint Deduplication**: Combines IP address + client-side device fingerprint to catch duplicate submissions from the same physical device even across different accounts.
- **Straight-Lining Detection**: Scans all `select_one`, `rating` and `radio` fields. If 3 or more choice questions share the exact same selected value, the response is flagged as pattern-clicked.
- **Text Quality Analysis**: Three-layer regex analysis on open-text fields — catches character mashing (`aaaaaaaa`), keyboard gibberish (`vbnmghj`) and under-effort short answers on substantive questions.

Flagged responses enter an **admin review pipeline** where they can be approved (releasing wallet rewards) or rejected (deleting the record). No binary pass/fail — the differential score gives researchers nuanced data quality tiers for analysis.

---

### 📄 DOCX Questionnaire → Live Survey Conversion
Upload an existing Word document questionnaire and KDAnalytiks reverse-engineers it into a live, editable survey:

- AI reads every question, heading, instruction and multiple-choice option from the DOCX file.
- Outputs a fully structured JSON schema compatible with the SurveyJS/FormBuilder engine.
- Preserves section headers, scale labels, conditional instructions and multi-choice values.
- This **document-to-live-survey pipeline** makes it trivial to digitise existing paper-based research instruments.

---

### 🌍 Multilingual Research Platform
Built from the ground up to serve multilingual research contexts:

- UI translations available in **English, Swahili, French, German, Spanish and Arabic**.
- Socius detects the language of every user message and **responds in the same language automatically**, regardless of UI locale setting.
- Academic report translation runs **natively in the target language** — themes, quotes and interpretations are generated in Swahili, French, etc., not translated after the fact.
- Statistical analysis and qualitative insight narratives honour the active locale.

---

### 💰 Respondent Wallets & Paid Surveys
A built-in incentive system that keeps the respondent pool engaged and honest:

- Respondents earn **wallet credits** for completing verified quality surveys.
- Earnings are withheld automatically for flagged/low-quality responses and released only after admin approval.
- In-app wallet dashboard shows balance, transaction history and withdrawal options via IntaSend (M-Pesa and card supported).
- Survey creators set the reward amount per survey; the platform handles disbursement.

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2 / Laravel 10.x |
| Frontend | Blade Templates, AlpineJS, Tailwind CSS |
| Asset Pipeline | Vite 8.x |
| Primary AI | Groq API (LLaMA 3.1 8B, Whisper Large v3) |
| Secondary AI | Google Gemini 2.5 Flash |
| Image Generation | HuggingFace (OpenJourney) / Pollinations.ai fallback |
| Web Search | Serper (Google Search API) |
| Database | MySQL / MariaDB (Production), SQLite (Testing) |
| Payments | IntaSend Gateway (M-Pesa + Cards) |
| Document Export | PhpWord (DOCX), mPDF (PDF), Maatwebsite Excel |
| Mobile | Capacitor JS (Android + iOS wrappers) |

---

## 📁 Directory Structure

```
├── android/                        # Capacitor Android native wrapper
├── ios/                            # Capacitor iOS native wrapper
├── app/
│   ├── Http/Controllers/
│   │   ├── SurveyController.php    # Full survey lifecycle + exports
│   │   ├── InsightController.php   # Statistical & qualitative analysis engine
│   │   ├── SociusChatController.php# Socius AI chat assistant
│   │   ├── ResearchProposalController.php  # Report & proposal generator
│   │   ├── SurveyResponseQualityController.php # Fraud review pipeline
│   │   └── ...                     # Admin, Org, Researcher, Respondent controllers
│   ├── Models/                     # Eloquent models (Survey, Wallet, InviteCampaign, etc.)
│   └── Services/
│       ├── AiService.php           # Dual-provider AI gateway (Groq + Gemini)
│       ├── AcademicSynthesisService.php  # 5-chapter thesis generator
│       ├── ResponseQualityService.php    # Fraud detection pipeline
│       ├── QualitativeAnalysisService.php# Sentiment + theme analysis
│       ├── SociusPromptBuilder.php       # Context injection layer for Socius
│       └── Payments/               # IntaSend payment gateway
├── database/
│   ├── migrations/                 # Chronological schema migrations
│   └── seeders/                    # Institutional seed data
├── resources/
│   ├── views/                      # Role-scoped Blade templates
│   └── js/ & css/                  # AlpineJS components + Tailwind source
├── public/                         # Compiled Vite output + entry point
├── lang/                           # Translation files (EN, SW, FR, DE, ES, AR)
├── backup/                         # Legacy flat-PHP archive (historical reference)
└── tests/Feature/                  # PHPUnit feature tests
```

---

## ⚙️ Local Setup & Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ & NPM
- MySQL / MariaDB (or SQLite for quick local dev)

### Steps

**1. Clone the repository:**
```bash
git clone <repository-url>
cd kdanalytiks
```

**2. Install dependencies:**
```bash
composer install
npm install
```

**3. Configure the environment:**
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your credentials:
```env
APP_NAME=KDAnalytiks
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_DATABASE=kdanalytiks
DB_USERNAME=root
DB_PASSWORD=your-password

# AI Providers (at least one required)
GROQ_API_KEY=your-groq-key
GEMINI_API_KEY=your-gemini-key
AI_PROVIDER=groq   # or "gemini"

# Web Search (for Socius web grounding)
SERPER_API_KEY=your-serper-key

# Payments
INTASEND_PUBLIC_KEY=your-intasend-public-key
INTASEND_SECRET_KEY=your-intasend-secret-key

# Mail (Gmail SMTP example)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=infokdanalytiks@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="infokdanalytiks@gmail.com"
```

**4. Run migrations and seed:**
```bash
php artisan migrate --seed
```

**5. Build frontend assets:**
```bash
npm run build
```

**6. Start the development server:**
```bash
php artisan serve
```

---

## 🧪 Running Tests

```bash
php artisan test
```

The test suite covers survey versioning, invite campaign flows, response quality fraud detection and dashboard builder access control.

---

## 🌐 cPanel Production Deployment

### Document Root
Point your cPanel domain's Document Root directly to the `public/` subdirectory:
```
/home/username/kdanalytiks.com/public
```
This keeps all source code, `.env` and database files out of the web root.

### After Uploading Files
Run the following in the **cPanel Terminal** from your project root:

```bash
# Apply any new migrations
php artisan migrate

# Rebuild config cache with live .env values
php artisan config:cache

# Clear compiled views
php artisan view:clear

# Rebuild route cache
php artisan route:cache
```

> **Note:** If the site loads but assets are missing, check that no `hot` file exists inside `public/`. This file is created by `npm run dev` and tells Laravel to load assets from your local dev server instead of the compiled production build. Delete it if present.

---

## 👥 User Roles

| Role | Description |
|------|-------------|
| **Administrator** | Full platform access — manages all users, surveys, payments and quality reviews |
| **Organization** | Creates and manages surveys; accesses AI reports with subscription-gated features |
| **Independent Researcher** | Individual researcher with personal survey workspace and AI tools |
| **Respondent** | Completes surveys; earns wallet rewards; withdraws via M-Pesa |

---

<div align="center">


Powered by [KENPRO](https://kenpro.org) · [kdanalytiks.com](https://kdanalytiks.com) · [infokdanalytiks@gmail.com](mailto:infokdanalytiks@gmail.com)

</div>
