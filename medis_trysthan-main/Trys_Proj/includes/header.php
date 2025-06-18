<header class="main-header">
    <div class="logo">
        <h1>Sistem Rekam Medis</h1>
    </div>
    <div class="user-menu">
        <span class="user-name">
            <i class="fas fa-user-circle"></i>
            <?php echo $_SESSION['username']; ?> 
            (<?php echo ucfirst($_SESSION['role']); ?>)
        </span>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>
