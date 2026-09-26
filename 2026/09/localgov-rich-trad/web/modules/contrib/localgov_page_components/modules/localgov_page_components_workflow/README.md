# LocalGov Page Component Workflow
## Overview
This module introduces an improved workflow for managing Page components with controlled publishing behaviour.

It provides:
- A custom workflow: Page components (page_components)
- A custom field formatter: Page components (smart revisions) (localgov_page_components_workflow_formatter)

The goal is to ensure that changes to components do not appear on the frontend until the parent content is published.

## Installation
Install the module as usual via Drush:

```drush en localgov_page_components_workflow```

or via the Drupal admin interface (Extend).

## Configuration
1. Go to the content type where Page components are used (e.g. Service Page, Homepage).
2. Manage the display of the entity reference field (Page components) and change its formatter to “Page components (smart revisions)”.
3. Apply the workflow to your component entities:
   - Navigate to Configuration > Workflow > Workflows (/admin/config/workflow/workflows).
   - Edit the Page components workflow.
   - Under the "This workflow applies to:" section, click "Select" next to Page component and ensure that "All Page component types" is selected/checked.

## Usage
When editing a component, set its moderation state to Draft before saving. Otherwise, changes will be published immediately on the frontend.
When a node is published all referenced components are automatically published.
The latest component changes will appear on the frontend.

### Important Notes
If a component is saved directly as Published, changes will be visible immediately, regardless of the parent node's current moderation state.

## Alternatives
As an alternative to this module, you can use standard Paragraph fields. Paragraphs are inherently tied to the parent host entity, meaning their revisioning and publishing states are managed out-of-the-box by the parent node without requiring a separate custom entity workflow wrapper.
