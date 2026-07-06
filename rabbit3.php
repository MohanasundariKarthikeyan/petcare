<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'petdemo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Establish database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Fetch rabbit chew toys from database
    $stmt = $pdo->prepare("SELECT * FROM product WHERE category = 'rabbit_toy'");
    $stmt->execute();
    $chewToys = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Fallback to hardcoded data if database connection fails
    $chewToys = [
        [
            "id" => "RT001",
            "name" => "Natural Apple Wood Chew Sticks",
            "image_url" => "https://andy.pet/cdn/shop/files/andy-by-anderson-hay-play-andy-natural-apple-wood-stick-chews-31880762753233_800x.jpg?v=1726317694",
            "description" => "Organic apple wood sticks for dental health.",
            "price" => 199,
            "rating" => 4.5
        ],
        [
            "id" => "RT002",
            "name" => "Bite Balls & Wooden Blocks Set",
            "image_url" => "https://m.media-amazon.com/images/I/71383Na1EyL.jpg",
            "description" => "Bright wooden shapes and balls for gnawing.",
            "price" => 299,
            "rating" => 4.2
        ],
        [
            "id" => "RT003",
            "name" => "Bamboo Rabbit Roller",
            "image_url" => "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiuhjo7ho5zKxth5eahCqot8UH6wsILQWLlg&s",
            "description" => "Fun chewable toy that also rolls!",
            "price" => 249,
            "rating" => 4.7
        ],
        [
            "id" => "RT004",
            "name" => "Carrot Chew Treat Toy",
            "image_url" => "https://images-cdn.ubuy.co.in/636fc84317c9a327f3553b73-bunny-chew-toys-rabbit-scratching.jpg",
            "description" => "Tasty carrot-shaped chew toy.",
            "price" => 189,
            "rating" => 4.0
        ],
        [
            "id" => "RT005",
            "name" => "Hanging Chew Ball Bundle",
            "image_url" => "https://m.media-amazon.com/images/I/71MnO3g1x7L._UF1000,1000_QL80_.jpg",
            "description" => "Multiple textured hanging balls for cages.",
            "price" => 349,
            "rating" => 4.4
        ]
    ];
}

// Handle add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
    
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if ($quantity < 1 || $quantity > 10) {
        $_SESSION['cart_message'] = "Invalid quantity selected. Please choose between 1-10.";
        $_SESSION['cart_message_type'] = 'danger';
        header("Location: cart.php" . $_SERVER['REQUEST_URI']);
        exit();
    }
    
    try {
        // Check if the product already exists in the user's cart
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Update quantity if product already in cart
            $newQuantity = $existingItem['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$newQuantity, $_SESSION['user_id'], $product_id]);
        } else {
            // Find the product in our array
            $product = null;
            foreach ($chewToys as $item) {
                if ($item['id'] === $product_id) {
                    $product = $item;
                    break;
                }
            }
            
            if ($product) {
                // Insert new item into cart table
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, product_name, price, image, quantity, added_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $product_id,
                    $product['name'],
                    $product['price'],
                    $product['image_url'],
                    $quantity
                ]);
            }
        }
        
        // Update cart count in session
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $_SESSION['cart_count'] = $result['total'] ?? 0;
        
        $_SESSION['cart_message'] = "Product added to cart successfully!";
        $_SESSION['cart_message_type'] = 'success';
        header("Location: cart.php" . $_SERVER['REQUEST_URI']);
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['cart_message'] = "Error adding product to cart. Please try again.";
        $_SESSION['cart_message_type'] = 'danger';
        header("Location: cart.php" . $_SERVER['REQUEST_URI']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Rabbit Chew Toys - PetCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Roboto+Slab&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)),
        url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') center center / cover no-repeat fixed;
      color: #fff;
      min-height: 100vh;
    }
    .navbar {
      background-color: rgba(0, 0, 0, 0.85);
      backdrop-filter: blur(8px);
    }
    .navbar-brand {
      font-family: 'Roboto Slab', serif;
      font-size: 2rem;
      font-weight: bold;
      color: #fff;
      display: flex;
      align-items: center;
    }
    .navbar-brand i {
      color: #ffb74d;
      margin-right: 10px;
    }
    .nav-link {
      color: #fff !important;
      font-weight: 500;
    }
    .nav-link:hover {
      color: #ffc107 !important;
    }
    .section-title {
      font-family: 'Roboto Slab', serif;
      font-weight: 600;
      font-size: 2rem;
      margin: 40px 0 30px;
      text-align: center;
      color: #ffc107;
    }
    .card {
      background-color: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 15px;
      transition: 0.3s ease;
      backdrop-filter: blur(4px);
    }
    .card:hover {
      transform: translateY(-6px);
      background-color: rgba(255, 255, 255, 0.2);
    }
    .card-img-top {
      height: 200px;
            background-size: cover;
      overflow: hidden;

    }
    .card-title, .card-text {
      color: #fff;
    }
    .btn-custom {
      background-color: #ffc107;
      color: #000;
      border: none;
      transition: all 0.3s;
    }
    .btn-custom:hover {
      background-color: #e0a800;
      transform: scale(1.05);
    }
    .price-tag {
      font-size: 1.2rem;
      font-weight: bold;
      color: #ffc107;
    }
    .quantity-selector {
      margin: 10px 0;
    }
    .quantity-selector input {
      width: 60px;
      text-align: center;
      background: rgba(255,255,255,0.1);
      color: white;
      border: 1px solid rgba(255,255,255,0.3);
    }
    .alert {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 1000;
      min-width: 300px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="home.php"><i class="fas fa-paw"></i> PetCare</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-shopping-cart"></i> Cart
            <?php if (isset($_SESSION['cart_count'])) : ?>
              <span class="badge bg-danger"><?= $_SESSION['cart_count'] ?></span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="cart.php"><i class="fas fa-shopping-basket"></i> View Cart</a></li>
            <li><a class="dropdown-item" href="order.php"><i class="fas fa-receipt"></i> My Orders</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="account.php"><i class="fas fa-user"></i> Account</a></li>
        <li class="nav-item"><a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="shopping.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <h2 class="section-title"><i class="fas fa-carrot"></i> Rabbit Chew Toys</h2>
  
  <?php if (isset($_SESSION['cart_message'])) : ?>
    <div class="alert alert-<?= $_SESSION['cart_message_type'] ?> alert-dismissible fade show" role="alert">
      <?= $_SESSION['cart_message'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['cart_message']); unset($_SESSION['cart_message_type']); ?>
  <?php endif; ?>
  
  <div class="row g-4">
    <?php foreach ($chewToys as $product) : ?>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100">
          <img src="<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" 
               style="cursor:pointer;" onclick="window.location.href='cart.php?id=<?= htmlspecialchars($product['id']) ?>'">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
            <div class="mb-2">
              <?= str_repeat('<i class="fas fa-star text-warning"></i>', floor($product['rating'])) ?>
              <?= (($product['rating'] - floor($product['rating'])) >= 0.5 ? '<i class="fas fa-star-half-alt text-warning"></i>' : '') ?>
              <?= str_repeat('<i class="far fa-star text-warning"></i>', 5 - ceil($product['rating'])) ?>
              <span class="ms-1">(<?= htmlspecialchars($product['rating']) ?>)</span>
            </div>
            <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
            <p class="price-tag">₹<?= number_format($product['price'], 2) ?></p>
            
             <form method="post" action="cart.php" class="add-to-cart-form">
              <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
              
              
              <button type="submit" name="add_to_cart" class="btn btn-custom w-100 mt-2">
                <i class="fas fa-cart-plus"></i> Add to Cart
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Add animation to "Add to Cart" buttons
  document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      const button = this.querySelector('button');
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
      button.disabled = true;
    });
  });
</script>
</body>
</html>