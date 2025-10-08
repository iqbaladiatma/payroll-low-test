<?php
session_start();
require_once './config/database.php';
require_once './includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user profile data
$profile_query = "SELECT * FROM user_profiles WHERE user_id = $user_id";
$profile_result = $db->query($profile_query);
$profile = $profile_result->fetch(PDO::FETCH_ASSOC);

// Check if profile is complete
$is_complete = $profile ? $profile['is_complete'] : false;

// Handle form submission
if ($_POST) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $id_number = $_POST['id_number'];
    $emergency_contact = $_POST['emergency_contact'];
    $emergency_phone = $_POST['emergency_phone'];
    $bank_account = $_POST['bank_account'];
    $bank_name = $_POST['bank_name'];
    
    if ($profile) {
        // Update existing profile
        $update_query = "UPDATE user_profiles SET 
            full_name = '$full_name',
            phone = '$phone',
            address = '$address',
            birth_date = '$birth_date',
            gender = '$gender',
            id_number = '$id_number',
            emergency_contact = '$emergency_contact',
            emergency_phone = '$emergency_phone',
            bank_account = '$bank_account',
            bank_name = '$bank_name',
            is_complete = TRUE
            WHERE user_id = $user_id";
        $db->exec($update_query);
    } else {
        // Insert new profile
        $insert_query = "INSERT INTO user_profiles 
            (user_id, full_name, phone, address, birth_date, gender, id_number, 
             emergency_contact, emergency_phone, bank_account, bank_name, is_complete) 
            VALUES 
            ($user_id, '$full_name', '$phone', '$address', '$birth_date', '$gender', 
             '$id_number', '$emergency_contact', '$emergency_phone', '$bank_account', '$bank_name', TRUE)";
        $db->exec($insert_query);
    }
    
    $_SESSION['success'] = 'Biodata berhasil disimpan!';
    header('Location: dashboard.php');
    exit();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-user-edit"></i> Lengkapi Biodata Anda</h4>
                    <?php if (!$is_complete): ?>
                        <div class="alert alert-warning mt-2">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Anda harus melengkapi biodata untuk dapat melihat informasi gaji.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Nama Lengkap *</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?= $profile['full_name'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>No. Telepon *</label>
                                    <input type="text" name="phone" class="form-control" 
                                           value="<?= $profile['phone'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Alamat *</label>
                            <textarea name="address" class="form-control" rows="3" required><?= $profile['address'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Tanggal Lahir *</label>
                                    <input type="date" name="birth_date" class="form-control" 
                                           value="<?= $profile['birth_date'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Jenis Kelamin *</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="male" <?= ($profile['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="female" <?= ($profile['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>No. KTP/ID *</label>
                            <input type="text" name="id_number" class="form-control" 
                                   value="<?= $profile['id_number'] ?? '' ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Kontak Darurat *</label>
                                    <input type="text" name="emergency_contact" class="form-control" 
                                           value="<?= $profile['emergency_contact'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>No. Telepon Darurat *</label>
                                    <input type="text" name="emergency_phone" class="form-control" 
                                           value="<?= $profile['emergency_phone'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>No. Rekening Bank</label>
                                    <input type="text" name="bank_account" class="form-control" 
                                           value="<?= $profile['bank_account'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Nama Bank</label>
                                    <input type="text" name="bank_name" class="form-control" 
                                           value="<?= $profile['bank_name'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Biodata
                            </button>
                            <?php if ($is_complete): ?>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>