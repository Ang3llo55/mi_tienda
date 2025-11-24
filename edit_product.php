<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Editar Producto';
$errors = [];

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

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        // Validar datos
        $name = validate_input($_POST['name'] ?? '');
        $description = validate_input($_POST['description'] ?? '');
        $price = validate_input($_POST['price'] ?? '');
        $stock = validate_input($_POST['stock'] ?? '');
        $category = validate_input($_POST['category'] ?? '');
        $delete_image = isset($_POST['delete_image']);
        
        // Validaciones
        if (empty($name)) {
            $errors[] = 'El nombre es obligatorio';
        }
        
        if (!is_valid_price($price)) {
            $errors[] = 'El precio debe ser un número mayor o igual a 0';
        }
        
        if (!is_valid_stock($stock)) {
            $errors[] = 'El stock debe ser un número entero mayor o igual a 0';
        }
        
        if (empty($errors)) {
            // Obtener datos actuales del producto
            $query = "SELECT image_path FROM products WHERE id = $1";
            $result = execute_prepared_query($conn, 'get_product_image', $query, [$product_id]);
            $current_product = pg_fetch_assoc($result);
            $current_image = $current_product['image_path'];
            
            // Procesar nueva imagen si se subió
            $image_path = $current_image;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $new_image = save_image($_FILES['image']);
                if ($new_image === false) {
                    $errors[] = 'Error al subir la imagen. Verifique que sea JPG/PNG y no exceda 5MB';
                } else {
                    // Eliminar imagen anterior si existe
                    if ($current_image) {
                        delete_image($current_image);
                    }
                    $image_path = $new_image;
                }
            } elseif ($delete_image && $current_image) {
                // Eliminar imagen si se marcó para eliminar
                delete_image($current_image);
                $image_path = null;
            }
        }
        
        // Si no hay errores, actualizar en BD
        if (empty($errors)) {
            $query = "UPDATE products 
                      SET name = $1, description = $2, price = $3, stock = $4, 
                          category = $5, image_path = $6, updated_at = NOW() 
                      WHERE id = $7";
            
            $params = [
                $name,
                $description ?: null,
                $price,
                $stock,
                $category ?: null,
                $image_path,
                $product_id
            ];
            
            $result = execute_prepared_query($conn, 'update_product_' . uniqid(), $query, $params);
            
            if ($result) {
                set_flash_message('success', 'Producto actualizado exitosamente');
                close_db_connection();
                redirect('product.php?id=' . $product_id);
            } else {
                $errors[] = 'Error al actualizar el producto en la base de datos';
            }
        }
    }
}

// Obtener datos del producto
$query = "SELECT * FROM products WHERE id = $1";
$result = execute_prepared_query($conn, 'get_product_for_edit', $query, [$product_id]);

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
        <div class="card">
            <div class="card-header">
                <h2>Editar Producto</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="edit_product.php?id=<?php echo $product['id']; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo e($product['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo e($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Precio *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price" 
                                       step="0.01" min="0" value="<?php echo e($product['price']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock *</label>
                            <input type="number" class="form-control" id="stock" name="stock" 
                                   min="0" value="<?php echo e($product['stock']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Categoría</label>
                        <input type="text" class="form-control" id="category" name="category" 
                               value="<?php echo e($product['category'] ?? ''); ?>">
                    </div>
                    
                    <?php if ($product['image_path']): ?>
                    <div class="mb-3">
                        <label class="form-label">Imagen actual</label>
                        <div>
                            <img src="<?php echo e($product['image_path']); ?>" alt="Imagen actual" class="img-thumbnail" style="max-width: 200px;">
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image">
                            <label class="form-check-label" for="delete_image">
                                Eliminar imagen actual
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">
                            <?php echo $product['image_path'] ? 'Cambiar imagen' : 'Subir imagen'; ?>
                        </label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/jpg">
                        <div class="form-text">Formatos permitidos: JPG, PNG. Tamaño máximo: 5MB</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Guardar Cambios</button>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">Cancelar</a>
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