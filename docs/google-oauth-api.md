# Google OAuth API Routes

Base URL: `/api`

## OAuth Flow

```
1. Frontend calls GET /api/google/redirect (with JWT)
2. Frontend navigates user to the returned redirect_url
3. User consents on Google's page
4. Google redirects browser to GET /api/google/callback?code=...&state=<user_id>
5. Backend stores tokens, redirects browser to GOOGLE_FRONTEND_REDIRECT_URL?google=connected
6. Frontend reads the ?google= query param to show success/failure
```

---

## `GET /api/google/redirect`

Get the Google OAuth consent page URL. Frontend should navigate the user to this URL.

**Auth:** `Bearer <access_token>` (required)

**Request:** No body required.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "redirect_url": "https://accounts.google.com/o/oauth2/v2/auth?client_id=...&redirect_uri=...&scope=...&state=<user_id>&access_type=offline&prompt=consent"
  }
}
```

---

## `GET /api/google/callback`

Google redirects the browser here after user consent. **This is NOT called by frontend directly** — Google sends the user here automatically.

**Auth:** None (public route). User is identified via the `state` query parameter.

**Query Parameters (provided by Google):**

| Param   | Type   | Description                          |
|---------|--------|--------------------------------------|
| `code`  | string | Authorization code from Google       |
| `state` | string | The user_id passed during redirect   |

**Response:** Browser redirect (302) to `GOOGLE_FRONTEND_REDIRECT_URL` with query params:

| Scenario | Redirect URL                                                    |
|----------|-----------------------------------------------------------------|
| Success  | `{GOOGLE_FRONTEND_REDIRECT_URL}?google=connected`              |
| Failure  | `{GOOGLE_FRONTEND_REDIRECT_URL}?google=failed&error=<message>` |

Frontend should check the `google` query parameter on the redirect URL:
- `?google=connected` — show success
- `?google=failed&error=...` — show error message

---

## `GET /api/google/status`

Check if the current user has a connected Google account.

**Auth:** `Bearer <access_token>` (required)

**Request:** No body required.

**Response when connected (200):**
```json
{
  "success": true,
  "data": {
    "connected": true,
    "provider": "google",
    "scopes": ["https://www.googleapis.com/auth/calendar"],
    "provider_user_id": "118234567890"
  }
}
```

**Response when not connected (200):**
```json
{
  "success": true,
  "data": {
    "connected": false,
    "provider": "google",
    "scopes": [],
    "email": null
  }
}
```

---

## `DELETE /api/google/disconnect`

Disconnect the user's Google account (soft-deletes the stored OAuth tokens).

**Auth:** `Bearer <access_token>` (required)

**Request:** No body required.

**Response (200):**
```json
{
  "success": true,
  "message": "Google account disconnected successfully"
}
```
