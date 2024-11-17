# LIBRARY API

The **Library API** is a RESTful web service that allows users to interact with a library database. This API enables users to perform actions such as adding, retrieving, updating, and deleting books, authors, and other resources in the library system. The API is built using **Slim Framework** for routing and **JWT (JSON Web Tokens)** for authentication.

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
Authorization: Bearer <existing_jwt_token>

{
    "old_password": "oldpassword",
    "new_password": "newpassword"
}

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDJ9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"
    }
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
Authorization: Bearer <admin_jwt_token>

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
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"
    }
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
Authorization: Bearer <admin_jwt_token>

{
    "book_id": 123
}
```

#### Example Response:
```bash
{
    "status": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiAiaHR0cHM6Ly9saWJyYXJ5Lm9yZyIsImF1ZCI6ICJodHRwczovL2xpYnJhcnkub3JnIiwiaWF0IjogMTY4MzQ1Mzc2MywiZXhwIjogMTY4MzQ1NzYwMywiZGF0YSI6IHsiaWR1c2VySWQiOiAxMiwgInJvbGVpZCI6IDF9fQ.X4dqKjKgHfFvPOtLnDlEqf5zwQtG2BYDs5KvP6L3E8Y"
    }
}
```
