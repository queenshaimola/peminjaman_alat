<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rental & Co.</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card card-login p-4 bg-white">
                    <div class="text-center mb-4">
                        <div class="bg-primary text-white d-inline-block p-3 rounded-circle mb-2 shadow-sm">
                            <i class="fas fa-tools fa-2x"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Rental & Co.</h3>
                        <p class="text-muted small">Silakan login untuk mengakses sistem peminjaman</p>
                    </div>

                    <!-- Notifikasi Pesan Gagal Login dari URL -->
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'gagal') { ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                            <i class="fas fa-exclamation-circle me-1"></i> Username atau Password salah!
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php } ?>

                    <!-- Form Login mengarah ke file proses_login.php -->
                    <form action="proses_login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold small">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username..." required autocomplete="off">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password..." required>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn btn-primary fw-bold py-2 rounded-pill shadow-sm">
                                <i class="fas fa-sign-in-alt me-2"></i> Masuk Sistem
                            </button>
                        </div>

                        <!-- Tombol Kembali ke Halaman Index / Beranda -->
                        <div class="text-center">
                            <a href="index.php" class="text-decoration-none small text-muted">
                                <i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>