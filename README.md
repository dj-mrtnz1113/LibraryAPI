<h1 style="font-family: 'Poppins';">LIBRARY API</h1>

The **Library API** is a RESTful web service that allows users to interact with a library database. This API enables users to perform actions such as adding, retrieving, updating, and deleting books, authors, and other resources in the library system. The API is built using **Slim Framework** for routing and **JWT (JSON Web Tokens)** for authentication.

---

## Technologies Used

- **Slim Framework**: A micro-framework for PHP that helps in creating APIs and web applications by providing routing and other essential tools for building RESTful services.
- **JWT (JSON Web Token)**: A secure and compact way to represent claims between two parties. JWT is used for securely transmitting information between the client and server.
- **PSR-7**: The **PSR-7 HTTP message** interface defines common methods for handling HTTP requests and responses. We are using it with Slim Framework for HTTP message handling.

## Code Snippet (JWT Authentication and Slim Framework Setup)

```php
use \psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require '../src/vendor/autoload.php';

$app = new \Slim\App;
```

## Endpoints

### 1. **Admin Registration**
- **Method**: `POST`
- **Endpoint**: `/admin/register`
- **Description**: Registers a new admin user. The registration is successful if the username does not already exist in the database. The password is hashed using SHA256 for security.

#### Example Request:
```bash
POST /admin/register
Content-Type: application/json

{
    "username": "adminuser",
    "password": "securepassword"
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": null
}
```

### 2. **User Registration**
- **Method**: `POST`
- **Endpoint**: `/user/register`
- **Description**: Registers a new regular user. The registration is successful if the username does not already exist in the database. The password is hashed using SHA256 for security, and the user is assigned a roleid of 2 (User).

#### Example Request:
```bash
POST /user/register
Content-Type: application/json

{
    "username": "newuser",
    "password": "newpassword"
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": null
}
```

### 3. **User Authentication**
- **Method**: `POST`
- **Endpoint**: `/user/authenticate`
- **Description**: Authenticates a user by checking the provided username and password. If the credentials are valid, the server generates a JWT token that the user can use to authenticate future requests. The password is hashed using SHA256 for security.

#### Example Request:
```bash
POST /user/authenticate
Content-Type: application/json

{
    "username": "newuser",
    "password": "userpassword"
}
```
#### Example Response:
```bash
{
    "status": "success",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDJ9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y",
    "data": null
}
```

### 4. **Change User Password**
- **Method**: `POST`
- **Endpoint**: `/user/changepassword`
- **Description**: Allows an authenticated user to change their password. The user must provide the old password and the new password. The old password is checked, and if it matches the current password, it is updated to the new one. A new JWT token is generated for the user after the password change, and the old token is marked as used.

#### Example Request:
```bash
POST /user/changepassword
Content-Type: application/json
Authorization: Bearer <jwt_token>

{
    "old_password": "oldpassword",
    "new_password": "newpassword"
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDJ9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"}
}
```


### 5. **Add a Book (Only by Admin)**
- **Method**: `POST`
- **Endpoint**: `/admin/addbook`
- **Description**: Allows an authenticated admin user to add a new book to the library. The admin must provide the book's name and the author's name. If the author does not exist, a new author is created. The book is linked to the author, and the action is logged with a new JWT token.

#### Example Request:
```bash
POST /admin/addbook
Content-Type: application/json
Authorization: Bearer <jwt_token>

{
    "book_name": "The Great Gatsby",
    "author_name": "F. Scott Fitzgerald"
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"}
}
```

### 6. **Delete a Book**
- **Method**: `DELETE`
- **Endpoint**: `/admin/deletebook`
- **Description**: Allows an authenticated admin user to delete a book from the library. The admin needs to provide the book's ID. If successful, the book and its associated author relationship will be deleted. A new JWT token will be issued.

#### Example Request:
```bash
DELETE /admin/deletebook
Content-Type: application/json
Authorization: Bearer <jwt_token>

{
    "book_id": 123
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"}
    }
```

### 7. **Update Book Title and Author Name**
- **Method**: `PUT`
- **Endpoint**: `/admin/updatebook`
- **Description**: Allows an authenticated admin user to update the title of an existing book and change its associated author. If the new author does not exist, a new entry will be created in the `authors` table.

#### Example Request:
```bash
PUT /admin/updatebook
Content-Type: application/json
Authorization: Bearer <jwt_token>

{
    "book_id": 123,
    "new_book_name": "New Book Title",
    "new_author_name": "New Author Name"
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"}
}
```


### 8. **View All Books and Authors**
- **Method**: `GET`
- **Endpoint**: `/books`
- **Description**: Allows the user to retrieve a list of all books along with their respective authors. The request requires a valid JWT token for authentication.

#### Example Request:
```bash
GET /books
Content-Type: application/json
Authorization: Bearer <jwt_token>
```

#### Example Response:
```bash
{
    "status": "success",
    "data": [
        {
            "bookid": 1,
            "title": "Book Title 1",
            "author": "Author Name 1"
        },
        {
            "bookid": 2,
            "title": "Book Title 2",
            "author": "Author Name 2"
        }
    ],
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"
}
```

### 9. **Search for Books by Title or Author**
- **Method**: `POST`
- **Endpoint**: `/user/books/search`
- **Description**: Allows the user to search for books by either the title or author name. The request requires a valid JWT token for authentication.

#### Example Request:
```bash
POST /user/books/search
Content-Type: application/json
Authorization: Bearer <valid_jwt_token>

{
    "query": "Through"
}
```
#### Example Response:
```bash
{
    "status": "success",
    "data": [
        {
            "bookid": 8,
            "title": "Through My Window",
            "author": "Ariana Godoy"
        },
        {
            "bookid": 212,
            "title": "Through My Window Book 2",
            "author": "Ariana Godoy"
        }
    ],
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"
}
```


### 10. **Delete User**
- **Method**: `DELETE`
- **Endpoint**: `/admin/deleteuser`
- **Description**: Allows an admin user to delete another user. The request requires a valid JWT token for authentication and authorization.

#### Example Request:
```bash
DELETE /admin/deleteuser
Content-Type: application/json
Authorization: Bearer <valid_jwt_token>

{
    "userid": 123
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"}
}
```

### 11. **Add Book to Favorites**
- **Method**: `POST`
- **Endpoint**: `/user/addfavorite`
- **Description**: Allows a user to add a book to their list of favorites. This request requires a valid JWT token for authentication.

#### Example Request:
```bash
POST /user/addfavorite
Content-Type: application/json
Authorization: Bearer <valid_jwt_token>

{
    "collectionid": 123
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"}
}
```

### 12. **Remove Book from Favorites**
- **Method**: `DELETE`
- **Endpoint**: `/user/removefavorite`
- **Description**: Allows a user to remove a book from their list of favorites. This request requires a valid JWT token for authentication.

#### Example Request:
```bash
DELETE /user/removefavorite
Content-Type: application/json
Authorization: Bearer <valid_jwt_token>

{
    "favoriteid": 456
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"}
}
```

