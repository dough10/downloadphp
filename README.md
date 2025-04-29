# Download Manager API Documentation

A PHP-based file download manager with rate limiting and session management.

## Endpoints

### File Operations

#### Get File
```http
GET /files/{file}
```
Downloads a specific file from the user's directory.

**Parameters:**
- `file` (path): Name of the file to download

**Headers:**
- `Content-Type`: File's mime type
- `Content-Length`: File size in bytes
- `Cache-Control`: no-store
- `Content-Disposition`: attachment; filename="filename"

**Responses:**
- `200`: File downloaded successfully
- `403`: Forbidden access
- `404`: File not found

---

#### Request File Download
```http
POST /request-file/{file}
```
Initiates a file download request and records it in the database.

**Parameters:**
- `file` (path): Name of the file to request

**Returns:**
```json
{
    "ndx": "number",
    "downloads": [
        {
            "id": "number",
            "filename": "string",
            "status": "string",
            "timestamp": "string"
        }
    ]
}
```

**Responses:**
- `200`: Download request recorded
- `403`: Forbidden access
- `404`: File not found
- `500`: Database error

---

### Download Management

#### Update Download Status
```http
POST /file-status/{ndx}/{status}
```
Updates the status of a download in the database.

**Parameters:**
- `ndx` (number): Download index
- `status` (string): New status value

**Returns:**
- Array of current downloads

**Responses:**
- `200`: Status updated successfully
- `500`: Database error

---

#### Reset Downloads
```http
POST /reset
```
Clears all download history from the database.

**Returns:**
- Empty array of downloads

**Responses:**
- `200`: Downloads cleared successfully
- `500`: Database error

---

### Application Resources

#### Get Session JavaScript
```http
GET /session.js
```
Returns JavaScript code for managing client-side session state.

**Headers:**
- `Content-Type`: application/javascript

**Responses:**
- `200`: JavaScript code returned

---

#### Get Main Page
```http
GET /
```
Renders the main application page.

**Returns:**
HTML page with:
- Host information
- Username
- Allowed file extensions
- File list
- Download history

**Responses:**
- `200`: Page rendered successfully
- `500`: Rendering error

---

### Error Handling

#### Catch-all Route
```http
ANY /{routes:.+}
```
Handles all undefined routes.

**Returns:**
```json
{
    "error": "File not found"
}
```

**Responses:**
- `404`: Route not found

## Rate Limiting

The application includes rate limiting middleware that restricts the number of requests per time window. Configure these settings in your `settings.php`:

```php
'limit' => [
    'max-requests' => 100,    // Maximum requests per window
    'limit-window' => 3600    // Time window in seconds
]
```

## Security Features

- Path traversal prevention using `realpath()`
- Session-based user management using auth header when served from nginx
- File extension restrictions
- Rate limiting
- Request logging

## Dependencies

- Slim Framework 4.x
- PHP-DI for dependency injection
- Monolog for logging
- PHP >= 8.0

## Installation

1. Clone the repository
2. Install dependencies:
```bash
npm install
```
3. Configure your web server to point to the `public` directory
4. Copy `config/settings.example.php` to `config/settings.php` and update the settings

## Development

Start the development server:
```bash
npm run host
```
