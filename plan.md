# Blogging CMS - Complete Requirements Document (REVISED)

## Project Overview

### Project Name
Multi-language Blogging CMS with Customizable Editorial Workflow

### Description
A comprehensive content management system for blogging with support for multiple independent language sites, customizable editorial workflows, role-based access control, and block-based content editing using Editor.js.

### Key Architecture Decision
**Language Model:** Each language operates as an independent content stream. Posts are created in a specific language and are not translations of each other. Users select their language preference on the frontend to see content in that language.

### Technical Stack
- **Backend:** Laravel 11+
- **Database:** MySQL 8.0+
- **Packages:**
  - Spatie Laravel Permission (Role/Permission management)
  - Spatie Laravel Media Library (Media management)
  - Spatie Laravel Activity Log (Audit trail)
  - Spatie Laravel Sluggable (SEO-friendly URLs)
- **Content Editor:** Editor.js (Block-based editor)
- **Frontend:** (To be determined - Vue.js/React/Blade)

---

## Module Breakdown

## Module 1: User Management & Authentication

### Priority: HIGH (Foundation Module)
### Estimated Effort: 2-3 weeks

### Overview
Complete user management system with authentication, profile management, and role-based access control using Spatie Permission package.

### Features

#### 1.1 User Registration & Authentication
**User Stories:**
- As a visitor, I can register for an account with email verification
- As a user, I can log in with username/email and password
- As a user, I can reset my password via email
- As an admin, I can enable 2FA for sensitive roles (Admin, Developer)

**Acceptance Criteria:**
- [ ] Public registration creates accounts with "User" role only
- [ ] Email verification required before account activation
- [ ] Password must meet requirements: min 8 chars (12 for Admin/Developer), uppercase, lowercase, number, special character
- [ ] Account lockout after 5 failed login attempts (15 min duration)
- [ ] "Remember me" functionality works for 30 days
- [ ] 2FA setup available in user settings (TOTP-based)
- [ ] Password reset link expires after 1 hour
- [ ] Users can see active sessions and revoke them

#### 1.2 User Profile Management
**User Stories:**
- As a user, I can update my profile information (name, bio, avatar, website, social links)
- As a user, I can change my password
- As a user, I can set my timezone and preferences
- As a user, I can upload and crop my avatar image

**Acceptance Criteria:**
- [ ] Profile fields: username (unique), email (unique), first_name, last_name, display_name, bio (500 chars max), website, social_links (JSON)
- [ ] Avatar upload supports JPEG, PNG, WebP (max 5MB)
- [ ] Avatar automatically generates 150x150 (thumb) and 300x300 (medium) conversions
- [ ] Preferences stored as JSON: editor_mode, notifications_email, notifications_in_app, posts_per_page, timezone
- [ ] Email change requires verification of new email
- [ ] Password change requires current password confirmation
- [ ] Activity log tracks profile changes

#### 1.3 User Management (Admin)
**User Stories:**
- As an administrator, I can view all users with filters and search
- As an administrator, I can create users and assign roles
- As an administrator/editor, I can invite users via email
- As an administrator, I can suspend/activate user accounts
- As an administrator, I can view user activity logs

**Acceptance Criteria:**
- [ ] User list with filters: role, status, email_verified, registration date, last login
- [ ] Search by: username, email, display_name
- [ ] Bulk actions: change role, suspend, activate, delete
- [ ] User creation generates temporary password (24hr expiry)
- [ ] Email invitation system with 7-day expiry tokens
- [ ] Invitation pre-assigns role and auto-verifies email
- [ ] Suspended users cannot log in but data preserved
- [ ] User deletion is soft delete (recoverable for 30 days)
- [ ] Activity log shows: login/logout, profile changes, password resets
- [ ] Users created by invitation show "invited_by" relationship

#### 1.4 Role & Permission Management
**User Stories:**
- As an administrator, I can view all roles and their permissions
- As an administrator, I can assign/revoke roles from users
- As an administrator, I can view permission matrix
- As a user, I see only features I have permission to access

**Acceptance Criteria:**
- [ ] Six default roles: Administrator, Editor, Writer, Photographer, Developer, User
- [ ] Roles cannot be deleted if system role (is_system flag)
- [ ] Permission groups: posts, pages, media, categories, tags, comments, users, analytics, settings, system
- [ ] Role assignment tracked in activity log
- [ ] Permission checks on all protected routes and actions
- [ ] Middleware enforces role/permission requirements
- [ ] UI elements hidden based on permissions (@can directive)
- [ ] API returns 403 for unauthorized access attempts

### Technical Requirements
```sql
-- Database Tables Required
users
roles (Spatie)
permissions (Spatie)
model_has_roles (Spatie)
model_has_permissions (Spatie)
role_has_permissions (Spatie)
password_reset_tokens
sessions
```

### Dependencies
- None (Foundation module)

### Testing Requirements
- [ ] Unit tests for User model methods
- [ ] Feature tests for registration/login/logout flows
- [ ] Feature tests for password reset flow
- [ ] Feature tests for email verification
- [ ] Feature tests for role/permission checks
- [ ] Test account lockout mechanism
- [ ] Test 2FA setup and verification
- [ ] Test invitation flow
- [ ] Test user suspension/activation

---

## Module 2: Multi-Language System

### Priority: HIGH (Foundation Module)
### Estimated Effort: 1 week

### Overview
Language configuration system where each language operates independently. Content is created in a specific language rather than being translated.

### Features

#### 2.1 Language Configuration
**User Stories:**
- As an administrator, I can add/edit/delete languages
- As an administrator, I can set default language
- As an administrator, I can activate/deactivate languages
- As a frontend user, I can choose my preferred language to view content

**Acceptance Criteria:**
- [ ] Language fields: name, code (ISO 639-1), locale, direction (ltr/rtl), is_default, is_active, sort_order
- [ ] Only one language can be default
- [ ] Default language cannot be deleted
- [ ] At least one language must remain active
- [ ] Language codes must be unique (e.g., 'en', 'dv')
- [ ] Locales must be unique (e.g., 'en', 'dv')
- [ ] Sort order determines display sequence in language switcher
- [ ] Frontend language switcher shows only active languages
- [ ] User language preference stored in cookie/session
- [ ] RTL languages properly supported in frontend CSS

#### 2.2 Language-Specific Content
**User Stories:**
- As a content creator, I select which language I'm writing in when creating content
- As a content creator, I see only content in my selected language in the admin panel
- As a system, I filter all content queries by selected language

**Acceptance Criteria:**
- [ ] Language selector in admin panel header (persists across pages)
- [ ] Posts, pages, categories, and tags have language_id field
- [ ] Content lists filtered by selected admin language by default
- [ ] Can view all languages with "All Languages" filter option
- [ ] Each content item belongs to exactly one language
- [ ] Language cannot be changed after content creation (prevent orphaned relationships)
- [ ] Language-specific content counts shown in dashboard
- [ ] Categories and tags are language-specific (not shared across languages)

#### 2.3 Shared vs Language-Specific Resources
**User Stories:**
- As a photographer, media I upload is shared across all languages
- As a writer, I can use any media regardless of language
- As a system administrator, users and settings are global, not language-specific

**Acceptance Criteria:**
- [ ] **Shared globally:** Users, Roles, Permissions, Media, Workflow States, Workflow Transitions, Post Types
- [ ] **Language-specific:** Posts, Pages, Categories, Tags, Comments (tied to language-specific posts)
- [ ] Media library shows all uploaded media regardless of language
- [ ] User profiles are global (not duplicated per language)
- [ ] Workflow configurations apply to all languages
- [ ] Analytics can be filtered by language or viewed globally

### Technical Requirements
```sql
-- Database Tables Required
languages

-- Tables with language_id field:
posts
pages  
categories
tags
comments (inherited from post's language)

-- Tables WITHOUT language_id (global):
users
media
workflow_states
workflow_transitions
post_types
```

### Dependencies
- None (Foundation module)

### Testing Requirements
- [ ] Test default language enforcement
- [ ] Test language activation/deactivation
- [ ] Test language-specific content filtering
- [ ] Test RTL language support
- [ ] Test language switcher functionality
- [ ] Test that media is shared across languages
- [ ] Test that users are global across languages

---

## Module 3: Workflow System

### Priority: HIGH (Core Business Logic)
### Estimated Effort: 3-4 weeks

### Overview
Customizable state machine for editorial workflow with configurable states, transitions, permissions, and history tracking.

### Features

#### 3.1 Workflow State Management
**User Stories:**
- As an administrator, I can create/edit/delete workflow states
- As an administrator, I can configure state properties (color, icon, flags)
- As an administrator, I can set which state is initial/published/final
- As a system, I enforce workflow rules based on state configuration

**Acceptance Criteria:**
- [ ] Workflow state fields: name, slug, description, color (hex), icon, is_initial, is_published, is_final, is_active, sort_order
- [ ] Only one state can be initial (auto-assigned to new posts)
- [ ] is_published flag determines if content is publicly visible
- [ ] is_final flag prevents further transitions (terminal state)
- [ ] States can be reordered for visual workflow representation
- [ ] Cannot delete states with posts currently assigned to them
- [ ] Inactive states hidden from UI but preserved in database
- [ ] State color and icon used in UI for visual identification
- [ ] Default states created via seeder: Draft, In Review, In Copydesk, Ready to Publish, Published, Scheduled, Archived, Rejected

#### 3.2 Workflow Transition Configuration
**User Stories:**
- As an administrator, I can define allowed transitions between states
- As an administrator, I can set permission/role requirements per transition
- As an administrator, I can configure conditions that must be met before transition
- As an administrator, I can define automated actions on transition

**Acceptance Criteria:**
- [ ] Transition fields: from_state_id, to_state_id, name, label, description, color, icon
- [ ] Permission requirements: requires_permission (Spatie permission name)
- [ ] Role requirements: allowed_roles (JSON array of role names)
- [ ] Conditions configuration (JSON):
  ```json
  {
    "required_fields": ["title", "content", "featured_image"],
    "min_content_length": 100,
    "require_categories": true,
    "require_featured_image": true
  }
  ```
- [ ] Actions configuration (JSON):
  ```json
  {
    "assign_to": "editor_role",
    "set_published_at": "now",
    "notify_users": ["author", "editor"]
  }
  ```
- [ ] Notification settings: send_notification (boolean), notification_template
- [ ] Approval settings: requires_approval, approval_count
- [ ] can_rollback flag enables transition reversal
- [ ] Unique constraint prevents duplicate transitions (from_state + to_state + name)
- [ ] Transitions can be enabled/disabled without deletion
- [ ] Sort order determines button display order in UI

#### 3.3 Workflow Execution
**User Stories:**
- As a content creator, I see only transitions I have permission to execute
- As a content creator, I am prevented from invalid transitions with clear error messages
- As a content creator, I receive validation errors when conditions not met
- As a user, I can view complete workflow history for any post

**Acceptance Criteria:**
- [ ] Available transitions calculated based on: current state + user permissions + user roles
- [ ] Transition validation checks performed in order:
  1. User has required permission (if specified)
  2. User has one of required roles (if specified)
  3. All conditions met (required fields, content length, etc.)
  4. Approval requirements met (if applicable)
- [ ] Successful transition performs:
  1. Updates post.workflow_state_id
  2. Creates workflow history record
  3. Executes configured actions (assign, set dates, etc.)
  4. Sends notifications (if enabled)
  5. Logs in activity_log table
- [ ] Failed transition returns specific error message indicating which check failed
- [ ] Workflow history shows: from_state, to_state, transition_used, user, timestamp, optional comment
- [ ] Comment field on transition form for notes/feedback (max 1000 chars)
- [ ] Rollback creates reverse history entry with "rollback" flag
- [ ] Transitions displayed as buttons with configured color and icon

#### 3.4 Multi-Approval Workflow
**User Stories:**
- As an administrator, I can configure transitions requiring multiple approvals
- As an approver, I can approve/reject pending transitions
- As a content creator, I see approval status and progress for my submissions

**Acceptance Criteria:**
- [ ] Transitions with requires_approval=true create approval records
- [ ] approval_count specifies number of approvals needed before transition executes
- [ ] Approval records track: post_id, transition_id, approver_id, status (pending/approved/rejected), comment, timestamp
- [ ] Transition automatically executes when approval_count reached
- [ ] Single rejection fails the entire transition
- [ ] Pending approvers notified via email/in-app notification
- [ ] Author notified of each approval/rejection
- [ ] Author notified when transition completes or fails
- [ ] Approval UI shows progress: "2 of 3 approvals" with list of approvers and status
- [ ] Approvers can be specific users or any user with specific role
- [ ] Cannot approve own submission (if author is also approver)
- [ ] Approval requests expire after configurable time (default 7 days)

#### 3.5 Scheduled Transitions
**User Stories:**
- As an editor, I can schedule posts for future publication
- As a system, I automatically publish scheduled posts at specified time
- As an editor, I can view upcoming scheduled publications
- As an editor, I can edit or cancel scheduled publications

**Acceptance Criteria:**
- [ ] scheduled_at field stores publication datetime
- [ ] "Scheduled" workflow state holds posts until publication time
- [ ] Artisan command `workflow:process-scheduled` checks for posts where scheduled_at <= now()
- [ ] Command scheduled to run every minute via Laravel scheduler
- [ ] Automatic transition from Scheduled → Published when time reached
- [ ] Scheduled posts appear in calendar/timeline view
- [ ] Can reschedule by updating scheduled_at (requires permission)
- [ ] Can cancel schedule, returning post to previous state (requires permission)
- [ ] Timezone handling: all scheduled times in UTC, displayed in user's timezone
- [ ] Email notification sent to author when post auto-publishes
- [ ] Failed auto-publish logged with error details

### Technical Requirements
```sql
-- Database Tables Required
workflow_states
workflow_transitions
post_workflow_history
post_workflow_approvals
```

### Dependencies
- Module 1: User Management (for permissions)

### Testing Requirements
- [ ] Unit tests for transition validation logic
- [ ] Unit tests for condition checking
- [ ] Unit tests for action execution
- [ ] Feature tests for workflow execution
- [ ] Test permission enforcement
- [ ] Test role enforcement
- [ ] Test required field validation
- [ ] Test multi-approval flow
- [ ] Test scheduled publication command
- [ ] Test workflow history creation
- [ ] Test rollback functionality
- [ ] Test notification dispatch
- [ ] Test approval expiration

---

## Module 4: Post Types & Custom Fields

### Priority: HIGH (Core Content Structure)
### Estimated Effort: 2 weeks

### Overview
Flexible post type system with custom field definitions supporting different content types (articles, people profiles, locations, videos).

### Features

#### 4.1 Post Type Management
**User Stories:**
- As an administrator, I can create custom post types
- As an administrator, I can define which features each post type supports
- As an administrator, I can configure custom fields per post type
- As a content creator, I select post type when creating new content

**Acceptance Criteria:**
- [ ] Post type fields: name, slug, description, supports_gallery, supports_comments, is_active
- [ ] custom_fields JSON defines field structure (see 4.2)
- [ ] Default post types created via seeder: Article, People, Location, Video
- [ ] Slug auto-generated from name, must be unique
- [ ] Cannot delete post types with existing posts (soft delete or prevent)
- [ ] Inactive post types hidden from "Create Post" dropdown
- [ ] Post type selection at post creation (cannot be changed after)
- [ ] Post type determines available custom fields in editor
- [ ] Icon/color for post type used in UI

#### 4.2 Custom Field Definition
**User Stories:**
- As an administrator, I define custom fields with type, label, validation rules
- As a content creator, I see custom fields based on selected post type
- As a content creator, field validation prevents invalid data entry
- As a content creator, I see helpful field descriptions/placeholders

**Acceptance Criteria:**
- [ ] Custom field structure (JSON):
  ```json
  {
    "key": "field_name",
    "label": "Display Label",
    "type": "text|textarea|number|date|url|email|select|checkbox|radio|json",
    "placeholder": "Placeholder text",
    "help_text": "Helper description",
    "required": true|false,
    "default_value": "default",
    "validation_rules": "max:255|url",
    "options": ["Option 1", "Option 2"], // for select/radio
    "multiple": false // for select
  }
  ```
- [ ] Supported field types:
  - text: Single line text input
  - textarea: Multi-line text input
  - number: Numeric input with min/max
  - date: Date picker
  - url: URL validation
  - email: Email validation
  - select: Dropdown (single or multiple)
  - checkbox: Boolean or multiple choices
  - radio: Single choice from options
  - json: Raw JSON input (admin only)
- [ ] Laravel validation rules applied to custom fields
- [ ] Required fields enforced on save
- [ ] Custom field values stored in post_meta table
- [ ] Field order determined by array order in custom_fields JSON

#### 4.3 Post Meta Storage & Retrieval
**User Stories:**
- As a system, I store custom field values efficiently
- As a developer, I can easily retrieve/update custom field values
- As a developer, I can query posts by custom field values

**Acceptance Criteria:**
- [ ] post_meta table: id, post_id, key, value (LONGTEXT), type, created_at, updated_at
- [ ] Helper methods on Post model:
  - `getMeta(string $key, $default = null)` - Retrieve value with auto-casting
  - `setMeta(string $key, $value, string $type = 'string')` - Store value
  - `deleteMeta(string $key)` - Remove meta field
  - `getAllMeta()` - Get all meta as key-value array
- [ ] Values automatically cast based on type field:
  - string: Return as-is
  - integer: Cast to int
  - boolean: Cast to bool
  - json: Decode to array
  - array: Unserialize
  - date: Return Carbon instance
- [ ] JSON values stored as JSON string, retrieved as array
- [ ] Array values serialized on save, unserialized on retrieve
- [ ] Scope `whereMeta($key, $operator, $value)` for querying by meta
- [ ] Index on (post_id, key) for query performance
- [ ] Relationship eager loading: `Post::with('meta')` loads all meta efficiently

#### 4.4 Default Post Types Configuration

**Article (Standard Blog Post):**
```json
{
  "custom_fields": [],
  "supports_gallery": true,
  "supports_comments": true
}
```

**People (Person Profile/Interview):**
```json
{
  "custom_fields": [
    {
      "key": "full_name",
      "label": "Full Name",
      "type": "text",
      "required": true,
      "placeholder": "John Doe"
    },
    {
      "key": "tagline",
      "label": "Tagline",
      "type": "text",
      "required": false,
      "placeholder": "CEO of Company Inc.",
      "max_length": 100
    },
    {
      "key": "occupation",
      "label": "Occupation",
      "type": "text",
      "required": false
    },
    {
      "key": "date_of_birth",
      "label": "Date of Birth",
      "type": "date",
      "required": false
    },
    {
      "key": "nationality",
      "label": "Nationality",
      "type": "text",
      "required": false
    }
  ],
  "supports_gallery": true,
  "supports_comments": true
}
```

**Location (Place/Destination):**
```json
{
  "custom_fields": [
    {
      "key": "location_name",
      "label": "Location Name",
      "type": "text",
      "required": true
    },
    {
      "key": "country",
      "label": "Country",
      "type": "text",
      "required": true
    },
    {
      "key": "address",
      "label": "Address",
      "type": "textarea",
      "required": false,
      "rows": 3
    },
    {
      "key": "coordinates",
      "label": "Coordinates (Latitude, Longitude)",
      "type": "text",
      "required": false,
      "placeholder": "4.1755, 73.5093",
      "help_text": "Format: latitude, longitude"
    },
    {
      "key": "google_maps_url",
      "label": "Google Maps URL",
      "type": "url",
      "required": false
    }
  ],
  "supports_gallery": true,
  "supports_comments": true
}
```

**Video:**
```json
{
  "custom_fields": [
    {
      "key": "video_url",
      "label": "Video URL",
      "type": "url",
      "required": true,
      "help_text": "YouTube or Vimeo URL"
    },
    {
      "key": "duration",
      "label": "Duration (minutes)",
      "type": "number",
      "required": false,
      "min": 0
    },
    {
      "key": "video_platform",
      "label": "Platform",
      "type": "select",
      "options": ["YouTube", "Vimeo", "Other"],
      "required": false
    }
  ],
  "supports_gallery": false,
  "supports_comments": true
}
```

### Technical Requirements
```sql
-- Database Tables Required
post_types
post_meta
```

### Dependencies
- Module 1: User Management

### Testing Requirements
- [ ] Unit tests for custom field validation
- [ ] Unit tests for getMeta/setMeta helpers
- [ ] Test different field types (text, number, date, url, etc.)
- [ ] Test required field enforcement
- [ ] Test default values
- [ ] Test post type validation
- [ ] Test meta queries (whereMeta)
- [ ] Test data type casting
- [ ] Test JSON storage and retrieval
- [ ] Test field ordering

---

## Module 5: Content Management (Posts)

### Priority: HIGH (Core Feature)
### Estimated Effort: 4-5 weeks

### Overview
Complete post management system with Editor.js integration, language-specific content, SEO, and media management.

### Features

#### 5.1 Post Creation & Editing
**User Stories:**
- As a writer, I can create posts in any active language
- As a writer, I select the language at post creation time
- As a writer, I can edit my draft posts
- As an editor, I can edit any post
- As a content creator, my work is auto-saved

**Acceptance Criteria:**
- [ ] Post fields: post_type_id, user_id (author), language_id, workflow_state_id, slug, title, excerpt, content (Editor.js JSON)
- [ ] Language selection required at post creation (dropdown of active languages)
- [ ] Language cannot be changed after post creation
- [ ] Auto-save functionality runs every 30 seconds
- [ ] Auto-save indicator shows "Saving..." / "Saved at HH:MM"
- [ ] Manual save button available
- [ ] Slug auto-generated from title (transliterated if necessary)
- [ ] Slug manually editable with validation
- [ ] Slug uniqueness validated within language (can have same slug in different languages)
- [ ] SEO fields: meta_title (default to title), meta_description (default to excerpt), meta_keywords (array), canonical_url
- [ ] Settings: is_featured, allow_comments, is_pinned, sort_order
- [ ] Scheduling: scheduled_at field for future publication
- [ ] Engagement metrics: views_count, likes_count, shares_count, comments_count (read-only)
- [ ] Permission checks:
  - Writers: create posts, edit own drafts only
  - Editors: create posts, edit all posts
  - Administrators: create posts, edit all posts, delete posts
- [ ] Revision history via Spatie Activity Log
- [ ] "Duplicate Post" creates new draft copy with "(Copy)" appended to title
- [ ] Post preview generates preview URL with token

#### 5.2 Editor.js Integration
**User Stories:**
- As a content creator, I use a modern block-based editor
- As a content creator, I can add rich content blocks
- As a content creator, my content is saved as structured JSON
- As a content creator, I can insert images inline via upload or URL

**Acceptance Criteria:**
- [ ] Editor.js version 2.28+ integrated
- [ ] Core blocks installed and configured:
  - **Header:** H2, H3, H4 (H1 reserved for title)
  - **Paragraph:** Default text block with inline formatting
  - **List:** Ordered and unordered lists
  - **Image:** Upload or URL with caption, alt text
  - **Quote:** Blockquote with citation
  - **Code:** Syntax-highlighted code blocks
  - **Embed:** YouTube, Vimeo, Twitter, Instagram embeds
  - **Table:** Dynamic table creation
  - **Link Tool:** Rich link previews
  - **Marker:** Highlight text
  - **Inline Code:** Inline code formatting
  - **Delimiter:** Visual separator
  - **Checklist:** Interactive task lists
  - **Raw HTML:** For advanced users (permission-gated)
  - **Warning/Notice:** Alert boxes (info, warning, error)
  - **Attaches:** File attachments
- [ ] Custom blocks to implement:
  - **Gallery:** Multiple image upload with captions and layouts (grid/carousel/masonry)
  - **Video Embed:** Direct YouTube/Vimeo URL input
  - **Button:** Call-to-action button with URL and styling
  - **Alert:** Styled notice boxes (info, success, warning, danger)
  - **Accordion:** Collapsible content sections
  - **Columns:** Two or three column layouts
- [ ] Content stored as JSON in posts.content field (LONGTEXT column)
- [ ] JSON structure validation on save
- [ ] Image upload workflow:
  1. User clicks image upload in editor
  2. AJAX POST to `/api/admin/upload-image`
  3. Laravel stores via Spatie Media Library
  4. Response includes image URL and media_id
  5. Editor.js inserts image block with URL
- [ ] Image upload restrictions:
  - Max size: 10MB
  - Allowed types: JPEG, PNG, WebP, GIF
  - Automatic WebP conversion for optimization
- [ ] Embed validation ensures URLs from allowed domains only
- [ ] Raw HTML block restricted to Administrators and Developers
- [ ] Content word count and reading time displayed
- [ ] Editor toolbar sticky on scroll

#### 5.3 Media Management
**User Stories:**
- As a content creator, I can upload a featured image for my post
- As a content creator, I can create image galleries
- As a content creator, I can attach files (PDFs, documents)
- As a photographer, I upload and manage media in shared library
- As any user, I can browse and insert existing media

**Acceptance Criteria:**
- [ ] **Featured Image Collection:**
  - Single image per post
  - Required for publication (configurable per workflow transition)
  - Image conversions:
    - `thumb`: 300x200 (4:3 ratio)
    - `medium`: 800x600 (4:3 ratio)
    - `large`: 1200x800 (3:2 ratio)
    - `og_image`: 1200x630 (Open Graph)
  - Can be selected from media library or uploaded directly
  - Alt text, caption, and credits fields
  - Crop/focal point selector
- [ ] **Gallery Collection:**
  - Multiple images per post (unlimited)
  - Only available if post_type supports_gallery = true
  - Per-image fields: caption, alt text, credits, sort order
  - Drag-and-drop reordering
  - Image conversions:
    - `thumb`: 300x300 (1:1 ratio)
    - `large`: 1600x1200 (4:3 ratio)
  - Gallery display layouts: grid, carousel, masonry
- [ ] **Attachments Collection:**
  - Any file type allowed
  - Max file size: 20MB
  - Common types: PDF, DOC, DOCX, XLS, XLSX, ZIP
  - Display as download links on frontend
  - Icon based on file type
- [ ] **Media Library Browser:**
  - Modal interface for selecting existing media
  - Grid view with thumbnails
  - Filter by: type (image/document/video), date uploaded, uploader
  - Search by: filename, alt text, caption
  - Sort by: date (newest/oldest), name (A-Z), size
  - Shows media usage: "Used in 3 posts"
  - Bulk select for galleries
  - Upload new media from browser
- [ ] **Delete Protection:**
  - Cannot delete media attached to published posts
  - Warning message: "This media is used in X published posts"
  - Soft delete for media (recoverable)
- [ ] **Permissions:**
  - Photographers: upload, view all, edit all, manage library
  - Writers: upload, view all, use in own posts
  - Editors: upload, view all, edit all
  - Media attribution tracked (uploaded_by user_id)
- [ ] **Image Optimization:**
  - Automatic WebP conversion with JPEG/PNG fallback
  - Strip EXIF data (except copyright if present)
  - Progressive JPEG encoding
  - Compression quality: 85%

#### 5.4 Post Listing & Filtering
**User Stories:**
- As a content creator, I see lists of posts with filters and search
- As a writer, I see only my posts by default
- As an editor, I see all posts
- As any user, I can filter posts by multiple criteria

**Acceptance Criteria:**
- [ ] **Default Views:**
  - Writers: Own posts only
  - Editors/Admins: All posts
  - Can toggle "All Posts" / "My Posts"
- [ ] **Filters:**
  - Workflow state (Draft, In Review, Published, etc.)
  - Language (English, Dhivehi, All)
  - Post type (Article, People, Location, Video, All)
  - Author (dropdown of all writers)
  - Date range (created, published)
  - Categories (if assigned)
  - Tags (if assigned)
  - Featured status (Featured only, Not featured, All)
  - Comment status (Enabled, Disabled, All)
- [ ] **Search:**
  - Search by title (primary match)
  - Search by content (secondary match)
  - Search by excerpt
  - Search by meta fields (for post types with custom fields)
  - Full-text search index for performance
- [ ] **Sorting:**
  - Created date (newest/oldest)
  - Published date (newest/oldest)
  - Updated date (recently edited)
  - Title (A-Z, Z-A)
  - Views (most/least)
  - Comments (most/least)
- [ ] **Table Columns:**
  - Thumbnail (featured image)
  - Title (linked to edit)
  - Author (with avatar)
  - Language (flag icon)
  - Post type (badge)
  - Workflow state (colored badge)
  - Categories (pills)
  - Views/Comments counts
  - Published date
  - Actions (Edit, View, Delete dropdown)
- [ ] **Bulk Actions:**
  - Change workflow state (if permitted)
  - Change author (admins only)
  - Delete (move to trash)
  - Add/remove category
  - Add/remove tag
  - Feature/unfeature
  - Enable/disable comments
- [ ] **Pagination:**
  - Configurable per page: 10, 25, 50, 100
  - User preference saved
  - Total count displayed
  - Load more (infinite scroll) or traditional pagination
- [ ] **Views:**
  - List view (detailed table)
  - Grid view (cards with featured image)
  - Calendar view (for scheduled posts)
  - Toggle between views (preference saved)

#### 5.5 Post Relationships (Optional Linking)
**User Stories:**
- As a content creator, I can optionally link related posts in other languages
- As a frontend user, I see "Also available in" links to related content
- As a content creator, related content suggestions help link same topics

**Acceptance Criteria:**
- [ ] **Related Posts Table:**
  - Junction table: post_relationships
  - Fields: post_id, related_post_id, relationship_type
  - Relationship types: 'translation', 'related', 'updated_version'
- [ ] **Linking Interface:**
  - "Linked Posts" section in post editor
  - Search for posts in other languages
  - Quick filter: "Posts with similar title in other languages"
  - Display linked posts with: language, title, workflow state
  - Remove link button
  - Bidirectional links (if A links to B, B shows link to A)
- [ ] **Frontend Display:**
  - "Also available in [Language]" links below post title
  - Language flag icons
  - Only shows published linked posts
- [ ] **Optional Feature:**
  - Can be disabled per post type
  - Admin setting to enable/disable globally

### Technical Requirements
```sql
-- Database Tables Required
posts
post_meta
media (Spatie)
post_relationships (optional)
```

### Dependencies
- Module 1: User Management
- Module 2: Multi-Language System
- Module 3: Workflow System
- Module 4: Post Types & Custom Fields

### Testing Requirements
- [ ] Feature tests for post CRUD operations
- [ ] Test permission enforcement (writer vs editor access)
- [ ] Test language assignment and immutability
- [ ] Test slug generation and uniqueness
- [ ] Test auto-save functionality
- [ ] Test Editor.js content saving/loading
- [ ] Test image upload workflow
- [ ] Test featured image assignment
- [ ] Test gallery management
- [ ] Test attachment uploads
- [ ] Test media library filtering and search
- [ ] Test media delete protection
- [ ] Test post filtering by all criteria
- [ ] Test search functionality
- [ ] Test bulk actions
- [ ] Test post duplication
- [ ] Test preview generation
- [ ] Test revision history
- [ ] Integration tests with workflow transitions

---

## Module 6: Taxonomy System (Categories & Tags)

### Priority: HIGH (Content Organization)
### Estimated Effort: 2 weeks

### Overview
Language-specific taxonomy system for organizing content with categories (hierarchical) and tags (flat).

### Features

#### 6.1 Category Management
**User Stories:**
- As an editor, I can create hierarchical categories per language
- As an editor, I can organize categories with parent/child relationships
- As a content creator, I assign categories to posts
- As a frontend user, I browse content by category

**Acceptance Criteria:**
- [ ] Category fields: name, slug, description, parent_id, language_id, meta_title, meta_description, color, icon, is_active, sort_order
- [ ] Categories are language-specific (each language has own category tree)
- [ ] Hierarchical structure supports multiple levels (parent → child → grandchild)
- [ ] Slug auto-generated from name, unique within language
- [ ] Cannot delete categories with assigned posts (must reassign first)
- [ ] Can merge categories (reassign all posts to another category)
- [ ] SEO fields for category archive pages
- [ ] Color and icon for UI differentiation
- [ ] Featured image support via Spatie Media
- [ ] sort_order for custom ordering within level
- [ ] Category permissions:
  - Administrators: full CRUD
  - Editors: create, edit, cannot delete (can propose deletion)
  - Writers: view only, can assign to own posts
  - Photographers: view only
- [ ] Cannot create circular relationships (parent → child → parent)
- [ ] Deleting parent reassigns children to grandparent (or null if no grandparent)

#### 6.2 Category Assignment
**User Stories:**
- As a content creator, I assign one or more categories to my post
- As a content creator, I see category hierarchy when selecting
- As a system, I enforce minimum/maximum category requirements

**Acceptance Criteria:**
- [ ] Posts can have multiple categories
- [ ] Category selection shows hierarchical tree with checkboxes
- [ ] Selected categories displayed with full path (Parent → Child)
- [ ] Configurable per workflow transition: require at least 1 category
- [ ] Recommended: 1-3 categories per post (warning if more)
- [ ] Primary category selection (for featured category display)
- [ ] Can only assign categories from post's language
- [ ] Category suggestions based on: content analysis, previous posts, similar posts

#### 6.3 Category Display & Navigation
**User Stories:**
- As a frontend user, I browse posts by category
- As a frontend user, I see category hierarchies in navigation
- As a frontend user, I see post count per category

**Acceptance Criteria:**
- [ ] Category archive page shows all posts in that category
- [ ] Option to include posts from child categories
- [ ] Post count (denormalized or real-time calculated)
- [ ] Category breadcrumbs: Home → Parent → Child
- [ ] Pagination on category archives
- [ ] Category RSS feed
- [ ] Empty categories optionally hidden from navigation

#### 6.4 Tag Management
**User Stories:**
- As a content creator, I create and assign tags to posts
- As an editor, I merge duplicate/similar tags
- As an editor, I manage tag consistency
- As a frontend user, I browse content by tag

**Acceptance Criteria:**
- [ ] Tag fields: name, slug, description, language_id, color, is_active, usage_count (denormalized)
- [ ] Tags are language-specific
- [ ] Flat structure (no hierarchy)
- [ ] Slug auto-generated from name, unique within language
- [ ] Writers can create tags while writing posts
- [ ] Tag suggestions while typing (autocomplete)
- [ ] Tag usage count automatically updated
- [ ] Popular tags widget (top 20 by usage)
- [ ] Tag permissions:
  - Administrators: full CRUD, merge
  - Editors: full CRUD, merge
  - Writers: create, assign to own posts
  - Photographers: view only
- [ ] Cannot delete tags with posts assigned
- [ ] Merge tags: reassign all posts from tag A to tag B, delete A
- [ ] Bulk edit: rename, change color, merge multiple into one
- [ ] Unused tags (usage_count = 0) can be bulk deleted

#### 6.5 Tag Assignment
**User Stories:**
- As a content creator, I assign multiple tags to my post
- As a content creator, I create new tags on-the-fly
- As a content creator, I see suggested tags

**Acceptance Criteria:**
- [ ] Posts can have unlimited tags (practical limit: 10-15)
- [ ] Tag input with autocomplete from existing tags
- [ ] Press Enter or comma to add tag
- [ ] Create new tag inline (no separate form)
- [ ] Tag suggestions based on:
  - Content keywords (NLP/frequency analysis)
  - Previously used tags by author
  - Tags from similar posts
  - Related tags (co-occurrence analysis)
- [ ] Can only assign tags from post's language
- [ ] Display format: pills/chips with remove (×) button
- [ ] Warning if more than 15 tags

#### 6.6 Taxonomy Administration
**User Stories:**
- As an editor, I see taxonomy overview dashboard
- As an editor, I identify and fix taxonomy issues
- As an administrator, I bulk manage categories and tags

**Acceptance Criteria:**
- [ ] **Taxonomy Dashboard:**
  - Total categories per language
  - Total tags per language
  - Unused categories (0 posts)
  - Unused tags (0 posts)
  - Most used categories (top 10)
  - Most used tags (top 20)
  - Recent taxonomy changes
- [ ] **Category Manager:**
  - Tree view of all categories
  - Drag-and-drop to reorganize hierarchy
  - Inline edit name/slug
  - Bulk actions: activate, deactivate, delete
  - Export category structure (JSON/CSV)
  - Import categories (JSON/CSV)
- [ ] **Tag Manager:**
  - List view with usage counts
  - Similar tag detection (fuzzy matching)
  - Merge interface: select multiple, merge into one
  - Bulk actions: delete unused, rename, change color
  - Export tags (JSON/CSV)
  - Import tags (JSON/CSV)
- [ ] **Taxonomy Reports:**
  - Posts without categories
  - Posts without tags
  - Categories with no posts
  - Tags with no posts
  - Duplicate detection (similar names)

### Technical Requirements
```sql
-- Database Tables Required
categories
tags
category_post (pivot)
post_tag (pivot)
```

### Dependencies
- Module 2: Multi-Language System
- Module 5: Content Management (Posts)

### Testing Requirements
- [ ] Test category CRUD operations
- [ ] Test hierarchical category relationships
- [ ] Test circular relationship prevention
- [ ] Test category deletion with posts
- [ ] Test category merging
- [ ] Test tag CRUD operations
- [ ] Test tag creation inline
- [ ] Test tag merging
- [ ] Test tag usage count updates
- [ ] Test autocomplete functionality
- [ ] Test slug uniqueness within language
- [ ] Test permission enforcement
- [ ] Test bulk operations
- [ ] Test import/export functionality

---

## Module 7: Comments System

### Priority: MEDIUM (Community Engagement)
### Estimated Effort: 2 weeks

### Overview
Threaded commenting system with moderation, spam protection, and support for both registered and anonymous users.

### Features

#### 7.1 Comment Submission
**User Stories:**
- As a registered user, I can comment on published posts
- As an anonymous user, I can comment with name/email (if enabled)
- As a user, I can reply to existing comments (threaded)
- As a user, I receive feedback when my comment is pending moderation

**Acceptance Criteria:**
- [ ] Comment fields: post_id, user_id, parent_id, content, status, author_name, author_email, author_website, author_ip, user_agent
- [ ] Comments only on published posts with allow_comments = true
- [ ] Registered users: name/email from profile, optional website
- [ ] Anonymous users (if enabled): required name/email, optional website
- [ ] Comment content: min 3 chars, max 5000 chars
- [ ] Threaded replies: max 3 levels deep
- [ ] Comment status: pending, approved, spam, trashed
- [ ] New comments default to pending (configurable: auto-approve for registered users)
- [ ] Email notification to author when comment posted
- [ ] Email notification to parent commenter on reply
- [ ] Honeypot field for spam detection
- [ ] Rate limiting: 5 comments per 15 minutes per IP
- [ ] Required fields validated with helpful error messages
- [ ] "Preview" before submitting
- [ ] Simple text editor with basic formatting (bold, italic, links)
- [ ] URL auto-linking in comment text
- [ ] Emoji support

#### 7.2 Comment Moderation
**User Stories:**
- As an editor, I moderate pending comments
- As an editor, I approve, spam, or trash comments
- As an editor, I edit comment content for typos/moderation
- As an administrator, I see moderation queue dashboard

**Acceptance Criteria:**
- [ ] **Moderation Queue:**
  - List of pending comments
  - Shows: content (truncated), author, post title, submission time
  - Inline actions: Approve, Spam, Trash, Edit
  - Bulk actions: Approve, Spam, Trash
  - Filter by: status, post, date
  - Search by: content, author name, author email
- [ ] **Comment Status Changes:**
  - Pending → Approved (comment appears on site)
  - Pending/Approved → Spam (hidden, learning for spam filter)
  - Any → Trashed (soft delete, recoverable)
  - Trashed → Approved (restore)
- [ ] **Edit Comment:**
  - Can edit content only
  - Cannot change author/post
  - Edit tracked in activity log with "edited by" metadata
  - Edited comments show "(edited)" badge
- [ ] **Spam Detection:**
  - Akismet integration (optional, configurable)
  - Keyword blacklist (configurable list)
  - Link limit: max 2 links per comment
  - Common spam patterns: viagra, casino, etc.
  - Auto-trash if spam score > 80%
- [ ] **Auto-Approve Rules:**
  - User has X approved comments (default 1)
  - User is registered and verified
  - Whitelist specific users/emails
- [ ] Moderation notification preferences per user
- [ ] Batch actions on old pending comments (approve all, trash all)

#### 7.3 Comment Display & Threading
**User Stories:**
- As a frontend user, I read comments below posts
- As a frontend user, I see threaded conversations
- As a frontend user, I paginate through comments
- As a frontend user, I sort comments by date/votes

**Acceptance Criteria:**
- [ ] Display only approved comments on frontend
- [ ] Threaded display: parent → children indented
- [ ] Show comment count: "X Comments"
- [ ] Pagination: 20 comments per page (configurable)
- [ ] Sort options:
  - Newest first
  - Oldest first
  - Most liked (if voting enabled)
- [ ] Comment metadata: author name (linked if website), date, reply count
- [ ] "Reply" button loads reply form beneath comment
- [ ] Highlight author's replies (special badge)
- [ ] Anchor links to specific comments (#comment-123)
- [ ] "Load more" button for nested replies
- [ ] Avatar support: Gravatar integration based on email
- [ ] Empty state message when no comments

#### 7.4 Comment Voting (Optional)
**User Stories:**
- As a frontend user, I can upvote helpful comments
- As a frontend user, I see vote counts

**Acceptance Criteria:**
- [ ] Upvote only (no downvote to reduce negativity)
- [ ] One vote per user per comment (tracked by user_id or IP)
- [ ] Vote count displayed
- [ ] Votes table: comment_id, user_id, ip_address, created_at
- [ ] Can remove own vote
- [ ] Sort comments by votes
- [ ] Admin can disable voting per post or globally

#### 7.5 Comment Notifications
**User Stories:**
- As a post author, I receive email when someone comments on my post
- As a commenter, I receive email when someone replies to me
- As a user, I can opt out of comment notifications

**Acceptance Criteria:**
- [ ] Notification types:
  - New comment on my post
  - Reply to my comment
  - Comment approved (to original commenter)
- [ ] Email template with:
  - Commenter name
  - Comment excerpt
  - Link to comment
  - Unsubscribe link
- [ ] User preferences: enable/disable each notification type
- [ ] "Subscribe to comments" checkbox on comment form
- [ ] Unsubscribe token in email (one-click unsubscribe)
- [ ] Rate limit: max 10 comment notification emails per hour per user

#### 7.6 Comment Administration
**User Stories:**
- As an editor, I view comment statistics
- As an administrator, I manage comment settings
- As an administrator, I ban users/IPs from commenting

**Acceptance Criteria:**
- [ ] **Comment Statistics:**
  - Total comments (all time, this month)
  - Pending count (requires attention)
  - Spam count (this month)
  - Approval rate
  - Average comments per post
  - Most commented posts (top 10)
  - Most active commenters (top 10)
- [ ] **Comment Settings:**
  - Enable/disable anonymous comments
  - Auto-approve registered users
  - Auto-approve users with X approved comments
  - Enable/disable comment voting
  - Max comment depth (threading levels)
  - Comments per page
  - Akismet API key
  - Spam keyword blacklist
  - Enable/disable email notifications
- [ ] **Ban Management:**
  - Ban by email address
  - Ban by IP address
  - Ban by username (for registered users)
  - Ban list table with reason and date
  - Remove ban (unban)
  - Banned users see message: "You are not allowed to comment"
- [ ] **Bulk Moderation:**
  - Select all pending
  - Approve all from user
  - Trash all spam older than 30 days
  - Delete trashed comments older than 90 days

### Technical Requirements
```sql
-- Database Tables Required
comments
comment_votes (optional)
comment_bans
```

### Dependencies
- Module 5: Content Management (Posts)
- Module 1: User Management

### Testing Requirements
- [ ] Test comment submission (registered and anonymous)
- [ ] Test threaded replies
- [ ] Test comment moderation flow
- [ ] Test spam detection
- [ ] Test auto-approve rules
- [ ] Test status changes
- [ ] Test editing comments
- [ ] Test voting functionality
- [ ] Test notification delivery
- [ ] Test unsubscribe functionality
- [ ] Test ban enforcement
- [ ] Test rate limiting
- [ ] Test bulk actions

---

## Module 8: Pages System

### Priority: MEDIUM (Static Content)
### Estimated Effort: 1-2 weeks

### Overview
Static page management for non-blog content (About, Contact, Terms, etc.) with template support and workflow integration.

### Features

#### 8.1 Page Management
**User Stories:**
- As an editor, I can create static pages
- As an editor, I can organize pages hierarchically
- As an editor, I can assign templates to pages
- As a developer, I can create custom page templates

**Acceptance Criteria:**
- [ ] Page fields: user_id, parent_id, language_id, workflow_state_id, slug, title, content (Editor.js JSON), template, meta_title, meta_description, is_active, sort_order
- [ ] Pages use same workflow system as posts
- [ ] Pages are language-specific (separate pages per language)
- [ ] Hierarchical structure: parent → child pages
- [ ] Slug auto-generated from title, unique within language
- [ ] Full path generated from hierarchy: /parent/child/grandchild
- [ ] Editor.js for content (same as posts)
- [ ] Template selection: default, landing, contact, custom
- [ ] Template determines layout and available sections
- [ ] Featured image support (optional per template)
- [ ] SEO fields for meta tags
- [ ] Page permissions:
  - Administrators: full CRUD
  - Editors: full CRUD
  - Writers: limited (if granted permission)
  - Developers: create/edit (for technical pages)

#### 8.2 Page Templates
**User Stories:**
- As a developer, I create custom page templates
- As an editor, I select appropriate template for page purpose
- As a system, I render pages with correct template

**Acceptance Criteria:**
- [ ] **Default Template:**
  - Standard page layout
  - Title, content, optional featured image
  - Sidebar optional
- [ ] **Landing Page Template:**
  - Hero section
  - Feature sections
  - Call-to-action areas
  - No sidebar
  - Custom CSS/JS allowed
- [ ] **Contact Template:**
  - Contact form integration
  - Location map (if coordinates provided)
  - Contact information sections
- [ ] **Full Width Template:**
  - No sidebar
  - Full width content area
- [ ] Templates stored as Blade files: `resources/views/templates/pages/{template-name}.blade.php`
- [ ] Template metadata defined in code:
  ```php
  [
    'name' => 'Landing Page',
    'slug' => 'landing',
    'description' => 'Full-width landing page with hero',
    'supports_sidebar' => false,
    'custom_fields' => [...]
  ]
  ```
- [ ] Custom fields per template (similar to post types)
- [ ] Template can define required blocks/sections

#### 8.3 Page Navigation
**User Stories:**
- As an administrator, I manage main navigation menu
- As an administrator, I add pages to menu in custom order
- As a frontend user, I navigate pages via menus

**Acceptance Criteria:**
- [ ] Menu builder interface:
  - Add pages, categories, custom links, external URLs
  - Drag-and-drop to reorder
  - Nested menu items (up to 3 levels)
  - Menu item options: label override, CSS class, target (_blank)
- [ ] Multiple menus: header, footer, sidebar
- [ ] Menu items table: menu_id, menuable_type, menuable_id, parent_id, label, url, sort_order
- [ ] Active menu item highlighted based on current page
- [ ] Breadcrumb navigation auto-generated from page hierarchy

#### 8.4 Special Pages
**User Stories:**
- As an administrator, I designate special pages (homepage, privacy, terms)
- As a system, I route special pages correctly

**Acceptance Criteria:**
- [ ] Settings for special pages:
  - Homepage: select page or "latest posts"
  - Privacy Policy page
  - Terms of Service page
  - 404 Not Found page
  - Search Results page
- [ ] Reserved slugs: home, search, admin (cannot be used)
- [ ] Special pages have designated routes
- [ ] Legal pages linked in footer automatically

### Technical Requirements
```sql
-- Database Tables Required
pages
menus
menu_items
```

### Dependencies
- Module 2: Multi-Language System
- Module 3: Workflow System
- Module 5: Editor.js integration

### Testing Requirements
- [ ] Test page CRUD operations
- [ ] Test hierarchical page relationships
- [ ] Test template assignment
- [ ] Test slug generation and uniqueness
- [ ] Test workflow integration
- [ ] Test menu builder functionality
- [ ] Test special page designation
- [ ] Test breadcrumb generation

---

## Module 9: Media Library

### Priority: HIGH (Supporting Module)
### Estimated Effort: 2 weeks

### Overview
Centralized media management system using Spatie Media Library for shared images, documents, and files across all languages.

### Features

#### 9.1 Media Upload
**User Stories:**
- As any content creator, I can upload media files
- As a photographer, I can batch upload multiple images
- As a user, I can drag-and-drop files to upload
- As a user, I receive progress indicators during upload

**Acceptance Criteria:**
- [ ] Upload methods:
  - Click to browse and select
  - Drag-and-drop onto upload area
  - Paste from clipboard (Ctrl+V)
  - URL import (fetch from external URL)
- [ ] Supported file types:
  - Images: JPEG, PNG, GIF, WebP, SVG
  - Documents: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX
  - Archives: ZIP, RAR (if needed)
  - Videos: MP4, MOV (stored, not transcoded)
- [ ] File size limits:
  - Images: 10MB
  - Documents: 20MB
  - Videos: 100MB
- [ ] Batch upload up to 50 files simultaneously
- [ ] Upload queue with progress bars
- [ ] Pause/resume uploads (large files)
- [ ] Error handling: show failed uploads with reason
- [ ] Auto-retry failed uploads (3 attempts)
- [ ] Duplicate detection: warn if same filename exists
- [ ] Metadata extraction:
  - Images: dimensions, EXIF data (camera, location, etc.)
  - Documents: page count, author
  - Videos: duration, resolution
- [ ] Who uploaded tracked (user_id)

#### 9.2 Media Organization
**User Stories:**
- As a photographer, I organize media into folders/collections
- As a user, I tag media for easy searching
- As a user, I filter media by type, date, uploader

**Acceptance Criteria:**
- [ ] Collections feature:
  - Create named collections (e.g., "Summer 2024", "Product Photos")
  - Add media to collections
  - Media can be in multiple collections
  - Collection thumbnail (first image or custom)
- [ ] Media metadata fields:
  - Title (auto-filled from filename, editable)
  - Alt text (for images, important for SEO/accessibility)
  - Caption
  - Description (longer text)
  - Credits/Attribution
  - Copyright notice
  - Tags (searchable keywords)
- [ ] Filters:
  - Type: All, Images, Documents, Videos
  - Date uploaded: Today, This week, This month, Date range
  - Uploaded by: User dropdown
  - Collection: Collection dropdown
  - Usage: Used in posts, Unused, All
- [ ] Sorting:
  - Date uploaded (newest/oldest)
  - Filename (A-Z, Z-A)
  - File size (largest/smallest)
  - Most used (usage count)
- [ ] View modes:
  - Grid view (thumbnails)
  - List view (details)
  - Detail view (single item with full info)

#### 9.3 Media Editing
**User Stories:**
- As a user, I can edit image properties
- As a user, I can crop and resize images
- As a user, I can replace media files
- As a photographer, I can perform basic image adjustments

**Acceptance Criteria:**
- [ ] Edit metadata: title, alt text, caption, description, credits
- [ ] Image editor (browser-based):
  - Crop with aspect ratio presets (16:9, 4:3, 1:1, free)
  - Rotate (90°, 180°, 270°)
  - Flip (horizontal, vertical)
  - Resize (specify dimensions or percentage)
  - Filters: Grayscale, Sepia, Brightness, Contrast, Saturation
  - Focal point selector (for smart cropping)
- [ ] Replace file: upload new file, keeps same media ID and associations
- [ ] Regenerate conversions: re-process all image sizes
- [ ] Edit history: track changes with "Edited by" and timestamp
- [ ] Non-destructive editing: original file preserved
- [ ] Save edited version as new media item (optional)

#### 9.4 Media Usage Tracking
**User Stories:**
- As a user, I see where media is being used
- As a system, I prevent deletion of in-use media
- As an administrator, I identify unused media

**Acceptance Criteria:**
- [ ] Usage tracking shows:
  - Posts using this media (title, status, language)
  - Pages using this media
  - User avatars (if used)
  - Count: "Used in X posts, Y pages"
- [ ] Click usage to navigate to content
- [ ] Delete protection:
  - Cannot delete media used in published content
  - Can delete media used only in drafts (with confirmation)
  - Warning message lists all usage
- [ ] Unused media report:
  - List media with 0 usage
  - Filter by upload date (e.g., unused and older than 30 days)
  - Bulk delete unused media
- [ ] Usage denormalized for performance (usage_count column)
- [ ] Usage count updated on post publish/unpublish

#### 9.5 Media Library Interface
**User Stories:**
- As a user, I search media quickly
- As a user, I select media for posts/pages
- As a user, I preview media before selecting

**Acceptance Criteria:**
- [ ] **Library Browser (Modal):**
  - Opens from: Featured image selector, Gallery, Content blocks
  - Grid view with thumbnails
  - Multi-select mode (for galleries)
  - Search bar (searches: filename, alt text, caption, tags)
  - Filters panel (type, date, uploader, collections)
  - Sort dropdown
  - Upload new button (inline upload)
  - Select button (or double-click to select)
  - Preview panel: shows full image, metadata, usage
- [ ] **Standalone Media Library Page:**
  - Full media management interface
  - Bulk actions: Delete, Add to collection, Edit metadata, Download
  - Detail view: large preview, full metadata, edit form
  - Share media: generate public URL (if enabled)
  - Download original file
- [ ] **Search:**
  - Instant search as you type
  - Search across: filename, alt text, caption, description, tags
  - Advanced search: combine filters and search
  - Recently searched terms (autocomplete)
- [ ] **Performance:**
  - Lazy loading (load more as scroll)
  - Thumbnail caching
  - Pagination: 50 items per page
  - Infinite scroll option

#### 9.6 Media Storage & Optimization
**User Stories:**
- As a system, I optimize uploaded images automatically
- As a system, I store media efficiently
- As an administrator, I monitor storage usage

**Acceptance Criteria:**
- [ ] Storage configuration:
  - Local storage: `storage/app/public/media`
  - Cloud storage: S3, DigitalOcean Spaces, GCS (via Spatie config)
  - CDN integration for delivery
- [ ] Image optimization:
  - WebP conversion with JPEG/PNG fallback
  - Progressive JPEG encoding
  - Strip unnecessary EXIF (keep copyright)
  - Compression: quality 85% for JPEG, 90% for PNG
- [ ] Responsive images:
  - Generate multiple sizes: thumb, small, medium, large, original
  - Custom conversions per media collection
- [ ] Storage statistics:
  - Total storage used (GB)
  - By media type breakdown
  - Growth over time (chart)
  - Storage limit warnings (if quota set)
- [ ] Cleanup tools:
  - Find and delete duplicate files (same hash)
  - Delete orphaned files (no database record)
  - Delete media older than X days with 0 usage

### Technical Requirements
```sql
-- Database Tables Required
media (Spatie table)
media_collections (custom, optional)
media_collection_media (pivot, optional)
```

### Dependencies
- Module 1: User Management
- Spatie Media Library package

### Testing Requirements
- [ ] Test file uploads (single and batch)
- [ ] Test drag-and-drop upload
- [ ] Test file type validation
- [ ] Test file size limits
- [ ] Test duplicate detection
- [ ] Test metadata extraction
- [ ] Test image editing operations
- [ ] Test usage tracking
- [ ] Test delete protection
- [ ] Test search functionality
- [ ] Test filters and sorting
- [ ] Test media selection in posts
- [ ] Test image optimization
- [ ] Test responsive image generation
- [ ] Test storage statistics

---

## Module 10: SEO & Analytics

### Priority: MEDIUM
### Estimated Effort: 2 weeks

### Overview
Built-in SEO tools, XML sitemaps, structured data, and analytics integration for tracking content performance.

### Features

#### 10.1 SEO Configuration
**User Stories:**
- As an administrator, I configure site-wide SEO settings
- As a content creator, I optimize individual posts for SEO
- As a system, I generate meta tags automatically

**Acceptance Criteria:**
- [ ] **Global SEO Settings:**
  - Site title and tagline
  - Meta description default
  - Default Open Graph image
  - Twitter Card settings
  - Facebook App ID
  - Google Analytics ID
  - Google Search Console verification code
  - Bing Webmaster verification code
- [ ] **Per-Content SEO:**
  - Meta title (default: content title, max 60 chars)
  - Meta description (default: excerpt, max 160 chars)
  - Focus keyword (optional)
  - Canonical URL (auto-generated, editable)
  - Robots meta: index/noindex, follow/nofollow
  - Open Graph tags (og:title, og:description, og:image)
  - Twitter Card tags
- [ ] **SEO Analysis:**
  - Keyword density checker
  - Readability score (Flesch-Kincaid)
  - Title length indicator (green if 50-60 chars)
  - Description length indicator (green if 150-160 chars)
  - Image alt text check (warn if missing)
  - Internal link suggestions
  - External link check
- [ ] **Structured Data:**
  - Article schema (JSON-LD)
  - Breadcrumb schema
  - Person schema (for author)
  - Organization schema
  - WebSite schema with site search
  - Auto-generated, injected in <head>

#### 10.2 XML Sitemap
**User Stories:**
- As a system, I generate XML sitemaps automatically
- As search engines, I discover all published content
- As an administrator, I control what's included in sitemap

**Acceptance Criteria:**
- [ ] Sitemap generation:
  - Main sitemap: `/sitemap.xml` (sitemap index)
  - Post sitemap: `/post-sitemap.xml`
  - Page sitemap: `/page-sitemap.xml`
  - Category sitemap: `/category-sitemap.xml`
  - Tag sitemap: `/tag-sitemap.xml`
  - Per language: `/sitemap-en.xml`, `/sitemap-dv.xml`
- [ ] Sitemap content:
  - Only published content
  - URLs with: loc, lastmod, changefreq, priority
  - Images in posts included with image tags
- [ ] Sitemap settings:
  - Enable/disable per content type
  - Set priorities (e.g., homepage: 1.0, posts: 0.8)
  - Set change frequencies
  - Exclude specific posts/pages
- [ ] Auto-update: regenerate on content publish/update
- [ ] Manual regenerate button in admin
- [ ] Sitemap cached for performance (1 hour TTL)
- [ ] Submit to Google Search Console (manual setup)

#### 10.3 Robots.txt
**User Stories:**
- As an administrator, I configure robots.txt
- As search engines, I respect crawl directives

**Acceptance Criteria:**
- [ ] Dynamic robots.txt at `/robots.txt`
- [ ] Default rules:
  ```
  User-agent: *
  Disallow: /admin
  Disallow: /api
  Allow: /
  Sitemap: https://example.com/sitemap.xml
  ```
- [ ] Admin interface to edit rules
- [ ] Validation to prevent blocking entire site accidentally
- [ ] Per-environment: dev environment disallows all

#### 10.4 Analytics Integration
**User Stories:**
- As an administrator, I integrate Google Analytics
- As an editor, I view content performance analytics
- As a writer, I see stats for my own posts

**Acceptance Criteria:**
- [ ] **Google Analytics:**
  - GA4 tracking code injection
  - Track page views, sessions, bounce rate
  - Track user behavior (scroll depth, time on page)
  - Event tracking: comments, shares, downloads
- [ ] **Built-in Analytics:**
  - Page view counter per post/page
  - Track: IP (hashed), user agent, referrer, timestamp
  - Daily view counts (denormalized for performance)
  - Real-time visitors count
- [ ] **Analytics Dashboard:**
  - Overview: total views, visitors, top posts (7/30/90 days)
  - Chart: views over time
  - Traffic sources: direct, search, social, referral
  - Top performing content (10 items)
  - Top search queries (if Search Console connected)
  - Geographic distribution (countries)
  - Device breakdown (mobile, desktop, tablet)
- [ ] **Per-Post Analytics:**
  - Total views (all time, this month)
  - Views chart over time
  - Average time on page
  - Bounce rate
  - Traffic sources for this post
  - Referrer URLs
- [ ] **Permission-based access:**
  - Writers: see own posts only
  - Editors: see all posts
  - Administrators: see all + system analytics

#### 10.5 Search Engine Indexing
**User Stories:**
- As an administrator, I control search engine indexing
- As a content creator, I prevent indexing of specific content
- As a system, I notify search engines of updates

**Acceptance Criteria:**
- [ ] Per-content robots meta tags
- [ ] Global setting: discourage search engines (entire site)
- [ ] Auto-submit to IndexNow API on content publish
- [ ] Track when content was last indexed (external service integration)
- [ ] Noindex settings:
  - Specific posts/pages
  - All drafts (automatic)
  - Category/tag archives (optional)
  - Author archives (optional)

### Technical Requirements
```sql
-- Database Tables Required
analytics_page_views (optional for built-in analytics)
seo_settings (JSON config)
```

### Dependencies
- Module 5: Content Management
- Module 6: Taxonomy System
- Module 8: Pages System

### Testing Requirements
- [ ] Test meta tag generation
- [ ] Test Open Graph tags
- [ ] Test Twitter Card tags
- [ ] Test structured data validation (Google Rich Results Test)
- [ ] Test sitemap generation
- [ ] Test sitemap index
- [ ] Test robots.txt
- [ ] Test analytics tracking code injection
- [ ] Test view counter
- [ ] Test IndexNow submission

---

## Module 11: Dashboard & Analytics

### Priority: MEDIUM
### Estimated Effort: 1-2 weeks

### Overview
Administrative dashboard providing overview of content, users, and site statistics with role-based customization.

### Features

#### 11.1 Dashboard Overview
**User Stories:**
- As any user, I see a personalized dashboard on login
- As a writer, I see my content stats and pending tasks
- As an editor, I see pending reviews and site overview
- As an administrator, I see comprehensive system stats

**Acceptance Criteria:**
- [ ] **Writer Dashboard:**
  - My posts count: draft, in review, published
  - Recent posts (5 items): title, status, last edited
  - My post performance: total views, avg views per post
  - Pending feedback/comments on my posts
  - Quick actions: Create new post, View all my posts
- [ ] **Editor Dashboard:**
  - Content overview: total posts, pages per language
  - Pending reviews count (clickable)
  - Recent posts (all authors): title, author, status, date
  - Content calendar: upcoming scheduled posts
  - Activity feed: recent user actions
  - Quick actions: Review pending, Create post, Manage users
- [ ] **Administrator Dashboard:**
  - Site statistics:
    - Total posts/pages (all languages)
    - Total users (per role breakdown)
    - Total media files (storage used)
    - Comments (approved, pending, spam)
  - Traffic overview (if analytics enabled):
    - Views today, this week, this month
    - Chart: traffic over last 30 days
  - System health:
    - Laravel version
    - PHP version
    - Database size
    - Cache status
    - Queue status
  - Recent activity log (10 items)
  - Pending tasks:
    - Posts in copydesk
    - Comments to moderate
    - User invitations pending
  - Quick actions: All admin functions
- [ ] **Photographer Dashboard:**
  - Media stats: total uploads, storage used
  - Recent uploads (thumbnails)
  - Most used images (top 10)
  - Quick action: Upload media
- [ ] **User (Frontend) Dashboard:**
  - My comments (recent 5)
  - Saved/bookmarked posts
  - My profile summary
  - Quick action: Edit profile

#### 11.2 Content Calendar
**User Stories:**
- As an editor, I view scheduled posts on a calendar
- As an editor, I plan content publication schedule
- As a team, we coordinate publication timing

**Acceptance Criteria:**
- [ ] Calendar view: month, week, day
- [ ] Show scheduled posts with title and time
- [ ] Color-coded by post type or category
- [ ] Click post to edit
- [ ] Drag-and-drop to reschedule
- [ ] Filter by: language, post type, author, category
- [ ] Show published posts (past dates) in different color
- [ ] Today highlighted
- [ ] Export to .ics file (import into Google Calendar, etc.)

#### 11.3 Activity Log
**User Stories:**
- As an administrator, I view all user activity
- As an editor, I see content changes
- As a system, I maintain audit trail

**Acceptance Criteria:**
- [ ] Activity log powered by Spatie Activity Log
- [ ] Tracked events:
  - User login/logout
  - Content create/update/delete (posts, pages)
  - Workflow state changes
  - Media upload/delete
  - User role changes
  - Settings changes
  - Comment moderation
- [ ] Log entry contains:
  - Timestamp
  - User (causer)
  - Action description
  - Subject (content affected)
  - Old/new values (for updates)
  - IP address
- [ ] Filters:
  - Date range
  - User
  - Event type
  - Subject type (posts, users, etc.)
- [ ] Search by description or subject
- [ ] Export to CSV
- [ ] Auto-delete logs older than 90 days (configurable)

#### 11.4 Reports
**User Stories:**
- As an administrator, I generate reports on content and users
- As an editor, I track content performance
- As a manager, I export data for external analysis

**Acceptance Criteria:**
- [ ] **Content Reports:**
  - Posts per author (date range, language filter)
  - Posts per category (breakdown)
  - Posts per workflow state (current snapshot)
  - Publication frequency (posts per day/week/month)
  - Content growth over time (chart)
- [ ] **User Reports:**
  - User registrations over time
  - Active users (logged in last 30 days)
  - Users per role
  - Most prolific authors (by post count)
- [ ] **Performance Reports:**
  - Top posts by views (all time, this month)
  - Top categories by traffic
  - Top tags by usage
  - Comments per post average
  - Engagement rate (views to comments ratio)
- [ ] **Export Options:**
  - PDF (formatted report)
  - CSV (raw data)
  - Excel (.xlsx)
- [ ] **Scheduled Reports:**
  - Email daily/weekly/monthly reports
  - Configurable recipients
  - Choose which metrics to include

### Technical Requirements
```sql
-- Database Tables Required
activity_log (Spatie)
dashboard_widgets (optional, for customization)
```

### Dependencies
- Module 1: User Management
- Module 5: Content Management
- Module 10: Analytics

### Testing Requirements
- [ ] Test dashboard data accuracy
- [ ] Test role-based dashboard content
- [ ] Test calendar functionality
- [ ] Test drag-and-drop rescheduling
- [ ] Test activity log filtering
- [ ] Test report generation
- [ ] Test export functionality
- [ ] Test scheduled report emails

---

## Module 12: Settings & Configuration

### Priority: MEDIUM
### Estimated Effort: 1 week

### Overview
Comprehensive settings management for site-wide configurations, preferences, and integrations.

### Features

#### 12.1 General Settings
**User Stories:**
- As an administrator, I configure basic site information
- As an administrator, I set site-wide defaults

**Acceptance Criteria:**
- [ ] **Site Identity:**
  - Site title
  - Tagline
  - Site logo (upload)
  - Favicon (upload)
  - Admin email address
- [ ] **Regional Settings:**
  - Timezone
  - Date format (Y-m-d, d/m/Y, m/d/Y, custom)
  - Time format (24h, 12h)
  - First day of week (Sunday/Monday)
  - Currency (if needed for future e-commerce)
- [ ] **Content Settings:**
  - Posts per page (frontend)
  - Default post type
  - Default workflow state for new posts
  - Enable/disable comments globally
  - Comment moderation (auto-approve, always moderate)
  - Allow anonymous comments (yes/no)
  - WYSIWYG editor default mode (visual/markdown)
- [ ] Settings stored in database (settings table or config cache)
- [ ] Setting helper: `setting('site.title')` retrieves value
- [ ] Validation on all settings fields

#### 12.2 Media Settings
**User Stories:**
- As an administrator, I configure media upload rules
- As an administrator, I set image sizes and quality

**Acceptance Criteria:**
- [ ] **Upload Limits:**
  - Max file size per user role
  - Allowed file types (MIME types)
  - Max uploads per request
- [ ] **Image Processing:**
  - JPEG quality (0-100)
  - PNG compression level (0-9)
  - Enable/disable WebP conversion
  - Enable/disable progressive JPEG
  - Strip EXIF data (yes/no, except copyright)
- [ ] **Image Sizes:**
  - Configure custom image sizes (name, width, height, crop)
  - Default conversions (thumb, medium, large)
- [ ] **Storage:**
  - Storage driver (local, s3, spaces)
  - S3 credentials (if S3 selected)
  - CDN URL (optional)
  - Storage quota warning threshold

#### 12.3 Email Settings
**User Stories:**
- As an administrator, I configure email delivery
- As an administrator, I customize email templates

**Acceptance Criteria:**
- [ ] **SMTP Configuration:**
  - Mail driver (smtp, sendmail, mailgun, ses)
  - SMTP host, port, encryption (TLS/SSL)
  - SMTP username, password
  - From address and name
  - Test email button (send test to admin)
- [ ] **Email Templates:**
  - Welcome email (new user registration)
  - Email verification
  - Password reset
  - Invitation email
  - Comment notification
  - Post published notification
  - Workflow transition notification
- [ ] **Template Editor:**
  - Subject line
  - Body content (HTML editor)
  - Available variables ({{user_name}}, {{post_title}}, etc.)
  - Preview before saving
  - Reset to default template
- [ ] **Notification Settings:**
  - Enable/disable each notification type
  - Batch notifications (digest instead of individual emails)

#### 12.4 Integration Settings
**User Stories:**
- As an administrator, I integrate third-party services
- As an administrator, I manage API keys securely

**Acceptance Criteria:**
- [ ] **Analytics:**
  - Google Analytics 4 tracking ID
  - Google Tag Manager ID
  - Facebook Pixel ID
- [ ] **SEO Tools:**
  - Google Search Console verification code
  - Bing Webmaster verification code
- [ ] **Social Media:**
  - Facebook App ID (for social login/sharing)
  - Twitter API keys (for Twitter cards)
  - OpenGraph default image
- [ ] **Content Delivery:**
  - CDN URL
  - CloudFlare integration (API key)
- [ ] **Spam Protection:**
  - Akismet API key
  - reCAPTCHA site key and secret
- [ ] **Search:**
  - Enable/disable site search
  - Algolia credentials (if used)
- [ ] **API keys masked in UI (show only last 4 characters)
- [ ] Secure storage (encrypted in database or .env)

#### 12.5 Permalink Settings
**User Stories:**
- As an administrator, I configure URL structure
- As an administrator, I set custom post type slugs

**Acceptance Criteria:**
- [ ] **Post URL Structure:**
  - Options:
    - `/post-title` (plain)
    - `/2024/05/post-title` (date-based)
    - `/category/post-title` (category-based)
    - `/author/post-title` (author-based)
    - `/:language/post-title` (language prefix)
    - Custom structure with variables
- [ ] **Custom Post Type Slugs:**
  - Article: `/article` or custom
  - People: `/people` or custom
  - Location: `/location` or custom
  - Video: `/video` or custom
- [ ] **Taxonomy Slugs:**
  - Category base: `/category` or custom
  - Tag base: `/tag` or custom
- [ ] **Page URLs:**
  - Use full path (`/parent/child`) or page ID (`/page/123`)
- [ ] Changing permalinks shows warning about redirects
- [ ] Option to generate 301 redirects for old URLs

#### 12.6 Advanced Settings
**User Stories:**
- As a developer, I access advanced system configurations
- As an administrator, I manage maintenance mode

**Acceptance Criteria:**
- [ ] **Maintenance Mode:**
  - Enable/disable site maintenance
  - Custom maintenance message
  - Whitelist IPs (admin can still access)
  - Estimated downtime
- [ ] **Performance:**
  - Enable/disable cache
  - Cache lifetime (minutes)
  - Clear cache button (all, views, config, routes)
  - Enable/disable query log (dev only)
- [ ] **Security:**
  - Force HTTPS (yes/no)
  - Enable 2FA for all users (enforce)
  - Session lifetime (minutes)
  - Password expiry days (0 = never)
  - Failed login attempts before lockout
- [ ] **Developer:**
  - Enable debug mode (dev environment only)
  - API rate limiting (requests per minute)
  - Enable/disable API
  - Allowed CORS origins

### Technical Requirements
```sql
-- Database Tables Required
settings
```

### Dependencies
- Module 1: User Management

### Testing Requirements
- [ ] Test settings save/retrieve
- [ ] Test email configuration and test send
- [ ] Test SMTP connection
- [ ] Test email template rendering
- [ ] Test permalink structure changes
- [ ] Test maintenance mode
- [ ] Test cache clearing
- [ ] Test settings validation

---

## Module 13: API (Optional but Recommended)

### Priority: LOW-MEDIUM
### Estimated Effort: 2-3 weeks

### Overview
RESTful API for headless CMS usage, mobile apps, or third-party integrations. Follows Laravel API resource conventions.

### Features

#### 13.1 Authentication
**User Stories:**
- As a developer, I authenticate API requests
- As a mobile app, I obtain access tokens

**Acceptance Criteria:**
- [ ] Laravel Sanctum for API authentication
- [ ] Login endpoint: `POST /api/auth/login` returns token
- [ ] Logout endpoint: `POST /api/auth/logout` revokes token
- [ ] Token refresh endpoint (if needed)
- [ ] Rate limiting: 60 requests per minute per user
- [ ] API token management in user settings
- [ ] Multiple tokens per user (name each device)
- [ ] Revoke specific tokens or all tokens

#### 13.2 Content Endpoints
**User Stories:**
- As a developer, I fetch published content via API
- As a mobile app, I display posts and pages

**Acceptance Criteria:**
- [ ] **Posts:**
  - `GET /api/posts` - List published posts
  - `GET /api/posts/{slug}` - Single post by slug
  - `GET /api/posts/{id}` - Single post by ID
  - Query params: language, post_type, category, tag, author, page, per_page, sort
  - Response includes: post data, author, categories, tags, featured_image, gallery, comments_count
- [ ] **Pages:**
  - `GET /api/pages` - List published pages
  - `GET /api/pages/{slug}` - Single page
- [ ] **Categories:**
  - `GET /api/categories` - List categories
  - `GET /api/categories/{slug}` - Single category with posts
  - Query params: language
- [ ] **Tags:**
  - `GET /api/tags` - List tags
  - `GET /api/tags/{slug}` - Single tag with posts
  - Query params: language
- [ ] **Comments:**
  - `GET /api/posts/{id}/comments` - List approved comments
  - `POST /api/posts/{id}/comments` - Create comment (auth required)
- [ ] **Search:**
  - `GET /api/search?q=query&language=en&type=post`
- [ ] All responses paginated (Laravel pagination links)
- [ ] JSON:API or custom JSON structure (consistent)
- [ ] ETags for caching
- [ ] CORS headers configured

#### 13.3 Admin API (Authenticated)
**User Stories:**
- As a developer, I manage content via API
- As a third-party tool, I create/update posts

**Acceptance Criteria:**
- [ ] **Posts (Admin):**
  - `POST /api/admin/posts` - Create post
  - `PUT /api/admin/posts/{id}` - Update post
  - `DELETE /api/admin/posts/{id}` - Delete post
  - `POST /api/admin/posts/{id}/workflow` - Transition workflow state
  - Permission checks enforced
- [ ] **Media (Admin):**
  - `POST /api/admin/media` - Upload media
  - `GET /api/admin/media` - List all media (with filters)
  - `DELETE /api/admin/media/{id}` - Delete media
- [ ] **Categories/Tags (Admin):**
  - Full CRUD endpoints
- [ ] **Users (Admin):**
  - `GET /api/admin/users` - List users (admin only)
  - `POST /api/admin/users` - Create user (admin only)
- [ ] All admin endpoints require authentication + permission
- [ ] Rate limiting: 120 requests per minute for authenticated users

#### 13.4 Webhooks
**User Stories:**
- As a third-party service, I receive notifications on events
- As a developer, I integrate external systems on post publish

**Acceptance Criteria:**
- [ ] Webhook configuration in settings:
  - URL endpoint
  - Events to trigger (post.published, comment.created, etc.)
  - Secret key for signature verification
- [ ] Supported events:
  - `post.published`
  - `post.updated`
  - `post.deleted`
  - `comment.created`
  - `comment.approved`
  - `user.registered`
- [ ] Webhook payload (JSON):
  ```json
  {
    "event": "post.published",
    "timestamp": "2024-01-01T12:00:00Z",
    "data": { "post": {...} }
  }
  ```
- [ ] HMAC signature in header for verification
- [ ] Retry failed webhooks (3 attempts with exponential backoff)
- [ ] Webhook delivery log in admin

#### 13.5 API Documentation
**User Stories:**
- As a developer, I read API documentation
- As a developer, I test API endpoints interactively

**Acceptance Criteria:**
- [ ] API docs auto-generated (Laravel Scribe or similar)
- [ ] Documentation includes:
  - Endpoint URL and method
  - Required headers (Authorization)
  - Request body schema (JSON)
  - Response schema (JSON)
  - Example requests and responses
  - Error codes and meanings
- [ ] Interactive API explorer (Postman collection or Swagger UI)
- [ ] Rate limit information
- [ ] Versioning strategy (e.g., `/api/v1/`)
- [ ] Changelog for API changes

### Technical Requirements
```sql
-- Database Tables Required
personal_access_tokens (Sanctum)
webhooks
webhook_logs
```

### Dependencies
- Module 1: User Management
- Module 5: Content Management
- Module 6: Taxonomy System

### Testing Requirements
- [ ] Test authentication flow
- [ ] Test token generation and revocation
- [ ] Test all GET endpoints
- [ ] Test POST/PUT/DELETE endpoints with permissions
- [ ] Test rate limiting
- [ ] Test pagination
- [ ] Test filtering and sorting
- [ ] Test error responses (4xx, 5xx)
- [ ] Test webhook delivery
- [ ] Test webhook signature verification

---

## Module 14: Frontend Theme

### Priority: MEDIUM
### Estimated Effort: 3-4 weeks

### Overview
Public-facing frontend for displaying content with responsive design, language switching, and SEO optimization.

### Features

#### 14.1 Homepage
**User Stories:**
- As a visitor, I see featured posts on homepage
- As a visitor, I see recent posts organized by category
- As a visitor, I navigate to different sections

**Acceptance Criteria:**
- [ ] **Layout Options:**
  - Magazine style (featured + grid)
  - Blog style (post list)
  - Minimal style (latest posts)
  - Custom homepage (select a page as homepage)
- [ ] **Homepage Sections:**
  - Hero section: Featured/pinned posts (slider or static)
  - Latest posts grid (6-9 posts with featured images)
  - Category sections (e.g., "Technology", "Lifestyle")
  - Popular posts sidebar
  - Newsletter signup form (if enabled)
  - Social media links
- [ ] Fully responsive: mobile, tablet, desktop
- [ ] Language switcher in header
- [ ] Lazy loading images
- [ ] Infinite scroll or pagination for posts

#### 14.2 Post/Page Display
**User Stories:**
- As a visitor, I read full post content
- As a visitor, I see related posts
- As a visitor, I comment on posts

**Acceptance Criteria:**
- [ ] Post layout:
  - Title (H1)
  - Author (with avatar and bio)
  - Published date
  - Reading time estimate
  - Featured image (full width or contained)
  - Category and tag badges
  - Content (rendered from Editor.js JSON)
  - Gallery (if present)
  - Social share buttons (Facebook, Twitter, LinkedIn, WhatsApp)
  - Comments section
- [ ] Related posts: 3-6 posts based on categories/tags
- [ ] Prev/Next post navigation
- [ ] Table of contents (auto-generated from headers)
- [ ] Progress bar (reading progress)
- [ ] Print-friendly version
- [ ] Structured data (JSON-LD) for SEO

#### 14.3 Archive Pages
**User Stories:**
- As a visitor, I browse posts by category, tag, author, or date
- As a visitor, I see filtered results with pagination

**Acceptance Criteria:**
- [ ] **Category Archive:**
  - Category name and description
  - Post grid (featured images + titles + excerpts)
  - Pagination or load more
  - Subcategory list (if hierarchical)
  - Breadcrumbs
- [ ] **Tag Archive:**
  - Similar to category archive
  - Tag description
  - Related tags
- [ ] **Author Archive:**
  - Author bio and avatar
  - Author social links
  - List of author's posts
  - Post count
- [ ] **Date Archive:**
  - Archive by year, month
  - Calendar widget (optional)
- [ ] All archives support sorting: latest, oldest, popular

#### 14.4 Search Functionality
**User Stories:**
- As a visitor, I search for content
- As a visitor, I filter search results

**Acceptance Criteria:**
- [ ] Search form in header (always visible or expandable)
- [ ] Search results page:
  - Query display: "Search results for: {query}"
  - Result count
  - Post list with excerpts and highlights
  - Filter by: post type, category, date range
  - Sort by: relevance, date, popularity
  - Pagination
- [ ] Search powered by:
  - Laravel Scout (with Algolia/Meilisearch)
  - Or full-text MySQL search
- [ ] Search suggestions/autocomplete
- [ ] Recent searches (stored in session)
- [ ] No results page with suggestions

#### 14.5 Navigation & Menus
**User Stories:**
- As a visitor, I navigate the site via menus
- As an administrator, I customize menus

**Acceptance Criteria:**
- [ ] Header menu:
  - Logo (linked to homepage)
  - Primary navigation (pages, categories)
  - Search icon
  - Language switcher
  - Mobile hamburger menu
- [ ] Footer menu:
  - Links to pages (About, Contact, Privacy, Terms)
  - Social media icons
  - Copyright notice
  - Secondary navigation (if needed)
- [ ] Sidebar widgets (if layout supports):
  - Recent posts
  - Popular posts
  - Categories list
  - Tags cloud
  - Newsletter signup
  - Social media follow
- [ ] Sticky header on scroll
- [ ] Mobile-responsive off-canvas menu

#### 14.6 RTL Language Support
**User Stories:**
- As a Dhivehi user, I see RTL layout
- As a system, I switch layout direction based on language

**Acceptance Criteria:**
- [ ] CSS supports RTL via `dir="rtl"` attribute on `<html>`
- [ ] All layouts mirror correctly in RTL:
  - Text alignment (right instead of left)
  - Float directions reversed
  - Margins/paddings reversed
  - Icon positions (e.g., arrows) reversed
- [ ] Fonts support Dhivehi characters (if applicable)
- [ ] Language-specific typography (font size, line height)
- [ ] Test all interactive elements in RTL mode

#### 14.7 Performance Optimization
**User Stories:**
- As a visitor, pages load quickly
- As a search engine, I can crawl efficiently

**Acceptance Criteria:**
- [ ] Image lazy loading (native or library)
- [ ] Critical CSS inline, non-critical deferred
- [ ] JavaScript deferred or async
- [ ] Asset minification and combination
- [ ] Gzip/Brotli compression
- [ ] Browser caching headers
- [ ] CDN for static assets
- [ ] Page caching (Laravel cache or Varnish)
- [ ] Database query optimization (N+1 prevention)
- [ ] Lighthouse score target: 90+ (Performance, Accessibility, Best Practices, SEO)

### Technical Requirements
- Blade templates or frontend framework (Vue/React)
- Tailwind CSS or Bootstrap (or custom)
- Alpine.js or Vue for interactivity

### Dependencies
- All content modules

### Testing Requirements
- [ ] Test responsive design on all breakpoints
- [ ] Test cross-browser compatibility (Chrome, Firefox, Safari, Edge)
- [ ] Test RTL layout
- [ ] Test navigation functionality
- [ ] Test search functionality
- [ ] Test comment submission
- [ ] Test social sharing
- [ ] Test performance metrics
- [ ] Test accessibility (WCAG 2.1 AA compliance)
- [ ] Test SEO metadata rendering

---

## Deployment & DevOps Requirements

### Priority: HIGH
### Estimated Effort: 1 week

### Requirements

#### Environment Setup
- [ ] Development environment (local)
- [ ] Staging environment (server)
- [ ] Production environment (server)
- [ ] Environment-specific .env files

#### Server Requirements
- [ ] PHP 8.2+
- [ ] MySQL 8.0+ or MariaDB 10.3+
- [ ] Composer 2.x
- [ ] Node.js 18+ and NPM (for assets)
- [ ] Redis (for cache and queues)
- [ ] Supervisor (for queue workers)
- [ ] Nginx or Apache with PHP-FPM

#### Deployment Process
- [ ] Git-based deployment (GitHub/GitLab/Bitbucket)
- [ ] Automated deployment via Laravel Forge, Envoyer, or CI/CD pipeline
- [ ] Zero-downtime deployment
- [ ] Database migrations automated
- [ ] Asset compilation automated (npm run production)
- [ ] Cache clearing automated
- [ ] Queue restart automated

#### Backup Strategy
- [ ] Daily automated database backups
- [ ] Weekly full server backups
- [ ] Media files backup (incremental)
- [ ] Backup retention: 30 days minimum
- [ ] Backup restoration testing (monthly)
- [ ] Off-site backup storage (S3, Backblaze, etc.)

#### Monitoring
- [ ] Uptime monitoring (UptimeRobot, Pingdom, etc.)
- [ ] Error tracking (Sentry, Bugsnag, Flare)
- [ ] Performance monitoring (New Relic, Scout, etc.)
- [ ] Log aggregation (Papertrail, Loggly, ELK stack)
- [ ] Server resource monitoring (CPU, memory, disk)
- [ ] Database slow query log
- [ ] Email notifications for critical errors

#### Security
- [ ] SSL certificate (Let's Encrypt or commercial)
- [ ] Firewall configuration (UFW, iptables)
- [ ] Fail2ban for brute force protection
- [ ] Regular security updates
- [ ] File permissions properly set
- [ ] Environment variables secured (.env not in Git)
- [ ] Database credentials rotated regularly
- [ ] CSRF protection enabled (Laravel default)
- [ ] XSS protection enabled
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] Security headers (X-Frame-Options, CSP, etc.)

---

## Documentation Requirements

### Priority: MEDIUM
### Estimated Effort: Ongoing

### Required Documentation

#### For Developers
- [ ] **README.md**
  - Project overview
  - Requirements
  - Installation steps
  - Development setup
  - Environment variables explanation
  - Running tests
  - Contribution guidelines
- [ ] **Architecture documentation**
  - Database schema (ERD)
  - Module dependencies diagram
  - Workflow state diagram
  - Authentication flow
  - API structure (if applicable)
- [ ] **Code documentation**
  - PHPDoc comments on all classes/methods
  - Inline comments for complex logic
  - Service layer documentation
  - Helper function documentation

#### For Users
- [ ] **User Guide**
  - Getting started
  - Creating posts (step-by-step with screenshots)
  - Using Editor.js
  - Managing media
  - Understanding workflow
  - Assigning categories/tags
  - Managing comments
  - User roles explanation
- [ ] **Admin Guide**
  - Dashboard overview
  - User management
  - Configuring settings
  - Managing workflow states/transitions
  - Creating post types
  - Managing taxonomy
  - Analytics interpretation
  - Backup/restore procedures
- [ ] **API Documentation** (if Module 13 implemented)
  - Authentication
  - All endpoints with examples
  - Error codes
  - Rate limits
  - Webhook configuration

#### Training Materials
- [ ] Video tutorials (5-10 minutes each):
  - Creating your first post
  - Using the media library
  - Understanding the workflow
  - Moderating comments
- [ ] Quick reference cards (PDF)
  - Keyboard shortcuts
  - Editor.js block types
  - Common tasks checklist

---

## Testing Requirements

### Overall Testing Strategy
- [ ] Unit tests: 80%+ code coverage
- [ ] Feature tests: All critical user flows
- [ ] Integration tests: Module interactions
- [ ] Browser tests: Laravel Dusk for frontend
- [ ] API tests: All endpoints (if Module 13)
- [ ] Manual QA testing checklist

### Continuous Integration
- [ ] CI pipeline (GitHub Actions, GitLab CI, etc.)
- [ ] Automated test execution on push
- [ ] Code quality checks (PHPStan, Laravel Pint)
- [ ] Dependency vulnerability scanning
- [ ] Build status badges

### Testing Deliverables
- [ ] PHPUnit test suite
- [ ] Test coverage report
- [ ] Browser test suite (Dusk)
- [ ] Load testing results (optional)
- [ ] Security audit report (optional)

---

## Acceptance Criteria Summary

### Definition of Done
A module is considered complete when:
1. All acceptance criteria met
2. Unit tests written and passing
3. Feature tests written and passing
4. Code reviewed and approved
5. Documentation updated
6. Deployed to staging and tested
7. Product owner sign-off

### Project Completion Criteria
The project is complete when:
1. All HIGH priority modules implemented and tested
2. All acceptance criteria met
3. User documentation complete
4. Deployed to production
5. Training provided to users
6. Handover documentation provided
7. 30-day warranty period completed successfully

---

## Timeline Estimate

### Phase 1: Foundation (6-7 weeks)
- Module 1: User Management (2-3 weeks)
- Module 2: Multi-Language System (1 week)
- Module 3: Workflow System (3-4 weeks)

### Phase 2: Core Content (8-9 weeks)
- Module 4: Post Types & Custom Fields (2 weeks)
- Module 5: Content Management (4-5 weeks)
- Module 6: Taxonomy System (2 weeks)

### Phase 3: Supporting Features (6-7 weeks)
- Module 7: Comments System (2 weeks)
- Module 8: Pages System (1-2 weeks)
- Module 9: Media Library (2 weeks)
- Module 10: SEO & Analytics (2 weeks)

### Phase 4: Dashboard & Admin (3-4 weeks)
- Module 11: Dashboard & Analytics (1-2 weeks)
- Module 12: Settings & Configuration (1 week)
- Module 14: Frontend Theme (3-4 weeks) - can run parallel

### Phase 5: Polish & Deployment (2-3 weeks)
- Testing and bug fixes
- Documentation
- Deployment setup
- Training

**Total Estimated Time: 25-30 weeks (6-7 months)**

Note: Module 13 (API) is optional and adds 2-3 weeks if needed.

---

## Success Metrics

### Technical Metrics
- [ ] Page load time < 2 seconds
- [ ] 99.9% uptime
- [ ] Zero critical security vulnerabilities
- [ ] 80%+ test coverage
- [ ] Lighthouse score 90+ (all categories)

### User Metrics
- [ ] User onboarding completion rate > 80%
- [ ] Average time to create first post < 10 minutes
- [ ] User satisfaction score > 4/5
- [ ] Support ticket volume < 5 per week after launch

### Content Metrics
- [ ] 100+ posts published in first month
- [ ] Average post creation time < 30 minutes
- [ ] 90%+ posts go through workflow without issues
- [ ] Media library usage growth 20%+ per month

---

## Risk Assessment

### High Risk Items
1. **Workflow complexity** - Solution: Thorough testing, clear documentation
2. **Editor.js integration** - Solution: Proof of concept early, fallback to simple editor
3. **Multi-language data consistency** - Solution: Comprehensive validation, constraints
4. **Performance with large datasets** - Solution: Indexing strategy, caching, query optimization

### Mitigation Strategies
- [ ] Weekly progress reviews
- [ ] Early prototypes for complex features
- [ ] Staged rollout (dev → staging → production)
- [ ] Rollback plan for production deployments
- [ ] Performance testing before production launch

---

This comprehensive requirements document provides a complete blueprint for implementing the blogging CMS. Each module is clearly defined with user stories, acceptance criteria, technical requirements, dependencies, and testing requirements. The developer can use this as a complete specification to build the system.
