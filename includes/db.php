<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'mi_tienda_db');
define('DB_USER', 'mi_tienda_user');
define('DB_PASSWORD', 'tienda123');

// Variable global para la conexión
$db_connection = null;

/**
 * Obtiene la conexión a PostgreSQL
 * @return resource|false Recurso de conexión o false en caso de error
 */
function get_db_connection() {
    global $db_connection;
    
    if ($db_connection !== null) {
        return $db_connection;
    }
    
    $connection_string = sprintf(
        "host=%s port=%s dbname=%s user=%s password=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_USER,
        DB_PASSWORD
    );
    
    $db_connection = @pg_connect($connection_string);
    
    if (!$db_connection) {
        error_log("Error de conexión a PostgreSQL: " . pg_last_error());
        return false;
    }
    
    return $db_connection;
}

/**
 * Cierra la conexión a la base de datos
 */
function close_db_connection() {
    global $db_connection;
    if ($db_connection !== null) {
        pg_close($db_connection);
        $db_connection = null;
    }
}

/**
 * Ejecuta una consulta preparada
 * @param resource $conn Conexión a la base de datos
 * @param string $query_name Nombre único de la consulta
 * @param string $query SQL de la consulta
 * @param array $params Parámetros de la consulta
 * @return resource|false Resultado de la consulta o false
 */
function execute_prepared_query($conn, $query_name, $query, $params = []) {
    $result = @pg_prepare($conn, $query_name, $query);
    if (!$result) {
        error_log("Error preparando consulta: " . pg_last_error($conn));
        return false;
    }
    
    $result = @pg_execute($conn, $query_name, $params);
    if (!$result) {
        error_log("Error ejecutando consulta: " . pg_last_error($conn));
        return false;
    }
    
    return $result;
}
?>