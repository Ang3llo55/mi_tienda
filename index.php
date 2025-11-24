<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Listado de Productos';

// Obtener conexión
$conn = get_db_connection();
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// Parámetros de búsqueda y paginación
$search = isset($_GET['search']) ? validate_input($_GET['search']) : '';
$category = isset($_GET['category']) ? validate_input($_GET['category']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Construir consulta con filtros
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

// Contar total de productos
$count_query = "SELECT COUNT(*) as total FROM products $where_sql";
$count_result = execute_prepared_query($conn, 'count_products_' . md5($where_sql), $count_query, $params);
$total_items = $count_result ? pg_fetch_assoc($count_result)['total'] : 0;

// Calcular paginación
$pagination = paginate($total_items, $per_page, $page);

// Obtener productos
$offset_param = $param_count++;
$limit_param = $param_count++;
$params[] = $pagination['per_page'];
$params[] = $pagination['offset'];

$query = "SELECT * FROM products $where_sql ORDER BY created_at DESC LIMIT $$offset_param OFFSET $$limit_param";
$result = execute_prepared_query($conn, 'get_products_' . md5($where_sql), $query, $params);

$products = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Obtener categorías únicas para el filtro
$cat_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
$cat_result = execute_prepared_query($conn, 'get_categories', $cat_query, []);
$categories = [];
if ($cat_result) {
    while ($row = pg_fetch_assoc($cat_result)) {
        $categories[] = $row['category'];
    }
}

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>Catálogo de Productos</h1>
    </div>
</div>

<!-- Formulario de búsqueda y filtros -->
<div class="row mb-4">
    <div class="col-md-12">
        <form method="GET" action="index.php" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..." value="<?php echo e($search); ?>">
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo e($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="index.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de productos -->
<div class="row">
    <div class="col-md-12">
        <?php if (empty($products)): ?>
            <div class="alert alert-info">No se encontraron productos.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo e($product['id']); ?></td>
                            <td>
                                <?php if ($product['image_path']): ?>
                                    <img src="<?php echo e($product['image_path']); ?>" alt="Imagen" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <span class="text-muted">Sin imagen</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($product['name']); ?></td>
                            <td><?php echo e($product['category'] ?? 'N/A'); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $product['stock'] > 10 ? 'bg-success' : ($product['stock'] > 0 ? 'bg-warning' : 'bg-danger'); ?>">
                                    <?php echo e($product['stock']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info">Ver</a>
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este producto?');">Eliminar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($pagination['has_prev']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">Anterior</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">Siguiente</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection();
?>