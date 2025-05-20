DROP DATABASE IF EXISTS tienda_virtual;

CREATE DATABASE tienda_virtual;

USE tienda_virtual;

CREATE TABLE usuarios (
    usuario VARCHAR(50) NOT NULL PRIMARY KEY,
    contrasena VARCHAR(255) NOT NULL
);

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    fecha_nacimiento DATE,
    genero ENUM('M', 'F', 'Otro') NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    FOREIGN KEY (usuario) REFERENCES usuarios(usuario)
);

CREATE TABLE productos (
    referencia VARCHAR(20) NOT NULL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL
);

INSERT INTO productos (referencia, nombre, precio) VALUES
('REF001', 'Smartphone X', 299.99),
('REF002', 'Laptop Pro', 899.99),
('REF003', 'Auriculares Bluetooth', 49.99),
('REF004', 'Tablet 10"', 199.99),
('REF005', 'Smartwatch', 129.99),
('REF006', 'Cámara Digital', 349.99),
('REF007', 'Teclado Mecánico', 79.99),
('REF008', 'Ratón Inalámbrico', 29.99),
('REF009', 'Monitor 24"', 149.99),
('REF010', 'Altavoz Bluetooth', 69.99);

CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    referencia_producto VARCHAR(20) NOT NULL,
    fecha_compra DATETIME NOT NULL,
    FOREIGN KEY (usuario) REFERENCES usuarios(usuario),
    FOREIGN KEY (referencia_producto) REFERENCES productos(referencia)
);