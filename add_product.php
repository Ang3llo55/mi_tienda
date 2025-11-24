<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Agregar Producto';
$errors = [];

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
        
        // Procesar imagen si se subió
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_path = save_image($_FILES['image']);
            if ($image_path === false) {
                $errors[] = 'Error al subir la imagen. Verifique que sea JPG/PNG y no exceda 5MB';
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Error al subir la imagen';
        }
        
        // Si no hay errores, insertar en BD
        if (empty($errors)) {
            $conn = get_db_connection();
            if (!$conn) {
                $errors[] = 'Error de conexión a la base de datos';
            } else {
                $query = "INSERT INTO products (name, description, price, stock, category, image_path, created_at) 
                          VALUES ($1, $2, $3, $4, $5, $6, NOW()) RETURNING id";
                
                $params = [
                    $name,
                    $description ?: null,
                    $price,
                    $stock,
                    $category ?: null,
                    $image_path
                ];
                
                $result = execute_prepared_query($conn, 'insert_product_' . uniqid(), $query, $params);
                
                if ($result) {
                    $row = pg_fetch_assoc($result);
                    $new_id = $row['id'];
                    set_flash_message('success', 'Producto agregado exitosamente');
                    close_db_connection();
                    redirect('product.php?id=' . $new_id);
                } else {
                    $errors[] = 'Error al guardar el producto en la base de datos';
                    // Si hubo error y se subió imagen, eliminarla
                    if ($image_path) {
                        delete_image($image_path);
                    }
                }
                
                close_db_connection();
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-3">
        <a href="index.php" class="btn btn-secondary">&larr; Volver al listado</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h2>Agregar Nuevo Producto</h2>
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
                
                <form method="POST" action="add_product.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($name) ? e($name) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($description) ? e($description) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Precio *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price" 
                                       step="0.01" min="0" value="<?php echo isset($price) ? e($price) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock *</label>
                            <input type="number" class="form-control" id="stock" name="stock" 
                                   min="0" value="<?php echo isset($stock) ? e($stock) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Categoría</label>
                        <input type="text" class="form-control" id="category" name="category" 
                               value="<?php echo isset($category) ? e($category) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagen del producto</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/jpg">
                        <div class="form-text">Formatos permitidos: JPG, PNG. Tamaño máximo: 5MB</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Agregar Producto</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>