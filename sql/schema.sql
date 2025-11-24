-- Crear la base de datos y usuario (ejecutar como superusuario postgres)
-- createdb mi_tienda_db
-- createuser -P mi_tienda_user (te pedirá contraseña, usa: tienda123)

-- Conectar a la base de datos
-- \c mi_tienda_db

-- Tabla de productos
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price NUMERIC(10,2) NOT NULL DEFAULT 0.00 CHECK (price >= 0),
    stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
    category VARCHAR(100),
    image_path VARCHAR(512),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE
);

-- Índices para mejorar rendimiento
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_name ON products(name);

-- Datos de prueba
INSERT INTO products (name, description, price, stock, category, image_path) VALUES
('Laptop HP Pavilion', 'Laptop potente con procesador Intel i7, 16GB RAM, 512GB SSD', 899.99, 15, 'Electrónica', NULL),
('Mouse Logitech MX Master', 'Mouse ergonómico inalámbrico con precisión profesional', 79.99, 50, 'Accesorios', NULL),
('Teclado Mecánico Keychron', 'Teclado mecánico compacto con switches Blue, retroiluminación RGB', 129.99, 30, 'Accesorios', NULL),
('Monitor Dell 27 pulgadas', 'Monitor 4K UHD con tecnología IPS, 60Hz', 399.99, 20, 'Electrónica', NULL),
('Auriculares Sony WH-1000XM4', 'Auriculares con cancelación de ruido activa premium', 279.99, 25, 'Audio', NULL);

-- Otorgar permisos al usuario
GRANT ALL PRIVILEGES ON TABLE products TO mi_tienda_user;
GRANT USAGE, SELECT ON SEQUENCE products_id_seq TO mi_tienda_user;