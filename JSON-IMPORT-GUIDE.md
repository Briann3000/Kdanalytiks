# JSON Import Feature Guide

## Overview

You can now create surveys in two ways:

1. **Visual Builder** - Use the drag-and-drop interface
2. **JSON Import** - Paste or write JSON directly

## How to Use JSON Import

### Step 1: Access the Builder

- Admin: Visit `admin-survey-builder-json.php`
- Researcher: Visit `independent-survey-builder-json.php`

### Step 2: Switch to JSON Import Mode

- Click the **"JSON Import"** button at the top of the builder
- The visual builder will be hidden
- A textarea for JSON input will appear on the left side
- A preview panel will appear on the right side

### Step 3: Paste or Write Your JSON

Paste your JSON survey schema into the textarea. The JSON must follow the **form-builder JSON format**.

### Step 4: Validate & Preview

Click **"Validate & Load"** to check if your JSON is valid.

- ✅ Green message = Valid JSON, ready to save
- ❌ Red message = Invalid JSON, fix the error and try again
- **Preview Panel** - Shows a live preview of how the survey will look

You can see exactly how your survey appears to respondents in the preview panel on the right!

### Step 5: Save the Survey

Click **"Save Survey"** to save your survey with the JSON schema.

## JSON Schema Format

### Minimal Example

```json
{
  "id": "form-0",
  "class": "my-survey",
  "name": "my-survey-form",
  "action": "/api/submit",
  "method": "POST",
  "fields": [
    {
      "type": "text",
      "label": "Your Name",
      "name": "field-name",
      "placeholder": "Enter your name",
      "required": true
    }
  ]
}
```

### Complete Example with Multiple Question Types

```json
{
  "id": "survey-form",
  "class": "feedback-survey",
  "name": "customer-feedback",
  "action": "/api/responses",
  "method": "POST",
  "fields": [
    {
      "type": "text",
      "label": "Full Name",
      "name": "name",
      "placeholder": "Enter your full name",
      "required": true
    },
    {
      "type": "email",
      "label": "Email Address",
      "name": "email",
      "placeholder": "example@domain.com",
      "required": true
    },
    {
      "type": "textarea",
      "label": "Comments",
      "name": "comments",
      "placeholder": "Your feedback here...",
      "rows": 5
    },
    {
      "type": "radio",
      "label": "Overall Satisfaction",
      "name": "satisfaction",
      "options": [
        { "label": "Very Satisfied", "value": "5" },
        { "label": "Satisfied", "value": "4" },
        { "label": "Neutral", "value": "3" },
        { "label": "Dissatisfied", "value": "2" },
        { "label": "Very Dissatisfied", "value": "1" }
      ],
      "required": true
    },
    {
      "type": "checkbox",
      "label": "Which features did you like?",
      "name": "features",
      "options": [
        { "label": "User Interface", "value": "ui" },
        { "label": "Performance", "value": "performance" },
        { "label": "Documentation", "value": "docs" },
        { "label": "Support", "value": "support" }
      ]
    },
    {
      "type": "select",
      "label": "How did you hear about us?",
      "name": "referral",
      "options": [
        { "label": "Search Engine", "value": "search" },
        { "label": "Social Media", "value": "social" },
        { "label": "Friend/Colleague", "value": "referral" },
        { "label": "Other", "value": "other" }
      ],
      "required": true
    }
  ]
}
```

## Supported Field Types

| Type        | Description                 | Example                          |
| ----------- | --------------------------- | -------------------------------- |
| `text`      | Single line text input      | Name, email (without validation) |
| `email`     | Email input with validation | Email address                    |
| `number`    | Numeric input               | Age, rating                      |
| `textarea`  | Multi-line text input       | Comments, feedback               |
| `radio`     | Single choice from options  | Yes/No, satisfaction level       |
| `checkbox`  | Multiple choice             | Select all that apply            |
| `select`    | Dropdown list               | Choose one option                |
| `date`      | Date picker                 | Birth date, survey date          |
| `file`      | File upload                 | Document submission              |
| `button`    | Clickable button            | Submit, Reset                    |
| `header`    | Section header              | Divide survey into sections      |
| `paragraph` | Static text                 | Instructions, descriptions       |

## Field Properties

### Common Properties

```json
{
  "type": "text", // Field type (required)
  "name": "field-id", // Unique field identifier (required)
  "label": "Question Text", // Display label
  "placeholder": "Type here", // Placeholder text
  "description": "Help text", // Additional description
  "required": true, // Whether field is required
  "readonly": false, // Whether field is read-only
  "disabled": false, // Whether field is disabled
  "className": "custom-class", // CSS class for styling
  "multiple": false // Allow multiple selections (for select/checkbox)
}
```

### Options Property (for radio, checkbox, select)

```json
{
  "type": "radio",
  "name": "choice",
  "label": "Select one",
  "options": [
    { "label": "Option 1", "value": "opt1" },
    { "label": "Option 2", "value": "opt2" },
    { "label": "Option 3", "value": "opt3" }
  ]
}
```

## Switching Between Modes

You can switch between **Visual Builder** and **JSON Import** modes anytime:

- Click **"Visual Builder"** button to go back to drag-and-drop
- Click **"JSON Import"** button to switch to JSON input
- **Note:** If you switch from Visual to JSON, the current visual form will be exported as JSON

## Tips & Best Practices

1. **Validate First** - Always click "Validate & Load" before saving
2. **Use Unique Names** - Each field's `name` property must be unique
3. **Be Consistent** - Use consistent naming conventions (e.g., kebab-case or snake_case)
4. **Test Your JSON** - Paste the JSON into a JSON validator online to double-check
5. **Copy & Paste** - Use the complete example and modify it for your needs
6. **Export First** - Switch to JSON mode from visual builder to see the export format

## Live Preview Feature

Both builder modes now include a **Live Preview Panel** on the right side:

### Visual Builder Preview

- Shows real-time preview as you drag and drop elements
- Updates automatically as you add or modify questions
- See exactly how your survey looks without saving

### JSON Import Preview

- Shows preview after you validate your JSON
- Click "Validate & Load" to see the preview rendered
- Click "Clear" to reset the preview
- Helps you catch layout and design issues before saving

## Troubleshooting

### "Invalid JSON" Error

- Check for missing commas between properties
- Ensure all quotes match (no mixing single/double quotes)
- Validate using an online JSON validator (jsonlint.com)

### Missing Fields After Save

- Verify all field `name` properties are unique
- Check that all required properties are present

### Styling Issues

- Add `className` property to customize styling
- Use W3.CSS class names for consistency with your theme

## Examples Repository

Visit the **Examples** page to see pre-built survey templates in JSON format:

- Customer Satisfaction Survey
- Product Feedback Survey
- Employee Engagement Survey
- Market Research Survey

Navigate to `surveyjs-examples.php` to view and copy examples.
