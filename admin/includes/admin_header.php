<?php 
// 🔑 CORRECTED PATH: '../../' moves up two levels (out of 'includes/' and 'admin/') to reach the root.
if (!isset($link)) {
    require_once '../../includes/config.php'; 
}

$store_name = "LogiX!"; // Custom store name
?>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><?php echo $store_name; ?> Admin Portal</a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-info text-white mr-2" href="dashboard.php">
                            🏠 Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-danger text-white" href="../logout.php">
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>