# jQuery Form Builder Integration Guide

## Overview

The survey tool now includes **jQuery Form Builder** - a free, MIT-licensed drag-and-drop form builder that generates JSON schemas. This replaces the manual question-by-question input with a modern, user-friendly interface.

## Features

### 1. Drag-and-Drop Survey Builder

- **jQuery Form Builder** provides an intuitive interface for creating surveys
- Supports 20+ question types (text, radio, checkbox, matrix, ranking, etc.)
- Real-time preview of surveys
- Conditional logic and branching
- Question validation rules

### 2. JSON Schema Storage

- All surveys are stored as JSON schemas in the database
- JSON schemas are editable and portable
- Easy to export/import surveys between systems

### 3. Dynamic Survey Rendering

- Surveys are rendered from JSON schemas at runtime
- Responsive design works on desktop and mobile
- Professional styling with multiple themes

## Files Added

### Builder Pages

- `admin-survey-builder-json.php` - Admin survey builder (for public surveys)
- `independent-survey-builder-json.php` - Researcher survey builder

### Renderer & API

- `survey-render-json.php` - Renders surveys from JSON for respondents
- ` ` - REST API for survey operations

### Database Migration

- `migrate-add-json-schema.php` - Adds `json_schema` column to surveys table

## How to Use

### Step 1: Run Database Migration

Navigate to `http://your-site/migrate-add-json-schema.php` to add the JSON schema column to the database.

### Step 2: Create a Survey (Admin)

1. Login as admin
2. Go to Dashboard → Create Public Survey
3. Or directly visit `admin-survey-builder-json.php`
4. Use the drag-and-drop builder on the right side
5. Configure survey details (title, description, category, type) on the left
6. Click "Save Survey"

### Step 3: Create a Survey (Researcher)

1. Login as independent researcher
2. Go to Dashboard → Create Survey
3. Or directly visit `independent-survey-builder-json.php`
4. Follow same steps as admin

### Step 4: Respondents Take Survey

1. Users navigate to public surveys list
2. Click "Take Survey"
3. Survey renders from JSON schema
4. Answers are stored in responses/answers tables

## Question Types Supported

**Text-based:**

- Text
- Email
- URL
- Phone
- Number
- Date
- Time
- DateTime

**Selection-based:**

- Radio (Single choice)
- Checkbox (Multiple choice)
- Dropdown (Select)
- Image picker
- Ranking

**Advanced:**

- Matrix (Rating scale)
- Composite (Multi-field)
- Boolean (Yes/No)
- File upload
- Comment

## API Endpoints

### Get Survey Schema

```
GET /api-survey-json.php?action=get&id=1
```

Returns the survey JSON schema and metadata.

### Save Survey Schema

```
POST /api-survey-json.php
action=save&id=1&json_schema={...}&status=active
```

### Submit Response

```
POST /api-survey-json.php
action=submit&id=1&data={...}
```

### List User Surveys

```
GET /api-survey-json.php?action=list
```

(Requires authentication)

## Database Schema

### surveys table changes

New columns added:

- `json_schema` (LONGTEXT) - Stores the SurveyJS JSON definition
- `created_by` (VARCHAR) - Tracks if survey was created by 'admin' or 'independent'

## Data Storage

### Old Approach (Still Supported)

- Questions stored in `questions` table
- Answers in `answers` table
- Works with HTML-based survey rendering

### New Approach (JSON-based)

- Entire survey structure stored as JSON in `surveys.json_schema`
- Response data stored as JSON in `answers.value`
- Faster rendering, more flexible question types
- Better for complex conditional logic

## Transitioning from Old Builder

### Old Survey Pages (Still Available)

- `admin-survey-builder.php` - Manual question builder (legacy)
- `independent-survey-builder.php` - Legacy researcher builder
- `public-take-survey.php` - Legacy survey renderer

### New Survey Pages (Recommended)

- `admin-survey-builder-json.php` - New admin builder
- `independent-survey-builder-json.php` - New researcher builder
- `survey-render-json.php` - New survey renderer

**Both systems can coexist.** The database will support both `questions` table (old system) and `json_schema` column (new system).

## Benefits

✅ **User-Friendly** - Drag-and-drop beats manual entry  
✅ **More Question Types** - 20+ vs 10 previously  
✅ **Conditional Logic** - Show/hide questions based on answers  
✅ **Better Performance** - Single JSON vs multiple queries  
✅ **Mobile Responsive** - Works on all devices  
✅ **Modern UI** - Professional, modern appearance  
✅ **Portable** - Easy to export/backup surveys

## Troubleshooting

### Migration Shows Error

- The column may already exist from a previous attempt
- Go to `http://your-site/sql-schema.php` to verify the schema

### Survey Not Rendering

- Ensure JSON schema is valid
- Check browser console for JavaScript errors
- Verify survey status is 'active'

### Can't Save Survey

- Ensure survey details are filled in
- Check browser console for validation errors
- Verify JSON is valid

## Next Steps

1. Update dashboard pages to link to new builders
2. Create survey import/export functionality
3. Add survey templates
4. Add survey versioning
5. Create analytics dashboard for JSON responses
6. Add CSRF token protection to API endpoints

## CDN References

The following CDN resources are used:

- SurveyJS Core: `https://surveyjs.org/survey/survey.core.min.js`
- SurveyJS Creator: `https://surveycss.azureedge.net/survey-creator/survey-creator.min.js`
- SurveyJS Modern Theme CSS: `https://surveycss.azureedge.net/modern-2.4.18/survey.modern.min.css`
