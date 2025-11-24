<?php
/**
 * Genera un token CSRF y lo almacena en sesión
 * @return string Token CSRF
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF
 * @param string $token Token a verificar
 * @return bool True si es válido
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escapa una cadena para uso seguro en HTML
 * @param string $string Cadena a escapar
 * @return string Cadena escapada
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida y limpia un input
 * @param string $data Datos a validar
 * @return string Datos limpios
 */
function validate_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Verifica si un archivo es una imagen válida
 * @param array $file Array $_FILES
 * @return bool True si es válida
 */
function is_valid_image($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mime, $allowed_types);
}

/**
 * Guarda una imagen subida
 * @param array $file Array $_FILES
 * @param string $upload_dir Directorio de destino
 * @return string|false Ruta relativa de la imagen o false
 */
function save_image($file, $upload_dir = 'uploads/') {
    if (!is_valid_image($file)) {
        return false;
    }
    
    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $extension;
    $destination = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $destination;
    }
    
    return false;
}

/**
 * Elimina una imagen del servidor
 * @param string $image_path Ruta de la imagen
 * @return bool True si se eliminó exitosamente
 */
function delete_image($image_path) {
    if ($image_path && file_exists($image_path)) {
        return unlink($image_path);
    }
    return false;
}

/**
 * Redirige a una URL
 * @param string $url URL de destino
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Establece un mensaje flash en sesión
 * @param string $type Tipo de mensaje (success, error, warning)
 * @param string $message Mensaje
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtiene y limpia el mensaje flash
 * @return array|null Mensaje flash o null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Calcula la paginación
 * @param int $total_items Total de elementos
 * @param int $per_page Elementos por página
 * @param int $current_page Página actual
 * @return array Datos de paginación
 */
function paginate($total_items, $per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_items' => $total_items,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

/**
 * Valida un precio
 * @param mixed $price Precio a validar
 * @return bool True si es válido
 */
function is_valid_price($price) {
    return is_numeric($price) && $price >= 0;
}

/**
 * Valida stock
 * @param mixed $stock Stock a validar
 * @return bool True si es válido
 */
function is_valid_stock($stock) {
    return is_numeric($stock) && $stock >= 0 && $stock == (int)$stock;
}

/**
 * Envía una respuesta JSON
 * @param mixed $data Datos a enviar
 * @param int $status_code Código de estado HTTP
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Función placeholder para verificar autenticación (para futuro uso)
 * @return bool True si está autenticado
 */
function is_logged_in() {
    // Implementar cuando se agregue autenticación
    return true;
}
?>