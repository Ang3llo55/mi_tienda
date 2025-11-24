<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Eliminar Producto';

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

// Procesar confirmación de eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('error', 'Token de seguridad inválido');
        redirect('index.php');
    }
    
    // Obtener ruta de imagen antes de eliminar
    $query = "SELECT image_path FROM products WHERE id = $1";
    $result = execute_prepared_query($conn, 'get_product_image_delete', $query, [$product_id]);
    
    if ($result && pg_num_rows($result) > 0) {
        $product = pg_fetch_assoc($result);
        $image_path = $product['image_path'];
        
        // Eliminar producto
        $delete_query = "DELETE FROM products WHERE id = $1";
        $delete_result = execute_prepared_query($conn, 'delete_product_' . uniqid(), $delete_query, [$product_id]);
        
        if ($delete_result) {
            // Eliminar imagen si existe
            if ($image_path) {
                delete_image($image_path);
            }
            
            set_flash_message('success', 'Producto eliminado exitosamente');
            close_db_connection();
            redirect('index.php');
        } else {
            set_flash_message('error', 'Error al eliminar el producto');
            close_db_connection();
            redirect('index.php');
        }
    } else {
        set_flash_message('error', 'Producto no encontrado');
        close_db_connection();
        redirect('index.php');
    }
}

// Obtener datos del producto para mostrar confirmación
$query = "SELECT * FROM products WHERE id = $1";
$result = execute_prepared_query($conn, 'get_product_for_delete', $query, [$product_id]);

if (!$result || pg_num_rows($result) === 0) {
    set_flash_message('error', 'Producto no encontrado');
    close_db_connection();
    redirect('index.php');
}

$product = pg_fetch_assoc($result);

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-3">
        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">&larr; Volver al producto</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h2>Confirmar Eliminación</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>¡Advertencia!</strong> Esta acción no se puede deshacer.
                </div>
                
                <p>¿Está seguro de que desea eliminar el siguiente producto?</p>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <?php if ($product['image_path']): ?>
                                    <img src="<?php echo e($product['image_path']); ?>" alt="<?php echo e($product['name']); ?>" class="img-fluid rounded">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 100px;">
                                        <span class="text-muted">Sin imagen</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <h4><?php echo e($product['name']); ?></h4>
                                <?php if ($product['category']): ?>
                                    <p class="text-muted mb-1">
                                        <strong>Categoría:</strong> <?php echo e($product['category']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <strong>Precio:</strong> $<?php echo number_format($product['price'], 2); ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Stock:</strong> <?php echo e($product['stock']); ?> unidades
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="delete_product.php?id=<?php echo $product['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg">
                            Sí, eliminar producto
                        </button>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">
                            No, cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
close_db_connection();
?>