<?php
/**
 * SurveyJS JSON Schema Examples
 * These are example survey schemas that can be stored in the database
 * Useful for reference or manual creation
 */

// Example 1: Simple Customer Satisfaction Survey
$example1 = <<<'JSON'
{
    "title": "Customer Satisfaction Survey",
    "description": "We'd like to know how satisfied you are with our service",
    "logoPosition": "right",
    "pages": [
        {
            "name": "page1",
            "elements": [
                {
                    "type": "rating",
                    "name": "satisfaction",
                    "title": "How satisfied are you with our service?",
                    "rateCount": 5,
                    "required": true
                },
                {
                    "type": "checkbox",
                    "name": "aspects",
                    "title": "What aspects did you like most?",
                    "choices": [
                        "Quality",
                        "Service",
                        "Price",
                        "Support"
                    ],
                    "required": true
                },
                {
                    "type": "comment",
                    "name": "feedback",
                    "title": "Additional comments?",
                    "required": false
                }
            ]
        }
    ]
}
JSON;

// Example 2: Product Feedback Survey with Conditional Logic
$example2 = <<<'JSON'
{
    "title": "Product Feedback Survey",
    "description": "Help us improve our products",
    "pages": [
        {
            "name": "page1",
            "elements": [
                {
                    "type": "radiogroup",
                    "name": "productUsage",
                    "title": "Do you currently use our products?",
                    "choices": [
                        "Yes",
                        "No",
                        "Planning to"
                    ],
                    "required": true
                },
                {
                    "type": "rating",
                    "name": "productRating",
                    "title": "How would you rate our product?",
                    "visibleIf": "{productUsage} = 'Yes'",
                    "rateCount": 5,
                    "minRateDescription": "Poor",
                    "maxRateDescription": "Excellent"
                },
                {
                    "type": "checkbox",
                    "name": "improvements",
                    "title": "What improvements would you like?",
                    "visibleIf": "{productRating} <= 3",
                    "choices": [
                        "Better UI",
                        "More features",
                        "Better documentation",
                        "Faster performance"
                    ]
                }
            ]
        }
    ]
}
JSON;

// Example 3: Employee Engagement Survey
$example3 = <<<'JSON'
{
    "title": "Employee Engagement Survey",
    "description": "Annual employee engagement assessment",
    "pages": [
        {
            "name": "Demographics",
            "elements": [
                {
                    "type": "text",
                    "name": "employeeId",
                    "title": "Employee ID",
                    "inputType": "text",
                    "required": true
                },
                {
                    "type": "dropdown",
                    "name": "department",
                    "title": "Department",
                    "choices": [
                        "HR",
                        "IT",
                        "Sales",
                        "Marketing",
                        "Operations"
                    ],
                    "required": true
                },
                {
                    "type": "dropdown",
                    "name": "tenure",
                    "title": "Years of Service",
                    "choices": [
                        "Less than 1 year",
                        "1-3 years",
                        "3-5 years",
                        "5-10 years",
                        "10+ years"
                    ],
                    "required": true
                }
            ]
        },
        {
            "name": "Engagement",
            "elements": [
                {
                    "type": "matrix",
                    "name": "engagement",
                    "title": "Rate your agreement with the following statements:",
                    "columns": [
                        {
                            "value": 1,
                            "text": "Strongly Disagree"
                        },
                        {
                            "value": 2,
                            "text": "Disagree"
                        },
                        {
                            "value": 3,
                            "text": "Neutral"
                        },
                        {
                            "value": 4,
                            "text": "Agree"
                        },
                        {
                            "value": 5,
                            "text": "Strongly Agree"
                        }
                    ],
                    "rows": [
                        "I am satisfied with my job",
                        "I have the tools to do my job effectively",
                        "My manager supports my development",
                        "I understand company goals",
                        "I would recommend this company as a great place to work"
                    ]
                }
            ]
        }
    ]
}
JSON;

// Example 4: Market Research Survey
$example4 = <<<'JSON'
{
    "title": "Market Research Survey",
    "description": "Help us understand your preferences",
    "completeText": "Thank you for your participation!",
    "pages": [
        {
            "name": "Demographics",
            "elements": [
                {
                    "type": "dropdown",
                    "name": "ageGroup",
                    "title": "Age Group",
                    "choices": [
                        "18-25",
                        "26-35",
                        "36-45",
                        "46-55",
                        "56-65",
                        "65+"
                    ],
                    "required": true
                },
                {
                    "type": "radiogroup",
                    "name": "gender",
                    "title": "Gender",
                    "choices": [
                        "Male",
                        "Female",
                        "Other",
                        "Prefer not to say"
                    ]
                }
            ]
        },
        {
            "name": "Preferences",
            "elements": [
                {
                    "type": "ranking",
                    "name": "brands",
                    "title": "Rank these brands by preference",
                    "choices": [
                        "Brand A",
                        "Brand B",
                        "Brand C",
                        "Brand D"
                    ]
                },
                {
                    "type": "matrix",
                    "name": "features",
                    "title": "Rate the importance of these features",
                    "columns": [
                        {
                            "value": 1,
                            "text": "Not Important"
                        },
                        {
                            "value": 2,
                            "text": "Somewhat Important"
                        },
                        {
                            "value": 3,
                            "text": "Very Important"
                        },
                        {
                            "value": 4,
                            "text": "Critical"
                        }
                    ],
                    "rows": [
                        "Quality",
                        "Price",
                        "Design",
                        "Service",
                        "Warranty"
                    ]
                }
            ]
        }
    ]
}
JSON;

?>

<!DOCTYPE html>
<html>
<head>
    <title>SurveyJS JSON Schema Examples</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        .example {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #0066cc;
            background-color: #f9f9f9;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 10px;
        }
        h2 {
            color: #0066cc;
            font-size: 18px;
            margin-top: 25px;
        }
        pre {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        code {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .note {
            background-color: #fffacd;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #ff9900;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>SurveyJS JSON Schema Examples</h1>
    
    <p>This page contains example SurveyJS survey definitions. You can copy these JSON schemas and:</p>
    <ul>
        <li>Paste them directly into the SurveyJS Designer</li>
        <li>Store them in the database's json_schema column</li>
        <li>Use them as templates for new surveys</li>
    </ul>

    <h2>Example 1: Simple Customer Satisfaction Survey</h2>
    <div class="example">
        <p><strong>Use case:</strong> Quick feedback collection</p>
        <p><strong>Features:</strong> Rating, checkboxes, comments</p>
        <p><strong>Estimated completion time:</strong> 2-3 minutes</p>
        <pre><code><?php echo $example1; ?></code></pre>
    </div>

    <h2>Example 2: Product Feedback with Conditional Logic</h2>
    <div class="example">
        <p><strong>Use case:</strong> Adaptive surveys that change based on answers</p>
        <p><strong>Features:</strong> Conditional visibility, branching logic</p>
        <p><strong>Key concept:</strong> visibleIf property shows/hides questions</p>
        <pre><code><?php echo $example2; ?></code></pre>
        <div class="note">
            <strong>Note:</strong> The "improvements" question only appears if user rates product ≤ 3
        </div>
    </div>

    <h2>Example 3: Employee Engagement Survey</h2>
    <div class="example">
        <p><strong>Use case:</strong> Multi-page organizational surveys</p>
        <p><strong>Features:</strong> Multiple pages, matrix questions, demographics</p>
        <p><strong>Page organization:</strong> Demographics → Engagement scales</p>
        <pre><code><?php echo $example3; ?></code></pre>
    </div>

    <h2>Example 4: Market Research Survey</h2>
    <div class="example">
        <p><strong>Use case:</strong> Comprehensive market research</p>
        <p><strong>Features:</strong> Ranking, matrix scales, demographics</p>
        <p><strong>Advanced feature:</strong> Ranking questions prioritize items</p>
        <pre><code><?php echo $example4; ?></code></pre>
    </div>

    <h2>How to Use These Examples</h2>
    <ol>
        <li>Copy the JSON code from any example (starts with { and ends with })</li>
        <li>Go to your survey builder page</li>
        <li>In the SurveyJS Designer, go to "Designer" tab → "JSON Editor"</li>
        <li>Paste the JSON code</li>
        <li>The survey will appear with all the questions</li>
        <li>Customize as needed</li>
    </ol>

    <h2>Key SurveyJS Properties</h2>
    <table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%; margin: 20px 0;">
        <tr style="background-color: #0066cc; color: white;">
            <th>Property</th>
            <th>Description</th>
            <th>Example</th>
        </tr>
        <tr>
            <td>title</td>
            <td>Survey title displayed at top</td>
            <td>"My Survey"</td>
        </tr>
        <tr>
            <td>description</td>
            <td>Survey subtitle/description</td>
            <td>"Please help us improve"</td>
        </tr>
        <tr>
            <td>pages</td>
            <td>Array of survey pages</td>
            <td>[{ name: "page1", elements: [...] }]</td>
        </tr>
        <tr>
            <td>elements</td>
            <td>Questions on a page</td>
            <td>[{ type: "text", name: "q1", ... }]</td>
        </tr>
        <tr>
            <td>type</td>
            <td>Question type (text, radio, checkbox, etc.)</td>
            <td>"radio", "text", "rating"</td>
        </tr>
        <tr>
            <td>name</td>
            <td>Question identifier (for responses)</td>
            <td>"email", "satisfaction"</td>
        </tr>
        <tr>
            <td>title</td>
            <td>Question text displayed to user</td>
            <td>"What is your email?"</td>
        </tr>
        <tr>
            <td>required</td>
            <td>Is question mandatory?</td>
            <td>true or false</td>
        </tr>
        <tr>
            <td>visibleIf</td>
            <td>Show question based on condition</td>
            <td>"{answer1} = 'Yes'"</td>
        </tr>
        <tr>
            <td>choices</td>
            <td>Options for choice questions</td>
            <td>["Option 1", "Option 2"]</td>
        </tr>
    </table>

    <h2>Common Question Types</h2>
    <ul>
        <li><strong>text</strong> - Single line text input</li>
        <li><strong>comment</strong> - Multi-line text area</li>
        <li><strong>email</strong> - Email input with validation</li>
        <li><strong>number</strong> - Numeric input</li>
        <li><strong>radiogroup</strong> - Single choice (radio buttons)</li>
        <li><strong>checkbox</strong> - Multiple choice (checkboxes)</li>
        <li><strong>dropdown</strong> - Select from dropdown</li>
        <li><strong>rating</strong> - Star rating (1-5)</li>
        <li><strong>matrix</strong> - Grid of choices</li>
        <li><strong>ranking</strong> - Rank items by priority</li>
        <li><strong>date</strong> - Date picker</li>
        <li><strong>file</strong> - File upload</li>
    </ul>

    <h2>Need Help?</h2>
    <p>See the documentation files:</p>
    <ul>
        <li><a href="SETUP-SURVEYJS.md">SETUP-SURVEYJS.md</a> - Quick start guide</li>
        <li><a href="SURVEYJS-INTEGRATION.md">SURVEYJS-INTEGRATION.md</a> - Full integration guide</li>
        <li><a href="https://surveyjs.io/form-library/documentation/overview" target="_blank">SurveyJS Official Docs</a></li>
    </ul>

</div>

</body>
</html>