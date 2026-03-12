# jQuery Form Builder Setup Instructions

## Quick Start

### 1. Migrate Database (IMPORTANT!)

This step adds the required columns to your database.

```
Visit: http://your-site/migrate-add-json-schema.php
```

This will:

- Add `json_schema` column to store jQuery Form Builder definitions
- Add `created_by` column to track who created the survey
- Display the current table schema for verification

### 2. Access the New Survey Builder

**For Admins (Create Public Surveys):**

```
http://your-site/admin-survey-builder-json.php
```

Or from Admin Dashboard → Create Survey (New)

**For Researchers (Create Own Surveys):**

```
http://your-site/independent-survey-builder-json.php
```

Or from Researcher Dashboard → Create Survey

### 3. Create Your First Survey

1. **Fill in Survey Details (Left Panel):**
   - Survey Title (required)
   - Description
   - Category (Marketing, Academic, Product, etc.)
   - Survey Type (Public or Invitation Only)

2. **Design with SurveyJS (Right Panel):**
   - Click "Add Question" button
   - Drag and drop to rearrange
   - Double-click questions to edit
   - Use the property panel to configure each question

3. **Save Survey:**
   - Click "Save Survey" button
   - Survey will be stored with JSON schema

### 4. Make Survey Active

Currently surveys are saved as "draft". To make them available:

1. Go to Admin Dashboard → Manage Surveys
2. Find your survey
3. Click Edit and change status to "active"
4. Survey will now appear in public list

### 5. Test Survey

1. Go to Public Surveys List
2. Find your survey
3. Click "Take Survey"
4. Survey renders from JSON and collects responses

## Available Question Types

| Type         | Description         | Example Use                |
| ------------ | ------------------- | -------------------------- |
| **Text**     | Single-line input   | Name, location             |
| **Comment**  | Multi-line textarea | Feedback, suggestions      |
| **Email**    | Email validation    | Contact email              |
| **URL**      | URL validation      | Website link               |
| **Phone**    | Phone number        | Contact number             |
| **Number**   | Numeric input       | Age, score                 |
| **Date**     | Date picker         | Birth date, event date     |
| **Time**     | Time picker         | Preferred time             |
| **Radio**    | Single choice       | Yes/No, preference         |
| **Checkbox** | Multiple choice     | Select all that apply      |
| **Dropdown** | Drop-down list      | Select from list           |
| **Rating**   | Star rating         | Satisfaction (1-5)         |
| **Matrix**   | Grid of choices     | Multiple questions at once |
| **Ranking**  | Rank items          | Priority ordering          |
| **File**     | File upload         | Document, image            |

## Key Features

### Conditional Logic

- Show/hide questions based on answers
- Create branching paths
- Skip logic built-in

### Validation

- Required/optional fields
- Custom validation rules
- Error messages

### Styling

- Multiple themes (modern, bootstrap, default)
- Responsive mobile design
- Custom CSS support

## File Structure

```
/kmsurveytool/
├── admin-survey-builder-json.php      ← Admin builder (NEW)
├── independent-survey-builder-json.php ← Researcher builder (NEW)
├── survey-render-json.php              ← Survey renderer (NEW)
├── api-survey-json.php                 ← REST API (NEW)
├── migrate-add-json-schema.php         ← Database migration (NEW)
├── SURVEYJS-INTEGRATION.md             ← Full documentation
├── admin-survey-builder.php            ← Legacy builder (still available)
├── independent-survey-builder.php      ← Legacy builder (still available)
└── public-take-survey.php              ← Legacy renderer (still available)
```

## Troubleshooting

### Column Already Exists Error

If you get an error during migration:

- Column may already exist from previous attempt
- Check via `sql-schema.php` to verify
- You can manually delete the column: `ALTER TABLE surveys DROP COLUMN json_schema`

### Survey Won't Save

1. Check browser console (F12 → Console tab) for errors
2. Ensure all required fields are filled
3. Verify JSON is valid
4. Check server error logs

### Survey Not Rendering

1. Ensure survey status is "active" (not "draft")
2. Check if JSON schema is stored correctly
3. Verify survey ID in URL matches database
4. Check browser console for JavaScript errors

### "Survey Not Found" Message

- Survey ID may be incorrect
- Survey may have been deleted
- Check database directly via sql-schema.php

## API Usage Examples

### Get Survey JSON

```javascript
fetch("/api-survey-json.php?action=get&id=1")
  .then((r) => r.json())
  .then((data) => console.log(data));
```

### Submit Response

```javascript
const formData = new FormData();
formData.append("action", "submit");
formData.append("id", 1);
formData.append("data", JSON.stringify(surveyAnswers));

fetch("/api-survey-json.php", {
  method: "POST",
  body: formData,
})
  .then((r) => r.json())
  .then((data) => console.log(data));
```

### List User's Surveys

```javascript
fetch("/api-survey-json.php?action=list")
  .then((r) => r.json())
  .then((surveys) => console.log(surveys));
```

## Next Steps

1. ✅ Run migration script
2. ✅ Create your first survey using new builder
3. ✅ Test taking the survey as respondent
4. 📋 Export/import surveys for backup
5. 📊 View analytics on responses
6. 🎨 Customize survey themes
7. 🔐 Add role-based survey access control

## Support & Documentation

- **Full Guide:** See `SURVEYJS-INTEGRATION.md`
- **SurveyJS Docs:** https://surveyjs.io/
- **JSON Schema Reference:** See API response from `api-survey-json.php?action=get`
