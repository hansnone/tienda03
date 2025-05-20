# Memoria del Proyecto: Tienda Virtual en PHP

## 1. Introducción

Este proyecto es una tienda virtual desarrollada en PHP, que permite a los usuarios registrarse, iniciar sesión, comprar productos, gestionar un carrito de compras y, para el administrador, gestionar productos y usuarios. Utiliza PDO para la conexión a una base de datos MySQL y Bootstrap para la interfaz.

---

## 2. Estructura del Proyecto

- **index.php**: Lógica principal de la aplicación y vistas.
- **conexion.php**: Conexión a la base de datos.
- **conexion.properties**: Parámetros de conexión a la base de datos.

---

## 3. Conexión a la Base de Datos

La conexión se realiza mediante PDO, cargando los parámetros desde `conexion.properties` para mayor seguridad y flexibilidad.

![**CAPTURA DE PANTALLA: CONEXIÓN A LA BASE DE DATOS (conexion.php líneas 1-22)](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20192417.png)

---

## 4. Gestión de Sesiones y Carrito

Se utiliza `session_start()` para mantener la sesión del usuario y almacenar el carrito de compras en `$_SESSION['cart']`.

---

## 5. Registro de Usuarios

El registro valida que todos los campos estén completos, que el usuario y el correo no existan previamente, y almacena la contraseña de forma segura usando `password_hash`.

![**CAPTURA DE PANTALLA: FORMULARIO DE REGISTRO (index.php líneas 122-146)**](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20192521.png)

---

## 6. Inicio de Sesión

El usuario puede iniciar sesión con su nombre de usuario y contraseña. Se verifica la contraseña usando `password_verify`.

![**CAPTURA DE PANTALLA: FORMULARIO DE LOGIN (index.php líneas 108-118)**](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20192610.png)

---

## 7. Panel de Administración

El administrador accede con credenciales fijas y puede:

- Añadir, editar y eliminar productos.
- Ver y eliminar usuarios.

![**CAPTURA DE PANTALLA: PANEL DE ADMINISTRACIÓN (index.php líneas 260-340)**](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20192715.png)

![](C:\Users\thebe\AppData\Roaming\marktext\images\2025-05-20-19-28-27-image.png)

---

## 8. Gestión de Productos

Los productos se pueden añadir, editar y eliminar desde el panel de administración. Se valida que los datos sean correctos y que la referencia no exista previamente.

![**CAPTURA DE PANTALLA: LISTADO DE PRODUCTOS Y FORMULARIO DE AÑADIR (index.php líneas 260-300)**](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20192932.png)

---

## 9. Gestión de Usuarios

El administrador puede ver todos los usuarios y eliminarlos. Al eliminar un usuario, se eliminan también sus compras y datos asociados.

![**CAPTURA DE PANTALLA: LISTADO DE USUARIOS (index.php líneas 302-340)**](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20193034.png)

---

## 10. Tienda y Carrito de Compras

Los usuarios pueden ver los productos, añadirlos al carrito y realizar compras. El carrito se almacena en la sesión y al finalizar la compra se registra en la base de datos.

![**CAPTURA DE PANTALLA: VISTA DE TIENDA (index.php líneas 148-164)**](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20193104.png)  
![**CAPTURA DE PANTALLA: VISTA DE CARRITO (index.php líneas 166-180)**](C:\Users\thebe\Pictures\Screenshots\Captura%20de%20pantalla%202025-05-20%20193146.png)

---

## 11. Seguridad

- Contraseñas cifradas con `password_hash`.
- Uso de sentencias preparadas para evitar inyección SQL.
- Validación de datos en formularios.


