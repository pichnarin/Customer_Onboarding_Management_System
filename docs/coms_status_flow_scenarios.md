# COMS Status Flow Scenarios - Complete Use Cases

## 🎯 Overview of Status Fields

### 1. onboarding_requests.status
`pending` → `assigned` → `in_progress` → `completed` (or `cancelled`)

### 2. training_assignments.status
`assigned` → `accepted` → `in_progress` → `completed` (or `rejected`)

### 3. training_sessions.status
`scheduled` → `in_progress` → `completed` (or `cancelled` / `rescheduled`)

### 4. session_attendees.attendance_status
`invited` → `confirmed` → `attended` (or `absent` / `cancelled`)

### 5. stage_progress.status
`not_started` → `in_progress` → `completed` (or `skipped`)

### 6. telegram_messages.delivery_status
`pending` → `sent` → `delivered` (or `failed`)

---

## 📖 Complete User Journey Scenarios

### SCENARIO 1: Standard Successful Training Flow

#### **Day 1 - Sales Creates Request**

**Actor:** Sophia (Sales Team)  
**Client:** ABC Company needs CRM training

**Actions:**
1. Sophia creates a new client record for ABC Company
2. Sophia creates onboarding request for CRM system training

**Status Changes:**
```
onboarding_requests.status = 'pending'
onboarding_requests.priority = 'medium'
```

**System Behavior:**
- Auto-generates unique `request_code` (e.g., "REQ-2024-001")
- Creates `stage_progress` records for all CRM training stages:
  - Stage 1: Initial Setup → status = 'not_started'
  - Stage 2: Basic Features → status = 'not_started'
  - Stage 3: Advanced Features → status = 'not_started'

---

#### **Day 2 - Sales Assigns Trainer**

**Actor:** Sophia (Sales Team)  
**Trainer:** Tom (Trainer)

**Actions:**
1. Sophia reviews available trainers
2. Sophia assigns Tom to the ABC Company request
3. System sends notification to Tom

**Status Changes:**
```
training_assignments.status = 'assigned'
training_assignments.assigned_at = '2024-02-17 09:00:00'

onboarding_requests.status = 'assigned'  // Changed from 'pending'
```

**System Behavior:**
- Creates notification for Tom
  ```
  notifications:
    - type = 'assignment_created'
    - title = 'New Training Assignment'
    - message = 'You have been assigned to train ABC Company on CRM'
    - is_read = false
  ```

---

#### **Day 2 (Later) - Trainer Accepts Assignment**

**Actor:** Tom (Trainer)

**Actions:**
1. Tom receives notification
2. Tom reviews the assignment details
3. Tom clicks "Accept Assignment"

**Status Changes:**
```
training_assignments.status = 'accepted'  // Changed from 'assigned'
training_assignments.accepted_at = '2024-02-17 14:30:00'
```

**System Behavior:**
- Notification sent to Sophia (sales) confirming acceptance
- Tom can now create training sessions

---

#### **Day 3 - Trainer Creates Training Sessions**

**Actor:** Tom (Trainer)

**Actions:**
1. Tom creates first session for Stage 1 (Initial Setup)
   - Date: February 20, 2024
   - Time: 10:00 AM - 12:00 PM
   - Type: Online (Zoom)
   - Adds customer contacts as attendees

**Status Changes:**
```
training_sessions.status = 'scheduled'
training_sessions.location_type = 'online'

session_attendees.attendance_status = 'invited'  // For each customer contact
```

**System Behavior:**
- Auto-creates Telegram notification for customers
  ```
  telegram_messages:
    - message_type = 'session_scheduled'
    - message_content = 'You have a training session on Feb 20 at 10:00 AM...'
    - delivery_status = 'pending'
    - related_session_id = [session_id]
  ```

---

#### **Day 3 (Moments Later) - Telegram Sends Notification**

**Actor:** System (Telegram Bot)

**Actions:**
1. System processes pending Telegram messages
2. Sends message to customer's Telegram

**Status Changes:**
```
telegram_messages.delivery_status = 'sent'  // Changed from 'pending'
telegram_messages.sent_at = '2024-02-17 15:05:00'
```

**If successful:**
```
telegram_messages.delivery_status = 'delivered'
```

**If failed:**
```
telegram_messages.delivery_status = 'failed'
telegram_messages.error_message = 'Invalid telegram_chat_id'
```

---

#### **Day 5 - Customer Confirms Attendance**

**Actor:** John (Customer Contact from ABC Company)

**Actions:**
1. John receives Telegram message
2. John clicks confirmation link or replies "Yes"

**Status Changes:**
```
session_attendees.attendance_status = 'confirmed'  // Changed from 'invited'
```

---

#### **Day 20 - Session Day: Before Training**

**Actor:** Tom (Trainer)

**Actions:**
1. Tom starts the Zoom meeting
2. Tom marks session as started in COMS

**Status Changes:**
```
training_sessions.status = 'in_progress'  // Changed from 'scheduled'
training_sessions.actual_start_time = '2024-02-20 10:00:00'

training_assignments.status = 'in_progress'  // Changed from 'accepted' - first session started

onboarding_requests.status = 'in_progress'  // Changed from 'assigned'
onboarding_requests.actual_start_date = '2024-02-20'

stage_progress.status = 'in_progress'  // For Stage 1 only
stage_progress.started_at = '2024-02-20 10:00:00'
```

---

#### **Day 20 - During Training: Customer Joins**

**Actor:** John (Customer Contact)

**Actions:**
1. John joins the Zoom meeting
2. Tom marks John as attended in COMS

**Status Changes:**
```
session_attendees.attendance_status = 'attended'  // Changed from 'confirmed'
session_attendees.attended_at = '2024-02-20 10:05:00'
```

---

#### **Day 20 - After Training: Session Completion**

**Actor:** Tom (Trainer)

**Actions:**
1. Training session ends
2. Tom marks session as completed
3. Tom adds completion notes
4. Tom uploads session recording

**Status Changes:**
```
training_sessions.status = 'completed'  // Changed from 'in_progress'
training_sessions.actual_end_time = '2024-02-20 12:00:00'
training_sessions.completion_notes = 'Covered all initial setup steps. Customer understood basic navigation.'

stage_progress.status = 'completed'  // For Stage 1
stage_progress.progress_percentage = 100.00
stage_progress.completed_at = '2024-02-20 12:00:00'
```

**System Behavior:**
- Session material added:
  ```
  session_materials:
    - material_type = 'recording'
    - description = 'Session 1: Initial Setup - Recording'
  ```

---

#### **Days 21-30 - Remaining Sessions**

Tom continues creating and completing sessions for Stage 2 and Stage 3:

**After Stage 2 completion:**
```
stage_progress (Stage 2):
  - status = 'completed'
  - progress_percentage = 100.00
```

**After Stage 3 (final stage) completion:**
```
stage_progress (Stage 3):
  - status = 'completed'
  - progress_percentage = 100.00

training_assignments.status = 'completed'  // All stages done
training_assignments.completed_at = '2024-03-05 12:00:00'

onboarding_requests.status = 'completed'  // Training complete
onboarding_requests.actual_end_date = '2024-03-05'
```

---

### SCENARIO 2: Trainer Rejects Assignment

#### **Day 1-2:** Same as Scenario 1 (Sales creates request and assigns trainer)

#### **Day 2 - Trainer Rejects Assignment**

**Actor:** Tom (Trainer)  
**Reason:** Too busy with other assignments

**Actions:**
1. Tom reviews the assignment
2. Tom clicks "Reject Assignment"
3. Tom provides rejection reason

**Status Changes:**
```
training_assignments.status = 'rejected'  // Changed from 'assigned'
training_assignments.rejection_reason = 'Currently overbooked. Can accept after March 1st.'

onboarding_requests.status = 'pending'  // Reverts back to pending
```

**System Behavior:**
- Notification sent to Sophia (sales)
  ```
  notifications:
    - type = 'assignment_rejected'
    - title = 'Training Assignment Rejected'
    - message = 'Tom rejected the ABC Company assignment. Reason: Currently overbooked...'
  ```
- Sophia must assign a different trainer

---

### SCENARIO 3: Session Rescheduling

#### **Day 1-19:** Same as Scenario 1 (up to scheduled session)

#### **Day 19 - Customer Requests Reschedule**

**Actor:** John (Customer Contact)  
**Reason:** Conflict with another meeting

**Actions:**
1. John contacts Tom via Telegram
2. Tom reschedules the session to February 22

**Status Changes:**
```
// Original session
training_sessions.status = 'rescheduled'  // Changed from 'scheduled'

// New session created
training_sessions.status = 'scheduled'
training_sessions.scheduled_date = '2024-02-22'

session_attendees.attendance_status = 'invited'  // New invitation
```

**System Behavior:**
- New Telegram notification sent
  ```
  telegram_messages:
    - message_type = 'session_rescheduled'
    - message_content = 'Your Feb 20 session has been rescheduled to Feb 22...'
  ```

---

### SCENARIO 4: Customer No-Show

#### **Day 20 - Session Day: Customer Doesn't Attend**

**Actor:** Tom (Trainer)

**Actions:**
1. Tom waits 15 minutes
2. John doesn't join the meeting
3. Tom marks as absent and completes the session anyway

**Status Changes:**
```
session_attendees.attendance_status = 'absent'  // Changed from 'confirmed'

training_sessions.status = 'completed'
training_sessions.completion_notes = 'Customer did not attend. Will follow up for makeup session.'
```

**System Behavior:**
- Tom creates a new makeup session
- Telegram notification sent to customer about absence

---

### SCENARIO 5: Training Cancelled by Sales

#### **Day 5 - Client Budget Issues**

**Actor:** Sophia (Sales Team)  
**Reason:** ABC Company decided not to proceed

**Actions:**
1. ABC Company notifies Sophia they're cancelling
2. Sophia cancels all upcoming sessions
3. Sophia marks the onboarding request as cancelled

**Status Changes:**
```
// All scheduled sessions
training_sessions.status = 'cancelled'  // For all future sessions

// All invited attendees
session_attendees.attendance_status = 'cancelled'

training_assignments.status = 'completed'  // Marked as done (early)

onboarding_requests.status = 'cancelled'  // Changed from 'in_progress'
```

**System Behavior:**
- Notifications sent to Tom (trainer)
- Telegram notifications sent to all customer contacts
  ```
  telegram_messages:
    - message_type = 'training_cancelled'
    - message_content = 'Your training sessions have been cancelled...'
  ```

---

### SCENARIO 6: Skipping a Stage

#### **Day 15 - Skip Advanced Features**

**Actor:** Tom (Trainer)  
**Reason:** Customer only needs basic features

**Actions:**
1. Tom completes Stage 1 and Stage 2
2. Tom and customer agree to skip Stage 3 (Advanced Features)
3. Tom marks Stage 3 as skipped

**Status Changes:**
```
stage_progress (Stage 1):
  - status = 'completed'

stage_progress (Stage 2):
  - status = 'completed'

stage_progress (Stage 3):
  - status = 'skipped'  // Not 'completed'
  - notes = 'Customer only needs basic CRM features. Advanced features not required.'
```

**System Behavior:**
- Training assignment still completes successfully
  ```
  training_assignments.status = 'completed'
  onboarding_requests.status = 'completed'
  ```

---

### SCENARIO 7: Hybrid Training Session

#### **Day 10 - Partial Onsite Training**

**Actor:** Tom (Trainer)

**Actions:**
1. Tom schedules a hybrid session
2. Some customers attend in person, others join online

**Status Changes:**
```
training_sessions.location_type = 'hybrid'
training_sessions.meeting_link = 'https://zoom.us/j/123456'
training_sessions.physical_location = 'ABC Company Office, Conference Room A'

session_attendees (John - In Person):
  - attendance_status = 'attended'
  - notes = 'Attended in person'

session_attendees (Sarah - Online):
  - attendance_status = 'attended'
  - notes = 'Attended via Zoom'

session_attendees (Mike - Absent):
  - attendance_status = 'absent'
  - notes = 'Did not attend'
```

---

### SCENARIO 8: Urgent Priority Training

#### **Day 1 - VIP Client Needs Immediate Training**

**Actor:** Sophia (Sales Team)  
**Client:** XYZ Corp (important enterprise client)

**Actions:**
1. Sophia creates onboarding request with urgent priority
2. System highlights this in trainer dashboard

**Status Changes:**
```
onboarding_requests.priority = 'urgent'  // Instead of default 'medium'
onboarding_requests.status = 'pending'
```

**System Behavior:**
- Urgent requests appear at the top of trainer assignment queue
- Notification includes urgency indicator

---

## 📊 Status Transition Summary

### onboarding_requests.status
```
pending 
  ↓ (sales assigns trainer)
assigned
  ↓ (trainer starts first session)
in_progress
  ↓ (all stages completed or skipped)
completed

From any state → cancelled (sales cancels)
```

### training_assignments.status
```
assigned
  ↓ (trainer accepts)
accepted
  ↓ (first session starts)
in_progress
  ↓ (all sessions completed)
completed

From assigned → rejected (trainer rejects)
```

### training_sessions.status
```
scheduled
  ↓ (trainer starts session)
in_progress
  ↓ (session ends)
completed

From scheduled → cancelled (session cancelled)
From scheduled → rescheduled (moved to different date/time)
```

### session_attendees.attendance_status
```
invited
  ↓ (customer confirms)
confirmed
  ↓ (customer joins)
attended

From invited/confirmed → absent (customer no-show)
From invited/confirmed → cancelled (session cancelled)
```

### stage_progress.status
```
not_started
  ↓ (first session of stage begins)
in_progress
  ↓ (all sessions of stage completed)
completed

From any state → skipped (trainer/customer agrees to skip)
```

### telegram_messages.delivery_status
```
pending
  ↓ (bot attempts send)
sent
  ↓ (Telegram confirms delivery)
delivered

From pending/sent → failed (delivery error)
```

---

## 🎭 User Role Permissions by Status

### Sales Team (Sophia)
**Can Change:**
- onboarding_requests.status (pending ↔ assigned, any → cancelled)
- onboarding_requests.priority (any time before completion)
- training_assignments (create, assign trainer)

**Cannot Change:**
- training_sessions.status (trainer only)
- stage_progress.status (trainer only)

### Trainer (Tom)
**Can Change:**
- training_assignments.status (assigned → accepted/rejected)
- training_sessions.status (all transitions)
- session_attendees.attendance_status (all transitions)
- stage_progress.status (all transitions)

**Cannot Change:**
- onboarding_requests.priority (sales only)
- onboarding_requests.status to 'cancelled' (sales only)

### Customer (John)
**Can View:**
- training_sessions (their own)
- session_attendees (their own attendance)

**Can Change:**
- session_attendees.attendance_status (invited → confirmed only)

**Cannot Change:**
- Any other statuses

### System (Automated)
**Can Change:**
- telegram_messages.delivery_status (all transitions)
- stage_progress.status (auto-update based on completed sessions)
- onboarding_requests.status (auto-update based on assignment status)

---

## ⚠️ Important Business Rules

### Rule 1: Cascade Status Updates
When a session is completed:
1. Update session_attendees for that session
2. Check if all sessions for a stage are complete → update stage_progress
3. Check if all stages are complete → update training_assignments
4. Check if assignment is complete → update onboarding_requests

### Rule 2: Cannot Skip Forward
- Cannot mark assignment as 'completed' if any stage is still 'in_progress'
- Cannot mark request as 'completed' if assignment is not 'completed'
- Must complete sessions in stage order (Stage 1 → 2 → 3)

### Rule 3: Cancellation Cascade
When onboarding_request is cancelled:
1. Cancel all scheduled future sessions
2. Cancel all invited/confirmed session attendees
3. Mark assignment as completed (early termination)

### Rule 4: Rejection Handling
When trainer rejects assignment:
1. Assignment status → 'rejected'
2. Request status → back to 'pending'
3. Sales must assign different trainer

### Rule 5: Progress Calculation
```
stage_progress.progress_percentage = 
  (completed_sessions_in_stage / total_sessions_in_stage) * 100

Overall assignment progress = 
  AVG(all stage_progress.progress_percentage)
```

---

## 🔔 Notification Triggers by Status Change

| Status Change | Notification Recipient | Notification Type |
|--------------|----------------------|------------------|
| onboarding_request: pending → assigned | Trainer | assignment_created |
| training_assignment: assigned → rejected | Sales | assignment_rejected |
| training_assignment: assigned → accepted | Sales | assignment_accepted |
| training_session created (scheduled) | Customer Contacts | session_scheduled |
| training_session: scheduled → in_progress | Customer Contacts | session_started |
| training_session: scheduled → rescheduled | Customer Contacts | session_rescheduled |
| training_session: * → cancelled | Customer Contacts | session_cancelled |
| training_session: in_progress → completed | Sales | session_completed |
| stage_progress: * → completed | Sales | stage_completed |
| training_assignment: * → completed | Sales, Trainer | training_completed |
| onboarding_request: * → completed | Sales, Trainer | onboarding_completed |

