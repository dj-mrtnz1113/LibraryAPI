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
