<?php
use \psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require '../src/vendor/autoload.php';


$app = new \Slim\App;

// Admin registration
$app->post('/admin/register', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        // Create a new PDO connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the user already exists
        $sql = "SELECT * FROM users WHERE username = '" . $usr . "'";
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll();

        if (count($data) > 0) {
            // If user exists, return an error message
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Username already exists"))));
        } else {
            // If user does not exist, insert new user with roleid 1 (Admin)
            $sql = "INSERT INTO users (username, password, roleid) VALUES ('" . $usr . "', '" . hash('SHA256', $pass) . "', 1)";
            $conn->exec($sql);
            // Return success response
            $response->getBody()->write(json_encode(array("status" => "success", "data" => null)));
        }
    } catch (PDOException $e) {
        // Return error message on exception
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    return $response;
});


// User registration
$app->post('/user/register', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        // Create a new PDO connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the user already exists
        $sql = "SELECT * FROM users WHERE username = '" . $usr . "'";
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll();

        if (count($data) > 0) {
            // If user exists, return an error message
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Username already exists"))));
        } else {
            // If user does not exist, insert new user with roleid 2 (User)
            $sql = "INSERT INTO users (username, password, roleid) VALUES ('" . $usr . "', '" . hash('SHA256', $pass) . "', 2)";
            $conn->exec($sql);
            // Return success response
            $response->getBody()->write(json_encode(array("status" => "success", "data" => null)));
        }
    } catch (PDOException $e) {
        // Return error message on exception
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    return $response;
});



//user authentication
$app->post('/user/authenticate', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM users WHERE username='" . $usr . "' AND password='" . hash('SHA256',$pass) . "'";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $data = $stmt->fetchAll();

        if (count($data) == 1) {
            $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'https://library.org',
                'aud' => 'https://library.org',
                'iat' => $iat,
                'exp' => $iat + 3600,
                "data" => array(
                    "userid" => $data[0]['userid'],
                    "roleid" => $data[0]['roleid']
                )
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
            $response->getBody()->write(json_encode((array("status" => "succes", "token" => $jwt, "data" => null))));
        }else{
            $response->getBody()->write(json_encode(array("status"=>"fail","data"=>array("title"=>"Authentication Failed"))));
        }
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("title"=>$e->getMessage()))));
    }

    return $response;
});

// Change User Password
$app->post('/user/changepassword', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Decode the JWT to get user ID
        $key = 'server_hack';
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        $userid = $decoded->data->userid;

        // Get the old and new passwords from the request body
        $data = json_decode($request->getBody());
        $oldPass = $data->old_password;
        $newPass = $data->new_password;

        // Check the old password
        $sql = "SELECT * FROM users WHERE userid = '" . $userid . "' AND password = '" . hash('SHA256', $oldPass) . "'";
        $stmt = $conn->query($sql);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Update the password if old password matches
            $sql = "UPDATE users SET password = '" . hash('SHA256', $newPass) . "' WHERE userid = '" . $userid . "'";
            $conn->query($sql);

            // Mark the token as used
            $sql = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
            $conn->query($sql);

            // Generate a new token
            $newToken = [
                "iat" => time(),
                "exp" => time() + 3600, // Token valid for 1 hour
                "data" => [
                    "userid" => $userid,
                    "roleid" => $user['roleid'] // Assuming you store roleid in user data
                ]
            ];
            $jwt = JWT::encode($newToken, $key, 'HS256');

            // Return the new token in a single response
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
                "status" => "success",
                "data" => ["token" => $jwt]
            ]));

        } else {
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json')->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Old password is incorrect"]
            ]));
        }

    } catch (Exception $e) {
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "fail",
            "data" => [
                "title" => "Authentication failed or invalid request",
                "error" => $e->getMessage()
            ]
        ]));
    }
});

// Add a book (only by admin)
$app->post('/admin/addbook', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Decode the JWT to get user ID and role
        $key = 'server_hack';
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        $userid = $decoded->data->userid;
        $roleid = $decoded->data->roleid;  // Assuming roleid is included in the JWT payload

        // Check if the user is an admin (roleid 1)
        if ($roleid !== 1) {
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json')->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Access denied: Admins only"]
            ]));
        }

        // Get the book and author details from the request body
        $data = json_decode($request->getBody());
        $bookName = $data->book_name;
        $authorName = $data->author_name;

        // Insert the new book into the books table
        $sqlInsertBook = "INSERT INTO books (title) VALUES ('" . $bookName . "')";
        $conn->query($sqlInsertBook);
        $bookid = $conn->lastInsertId();  // Get the newly created bookid

        // Check if the author exists
        $sqlCheckAuthor = "SELECT authorid FROM authors WHERE name = '" . $authorName . "'";
        $stmt = $conn->query($sqlCheckAuthor);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($author) {
            // If the author exists, get the authorid
            $authorid = $author['authorid'];
        } else {
            // If the author does not exist, insert the author
            $sqlInsertAuthor = "INSERT INTO authors (name) VALUES ('" . $authorName . "')";
            $conn->query($sqlInsertAuthor);
            $authorid = $conn->lastInsertId();  // Get the newly created authorid
        }

        // Insert into the book_authors table to link the book and author
        $sqlInsertBookAuthor = "INSERT INTO book_authors (bookid, authorid) VALUES ('" . $bookid . "', '" . $authorid . "')";
        $conn->query($sqlInsertBookAuthor);

        // Mark the token as used
        $sql = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
        $conn->query($sql);

        // Generate a new token
        $newToken = [
            "iat" => time(),
            "exp" => time() + 3600, // Token valid for 1 hour
            "data" => [
                "userid" => $userid,
                "roleid" => $roleid
            ]
        ];
        $jwt = JWT::encode($newToken, $key, 'HS256');

        // Return the new token in a single response
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "success",
            "data" => ["token" => $jwt]
        ]));

    } catch (Exception $e) {
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "fail",
            "data" => [
                "title" => "Authentication failed or invalid request",
                "error" => $e->getMessage()
            ]
        ]));
    }
});


// Delete book
$app->delete('/admin/deletebook', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Secret key to decode the JWT
        $key = 'server_hack';
        
        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Get the user id and role from the token data
        $userid = $decoded->data->userid;
        $roleid = $decoded->data->roleid;  // Assuming roleid is included in the JWT payload

        // Check if the user is an admin (roleid 1)
        if ($roleid !== 1) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Access denied: Admins only"]
            ]));
        }

        // Get the book ID from the request body
        $data = json_decode($request->getBody());

        if (!isset($data->book_id)) {
            return $response->withStatus(400)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Missing book ID"]
            ]));
        }

        $bookId = $data->book_id;

        // Delete from book_authors table
        $sqlDeleteBookAuthor = "DELETE FROM book_authors WHERE bookid = '" . $bookId . "'";
        $conn->exec($sqlDeleteBookAuthor);

        // Delete the book from the books table
        $sqlDeleteBook = "DELETE FROM books WHERE bookid = '" . $bookId . "'";
        $conn->exec($sqlDeleteBook);

        // Mark the token as used
        $sql = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
        $conn->query($sql);

        // Generate a new token after the transaction
        $newToken = [
            "iat" => time(),
            "exp" => time() + 3600, // Token valid for 1 hour
            "data" => [
                "userid" => $userid,
                "roleid" => $roleid
            ]
        ];
        $jwt = JWT::encode($newToken, $key, 'HS256');

        // Return success response with new token
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "success",
            "data" => ["token" => $jwt]
        ]));

    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => [
                "title" => "Authentication failed or invalid request", 
                "error" => $e->getMessage()
            ]
        ]));
    }
});

// Update book title and author name
$app->put('/admin/updatebook', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Secret key to decode the JWT
        $key = 'server_hack';

        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Get the user id and role from the token data
        $userid = $decoded->data->userid;
        $roleid = $decoded->data->roleid;

        // Check if the user is an admin (roleid 1)
        if ($roleid !== 1) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Access denied: Admins only"]
            ]));
        }

        // Get the book and author details from the request body
        $data = json_decode($request->getBody());

        if (!isset($data->book_id) || !isset($data->new_book_name) || !isset($data->new_author_name)) {
            return $response->withStatus(400)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Missing book or author details"]
            ]));
        }

        $bookId = $data->book_id;
        $newBookName = $data->new_book_name;
        $newAuthorName = $data->new_author_name;

        // Update the book title
        $sqlUpdateBook = "UPDATE books SET title = '" . $newBookName . "' WHERE bookid = '" . $bookId . "'";
        $conn->exec($sqlUpdateBook);

        // Check if the author exists in the authors table
        $sqlCheckAuthor = "SELECT authorid FROM authors WHERE name = '" . $newAuthorName . "'";
        $stmt = $conn->query($sqlCheckAuthor);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($author) {
            $authorid = $author['authorid'];
        } else {
            // Insert the author if it does not exist
            $sqlInsertAuthor = "INSERT INTO authors (name) VALUES ('" . $newAuthorName . "')";
            $conn->exec($sqlInsertAuthor);
            $authorid = $conn->lastInsertId();
        }

        // Delete the existing relationship for the book in the book_authors table
        $sqlDeleteOldBookAuthor = "DELETE FROM book_authors WHERE bookid = '" . $bookId . "'";
        $conn->exec($sqlDeleteOldBookAuthor);

        // Insert the new relationship between the book and the new author
        $sqlInsertBookAuthor = "INSERT INTO book_authors (bookid, authorid) VALUES ('" . $bookId . "', '" . $authorid . "')";
        $conn->exec($sqlInsertBookAuthor);

        // Mark the token as used
        $sql = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
        $conn->query($sql);

        // Generate a new token after the transaction
        $newToken = [
            "iat" => time(),
            "exp" => time() + 3600, // Token valid for 1 hour
            "data" => [
                "userid" => $userid,
                "roleid" => $roleid
            ]
        ];
        $jwt = JWT::encode($newToken, $key, 'HS256');

        // Return success response with the new token
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "success",
            "data" => ["token" => $jwt]
        ]));

    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => [
                "title" => "Authentication failed or invalid request",
                "error" => $e->getMessage()
            ]
        ]));
    }
});


// View all books and authors
$app->get('/books', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Secret key to decode the JWT
        $key = 'server_hack';

        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Fetch all books and their authors
        $sql = "SELECT books.bookid, books.title AS title, authors.name AS author 
                FROM books 
                JOIN book_authors ON books.bookid = book_authors.bookid 
                JOIN authors ON book_authors.authorid = authors.authorid";
        $stmt = $conn->query($sql);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark the token as used
        $sql = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
        $conn->query($sql);

        // Generate a new token after the transaction
        $newToken = [
            "iat" => time(),
            "exp" => time() + 3600, // Token valid for 1 hour
            "data" => [
                "userid" => $decoded->data->userid,
                "roleid" => $decoded->data->roleid
            ]
        ];
        $newJwt = JWT::encode($newToken, $key, 'HS256');

        // Return the list of books and authors with a new token
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "success", 
            "data" => $books, 
            "token" => $newJwt
        ]));

    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => [
                "title" => "Authentication failed or invalid request", 
                "error" => $e->getMessage()
            ]
        ]));
    }
});




// Search for books by title or author
$app->post('/user/books/search', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Secret key to decode the JWT
        $key = 'server_hack';

        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Get the search query from the request body
        $payload = json_decode($request->getBody(), true);

        if (isset($payload['query'])) {
            $searchQuery = $payload['query'];
        } else {
            return $response->withStatus(400)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Search query is missing"]
            ]));
        }

        // Prepare the SQL query to search for books by title or author
        $sql = "
            SELECT b.bookid, b.title AS title, a.name AS author
            FROM books b
            LEFT JOIN book_authors ba ON b.bookid = ba.bookid
            LEFT JOIN authors a ON ba.authorid = a.authorid
            WHERE b.title LIKE :searchQuery OR a.name LIKE :searchQuery
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['searchQuery' => '%' . $searchQuery . '%']);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            // Mark the token as used
            $sql = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
            $conn->query($sql);

            // Generate a new token after the transaction
            $newToken = [
                "iat" => time(),
                "exp" => time() + 3600, // Token valid for 1 hour
                "data" => [
                    "userid" => $decoded->data->userid
                ]
            ];
            $newJwt = JWT::encode($newToken, $key, 'HS256');

            // Return success response with found books and new token
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
                "status" => "success", 
                "data" => $results,
                "token" => $newJwt
            ]));
        } else {
            // Return failure response if no books found
            return $response->withStatus(404)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "No books found matching the query"]
            ]));
        }
    } catch (Exception $e) {
        // If JWT validation or any other error occurs, return failure
        return $response->withStatus(500)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Authentication failed or invalid request", "error" => $e->getMessage()]
        ]));
    }
});


// Delete user
$app->delete('/admin/deleteuser', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Secret key to decode the JWT
    $key = 'server_hack';

    try {
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Get the user id from the token data
        $userid = $decoded->data->userid;

        // Check the role of the user making the request
        $sqlCheckRole = "SELECT roleid FROM users WHERE userid = '" . $userid . "'";
        $stmt = $conn->query($sqlCheckRole);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ensure the requesting user is an admin
        if ($user['roleid'] != 1) { // Assuming 1 is the role ID for admin
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Permission denied"]
            ]));
        }

        // Get the user ID to be deleted from the request body
        $data = json_decode($request->getBody());

        if (isset($data->userid)) {
            $userIdToDelete = $data->userid;
        } else {
            return $response->withStatus(400)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Missing user ID"]
            ]));
        }

        // Delete the user from the users table
        $sqlDeleteUser = "DELETE FROM users WHERE userid = '" . $userIdToDelete . "'";
        $conn->exec($sqlDeleteUser);

        // Mark the token as used
        $sqlInsertUsedToken = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
        $conn->exec($sqlInsertUsedToken);

        // Generate a new token after the transaction
        $newToken = [
            "iat" => time(),
            "exp" => time() + 3600, // Token valid for 1 hour
            "data" => [
                "userid" => $userid,
                "roleid" => $user['roleid']
            ]
        ];
        $newJwt = JWT::encode($newToken, $key, 'HS256');

        // Return success response with the new token
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "success", 
            "data" => [
                "token" => $newJwt
            ]
        ]));

    } catch (Exception $e) {
        // If JWT validation fails or any other error occurs, return failure
        return $response->withStatus(500)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => [
                "title" => "Authentication failed or invalid request", 
                "error" => $e->getMessage()
            ]
        ]));
    }
});




// Add book to favorites
$app->post('/user/addfavorite', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Secret key to decode the JWT
    $key = 'server_hack';

    try {
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Get the user ID from the token data
        $userid = $decoded->data->userid;

        // Get the collection ID from the request body
        $data = json_decode($request->getBody());
        
        if (!isset($data->collectionid)) {
            return $response->withStatus(400)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Missing collection ID"]
            ]));
        }
        
        $collectionid = $data->collectionid;

        // Check if the collectionid exists in the book_authors table
        $sqlCheckCollection = "SELECT * FROM book_authors WHERE collectionid = '" . $collectionid . "'";
        $stmt = $conn->prepare($sqlCheckCollection);
        $stmt->execute();
        $collectionExists = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$collectionExists) {
            return $response->withStatus(400)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Book is not in the list"]
            ]));
        }

        // Insert into favorites table
        $sqlInsertFavorite = "INSERT INTO favorites (userid, collectionid) VALUES ('" . $userid . "', '" . $collectionid . "')";
        $conn->exec($sqlInsertFavorite);

        // Mark the token as used
        $sqlInsertUsedToken = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
        $conn->exec($sqlInsertUsedToken);

        // Generate a new token after the transaction
        $newToken = [
            "iat" => time(),
            "exp" => time() + 3600, // Token valid for 1 hour
            "data" => [
                "userid" => $userid
            ]
        ];
        $newJwt = JWT::encode($newToken, $key, 'HS256');

        // Return success response with the new token
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "success", 
            "data" => [
                "token" => $newJwt
            ]
        ]));

    } catch (Exception $e) {
        // If JWT validation fails or any other error occurs, return failure
        return $response->withStatus(500)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => [
                "title" => "Authentication failed or invalid request", 
                "error" => $e->getMessage()
            ]
        ]));
    }
});



$app->delete('/user/removefavorite', function (Request $request, Response $response, array $args) {
    // Get the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return $response->withStatus(400)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => "Missing token"]
        ]));
    }

    // Extract the token from the Authorization header (e.g., "Bearer <token>")
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    // Secret key to decode the JWT
    $key = 'server_hack';

    try {
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the token has already been used
        $sql = "SELECT * FROM used_tokens WHERE token = '" . $jwt . "'";
        $stmt = $conn->query($sql);
        $usedToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usedToken) {
            return $response->withStatus(403)->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token already used"]
            ]));
        }

        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Get the user ID from the token data
        $userid = $decoded->data->userid;

        // Get the favorite ID from the request body
        $data = json_decode($request->getBody());
        
        if (!isset($data->favoriteid)) {
            return $response->withStatus(400)->getBody()->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Missing favorite ID"]
            ]));
        }
        
        $favoriteid = $data->favoriteid;

        // Delete from favorites table using concatenation
        $sqlDeleteFavorite = "DELETE FROM favorites WHERE favoriteid = '" . $favoriteid . "' AND userid = '" . $userid . "'";
        $conn->exec($sqlDeleteFavorite);

        // Mark the token as used
        $sqlInsertUsedToken = "INSERT INTO used_tokens (token) VALUES ('" . $jwt . "')";
        $conn->exec($sqlInsertUsedToken);

        // Generate a new token after the transaction
        $newToken = [
            "iat" => time(),
            "exp" => time() + 3600, // Token valid for 1 hour
            "data" => [
                "userid" => $userid
            ]
        ];
        $newJwt = JWT::encode($newToken, $key, 'HS256');

        // Return success response with the new token
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
            "status" => "success", 
            "data" => [
                "token" => $newJwt
            ]
        ]));

    } catch (Exception $e) {
        // If JWT validation fails or any other error occurs, return failure
        return $response->withStatus(500)->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => [
                "title" => "Authentication failed or invalid request", 
                "error" => $e->getMessage()
            ]
        ]));
    }
});


$app->run();
?>