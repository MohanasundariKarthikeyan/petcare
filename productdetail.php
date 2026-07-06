<?php
session_start();

// Products with category tags
$products = [
  ["Dog Toy Bone", "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6_4HOO-tqkThwh7YzhqvJkxj9E6kYn4WcHA&s", "Durable chew toy for active dogs.", "dog"],
  ["Dog Leash", "https://ruffwear.com/cdn/shop/products/4095-Flagline-Leash-Lichen-Green-SMALL.png?crop=center&height=550&v=1707323112&width=820", "Strong and stylish leash for walks.", "dog"],
  ["Dog Bed", "https://www.penguin-egy.com/cdn/shop/products/penguin-group-barry-pet-bed-40223780405507.jpg?v=1677361677&width=533", "Comfortable sleeping pad for dogs.", "dog"],
  ["Dog Food", "https://t4.ftcdn.net/jpg/02/37/25/57/360_F_237255740_7WSFyyoSBmgi3P1T5A5IrJWPwRteL30I.jpg", "Nutritious chicken-flavored dog food.", "dog"],
  ["Cat Scratching Post", "https://cdn.bmstores.co.uk/images/hpcProductImage/imgSource/376819-kittykins-scratching-post-cat-toy.jpg", "Keeps cats entertained and stress-free.", "cat"],
  ["Cat Litter", "https://5.imimg.com/data5/SELLER/Default/2024/1/376263469/SY/FY/KK/5699887/cat-litter-500x500.jpg", "Odor-free and easy to clean litter.", "cat"],
  ["Cat Toy Mouse", "https://m.media-amazon.com/images/I/71q4vWkLCKL.jpg", "Fun plush toy to keep your cat active.", "cat"],
  ["Cat Food", "https://media.istockphoto.com/id/1359173333/photo/bengal-cat-reaches-for-food-with-its-paw.jpg?s=612x612&w=0&k=20&c=ldT20hS7D1SNpudk3DTYNNiMm-qeMAcgqLCyIrHEMp4=", "Delicious tuna-based food for cats.", "cat"],
];

// Get product ID from query
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!isset($products[$productId])) {
  die("Product not found.");
}

$product = $products[$productId];
$productCategory = $product[3]; // "dog" or "cat"

// Filter related products from the same category, excluding the current one
$related = array_filter($products, function($p) use ($productCategory, $product) {
  return $p[3] === $productCategory && $p !== $product;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $product[0] ?> - PetCare Product</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(rgba(0,0,0,0.85), rgba(0,0,0,0.85)),
        url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') center/cover no-repeat fixed;
      color: #fff;
    }
    .navbar {
      background-color: rgba(0, 0, 0, 0.9);
      backdrop-filter: blur(6px);
    }
    .nav-link {
      color: #fff !important;
    }
    .nav-link:hover {
      color: #ffc107 !important;
    }
    .card {
      background-color: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 15px;
      backdrop-filter: blur(4px);
      transition: transform 0.3s ease;
    }
    .card:hover {
      transform: translateY(-6px);
    }
    .card-img-top {
      height: 200px;
      object-fit: cover;
    }
    .card-title, .card-text {
      color: #fff;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fas fa-paw"></i> PetCare</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="shoppinghome.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="cart.php">Add to Cart</a></li>
        <li class="nav-item"><a class="nav-link" href="account.php">Account</a></li>
        <li class="nav-item"><a class="nav-link" href="shopping.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <div class="row mb-5">
    <div class="col-md-6">
      <img src="<?= $product[1] ?>" class="img-fluid rounded shadow" alt="<?= $product[0] ?>">
    </div>
    <div class="col-md-6">
      <h2><?= $product[0] ?></h2>
      <p class="lead"><?= $product[2] ?></p>
      <button class="btn btn-warning mt-3">Add to Cart</button>
    </div>
  </div>

  <?php if (!empty($related)): ?>
  <h4 class="text-warning mb-4">More products for <?= ucfirst($productCategory) ?>s</h4>
  <div class="row g-4">
    <?php foreach ($related as $key => $rel): ?>
      <div class="col-sm-6 col-md-3">
        <a href="productdetail.php?id=<?= array_search($rel, $products) ?>" class="text-decoration-none">
          <div class="card h-100">
            <img src="<?= $rel[1] ?>" class="card-img-top" alt="<?= $rel[0] ?>">
            <div class="card-body">
              <h5 class="card-title"><?= $rel[0] ?></h5>
              <p class="card-text"><?= $rel[2] ?></p>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
