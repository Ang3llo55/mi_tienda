<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Configurar headers para JSON
header('Content-Type: application/json');

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener acción desde query string
$action = $_GET['action'] ?? '';

// Obtener conexión
$conn = get_db_connection();
if (!$conn) {
    json_response(['error' => 'Error de conexión a la base de datos'], 500);
}

/**
 * GET /api.php?action=list
 * Lista todos los productos (con paginación opcional)
 */
if ($method === 'GET' && $action === 'list') {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 10;
    $search = isset($_GET['search']) ? validate_input($_GET['search']) : '';
    $category = isset($_GET['category']) ? validate_input($_GET['category']) : '';
    
    // Construir filtros
    $where_clauses = [];
    $params = [];
    $param_count = 1;
    
    if ($search) {
        $where_clauses[] = "name ILIKE $" . $param_count++;
        $params[] = "%$search%";
    }
    
    if ($category) {
        $where_clauses[] = "category = $" . $param_count++;
        $params[] = $category;
    }
    
    $where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    
    // Contar total
    $count_query = "SELECT COUNT(*) as total FROM products $where_sql";
    $count_result = execute_prepared_query($conn, 'api_count_' . md5($where_sql), $count_query, $params);
    $total = $count_result ? (int)pg_fetch_assoc($count_result)['total'] : 0;
    
    // Obtener productos
    $pagination = paginate($total, $per_page, $page);
    $params[] = $per_page;
    $params[] = $pagination['offset'];
    
    $query = "SELECT * FROM products $where_sql ORDER BY created_at DESC LIMIT $" . $param_count++ . " OFFSET $" . $param_count;
    $result = execute_prepared_query($conn, 'api_list_' . md5($where_sql), $query, $params);
    
    $products = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    json_response([
        'success' => true,
        'data' => $products,
        'pagination' => [
            'current_page' => $pagination['current_page'],
            'per_page' => $pagination['per_page'],
            'total' => $total,
            'total_pages' => $pagination['total_pages']
        ]
    ]);
}

/**
 * GET /api.php?action=get&id=X
 * Obtiene un producto por ID
 */
if ($method === 'GET' && $action === 'get') {
    $id = $_GET['id'] ?? null;
    
    if (!$id || !is_numeric($id)) {
        json_response(['error' => 'ID inválido o no proporcionado'], 400);
    }
    
    $query = "SELECT * FROM products WHERE id = $1";
    $result = execute_prepared_query($conn, 'api_get_product', $query, [(int)$id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        json_response(['error' => 'Producto no encontrado'], 404);
    }
    
    $product = pg_fetch_assoc($result);
    json_response(['success' => true, 'data' => $product]);
}

/**
 * POST /api.php?action=create
 * Crea un nuevo producto (espera JSON body)
 */
if ($method === 'POST' && $action === 'create') {
    // Leer JSON del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        json_response(['error' => 'JSON inválido'], 400);
    }
    
    // Validar campos requeridos
    $errors = [];
    
    if (empty($data['name'])) {
        $errors[] = 'El nombre es obligatorio';
    }
    
    if (!isset($data['price']) || !is_valid_price($data['price'])) {
        $errors[] = 'El precio debe ser un número mayor o igual a 0';
    }
    
    if (!isset($data['stock']) || !is_valid_stock($data['stock'])) {
        $errors[] = 'El stock debe ser un número entero mayor o igual a 0';
    }
    
    if (!empty($errors)) {
        json_response(['error' => 'Errores de validación', 'details' => $errors], 400);
    }
    
    // Insertar producto
    $query = "INSERT INTO products (name, description, price, stock, category, image_path, created_at) 
              VALUES ($1, $2, $3, $4, $5, $6, NOW()) RETURNING *";
    
    $params = [
        validate_input($data['name']),
        validate_input($data['description'] ?? '') ?: null,
        $data['price'],
        $data['stock'],
        validate_input($data['category'] ?? '') ?: null,
        validate_input($data['image_path'] ?? '') ?: null
    ];
    
    $result = execute_prepared_query($conn, 'api_create_' . uniqid(), $query, $params);
    
    if (!$result) {
        json_response(['error' => 'Error al crear el producto'], 500);
    }
    
    $product = pg_fetch_assoc($result);
    json_response(['success' => true, 'data' => $product, 'message' => 'Producto creado exitosamente'], 201);
}

/**
 * PUT /api.php?action=update&id=X
 * Actualiza un producto existente (espera JSON body)
 */
if ($method === 'PUT' && $action === 'update') {
    $id = $_GET['id'] ?? null;
    
    if (!$id || !is_numeric($id)) {
        json_response(['error' => 'ID inválido o no proporcionado'], 400);
    }
    
    // Verificar que el producto existe
    $check_query = "SELECT id FROM products WHERE id = $1";
    $check_result = execute_prepared_query($conn, 'api_check_product', $check_query, [(int)$id]);
    
    if (!$check_result || pg_num_rows($check_result) === 0) {
        json_response(['error' => 'Producto no encontrado'], 404);
    }
    
    // Leer JSON del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        json_response(['error' => 'JSON inválido'], 400);
    }
    
    // Validar campos si están presentes
    $errors = [];
    
    if (isset($data['name']) && empty($data['name'])) {
        $errors[] = 'El nombre no puede estar vacío';
    }
    
    if (isset($data['price']) && !is_valid_price($data['price'])) {
        $errors[] = 'El precio debe ser un número mayor o igual a 0';
    }
    
    if (isset($data['stock']) && !is_valid_stock($data['stock'])) {
        $errors[] = 'El stock debe ser un número entero mayor o igual a 0';
    }
    
    if (!empty($errors)) {
        json_response(['error' => 'Errores de validación', 'details' => $errors], 400);
    }
    
    // Construir actualización dinámica
    $updates = [];
    $params = [];
    $param_count = 1;
    
    if (isset($data['name'])) {
        $updates[] = "name = $" . $param_count++;
        $params[] = validate_input($data['name']);
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = $" . $param_count++;
        $params[] = validate_input($data['description']) ?: null;
    }
    
    if (isset($data['price'])) {
        $updates[] = "price = $" . $param_count++;
        $params[] = $data['price'];
    }
    
    if (isset($data['stock'])) {
        $updates[] = "stock = $" . $param_count++;
        $params[] = $data['stock'];
    }
    
    if (isset($data['category'])) {
        $updates[] = "category = $" . $param_count++;
        $params[] = validate_input($data['category']) ?: null;
    }
    
    if (isset($data['image_path'])) {
        $updates[] = "image_path = $" . $param_count++;
        $params[] = validate_input($data['image_path']) ?: null;
    }
    
    if (empty($updates)) {
        json_response(['error' => 'No hay campos para actualizar'], 400);
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = (int)$id;
    
    $query = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = $" . $param_count . " RETURNING *";
    $result = execute_prepared_query($conn, 'api_update_' . uniqid(), $query, $params);
    
    if (!$result) {
        json_response(['error' => 'Error al actualizar el producto'], 500);
    }
    
    $product = pg_fetch_assoc($result);
    json_response(['success' => true, 'data' => $product, 'message' => 'Producto actualizado exitosamente']);
}

/**
 * DELETE /api.php?action=delete&id=X
 * Elimina un producto
 */
if ($method === 'DELETE' && $action === 'delete') {
    $id = $_GET['id'] ?? null;
    
    if (!$id || !is_numeric($id)) {
        json_response(['error' => 'ID inválido o no proporcionado'], 400);
    }
    
    // Obtener imagen antes de eliminar
    $query = "SELECT image_path FROM products WHERE id = $1";
    $result = execute_prepared_query($conn, 'api_get_image_delete', $query, [(int)$id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        json_response(['error' => 'Producto no encontrado'], 404);
    }
    
    $product = pg_fetch_assoc($result);
    
    // Eliminar producto
    $delete_query = "DELETE FROM products WHERE id = $1";
    $delete_result = execute_prepared_query($conn, 'api_delete_' . uniqid(), $delete_query, [(int)$id]);
    
    if (!$delete_result) {
        json_response(['error' => 'Error al eliminar el producto'], 500);
    }
    
    // Eliminar imagen si existe
    if ($product['image_path']) {
        delete_image($product['image_path']);
    }
    
    json_response(['success' => true, 'message' => 'Producto eliminado exitosamente']);
}

// Si no coincide ninguna ruta
json_response([
    'error' => 'Endpoint no encontrado',
    'help' => [
        'GET /api.php?action=list' => 'Lista productos',
        'GET /api.php?action=get&id=X' => 'Obtiene producto por ID',
        'POST /api.php?action=create' => 'Crea producto (JSON body)',
        'PUT /api.php?action=update&id=X' => 'Actualiza producto (JSON body)',
        'DELETE /api.php?action=delete&id=X' => 'Elimina producto'
    ]
], 404);

close_db_connection();
?>