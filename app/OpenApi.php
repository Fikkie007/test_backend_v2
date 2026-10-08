<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Product API', version: '1.0.0', description: 'Authentication and product management API')]
#[OA\Server(url: '/', description: 'Current server')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'token')]
#[OA\Get(path: '/api/products', summary: 'List products', responses: [new OA\Response(response: 200, description: 'Product list')])]
#[OA\Get(path: '/api/products/{product}', summary: 'Get a product', parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Product detail'), new OA\Response(response: 404, description: 'Not found')])]
#[OA\Post(path: '/api/auth/register', summary: 'Register', responses: [new OA\Response(response: 201, description: 'Registered'), new OA\Response(response: 400, description: 'Validation error')])]
#[OA\Post(path: '/api/auth/login', summary: 'Login', responses: [new OA\Response(response: 200, description: 'Authenticated'), new OA\Response(response: 401, description: 'Invalid credentials')])]
#[OA\Post(path: '/api/auth/refresh', summary: 'Refresh token', responses: [new OA\Response(response: 200, description: 'Token refreshed'), new OA\Response(response: 401, description: 'Invalid refresh token')])]
#[OA\Post(path: '/api/auth/logout', summary: 'Logout', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Logged out'), new OA\Response(response: 401, description: 'Unauthenticated')])]
#[OA\Post(path: '/api/products', summary: 'Create product', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 400, description: 'Validation error')])]
#[OA\Put(path: '/api/products/{product}', summary: 'Update product', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Updated'), new OA\Response(response: 404, description: 'Not found')])]
#[OA\Delete(path: '/api/products/{product}', summary: 'Delete product', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Deleted'), new OA\Response(response: 404, description: 'Not found')])]
class OpenApi
{
}
