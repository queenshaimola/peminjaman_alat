<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role_user = strtolower($_SESSION['role'] ?? '');
?>

<!-- CSS KHUSUS SIDEBAR (Agar otomatis rapi di setiap halaman tanpa menuh-menuhin file lain) -->
<style>
    .sidebar {
        height: 100vh;
        background-color: #212529;
        color: #fff;
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        padding-top: 20px;
        transition: all 0.3s ease;
        z-index: 1000;
    }
    .sidebar.hidden {
        left: -260px;
    }
    .sidebar .brand {
        font-size: 1.25rem;
        font-weight: bold;
        padding: 0 20px 20px 20px;
        border-bottom: 1px solid #343a40;
    }
    .sidebar .nav-link {
        color: #adb5bd;
        padding: 12px 20px;
        font-weight: 500;
        transition: 0.2s;
    }
    .sidebar .nav-link:hover, .sidebar .nav-link.active {
        color: #fff;
        background-color: #0d6efd;
        border-radius: 5px;
        margin: 0 10px;
    }
</style>

<!-- SIDEBAR NAVIGATION -->
<div class="sidebar d-flex flex-column justify-content-between" id="sidebarNav">
    <div>
        <div class="brand text-warning d-flex justify-content-between align-items-center">
            <span><i class="fas fa-tools me-2"></i> Rental & Co.</span>
            <button class="btn btn-sm text-white d-md-none" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item mb-1">
                <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>

            <!-- MENU KHUSUS PETUGAS (PERSETUJUAN, PEMANTAUAN & LAPORAN) -->
            <?php if ($role_user === 'petugas') { ?>
                <li class="nav-item mb-1">
                    <a href="persetujuan.php" class="nav-link <?php echo ($current_page == 'persetujuan.php') ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle me-2"></i> Menyetujui Peminjaman
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="pengembalian.php" class="nav-link <?php echo ($current_page == 'pengembalian.php') ? 'active' : ''; ?>">
                        <i class="fas fa-eye me-2"></i> Memantau Pengembalian
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="laporan.php" class="nav-link <?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>">
                        <i class="fas fa-print me-2"></i> Mencetak Laporan
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
    <div class="p-3 border-top border-secondary">
        <a href="../logout.php" class="btn btn-outline-danger w-100 btn-sm fw-bold"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>