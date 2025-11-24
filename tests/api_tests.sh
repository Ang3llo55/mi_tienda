#!/bin/bash

# Tests para la API de Mi Tienda
# Ejecutar: chmod +x api_tests.sh && ./api_tests.sh

BASE_URL="http://localhost/mi_tienda/api.php"

echo "=========================================="
echo "Tests de API - Mi Tienda"
echo "=========================================="
echo ""

# Test 1: Listar productos
echo "1. GET - Listar todos los productos"
curl -X GET "${BASE_URL}?action=list" | jq .
echo -e "\n"

# Test 2: Listar productos con paginación
echo "2. GET - Listar productos (página 1, 5 por página)"
curl -X GET "${BASE_URL}?action=list&page=1&per_page=5" | jq .
echo -e "\n"

# Test 3: Buscar productos
echo "3. GET - Buscar productos por nombre"
curl -X GET "${BASE_URL}?action=list&search=laptop" | jq .
echo -e "\n"

# Test 4: Filtrar por categoría
echo "4. GET - Filtrar productos por categoría"
curl -X GET "${BASE_URL}?action=list&category=Electrónica" | jq .
echo -e "\n"

# Test 5: Obtener producto por ID
echo "5. GET - Obtener producto con ID=1"
curl -X GET "${BASE_URL}?action=get&id=1" | jq .
echo -e "\n"

# Test 6: Crear nuevo producto
echo "6. POST - Crear nuevo producto"
curl -X POST "${BASE_URL}?action=create" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Tablet Samsung Galaxy",
    "description": "Tablet Android 10 pulgadas con S-Pen",
    "price": 399.99,
    "stock": 25,
    "category": "Electrónica"
  }' | jq .
echo -e "\n"

# Test 7: Actualizar producto (asumiendo que el ID del producto creado es 6)
echo "7. PUT - Actualizar producto con ID=6"
curl -X PUT "${BASE_URL}?action=update&id=6" \
  -H "Content-Type: application/json" \
  -d '{
    "price": 379.99,
    "stock": 30
  }' | jq .
echo -e "\n"

# Test 8: Intentar crear producto con datos inválidos
echo "8. POST - Intentar crear producto con precio negativo (debe fallar)"
curl -X POST "${BASE_URL}?action=create" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Producto Inválido",
    "price": -10,
    "stock": 5
  }' | jq .
echo -e "\n"

# Test 9: Intentar obtener producto inexistente
echo "9. GET - Intentar obtener producto inexistente (ID=9999)"
curl -X GET "${BASE_URL}?action=get&id=9999" | jq .
echo -e "\n"

# Test 10: Eliminar producto
echo "10. DELETE - Eliminar producto con ID=6"
curl -X DELETE "${BASE_URL}?action=delete&id=6" | jq .
echo -e "\n"

# Test 11: Verificar que el producto fue eliminado
echo "11. GET - Verificar que el producto ID=6 fue eliminado"
curl -X GET "${BASE_URL}?action=get&id=6" | jq .
echo -e "\n"

echo "=========================================="
echo "Tests completados"
echo "=========================================="

# Nota: Instalar jq para formato JSON bonito: sudo apt install jq