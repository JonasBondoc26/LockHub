<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="keywords" content="" />
  <meta name="description" content="LockHub - Your Secure Password Manager" />
  <meta name="author" content="LockHub Team" />
  <link rel="shortcut icon" href="images/favicon.png" type="image/x-icon">

  <title>LOCKHUB - Sign Up</title>

  <!-- bootstrap core css -->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />

  <!-- fonts style -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

  <!-- font awesome style -->
  <link href="css/font-awesome.min.css" rel="stylesheet" />

  <!-- Custom styles for this template -->
  <link href="css/style.css" rel="stylesheet" />
  <!-- responsive style -->
  <link href="css/responsive.css" rel="stylesheet" />
</head>

<body class="sub_page">

  <div class="hero_area">
    <div class="hero_bg_box">
      <div class="bg_img_box">
        <img src="images/hero.png" alt="LockHub Hero Image">
      </div>
    </div>

    <!-- header section starts -->
    <header class="header_section">
      <div class="container-fluid">
        <nav class="navbar navbar-expand-lg custom_nav-container">
          <a class="navbar-brand" href="main.php">
            <span>
              LOCKHUB
            </span>
          </a>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class=""> </span>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav">
              <li class="nav-item">
                <a class="nav-link" href="main.php">Home</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="about.php">About</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="why.php">Why Us</a>
              </li>
              <li class="nav-item active">
                <a class="nav-link" href="signup.php">Sign Up</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="index.php"><i class="fa fa-user" aria-hidden="true"></i> Login</a>
              </li>
            </ul>
          </div>
        </nav>
      </div>
    </header>
    <!-- end header section -->
  </div>

  <!-- Sign Up Form Section -->
  <div class="home-page">
    <form action="signup-check.php" method="post">
      <h2>SIGN UP</h2>

      <?php if (isset($_GET['error'])) { ?>
        <p class="error"><?php echo $_GET['error']; ?></p>
      <?php } ?>

      <?php if (isset($_GET['success'])) { ?>
        <p class="success"><?php echo $_GET['success']; ?></p>
      <?php } ?>

      <label>Name</label>
      <?php if (isset($_GET['name'])) { ?>
        <input type="text" name="name" placeholder="Name" value="<?php echo $_GET['name']; ?>"><br>
      <?php } else { ?>
        <input type="text" name="name" placeholder="Name"><br>
      <?php } ?>

      <label>User Name</label>
      <?php if (isset($_GET['uname'])) { ?>
        <input type="text" name="uname" placeholder="User Name" value="<?php echo $_GET['uname']; ?>"><br>
      <?php } else { ?>
        <input type="text" name="uname" placeholder="User Name"><br>
      <?php } ?>

      <label>Password</label>
      <input type="password" name="password" placeholder="Password"><br>

      <label>Confirm Password</label>
      <input type="password" name="re_password" placeholder="Retype Password"><br>

      <button type="submit">Sign Up</button> <!-- Styled Sign Up button -->
      <a href="index.php" class="ca">Already have an account?</a>
    </form>
  </div>
  <!-- End Sign Up Form Section -->


    <!-- Info Section -->
    <section class="info_section layout_padding2">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-4 info_col">
                <div class="info_contact">
                    <h4>Contact Information</h4>
                    <div class="contact_link_box">
                        <a href="mailto:support@lockhub.com">
                            <i class="fa fa-envelope" aria-hidden="true"></i> support@lockhub.com
                        </a>
                        <a href="tel:+011234567890">
                            <i class="fa fa-phone" aria-hidden="true"></i> Call +01 1234567890
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 info_col">
                <div class="info_detail">
                    <h4>About Us</h4>
                    <p>LockHub is a secure password manager that stores your passwords and sensitive data, accessible anytime, anywhere.</p>
                </div>
            </div>
            <div class="col-md-4 info_col">
                <div class="info_link_box">
                    <h4>Quick Links</h4>
                    <div class="info_links">
                        <a href="home.php">Home</a>
                        <a href="about.php">About</a>
                        <a href="why.php">Why Us</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
  <!-- End Info Section -->

  <!-- Footer Section -->
  <section class="footer_section">
    <div class="container">
      <p>&copy; <span id="displayYear"></span> All Rights Reserved By <a href="https://lockhub.com/">LockHub</a></p>
    </div>
  </section>
  <!-- End Footer Section -->

  <script src="js/jquery-3.4.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="js/bootstrap.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
  <script src="js/custom.js"></script>
</body>

</html>
