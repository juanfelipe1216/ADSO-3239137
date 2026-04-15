-- 1. CREAR LA BASE DE DATOS
CREATE DATABASE prueba_sena;
USE prueba_sena;

-- BLOQUE 1: UBICACIÓN (De lo más grande a lo más pequeño)

CREATE TABLE continente (
    id_continente INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50)
);

CREATE TABLE pais (
    id_pais INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    id_continente INT,
    FOREIGN KEY (id_continente) REFERENCES continente(id_continente)
);

CREATE TABLE departamento (
    id_departamento INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    id_pais INT,
    FOREIGN KEY (id_pais) REFERENCES pais(id_pais)
);

CREATE TABLE ciudad (
    id_ciudad INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    id_departamento INT,
    FOREIGN KEY (id_departamento) REFERENCES departamento(id_departamento)
);

CREATE TABLE barrio (
    id_barrio INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    id_ciudad INT,
    FOREIGN KEY (id_ciudad) REFERENCES ciudad(id_ciudad)
);

-- BLOQUE 2: SEGURIDAD Y PERSONAS

CREATE TABLE rol (
    id_rol INT PRIMARY KEY AUTO_INCREMENT,
    nombre_rol VARCHAR(50)
);

CREATE TABLE persona (
    id_persona INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    apellido VARCHAR(50),
    correo VARCHAR(50),
    id_barrio INT,
    FOREIGN KEY (id_barrio) REFERENCES barrio(id_barrio)
);

CREATE TABLE usuario (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    user_name VARCHAR(50),
    password VARCHAR(50),
    id_persona INT,
    id_rol INT,
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona),
    FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
);

-- BLOQUE 3: VENTAS (PRODUCTOS Y FACTURAS)

CREATE TABLE categoria (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50)
);

CREATE TABLE producto (
    id_producto INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50),
    precio INT,
    stock INT,
    id_categoria INT,
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE factura (
    id_factura INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE,
    id_persona INT,
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
);

CREATE TABLE detalle_factura (
    id_detalle INT PRIMARY KEY AUTO_INCREMENT,
    id_factura INT,
    id_producto INT,
    cantidad INT,
    precio_unitario INT,
    FOREIGN KEY (id_factura) REFERENCES factura(id_factura),
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);