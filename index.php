<?php
// Muat semua class yang dibutuhkan
require_once __DIR__ . '/src/Validator.php';
require_once __DIR__ . '/src/FileUploader.php';
require_once __DIR__ . '/src/CsrfProtector.php';

session_start();

$errors  = [];
$old     = [];
$sukses  = false;

// Helper escape output agar aman dari XSS
function e(string $val): string
{
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

// ============================================
// PROSES FORM SAAT SUBMIT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verifikasi CSRF token dulu sebelum proses apapun
    if (!CsrfProtector::verifyToken($_POST['csrf_token'] ?? null)) {
        die('CSRF token tidak valid. Silakan muat ulang halaman.');
    }

    // 2. Ambil semua input & simpan untuk sticky form
    $old = [
        'nama'     => trim($_POST['nama'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'telepon'  => trim($_POST['telepon'] ?? ''),
        'usia'     => trim($_POST['usia'] ?? ''),
        'kota'     => $_POST['kota'] ?? '',
        'pesan'    => trim($_POST['pesan'] ?? ''),
    ];

    // 3. Validasi semua input menggunakan class Validator
    $validator = new Validator($_POST);
    $validator
        ->required('nama', 'Nama')
        ->minLength('nama', 3, 'Nama')
        ->maxLength('nama', 100, 'Nama')
        ->required('email', 'Email')
        ->email('email', 'Email')
        ->required('telepon', 'Telepon')
        ->pattern('telepon', '/^08[0-9]{8,11}$/', 'Format telepon tidak valid (contoh: 081234567890)')
        ->required('usia', 'Usia')
        ->numericBetween('usia', 17, 100, 'Usia')
        ->required('kota', 'Kota')
        ->required('pesan', 'Pesan')
        ->minLength('pesan', 10, 'Pesan')
        ->maxLength('pesan', 500, 'Pesan');

    $errors = $validator->getErrors();

    // 4. Proses upload foto kalau ada file yang dipilih
    $namaFile = null;
    if (!empty($_FILES['foto']['name'])) {
        $uploader = new FileUploader(
            allowedMimes:      ['image/jpeg', 'image/png', 'image/webp'],
            allowedExtensions: ['jpg', 'jpeg', 'png', 'webp'],
            maxSize:           2 * 1024 * 1024,
            uploadDir:         __DIR__ . '/uploads/'
        );
        $hasilUpload = $uploader->upload($_FILES['foto']);
        if (!$hasilUpload['success']) {
            $errors['foto'] = $hasilUpload['error'];
        } else {
            $namaFile = $hasilUpload['filename'];
        }
    }

    // 5. Kalau tidak ada error, tampilkan sukses
    if (empty($errors)) {
        $sukses = true;
        $old    = []; // Kosongkan form setelah sukses
    }
}

// Generate CSRF token untuk form
$csrfToken = CsrfProtector::getToken();

// Pilihan kota
$kotaOptions = [
    'wonosobo' => 'Wonosobo',
    'magelang' => 'Magelang',
    'purworejo' => 'Purworejo',
    'temanggung' => 'Temanggung',
    'banjarnegara' => 'Banjarnegara',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Registrasi | Modern UI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
            position: relative;
        }

        /* Decorative blobs */
        body::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            pointer-events: none;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Glassmorphism card */
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .form-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(120deg, #1e293b, #0f172a);
            padding: 1.8rem 2rem;
            text-align: center;
        }

        .card-header h1 {
            color: white;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .card-header .subtitle {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .badge-modern {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            color: #e2e8f0;
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .card-body {
            padding: 2rem;
        }

        /* Form styling modern */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        label i {
            font-style: normal;
            font-size: 1.1rem;
        }

        .label-icon {
            width: 24px;
            text-align: center;
        }

        .opsional {
            font-weight: 400;
            color: #64748b;
            font-size: 0.7rem;
            background: #f1f5f9;
            padding: 0.125rem 0.5rem;
            border-radius: 999px;
            margin-left: 0.5rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            background: white;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }

        input.error-input, select.error-input, textarea.error-input {
            border-color: #f43f5e;
            background: #fff1f2;
        }

        .error-msg {
            color: #e11d48;
            font-size: 0.75rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .error-msg::before {
            content: "⚠️";
            font-size: 0.7rem;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Button modern */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(120deg, #8b5cf6, #7c3aed);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            border-radius: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 14px 0 rgba(124, 58, 237, 0.4);
        }

        .btn-submit:hover {
            transform: scale(1.02);
            background: linear-gradient(120deg, #7c3aed, #6d28d9);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.5);
        }

        /* Alert success modern */
        .alert-success {
            background: linear-gradient(120deg, #d1fae5, #a7f3d0);
            border-left: 5px solid #059669;
            color: #065f46;
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        .alert-success strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .foto-preview {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f1f5f9;
            border-radius: 1rem;
            text-align: center;
        }

        .foto-preview img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border: 3px solid white;
        }

        input[type="file"] {
            padding: 0.6rem;
            background: white;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .card-body {
                padding: 1.5rem;
            }
            .card-header h1 {
                font-size: 1.4rem;
            }
        }

        /* Animasi fade in */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="form-card">
        <div class="card-header">
            <span class="badge-modern">✨ Pertemuan 6 — Secure Form</span>
            <h1>Buat Akun Baru</h1>
            <p class="subtitle">Lengkapi data diri Anda dengan benar</p>
        </div>
        <div class="card-body">

            <?php if ($sukses): ?>
                <div class="alert-success">
                    <strong>🎉 Pendaftaran Berhasil!</strong>
                    Data Anda telah tersimpan dengan aman.
                    <?php if ($namaFile): ?>
                        <br>📸 Foto profil: <strong><?= e($namaFile) ?></strong>
                        <div class="foto-preview">
                            <img src="uploads/<?= e($namaFile) ?>" alt="Foto Profil">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" enctype="multipart/form-data">

                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <!-- Nama -->
                <div class="form-group">
                    <label><span class="label-icon">👤</span> Nama Lengkap</label>
                    <input type="text" id="nama" name="nama"
                           value="<?= e($old['nama'] ?? '') ?>"
                           class="<?= isset($errors['nama']) ? 'error-input' : '' ?>"
                           placeholder="Masukkan nama lengkap Anda">
                    <?php if (isset($errors['nama'])): ?>
                        <div class="error-msg"><?= e($errors['nama']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label><span class="label-icon">📧</span> Alamat Email</label>
                    <input type="email" id="email" name="email"
                           value="<?= e($old['email'] ?? '') ?>"
                           class="<?= isset($errors['email']) ? 'error-input' : '' ?>"
                           placeholder="contoh: nama@domain.com">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-msg"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Telepon -->
                <div class="form-group">
                    <label><span class="label-icon">📞</span> Nomor Telepon</label>
                    <input type="text" id="telepon" name="telepon"
                           value="<?= e($old['telepon'] ?? '') ?>"
                           class="<?= isset($errors['telepon']) ? 'error-input' : '' ?>"
                           placeholder="08xxxxxxxxxx">
                    <?php if (isset($errors['telepon'])): ?>
                        <div class="error-msg"><?= e($errors['telepon']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Usia -->
                <div class="form-group">
                    <label><span class="label-icon">🎂</span> Usia</label>
                    <input type="number" id="usia" name="usia"
                           value="<?= e($old['usia'] ?? '') ?>"
                           class="<?= isset($errors['usia']) ? 'error-input' : '' ?>"
                           placeholder="17 - 100" min="17" max="100">
                    <?php if (isset($errors['usia'])): ?>
                        <div class="error-msg"><?= e($errors['usia']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Kota -->
                <div class="form-group">
                    <label><span class="label-icon">🏙️</span> Kota</label>
                    <select id="kota" name="kota"
                            class="<?= isset($errors['kota']) ? 'error-input' : '' ?>">
                        <option value="">-- Pilih Kota --</option>
                        <?php foreach ($kotaOptions as $val => $label): ?>
                            <option value="<?= e($val) ?>"
                                <?= (($old['kota'] ?? '') === $val) ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['kota'])): ?>
                        <div class="error-msg"><?= e($errors['kota']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Pesan -->
                <div class="form-group">
                    <label><span class="label-icon">💬</span> Pesan</label>
                    <textarea id="pesan" name="pesan"
                              class="<?= isset($errors['pesan']) ? 'error-input' : '' ?>"
                              placeholder="Tulis pesan atau kesan Anda (minimal 10 karakter)..."><?= e($old['pesan'] ?? '') ?></textarea>
                    <?php if (isset($errors['pesan'])): ?>
                        <div class="error-msg"><?= e($errors['pesan']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Upload Foto -->
                <div class="form-group">
                    <label><span class="label-icon">🖼️</span> Foto <span class="opsional">Opsional</span></label>
                    <input type="file" id="foto" name="foto" accept="image/*"
                           class="<?= isset($errors['foto']) ? 'error-input' : '' ?>">
                    <small style="color: #64748b; display: block; margin-top: 0.25rem;">JPG, PNG, WEBP (Max 2MB)</small>
                    <?php if (isset($errors['foto'])): ?>
                        <div class="error-msg"><?= e($errors['foto']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">
                    ✨ Daftar Sekarang
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>