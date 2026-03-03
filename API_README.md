# Virtual Praise Room API

This API is a PHP-based JSON layer intended to decouple the frontend from the backend.
It uses the existing session cookie for authentication and CSRF tokens for write actions.

## Base URL

All endpoints live under `/api`.

## Auth & CSRF

- **Session cookie** is used for auth (same as the PHP site).
- **CSRF token** is required for any write action.
- Fetch a CSRF token from:
  - `GET /api/csrf`
- Send it in:
  - `X-CSRF-Token` header, or
  - `csrf_token` field in JSON body.

## Endpoints

### Health
- `GET /api`

### Auth
- `POST /api/auth/login`
  - Body: `{ "username": "...", "password": "..." }`
  - Returns: user + `csrf_token`
- `POST /api/auth/logout`
  - Requires CSRF
- `POST /api/auth/register`
  - Body: `{ "username", "email", "password", "account_type", "kc_handle", "country", "city", "region", "satellite_campus", "church" }`
  - Returns: user + `csrf_token`
- `POST /api/auth/forgot`
  - Body: `{ "email": "..." }`
  - Note: reset link is logged server-side for now.

### Session
- `GET /api/me`
  - Returns current session user (401 if not logged in)

### Crusades
- `POST /api/crusades`
  - Requires auth + CSRF + active subscription
  - Body: `{ "title", "description", "video_url", "live_stream_id", "schedule_crusade", "start_time", "end_time" }`
- `GET /api/crusades/{code}`
- `GET /api/crusades?mine=1[&page=&per_page=]`
  - Requires auth
- `GET /api/crusades/{code}/stats`
- `POST /api/crusades/{code}/share`
  - Requires auth + CSRF + active subscription
- `POST /api/crusades/{code}/end`
  - Requires auth + CSRF (owner only)

### Meetings
- `POST /api/meetings`
  - Requires auth + CSRF + active subscription
- `POST /api/meetings/start`
  - Requires auth + CSRF
  - Body: `{ "meeting_id", "duration" }`
- `POST /api/meetings/end`
  - Requires auth + CSRF
  - Body: `{ "meeting_code" }`

### Live Streams
- `GET /api/streams/live`

### Videos
- `GET /api/videos`
  - Filters: `language`, `children_outreach`, `movie_outreach`, `ministrations`
  - Pagination: `page`, `per_page`
- `GET /api/videos/languages`

### Comments (N-gage via API)
- `GET /api/comments?property=...&limit=...`
- `GET /api/comments/updates?property=...&since_id=...&xid=...`
- `POST /api/comments`
  - Body: `{ "comment", "name", "property", "xid" }`
  - Rate limit: 20 comments / 5 minutes per IP + property

## Responses

All responses are JSON:

```
{ "success": true, ... }
```

Errors return:

```
{ "success": false, "message": "..." }
```

## Notes

- This API is session-based and intended for same-origin frontend usage.
- For mobile apps, consider adding token-based auth in a future update.
