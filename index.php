<?php
session_start();

// Load database connection properties
$properties = parse_ini_file('conexion.properties');
$servername = $properties['servername'];
$username = $properties['username'];
$password = $properties['password'];
$dbname = $properties['dbname'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Connection error: " . $e->getMessage());
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'home';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $usuario = trim($_POST['usuario']);
        $contrasena = trim($_POST['contrasena']);
        if (empty($usuario) || empty($contrasena)) {
            $error = "Username and password are required.";
        } else {
            $stmt = $conn->prepare("SELECT contrasena FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            if ($user && password_verify($contrasena, $user['contrasena'])) {
                $_SESSION['usuario'] = $usuario;
                $action = 'store';
            } else {
                $error = "Invalid username or password.";
            }
        }
    } elseif ($action === 'register') {
        $usuario = trim($_POST['usuario']);
        $contrasena = trim($_POST['contrasena']);
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $correo = trim($_POST['correo']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $genero = $_POST['genero'];
        if (empty($usuario) || empty($contrasena) || empty($nombre) || empty($apellidos) || empty($correo) || empty($genero)) {
            $error = "All fields are required.";
        } else {
            $stmt = $conn->prepare("SELECT usuario FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetch()) {
                $error = "Username already exists.";
            } else {
                $stmt = $conn->prepare("SELECT correo FROM clientes WHERE correo = ?");
                $stmt->execute([$correo]);
                if ($stmt->fetch()) {
                    $error = "Email already exists.";
                } else {
                    $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
                    $conn->beginTransaction();
                    try {
                        $stmt = $conn->prepare("INSERT INTO usuarios (usuario, contrasena) VALUES (?, ?)");
                        $stmt->execute([$usuario, $hashed_password]);
                        $stmt = $conn->prepare("INSERT INTO clientes (nombre, apellidos, correo, fecha_nacimiento, genero, usuario) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nombre, $apellidos, $correo, $fecha_nacimiento, $genero, $usuario]);
                        $conn->commit();
                        $success = "User registered successfully. Please log in.";
                        $action = 'login';
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = "Registration failed: " . $e->getMessage();
                    }
                }
            }
        }
    } elseif ($action === 'admin_login') {
        $usuario = trim($_POST['usuario']);
        $contrasena = trim($_POST['contrasena']);
        if ($usuario === 'admin' && $contrasena === 'cmadrid') {
            $_SESSION['admin'] = true;
            $action = 'admin';
        } else {
            $error = "Invalid admin credentials.";
        }
    } elseif ($action === 'add_to_cart') {
        $referencia = $_POST['referencia'];
        if (!isset($_SESSION['cart'][$referencia])) {
            $_SESSION['cart'][$referencia] = 1;
        } else {
            $_SESSION['cart'][$referencia]++;
        }
        $success = "Product added to cart.";
        $action = 'store';
    } elseif ($action === 'checkout') {
        if (empty($_SESSION['cart'])) {
            $error = "Your cart is empty.";
        } else {
            $usuario = $_SESSION['usuario'];
            $conn->beginTransaction();
            try {
                foreach ($_SESSION['cart'] as $referencia => $cantidad) {
                    for ($i = 0; $i < $cantidad; $i++) {
                        $stmt = $conn->prepare("INSERT INTO compras (usuario, referencia_producto, fecha_compra) VALUES (?, ?, NOW())");
                        $stmt->execute([$usuario, $referencia]);
                    }
                }
                $conn->commit();
                $_SESSION['cart'] = [];
                $success = "Purchase completed successfully!";
                $action = 'store';
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Purchase failed: " . $e->getMessage();
            }
        }
    } elseif ($action === 'admin' && isset($_SESSION['admin'])) {
        if (isset($_POST['add_product'])) {
            $referencia = trim($_POST['referencia']);
            $nombre = trim($_POST['nombre']);
            $precio = floatval($_POST['precio']);
            if (empty($referencia) || empty($nombre) || $precio <= 0) {
                $error = "All product fields are required and price must be positive.";
            } else {
                $stmt = $conn->prepare("SELECT referencia FROM productos WHERE referencia = ?");
                $stmt->execute([$referencia]);
                if ($stmt->fetch()) {
                    $error = "Product reference already exists.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO productos (referencia, nombre, precio) VALUES (?, ?, ?)");
                    if ($stmt->execute([$referencia, $nombre, $precio])) {
                        $success = "Product added successfully.";
                    } else {
                        $error = "Failed to add product.";
                    }
                }
            }
        } elseif (isset($_POST['edit_product'])) {
            $referencia = $_POST['referencia'];
            $nombre = trim($_POST['nombre']);
            $precio = floatval($_POST['precio']);
            if (empty($nombre) || $precio <= 0) {
                $error = "Product name and positive price are required.";
            } else {
                $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ? WHERE referencia = ?");
                if ($stmt->execute([$nombre, $precio, $referencia])) {
                    $success = "Product updated successfully.";
                } else {
                    $error = "Failed to update product.";
                }
            }
        } elseif (isset($_POST['delete_product'])) {
            $referencia = $_POST['referencia'];
            $stmt = $conn->prepare("DELETE FROM productos WHERE referencia = ?");
            if ($stmt->execute([$referencia])) {
                $success = "Product deleted successfully.";
            } else {
                $error = "Failed to delete product.";
            }
        } elseif (isset($_POST['delete_user'])) {
            $usuario = $_POST['usuario'];
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("DELETE FROM compras WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $stmt = $conn->prepare("DELETE FROM clientes WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $conn->commit();
                $success = "User deleted successfully.";
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Failed to delete user: " . $e->getMessage();
            }
        }
    }
}

// Logout handling
if ($action === 'logout') {
    session_destroy();
    session_start();
    $success = "Logged out successfully.";
    $action = 'home';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="?action=home">Virtual Store</a>
            <div class="navbar-nav">
                <?php if (isset($_SESSION['usuario']) || isset($_SESSION['admin'])): ?>
                    <a class="nav-link" href="?action=store">Store</a>
                    <a class="nav-link" href="?action=cart">Cart (<?php echo array_sum($_SESSION['cart']); ?>)</a>
                    <a class="nav-link" href="?action=logout">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="?action=login">Login</a>
                    <a class="nav-link" href="?action=register">Register</a>
                    <a class="nav-link" href="?action=admin_login">Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($action === 'home'): ?>
            <h1>Welcome to the Virtual Store</h1>
            <p>Please <a href="?action=login">login</a> or <a href="?action=register">register</a> to start shopping.</p>

        <?php elseif ($action === 'login'): ?>
            <h2>Login</h2>
            <form method="POST" action="?action=login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="usuario" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="contrasena" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>

        <?php elseif ($action === 'register'): ?>
            <h2>Register</h2>
            <form method="POST" action="?action=register">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="usuario" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="contrasena" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Surname</label>
                    <input type="text" name="apellidos" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="correo" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Birth Date</label>
                    <input type="date" name="fecha_nacimiento" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select name="genero" class="form-control" required>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                        <option value="Otro">Other</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Register</button>
            </form>

        <?php elseif ($action === 'store' && isset($_SESSION['usuario'])): ?>
            <h2>Store</h2>
            <div class="row">
                <?php
                $stmt = $conn->query("SELECT * FROM productos");
                while ($product = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['nombre']); ?></h5>
                                <p class="card-text">Price: $<?php echo number_format($product['precio'], 2); ?></p>
                                <form method="POST" action="?action=add_to_cart">
                                    <input type="hidden" name="referencia" value="<?php echo $product['referencia']; ?>">
                                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php elseif ($action === 'cart' && isset($_SESSION['usuario'])): ?>
            <h2>Shopping Cart</h2>
            <?php if (empty($_SESSION['cart'])): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        foreach ($_SESSION['cart'] as $referencia => $cantidad):
                            $stmt = $conn->prepare("SELECT nombre, precio FROM productos WHERE referencia = ?");
                            $stmt->execute([$referencia]);
                            $product = $stmt->fetch(PDO::FETCH_ASSOC);
                            $subtotal = $product['precio'] * $cantidad;
                            $total += $subtotal;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['nombre']); ?></td>
                                <td>$<?php echo number_format($product['precio'], 2); ?></td>
                                <td><?php echo $cantidad; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                <form method="POST" action="?action=checkout">
                    <button type="submit" class="btn btn-success">Complete Purchase</button>
                </form>
            <?php endif; ?>

        <?php elseif ($action === 'admin_login'): ?>
            <h2>Admin Login</h2>
            <form method="POST" action="?action=admin_login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="usuario" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="contrasena" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>

        <?php elseif ($action === 'admin' && isset($_SESSION['admin'])): ?>
            <h2>Admin Panel</h2>
            <h3>Manage Products</h3>
            <form method="POST" action="?action=admin" class="mb-4">
                <h4>Add Product</h4>
                <div class="mb-3">
                    <label class="form-label">Reference</label>
                    <input type="text" name="referencia" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="precio" class="form-control" required>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>

            <h4>Product List</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("SELECT * FROM productos");
                    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['referencia']); ?></td>
                            <td><?php echo htmlspecialchars($product['nombre']); ?></td>
                            <td>$<?php echo number_format($product['precio'], 2); ?></td>
                            <td>
                                <form method="POST" action="?action=admin" class="d-inline">
                                    <input type="hidden" name="referencia" value="<?php echo $product['referencia']; ?>">
                                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($product['nombre']); ?>" class="form-control d-inline-block w-auto" required>
                                    <input type="number" step="0.01" name="precio" value="<?php echo $product['precio']; ?>" class="form-control d-inline-block w-auto" required>
                                    <button type="submit" name="edit_product" class="btn btn-warning btn-sm">Edit</button>
                                </form>
                                <form method="POST" action="?action=admin" class="d-inline">
                                    <input type="hidden" name="referencia" value="<?php echo $product['referencia']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <h3>Manage Users</h3>
            <?php
            $stmt = $conn->query("SELECT u.usuario, c.nombre, c.apellidos, c.correo FROM usuarios u LEFT JOIN clientes c ON u.usuario = c.usuario");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($users)):
            ?>
                <p>No users found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['usuario']); ?></td>
                                <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?></td>
                                <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                <td>
                                    <form method="POST" action="?action=admin">
                                        <input type="hidden" name="usuario" value="<?php echo $user['usuario']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn = null; ?>