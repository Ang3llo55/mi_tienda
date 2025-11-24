<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'ID de producto inválido');
    redirect('index.php');
}

$product_id = (int)$_GET['id'];

// Obtener conexión
$conn = get_db_connection();
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// Obtener producto
$query = "SELECT * FROM products WHERE id = $1";
$result = execute_prepared_query($conn, 'get_product_by_id', $query, [$product_id]);

if (!$result || pg_num_rows($result) === 0) {
    set_flash_message('error', 'Producto no encontrado');
    redirect('index.php');
}

$product = pg_fetch_assoc($result);
$page_title = $product['name'];

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-3">
        <a href="index.php" class="btn btn-secondary">&larr; Volver al listado</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <?php if ($product['image_path']): ?>
            <img src="<?php echo e($product['image_path']); ?>" alt="<?php echo e($product['name']); ?>" class="img-fluid rounded shadow">
        <?php else: ?>
            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 400px;">
                <span class="text-muted fs-4">Sin imagen disponible</span>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <h1><?php echo e($product['name']); ?></h1>
        
        <?php if ($product['category']): ?>
            <p class="text-muted">
                <strong>Categoría:</strong> 
                <span class="badge bg-secondary"><?php echo e($product['category']); ?></span>
            </p>
        <?php endif; ?>
        
        <hr>
        
        <div class="mb-3">
            <h2 class="text-primary">$<?php echo number_format($product['price'], 2); ?></h2>
        </div>
        
        <div class="mb-3">
            <strong>Disponibilidad:</strong>
            <?php if ($product['stock'] > 0): ?>
                <span class="badge bg-success fs-6">
                    <?php echo e($product['stock']); ?> unidades en stock
                </span>
            <?php else: ?>
                <span class="badge bg-danger fs-6">Agotado</span>
            <?php endif; ?>
        </div>
        
        <?php if ($product['description']): ?>
        <div class="mb-4">
            <h5>Descripción</h5>
            <p><?php echo nl2br(e($product['description'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="mb-3">
            <p class="text-muted small">
                <strong>Fecha de creación:</strong> 
                <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?>
            </p>
            <?php if ($product['updated_at']): ?>
                <p class="text-muted small">
                    <strong>Última actualización:</strong> 
                    <?php echo date('d/m/Y H:i', strtotime($product['updated_at'])); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="d-grid gap-2">
            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-lg">
                Editar Producto
            </a>
            <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro de eliminar este producto?');">
                Eliminar Producto
            </a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection();
?>