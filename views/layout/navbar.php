<?php
// In the New e-LAAS layout, the top bar and sidebar navigation are rendered
// directly inside views/layout/header.php (matching index.html's structure:
// #app-container > .sidebar + .main-content > .top-header).
//
// This file is intentionally left as a no-op. Every controller in the app
// does:
//     include 'views/layout/header.php';
//     include 'views/layout/navbar.php';
//     include 'views/<page>/<action>.php';
//     include 'views/layout/footer.php';
//
// so navbar.php still needs to exist and be includable, but it has nothing
// left to render — keeping this avoids touching every controller file.
?>
