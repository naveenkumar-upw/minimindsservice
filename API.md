# MiniMinds Service API Documentation

## Table of Contents
- [Base URL](#base-url)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
  - [Categories](#1-categories-api)
  - [Stories](#2-stories-api)
  - [Device Token](#3-device-token-api)
  - [Authors](#4-authors-api)
  - [Quotes](#5-quotes-api)
- [Common Response Formats](#common-response-formats)
- [Error Handling](#error-handling)

## Base URL
All API endpoints are relative to the base URL of your server.

## Authentication

The API uses two authentication methods:
1. **JWT (JSON Web Token)**: Used for session-based authentication
2. **API Key**: Used for long-term API access

### Authentication Headers

- For JWT: `Authorization: Bearer <token>`
- For API Key: `X-API-Key: <your-api-key>`

### Authentication Endpoints

#### 1. Login
**Endpoint:** `/api_auth.php`  
**Method:** POST  
**Description:** Authenticate user and get JWT token

##### Request Body
```json
{
    "action": "login",
    "username": "your-username",
    "password": "your-password"
}
```

##### Success Response
```json
{
    "success": true,
    "token": "jwt-token-here",
    "user": {
        "id": 1,
        "username": "username",
        "role": "admin"
    }
}
```

#### 2. Register
**Endpoint:** `/api_auth.php`  
**Method:** POST  
**Description:** Register new user and get API key

##### Request Body
```json
{
    "action": "register",
    "username": "new-username",
    "email": "user@example.com",
    "password": "password"
}
```

##### Success Response
```json
{
    "success": true,
    "message": "Registration successful",
    "api_key": "your-api-key-here"
}
```

#### 3. Verify Token
**Endpoint:** `/api_auth.php`  
**Method:** POST  
**Description:** Verify authentication token or API key

##### Request Body
```json
{
    "action": "verify"
}
```

##### Success Response
```json
{
    "success": true,
    "message": "Authentication valid",
    "user": {
        "id": 1,
        "username": "username",
        "role": "role"
    }
}
```

#### 4. Logout
**Endpoint:** `/api_logout.php`  
**Method:** POST  
**Description:** Invalidate JWT token  
**Authentication:** Required (JWT token)

##### Success Response
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### Error Responses

#### Unauthorized (401)
```json
{
    "success": false,
    "message": "Unauthorized"
}
```

#### Invalid Credentials
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

#### Invalid Token
```json
{
    "success": false,
    "message": "Invalid token"
}
```

## API Endpoints

### 1. Categories API
**Endpoint:** `/api_categories.php`  
**Method:** GET  
**Description:** Retrieves all available categories.  
**Authentication:** Required for POST/DELETE operations (Admin only)

#### Response Format
```json
{
    "categories": [
        {
            "id": 1,
            "name": "Category Name",
            "thumbnail": "thumbnail_path.jpg"
        }
    ]
}
```

#### Fields Description
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Unique identifier for the category |
| name | string | Name of the category |
| thumbnail | string | Path to the category thumbnail image |

---

### 2. Stories API
**Endpoint:** `/api_stories.php`  
**Method:** GET  
**Description:** Retrieves all stories with their associated category information.  
**Authentication:** Required for POST/DELETE operations (Admin only)

#### Response Format
```json
{
    "stories": [
        {
            "id": 1,
            "category_id": 1,
            "category_name": "Category Name",
            "title": "Story Title",
            "content": "Story Content",
            "language": "Language",
            "country": "Country",
            "state": "State",
            "images": ["image1.jpg", "image2.jpg"],
            "read_time": 5
        }
    ]
}
```

#### Fields Description
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Unique identifier for the story |
| category_id | integer | ID of the associated category |
| category_name | string | Name of the associated category |
| title | string | Title of the story |
| content | string | Main content of the story |
| language | string | Language of the story |
| country | string | Country associated with the story |
| state | string | State/region associated with the story |
| images | array | Array of image paths associated with the story |
| read_time | integer | Estimated reading time in minutes |

---

### 3. Device Token API
**Endpoint:** `/api_device.php`  
**Description:** Manages device token registration for push notifications.  
**Authentication:** Required (Any authenticated user)

#### Register Device Token
**Method:** POST  
**Content-Type:** application/json

##### Request Body
```json
{
    "device_token": "your-device-token-here"
}
```

##### Success Response (201 Created - New Registration)
```json
{
    "status": "success",
    "message": "Device token registered successfully"
}
```

##### Success Response (200 OK - Already Registered)
```json
{
    "status": "success",
    "message": "Device token already registered"
}
```

#### Unregister Device Token
**Method:** DELETE  
**Content-Type:** application/json

##### Request Body
```json
{
    "device_token": "your-device-token-here"
}
```

##### Success Response
```json
{
    "status": "success",
    "message": "Device token unregistered successfully"
}
```

---

### 4. Authors API
**Endpoint:** `/api_authors.php`  
**Description:** Manages author information including CRUD operations.  
**Authentication:** Required for POST/DELETE operations (Admin only)

#### Get Authors
**Method:** GET  
**Description:** Retrieve all authors or a specific author

##### Parameters
- `id` (optional): Get specific author by ID

##### Success Response (All Authors)
```json
[
    {
        "id": 1,
        "name": "Author Name",
        "photo": "author_photo.jpg",
        "details": "Author biography"
    }
]
```

#### Create Author
**Method:** POST  
**Content-Type:** multipart/form-data

##### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| name | string | Yes | Author's name |
| details | string | Yes | Author's biography |
| photo | file | No | Author's photo |

##### Success Response
```json
{
    "success": true,
    "id": 1,
    "message": "Author created successfully"
}
```

#### Update Author
**Method:** POST  
**Content-Type:** multipart/form-data

##### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Author's ID |
| name | string | Yes | Updated name |
| details | string | Yes | Updated biography |
| photo | file | No | New photo |

##### Success Response
```json
{
    "success": true,
    "message": "Author updated successfully"
}
```

#### Delete Author
**Method:** DELETE  
**Content-Type:** application/json

##### Request Body
```json
{
    "id": 1
}
```

##### Success Response
```json
{
    "success": true,
    "message": "Author deleted successfully"
}
```

---

### 5. Quotes API
**Endpoint:** `/api_quotes.php`  
**Description:** Manages quotes with author information.  
**Authentication:** Required for POST/DELETE operations (Admin only)

#### Get Quotes
**Method:** GET  
**Description:** Retrieve all quotes or a specific quote with author details

##### Parameters
- `id` (optional): Get specific quote by ID

##### Success Response (All Quotes)
```json
[
    {
        "id": 1,
        "quote": "Quote text",
        "author_id": 1,
        "author_name": "Author Name",
        "author_photo": "author_photo.jpg",
        "language": "en",
        "image": "quote_image.jpg",
        "created_at": "2023-08-01 12:00:00"
    }
]
```

#### Create Quote
**Method:** POST  
**Content-Type:** multipart/form-data

##### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| quote | string | Yes | Quote text |
| author_id | integer | Yes | Author's ID |
| language | string | Yes | Quote language |
| image | file | No | Quote image |

##### Success Response
```json
{
    "success": true,
    "id": 1,
    "message": "Quote created successfully"
}
```

#### Update Quote
**Method:** POST  
**Content-Type:** multipart/form-data

##### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Quote ID |
| quote | string | Yes | Updated quote text |
| author_id | integer | Yes | Author's ID |
| language | string | Yes | Quote language |
| image | file | No | New quote image |

##### Success Response
```json
{
    "success": true,
    "message": "Quote updated successfully"
}
```

#### Delete Quote
**Method:** DELETE  
**Content-Type:** application/json

##### Request Body
```json
{
    "id": 1
}
```

##### Success Response
```json
{
    "success": true,
    "message": "Quote deleted successfully"
}
```

## Common Response Formats

### Success Response Format
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {} // Optional response data
}
```

### Error Response Format
```json
{
    "success": false,
    "message": "Error description"
}
```

## Error Handling

### Common HTTP Status Codes
| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 404 | Not Found |
| 500 | Server Error |

### Error Response Examples

#### Bad Request (400)
```json
{
    "success": false,
    "message": "Invalid request parameters"
}
```

#### Not Found (404)
```json
{
    "success": false,
    "message": "Resource not found"
}
```

#### Server Error (500)
```json
{
    "success": false,
    "message": "Internal server error"
}
```

## Notes
- All POST requests involving file uploads must use `multipart/form-data`
- All responses are in JSON format
- Image paths are relative to the server's upload directory
- All DELETE operations require the ID of the resource to be deleted
- Success responses include a boolean `success` field and a descriptive `message`
- Most endpoints require authentication for write operations (POST, PUT, DELETE)
- Admin role is required for most write operations
- JWT tokens expire after 1 hour
- API keys do not expire but can be revoked
