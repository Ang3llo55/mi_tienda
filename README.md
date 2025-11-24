# Mi Tienda - Sistema de Gestión de Productos

Sistema web simple de gestión de productos desarrollado con PHP nativo y PostgreSQL.

## 🚀 Características

- ✅ Listado de productos con búsqueda y filtros
- ✅ Paginación
- ✅ CRUD completo (Crear, Leer, Actualizar, Eliminar)
- ✅ Subida y gestión de imágenes
- ✅ API REST JSON completa
- ✅ Protección CSRF en formularios
- ✅ Validaciones de servidor
- ✅ Consultas preparadas (prevención SQL injection)
- ✅ Interfaz responsiva con Bootstrap 5

## 📋 Requisitos

- PHP 8.0 o superior
- PostgreSQL 14 o superior
- Apache con mod_rewrite habilitado
- Extensión PHP: php-pgsql

## 🔧 Instalación

### 1. Instalar dependencias del sistema

**Debian/Ubuntu:**
```bash
sudo apt update
sudo apt install apache2 php php-pgsql postgresql postgresql-contrib
```

**CentOS/RHEL:**
```bash
sudo dnf install httpd php php-pgsql postgresql-server postgresql-contrib
sudo postgresql-setup --initdb
sudo systemctl start postgresql
```

### 2. Configurar PostgreSQL

```bash
# Cambiar a usuario postgres
sudo -u postgres psql

# En el prompt de PostgreSQL, ejecutar:
```

```sql
-- Crear usuario
CREATE USER mi_tienda_user WITH PASSWORD 'tienda123';

-- Crear base de datos
CREATE DATABASE mi_tienda_db OWNER mi_tienda_user;

-- Salir
\q
```

### 3. Importar el esquema

```bash
# Copiar los archivos del proyecto
sudo mkdir -p /var/www/html/mi_tienda
sudo cp -r * /var/www/html/mi_tienda/

# Tambien puedes clonar el repositorio
cd /var/www/html/
git clone https://github.com/Ang3llo55/mi_tienda.git

# Importar schema.sql
sudo -u postgres psql -d mi_tienda_db -f /var/www/html/mi_tienda/sql/schema.sql
```

### 4. Configurar permisos

```bash
# Dar permisos a Apache
sudo chown -R www-data:www-data /var/www/html/mi_tienda
sudo chmod -R 755 /var/www/html/mi_tienda

# Crear y configurar directorio de uploads
sudo mkdir -p /var/www/html/mi_tienda/uploads
sudo chown -R www-data:www-data /var/www/html/mi_tienda/uploads
sudo chmod 775 /var/www/html/mi_tienda/uploads
```

### 5. Configurar Apache (Opcional - VirtualHost)

```bash
# Copiar configuración
sudo cp apache-config-example.conf /etc/apache2/sites-available/mi_tienda.conf

# Habilitar sitio
sudo a2ensite mi_tienda.conf

# Habilitar mod_rewrite
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2
```

### 6. Configurar /etc/hosts (para desarrollo local)

```bash
sudo nano /etc/hosts
# Agregar línea:
127.0.0.1    mi-tienda.local
```

## 🌐 Acceso

- **Aplicación web:** http://localhost/mi_tienda/ o http://mi-tienda.local
- **API REST:** http://localhost/mi_tienda/api.php

## 📡 Endpoints de la API

### Listar productos
```bash
GET /api.php?action=list
GET /api.php?action=list&page=1&per_page=10
GET /api.php?action=list&search=laptop
GET /api.php?action=list&category=Electrónica
```

### Obtener producto por ID
```bash
GET /api.php?action=get&id=1
```

### Crear producto
```bash
POST /api.php?action=create
Content-Type: application/json

{
  "name": "Producto Nuevo",
  "description": "Descripción del producto",
  "price": 99.99,
  "stock": 50,
  "category": "Categoría",
  "image_path": null
}
```

### Actualizar producto
```bash
PUT /api.php?action=update&id=1
Content-Type: application/json

{
  "price": 89.99,
  "stock": 45
}
```

### Eliminar producto
```bash
DELETE /api.php?action=delete&id=1
```

## 🧪 Pruebas

Ejecutar tests de la API:

```bash
cd tests
chmod +x api_tests.sh
./api_tests.sh
```

*Nota: Requiere `jq` instalado: `sudo apt install jq`*

## 📁 Estructura del Proyecto

```
mi_tienda/
├── index.php              # Listado de productos
├── product.php            # Detalle de producto
├── add_product.php        # Agregar producto
├── edit_product.php       # Editar producto
├── delete_product.php     # Eliminar producto
├── api.php                # API REST JSON
├── includes/
│   ├── db.php            # Conexión a PostgreSQL
│   ├── functions.php     # Funciones reutilizables
│   ├── header.php        # Header HTML
│   └── footer.php        # Footer HTML
├── assets/
│   └── css/
│       └── style.css     # Estilos personalizados
├── uploads/              # Directorio de imágenes
│   └── .htaccess        # Seguridad
├── sql/
│   └── schema.sql       # Esquema de base de datos
├── tests/
│   └── api_tests.sh     # Tests con cURL
└── README.md            # Esta documentación
```

## 🔒 Seguridad

El proyecto implementa:

- **Consultas preparadas** con `pg_prepare()` y `pg_execute()`
- **Escape de HTML** con `htmlspecialchars()`
- **Protección CSRF** en formularios
- **Validación de tipos de archivo** en uploads
- **Limitación de tamaño** de archivos (5MB)
- **Prevención de ejecución** en directorio uploads (`.htaccess`)
- **Validación de inputs** del lado del servidor

## 🔑 Credenciales por Defecto

- **Base de datos:** mi_tienda_db
- **Usuario PostgreSQL:** mi_tienda_user
- **Contraseña:** tienda123

⚠️ **Importante:** Cambiar estas credenciales en producción editando `includes/db.php`

## 🎯 Mejoras Futuras (Opcionales)

- [ ] Sistema de autenticación de usuarios
- [ ] Roles y permisos
- [ ] Carrito de compras
- [ ] Pasarela de pago
- [ ] Panel de administración avanzado
- [ ] Logs de auditoría
- [ ] Exportación de datos (CSV/PDF)
- [ ] Notificaciones por email

## 🐛 Solución de Problemas

### Error de conexión a PostgreSQL

```bash
# Verificar que PostgreSQL esté corriendo
sudo systemctl status postgresql

# Verificar configuración de autenticación
sudo nano /etc/postgresql/14/main/pg_hba.conf
# Asegurarse de tener:
# local   all   mi_tienda_user   md5
```

### Permisos de uploads

```bash
# Si hay errores al subir imágenes:
sudo chown -R www-data:www-data /var/www/html/mi_tienda/uploads
sudo chmod 775 /var/www/html/mi_tienda/uploads
```

### Apache no muestra PHP

```bash
# Verificar que PHP esté instalado
php -v

# Reiniciar Apache
sudo systemctl restart apache2
```

## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

## 👨‍💻 Autor

Proyecto de ejemplo para demostración de PHP + PostgreSQL.

---


**¡Listo para usar! 🎉**
