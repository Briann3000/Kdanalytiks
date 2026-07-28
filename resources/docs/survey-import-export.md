# Importing & Version Control

KDAnalytiks allows you to import existing questionnaires from external documents and maintain full revision history with built-in version control.

---

## 1. Document Import with AI Question Extraction

If you already have a questionnaire drafted in a Microsoft Word document (.docx) or PDF file, you can import it directly into KDAnalytiks without manually retyping questions.

### How Document Import Works

1. Navigate to **Create Survey** -> **Import Document**.
2. Upload your **Word (.docx)** or **PDF** document.
3. The AI parser extracts question titles, option lists and scale grids automatically.
4. Review the extracted structure in the review drawer.
5. Click **Confirm & Load into Builder** to convert the document into an editable KDAnalytiks survey.

---

## 2. Survey Versioning & Draft Management

KDAnalytiks tracks all changes made to published surveys to prevent data corruption while fieldwork is active.

### Version Control States

- **Draft**: Editing mode. You can add, edit, or delete questions freely.
- **Published (v1.0, v2.0...)**: The live survey version being answered by respondents.
- **Revisions**: If you need to make structural edits to a published survey, KDAnalytiks creates a minor revision (e.g. v1.1) while keeping historical response data aligned.

### Best Practices for Versioning

- Avoid deleting questions from a published survey that already has responses; instead, use display logic or archive the question.
- Use the **Revision History** panel in Survey Settings to compare structural changes between versions.
