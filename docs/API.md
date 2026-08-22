# Task Manage API

## Base URL

All endpoints are prefixed with `/api`. For a local WAMP installation, the base URL is normally:

```
http://localhost/task_manage_api/public/api
```

Set `BASE_URL` to the URL used by your environment.

## Conventions

- Send JSON requests with `Content-Type: application/json` and `Accept: application/json`.
- All timestamps are ISO 8601 strings. Dates use `YYYY-MM-DD`; times use `HH:MM` in requests.
- Protected endpoints require `Authorization: Bearer <token>`.
- IDs in route placeholders are integers.
- Successful mutations return JSON. Validation failures return HTTP `422` with Laravel's `message` and `errors` fields.
- A task is only visible to its owner. Using another user's task or subtask ID returns `404`.

## Authentication

Authentication tokens are Laravel Sanctum bearer tokens. Include the token returned by register or login in every protected request:

```
Authorization: Bearer <token>
```

### Health check

`GET /health`

No authentication required.

Response `200`:

```json
{
  "status": "ok",
  "application": "Laravel"
}
```

### Register

`POST /register`

Rate limited to 6 requests per minute.

| Field | Required | Rules |
| --- | --- | --- |
| `name` | Yes | String, maximum 255 characters |
| `email` | Yes | Valid, unique email address; maximum 255 characters |
| `password` | Yes | Must meet the application's password rules |
| `password_confirmation` | Yes | Must exactly match `password` |
| `device_name` | No | String, maximum 255 characters; defaults to `api-client` |

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "ExamplePassword123!",
  "password_confirmation": "ExamplePassword123!",
  "device_name": "web-app"
}
```

Response `201`:

```json
{
  "message": "Authenticated successfully.",
  "token": "1|...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "created_at": "2026-08-22T00:00:00.000000Z",
    "updated_at": "2026-08-22T00:00:00.000000Z"
  }
}
```

### Login

`POST /login`

Rate limited to 6 requests per minute.

| Field | Required | Rules |
| --- | --- | --- |
| `email` | Yes | Valid email address |
| `password` | Yes | String |
| `device_name` | No | String, maximum 255 characters; defaults to `api-client` |

```json
{
  "email": "jane@example.com",
  "password": "ExamplePassword123!",
  "device_name": "web-app"
}
```

Response `200` has the same shape as the registration response. Invalid credentials return `422`:

```json
{ "message": "Invalid credentials." }
```

### Current user

`GET /user` — authentication required.

Returns the authenticated user object with HTTP `200`.

### Logout

`POST /logout` — authentication required.

Revokes the bearer token used for this request.

Response `200`:

```json
{ "message": "Logged out successfully." }
```

## Tasks

All task endpoints require authentication.

### Task fields

| Field | Create | Update | Rules / default |
| --- | --- | --- | --- |
| `title` | Required | Optional | String, max 255 |
| `description` | Optional | Optional | String or `null` |
| `category` | Optional | Optional | String, max 255, or `null` |
| `priority` | Optional | Optional | `low`, `medium`, or `high`; default `medium` |
| `date` | Required | Optional | Valid date |
| `start_time` | Optional | Optional | `HH:MM` or `null` |
| `end_time` | Optional | Optional | `HH:MM` or `null` |
| `latitude` | Optional | Optional | Number from -90 to 90, or `null` |
| `longitude` | Optional | Optional | Number from -180 to 180, or `null` |
| `status` | Optional | Optional | `pending`, `in_progress`, `completed`, or `cancelled`; default `pending` |
| `enabled` | Optional | Optional | Boolean; default `true` |
| `completion_percentage` | Optional | Optional | Integer from 0 to 100; default 0 |
| `notes` | Optional | Optional | String or `null` |
| `subtasks` | Optional | Not accepted | Array of subtask objects; create a subtask only when creating its main task |

### List tasks

`GET /tasks`

Optional query parameters:

| Parameter | Rules | Description |
| --- | --- | --- |
| `date` | Date | Return tasks on this date |
| `status` | Status value | Return tasks with this status |
| `per_page` | Integer | Page size; defaults to 15 |
| `page` | Integer | Pagination page |

Each paginated task includes its `subtasks` array. Response `200` is Laravel's paginator object, with `data`, pagination links, and metadata.

### Create a task with subtasks

`POST /tasks`

Use one request to create a main task and its subtasks. There is no separate subtask-creation endpoint.

Subtask fields in `subtasks[]`:

| Field | Required | Rules |
| --- | --- | --- |
| `title` | Yes | String, maximum 255 characters |
| `description` | No | String or `null` |
| `status` | No | `pending`, `in_progress`, `completed`, or `cancelled` |
| `completion_percentage` | No | Integer from 0 to 100 |
| `notes` | No | String or `null` |

If a subtask is created with `status: "completed"`, its completion percentage is set to `100` and its completion time is recorded. If every supplied subtask is completed, the main task is also created as completed with a completion percentage of `100`.

```json
{
  "title": "Release version 1.0",
  "description": "Prepare the production release.",
  "priority": "high",
  "date": "2026-08-22",
  "start_time": "09:00",
  "end_time": "17:00",
  "latitude": 6.9271,
  "longitude": 79.8612,
  "subtasks": [
    { "title": "Run regression tests" },
    { "title": "Publish release notes", "status": "in_progress", "completion_percentage": 50 }
  ]
}
```

Response `201`:

```json
{
  "message": "Task created successfully.",
  "task": {
    "id": 10,
    "user_id": 1,
    "title": "Release version 1.0",
    "status": "pending",
    "completion_percentage": 0,
    "subtasks": [
      {
        "id": 21,
        "task_id": 10,
        "title": "Run regression tests",
        "status": "pending",
        "completion_percentage": 0,
        "completed_at": null
      }
    ]
  }
}
```

### Get one task

`GET /tasks/{task}`

Returns `200` with the task, all of its subtasks, live weather data, and weather suggestions:

```json
{
  "task": { "id": 10, "title": "Release version 1.0", "subtasks": [] },
  "weather": { "current": {} },
  "weather_suggestions": ["Warm conditions are expected. Stay hydrated if the task involves being outdoors."]
}
```

When the task has both `latitude` and `longitude`, the API calls the weather provider with those coordinates, saves a fresh forecast to the task, and returns suggestions based on rain, temperature, and wind. The task date is used when a daily forecast is available. If weather cannot be fetched, the previously saved weather (if any) and suggestions derived from it are returned instead.

### Update task details

`PUT /tasks/{task}` or `PATCH /tasks/{task}`

Send only the task fields that should change. `subtasks` is not accepted here; subtask creation is only part of `POST /tasks` and their statuses use the endpoint below.

```json
{
  "priority": "medium",
  "notes": "Deployment moved to Friday."
}
```

Response `200`:

```json
{ "message": "Task updated successfully.", "task": {} }
```

### Update main-task status

`PATCH /tasks/{task}/status`

| Field | Required | Rules |
| --- | --- | --- |
| `status` | Yes | `pending`, `in_progress`, `completed`, or `cancelled` |

```json
{ "status": "completed" }
```

Setting a main task to `completed` automatically sets its `completion_percentage` to `100`.

Response `200`:

```json
{ "message": "Task status updated successfully.", "task": {} }
```

### Delete task

`DELETE /tasks/{task}`

Deletes the task and all of its subtasks.

Response `200`:

```json
{ "message": "Task deleted successfully." }
```

## Subtask status

Subtasks are created with their main task and are read from task responses. Their status is updated through the following protected endpoint.

### Update subtask status

`PATCH /tasks/{task}/subtasks/{subTask}/status`

| Field | Required | Rules |
| --- | --- | --- |
| `status` | Yes | `pending`, `in_progress`, `completed`, or `cancelled` |

```json
{ "status": "completed" }
```

When set to `completed`, the subtask is assigned `completion_percentage: 100` and a `completed_at` timestamp. Changing it to any other status clears `completed_at`.

When every subtask belonging to the main task is completed, the API automatically sets the main task's status to `completed` and its completion percentage to `100`. If a completed main task later has a subtask moved out of `completed`, its status changes to `in_progress` and its completion percentage becomes the rounded average of its subtasks.

Response `200`:

```json
{
  "message": "Subtask status updated successfully.",
  "subtask": {
    "id": 21,
    "task_id": 10,
    "status": "completed",
    "completion_percentage": 100,
    "completed_at": "2026-08-22T10:30:00.000000Z"
  },
  "task": {
    "id": 10,
    "status": "completed",
    "completion_percentage": 100,
    "subtasks": []
  }
}
```

## Weather

### Get forecast

`GET /weather`

Authentication required.

| Query parameter | Required | Rules | Default |
| --- | --- | --- | --- |
| `latitude` | Yes | Number from -90 to 90 | — |
| `longitude` | Yes | Number from -180 to 180 | — |
| `forecast_days` | No | Integer from 1 to 16 | 7 |
| `timezone` | No | String, maximum 64 characters | `auto` |

Example: `GET /weather?latitude=6.9271&longitude=79.8612&forecast_days=7&timezone=Asia%2FColombo`

The response is the forecast object supplied by the weather provider. If that service is unavailable, the API returns `502`:

```json
{ "message": "Weather service is temporarily unavailable." }
```

## Common errors

| HTTP status | Meaning |
| --- | --- |
| `401` | Missing, invalid, or revoked authentication token |
| `404` | Endpoint, task, or subtask was not found (including resources owned by another user) |
| `422` | Validation failed or login credentials are invalid |
| `429` | Registration or login rate limit exceeded |
| `502` | Weather provider was unavailable |
