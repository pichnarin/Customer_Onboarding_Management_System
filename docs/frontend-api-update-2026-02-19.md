# Frontend API Update — 2026-02-19

This document covers every breaking change and new endpoint introduced in the
`imp/on_boarding_session` branch. Read it top to bottom before updating your
integration layer.

---

## Table of Contents

1. [What Changed at a Glance](#1-what-changed-at-a-glance)
2. [Breaking Changes — Existing Endpoints](#2-breaking-changes--existing-endpoints)
   - [PATCH /api/sessions/{id}/start](#patch-apisessionsidstart)
   - [PATCH /api/sessions/{id}/complete](#patch-apisessionsidcomplete)
   - [PATCH /api/sessions/{id}/cancel](#patch-apisessionsidcancel)
   - [PATCH /api/sessions/{id}/reschedule](#patch-apisessionsidreschedule)
3. [New Endpoints — Session Students](#3-new-endpoints--session-students)
   - [POST /api/sessions/{id}/students](#post-apisessionsidstudents)
   - [GET /api/sessions/{id}/students](#get-apisessionsidstudents)
4. [New Endpoints — Onboarding Stage Management](#4-new-endpoints--onboarding-stage-management)
   - [GET /api/onboarding/stages](#get-apionboardingstages)
   - [GET /api/onboarding/stages/{id}](#get-apionboardingstagesid)
   - [POST /api/onboarding/stages](#post-apionboardingstages)
   - [PATCH /api/onboarding/stages/{id}](#patch-apionboardingstagesid)
   - [PATCH /api/onboarding/stages/{id}/toggle](#patch-apionboardingstagesidtoggle)
5. [New Fields on Session Objects](#5-new-fields-on-session-objects)
6. [New Notification Types](#6-new-notification-types)
7. [Error Reference](#7-error-reference)

---

## 1. What Changed at a Glance

| Category | What | Impact |
|----------|------|--------|
| **Breaking** | `PATCH /sessions/{id}/start` now requires a body | Must update all call sites |
| **Breaking** | `PATCH /sessions/{id}/complete` requires 2 new fields | Must update all call sites |
| **Breaking** | `PATCH /sessions/{id}/cancel` now requires a body | Must update all call sites |
| **Breaking** | `PATCH /sessions/{id}/reschedule` requires 1 new field | Must update all call sites |
| **New** | `POST /sessions/{id}/students` — submit attendance roster | New feature |
| **New** | `GET /sessions/{id}/students` — fetch attendance roster | New feature |
| **New** | 5 stage management routes under `/onboarding/stages` | New feature |
| **New fields** | Session objects now carry proof, audit, and student fields | Update your TypeScript types |
| **New notifications** | 4 new notification `type` values the sale role will receive | Update notification renderer |

---

## 2. Breaking Changes — Existing Endpoints

> All endpoints below already existed. Their request bodies have changed.
> Calls without the new required fields will receive a **422 Validation Error**.

---

### PATCH /api/sessions/{id}/start

**Before:** No request body was needed.

**After:** Requires a proof-of-start image that was already uploaded to the media
library.

**Request body**

```json
{
  "start_proof_media_id": "uuid"
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `start_proof_media_id` | uuid | Yes | Must exist in the `media` table. Upload the photo first, then pass its ID here. |

**Success response (200)**

```json
{
  "success": true,
  "message": "Session started",
  "data": {
    "status": "in_progress"
  }
}
```

**Validation error (422)**

```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "start_proof_media_id": ["The start proof media id field is required."]
  }
}
```

---

### PATCH /api/sessions/{id}/complete

**Before:** Only `completion_notes` was required.

**After:** Two additional fields are required.

**Request body**

```json
{
  "completion_notes": "string",
  "end_proof_media_id": "uuid",
  "student_count": 12
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `completion_notes` | string (max 2000) | Yes | Summary of what was covered |
| `end_proof_media_id` | uuid | Yes | Proof-of-completion photo; must exist in `media` |
| `student_count` | integer (≥ 0) | Yes | Total number of students present at the session |

**Success response (200)**

```json
{
  "success": true,
  "message": "Session completed",
  "data": {
    "status": "completed"
  }
}
```

---

### PATCH /api/sessions/{id}/cancel

**Before:** No request body was needed.

**After:** A cancellation reason is required.

**Request body**

```json
{
  "cancellation_reason": "string"
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `cancellation_reason` | string (max 1000) | Yes | Free-text reason displayed to the sale agent |

**Success response (200)**

```json
{
  "success": true,
  "message": "Session cancelled"
}
```

**Side effects**
- All `invited` / `confirmed` attendees are moved to `cancelled`
- Sale agent receives a `session_cancelled` notification including the reason

---

### PATCH /api/sessions/{id}/reschedule

**Before:** Only schedule fields were required.

**After:** A reschedule reason is also required.

**Request body**

```json
{
  "scheduled_date": "2026-03-10",
  "scheduled_start_time": "09:00",
  "scheduled_end_time": "11:00",
  "reschedule_reason": "string",
  "meeting_link": "https://...",
  "physical_location": "optional string"
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `scheduled_date` | date | Yes | Format: `YYYY-MM-DD` |
| `scheduled_start_time` | time | Yes | Format: `HH:MM` (24-hour) |
| `scheduled_end_time` | time | Yes | Format: `HH:MM`; must be after start |
| `reschedule_reason` | string (max 1000) | Yes | Reason sent to sale agent |
| `meeting_link` | url | No | Overrides the old meeting link |
| `physical_location` | string | No | Overrides the old physical location |

**Success response (200)**

```json
{
  "success": true,
  "message": "Session rescheduled",
  "data": {
    "new_session_id": "uuid",
    "status": "scheduled"
  }
}
```

> The old session's status becomes `rescheduled`. A brand-new session record is
> created with status `scheduled`. All original attendees are re-invited on the
> new session.

**Overlap guard**: If the trainer already has a session during the new time
window, you receive a **422** — see [Error Reference](#7-error-reference).

---

## 3. New Endpoints — Session Students

These endpoints manage the named attendance roster for a session (distinct from
the `session_attendees` table that tracks client contacts). Student records can
be filled in any time after a session is completed.

---

### POST /api/sessions/{id}/students

Submit a batch of student attendance records for a session.

**Auth required:** Yes (`jwt.auth`)

**Request body**

```json
{
  "students": [
    {
      "name": "Dara Chan",
      "phone_number": "012345678",
      "profession": "Software Engineer"
    },
    {
      "name": "Sok Maly",
      "phone_number": null,
      "profession": null
    }
  ]
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `students` | array | Yes | At least one item |
| `students.*.name` | string (max 255) | No | |
| `students.*.phone_number` | string (max 20) | No | |
| `students.*.profession` | string (max 100) | No | |

**Success response (201)**

```json
{
  "success": true,
  "message": "Students added successfully",
  "data": {
    "count": 2
  }
}
```

**Side effects**
- Sale agent receives a `student_attendance_submitted` notification

---

### GET /api/sessions/{id}/students

Retrieve all student records submitted for a session.

**Auth required:** Yes (`jwt.auth`)

**Success response (200)**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "session_id": "uuid",
      "name": "Dara Chan",
      "phone_number": "012345678",
      "profession": "Software Engineer",
      "created_at": "2026-02-19T08:00:00.000000Z",
      "updated_at": "2026-02-19T08:00:00.000000Z"
    }
  ]
}
```

---

## 4. New Endpoints — Onboarding Stage Management

All stage endpoints live under the `jwt.auth` middleware. Write operations
(`POST`, `PATCH`) return **403** for `trainer` and `customer` roles.

---

### GET /api/onboarding/stages

List all stages for a given system.

**Auth required:** Yes — all roles

**Query parameters**

| Param | Type | Required | Notes |
|-------|------|----------|-------|
| `system_id` | uuid | Yes | Filter stages by system |
| `include_inactive` | boolean | No | Default `false`. Pass `true` to show inactive stages |

**Example request**

```
GET /api/onboarding/stages?system_id=<uuid>
GET /api/onboarding/stages?system_id=<uuid>&include_inactive=true
```

**Success response (200)**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Initial Setup",
      "description": "Configure client environment",
      "sequence_order": 1,
      "estimated_duration_days": 3,
      "system_id": "uuid",
      "is_active": true,
      "system": {
        "id": "uuid",
        "name": "coms"
      },
      "created_at": "2026-02-19T08:00:00.000000Z",
      "updated_at": "2026-02-19T08:00:00.000000Z"
    }
  ]
}
```

**Error — missing system_id (422)**

```json
{
  "success": false,
  "message": "system_id query parameter is required"
}
```

---

### GET /api/onboarding/stages/{id}

Fetch a single stage with its system.

**Auth required:** Yes — all roles

**Success response (200)**

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "name": "Basic Training",
    "description": "...",
    "sequence_order": 2,
    "estimated_duration_days": 5,
    "system_id": "uuid",
    "is_active": true,
    "system": { "id": "uuid", "name": "coms" },
    "created_at": "...",
    "updated_at": "..."
  }
}
```

---

### POST /api/onboarding/stages

Create a new onboarding stage.

**Auth required:** Yes — `admin`, `sale` only (others get 403)

**Request body**

```json
{
  "system_id": "uuid",
  "name": "Advanced Training",
  "description": "Deep-dive into advanced features",
  "sequence_order": 3,
  "estimated_duration_days": 7
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `system_id` | uuid | Yes | Must exist in `systems` table |
| `name` | string (max 100) | Yes | |
| `description` | string | No | |
| `sequence_order` | integer (≥ 1) | Yes | Controls the display and progression order |
| `estimated_duration_days` | integer (≥ 1) | No | |

**Success response (201)**

```json
{
  "success": true,
  "message": "Stage created successfully",
  "data": { /* full stage object */ }
}
```

---

### PATCH /api/onboarding/stages/{id}

Update an existing stage. `system_id` cannot be changed.

**Auth required:** Yes — `admin`, `sale` only

**Request body** — all fields optional, only send what you want to change

```json
{
  "name": "Advanced Training (Updated)",
  "sequence_order": 4,
  "estimated_duration_days": 10
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string (max 100) | No | |
| `description` | string | No | Pass `null` to clear |
| `sequence_order` | integer (≥ 1) | No | |
| `estimated_duration_days` | integer (≥ 1) | No | Pass `null` to clear |

**Success response (200)**

```json
{
  "success": true,
  "message": "Stage updated successfully",
  "data": { /* updated stage object */ }
}
```

---

### PATCH /api/onboarding/stages/{id}/toggle

Flip `is_active` on a stage (active → inactive or vice versa). No body needed.

**Auth required:** Yes — `admin`, `sale` only

**Success response (200)**

```json
{
  "success": true,
  "message": "Stage toggled successfully",
  "data": {
    "is_active": false
  }
}
```

> Inactive stages are excluded from new assignment stage-progress creation. Use
> `include_inactive=true` on the list endpoint to show them in admin/sale UIs.

---

## 5. New Fields on Session Objects

The `training_sessions` resource now includes the following additional fields.
Update your TypeScript interfaces / response types accordingly.

```ts
interface TrainingSession {
  // existing fields ...
  id: string;
  assignment_id: string;
  stage_id: string;
  session_title: string;
  session_description: string | null;
  scheduled_date: string;          // "YYYY-MM-DD"
  scheduled_start_time: string;    // "HH:MM:SS"
  scheduled_end_time: string;      // "HH:MM:SS"
  actual_start_time: string | null; // ISO datetime
  actual_end_time: string | null;   // ISO datetime
  location_type: "online" | "onsite" | "hybrid";
  meeting_link: string | null;
  physical_location: string | null;
  status: "scheduled" | "in_progress" | "completed" | "cancelled" | "rescheduled";
  completion_notes: string | null;

  // NEW fields
  start_proof_media_id: string | null;  // uuid → media record
  end_proof_media_id: string | null;    // uuid → media record
  student_count: number | null;         // set on complete
  cancellation_reason: string | null;   // set on cancel
  cancelled_by_user_id: string | null;  // uuid → user who cancelled
  cancelled_at: string | null;          // ISO datetime
  reschedule_reason: string | null;     // set on reschedule
}
```

> `start_proof_media_id` / `end_proof_media_id` are foreign keys into the
> `media` table. If you need to display the proof images, fetch the media record
> separately or load it via eager-loading relations (`startProof`, `endProof`).

---

## 6. New Notification Types

The sale role now receives in-app notifications for additional trainer actions.
The notification object shape is unchanged — only `type` and `message` vary.

| `type` value | When it fires | `message` contains |
|---|---|---|
| `session_created` | Trainer schedules a new session | Session title, client name, scheduled date |
| `session_started` | Trainer starts a session | Session title, client name |
| `session_rescheduled` | Trainer reschedules a session | Old date, new date, reason |
| `session_cancelled` | Trainer cancels a session | Session title, reason |
| `student_attendance_submitted` | Trainer submits student roster | Session title, student count |

> The existing `session_completed` notification now also includes the student
> count in its message.

**Existing notification types (unchanged)**

| `type` | Recipient |
|---|---|
| `assignment_created` | Trainer |
| `assignment_accepted` | Sale |
| `assignment_rejected` | Sale |
| `session_completed` | Sale |
| `stage_completed` | Sale |
| `training_completed` | Sale + Trainer |
| `onboarding_completed` | Sale + Trainer |

---

## 7. Error Reference

### Overlap conflict (422)

Returned when creating or rescheduling a session that overlaps with another
session already assigned to the same trainer on the same date.

```json
{
  "message": "You already have a training session scheduled during this time. Please choose a different time.",
  "status": 422
}
```

Show this message directly in the UI time-picker so the trainer knows to pick a
different slot.

### Forbidden (403)

Returned by stage write endpoints (`POST /stages`, `PATCH /stages/{id}`,
`PATCH /stages/{id}/toggle`) when called by a `trainer` or `customer` role.

```json
{
  "success": false,
  "message": "Forbidden"
}
```

### Validation error (422)

Consistent shape across all endpoints:

```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "field_name": ["Human-readable error message."]
  }
}
```

---

## Quick Checklist for Frontend

- [ ] Upload proof photo to media library first, then pass the returned `id` as `start_proof_media_id` / `end_proof_media_id`
- [ ] Add `start_proof_media_id` input to the **Start Session** form
- [ ] Add `end_proof_media_id` + `student_count` inputs to the **Complete Session** form
- [ ] Add `cancellation_reason` textarea to the **Cancel Session** confirmation dialog
- [ ] Add `reschedule_reason` textarea to the **Reschedule Session** form
- [ ] Handle the new `SessionOverlapException` (422) on create and reschedule session forms
- [ ] Build the **Add Students** form (POST `/sessions/{id}/students`) for trainers after session completion
- [ ] Build the **Stage Management** CRUD screens for admin/sale (hide write actions for trainer/customer)
- [ ] Update TypeScript `TrainingSession` interface with the 7 new fields
- [ ] Add rendering logic for 5 new notification `type` values in the notification feed
