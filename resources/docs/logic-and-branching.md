# Logic & Skip Rules

Create dynamic, adaptive questionnaires using **Logic Rules**, **Display Conditions** and **Variable Piping**.

---

## 1. Skip Logic & Conditional Branching

Skip logic routes respondents to specific pages or questions based on their answers, keeping surveys relevant and concise.

### Example Use Case

If a respondent answers **"No"** to _"Have you used mobile banking in the past 6 months?"_, skip all detailed feature evaluation questions and jump directly to _"Section D: Non-User Feedback"_.

### Setting Up Skip Logic

1. Hover over the question and click **Add Logic Rule**.
2. Define the condition: `IF [Question X] [Equals / Is Not Equal To] [Option Y]`.
3. Choose the action: `THEN Skip To [Page Z / Question Z]` or `THEN End Survey`.

---

## 2. Display Logic

Display logic controls the visibility of individual questions on the same page.

### Example Use Case

Only show a follow-up text box _"Please specify your university department"_ if the respondent selected **"Other"** in the main dropdown.

### Setting Up Display Logic

1. Open the question settings panel.
2. Under **Display Conditions**, select `Show only if...`.
3. Set the trigger condition based on a previous question's selected answer.
