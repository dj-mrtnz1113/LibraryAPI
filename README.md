# LIBRARY API

The **Library API** is a RESTful web service that allows users to interact with a library database. This API enables users to perform actions such as adding, retrieving, updating, and deleting books, authors, and other resources in the library system.

## Endpoints

### 1. **Get All Books**
- **Method**: `GET`
- **Endpoint**: `/api/books`
- **Description**: Retrieves a list of all books available in the library.

#### Example Request:
```bash
GET /api/books
