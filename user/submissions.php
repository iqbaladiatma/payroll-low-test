<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if profile is complete
$profile_query = "SELECT is_complete FROM user_profiles WHERE user_id = $user_id";
$profile_result = $db->query($profile_query);
$profile = $profile_result->fetch(PDO::FETCH_ASSOC);

if (!$profile || !$profile['is_complete']) {
    $_SESSION['error'] = 'Anda harus melengkapi biodata terlebih dahulu!';
    header('Location: profile.php');
    exit();
}

// Handle form submission
if ($_POST && isset($_POST['submit_request'])) {
    $type = $_POST['type'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    $insert_query = "INSERT INTO submissions (user_id, type, title, description) 
                     VALUES ($user_id, '$type', '$title', '$description')";
    $db->exec($insert_query);
    
    $_SESSION['success'] = 'Pengajuan berhasil dikirim!';
    header('Location: submissions.php');
    exit();
}

// Get user submissions
$submissions_query = "SELECT * FROM submissions WHERE user_id = $user_id ORDER BY created_at DESC";
$submissions_result = $db->query($submissions_query);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-plus-circle"></i> Buat Pengajuan Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label>Jenis Pengajuan *</label>
                            <select name="type" class="form-control" required>
                                <option value="">Pilih Jenis</option>
                                <option value="leave">Cuti/Izin</option>
                                <option value="overtime">Lembur</option>
                                <option value="salary_adjustment">Penyesuaian Gaji</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Judul Pengajuan *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Deskripsi *</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" name="submit_request" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Riwayat Pengajuan Saya</h5>
                </div>
                <div class="card-body">
                    <?php if ($submissions_result->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Judul</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($submission = $submissions_result->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($submission['created_at'])) ?></td>
                                            <td>
                                                <?php
                                                $types = [
                                                    'leave' => 'Cuti/Izin',
                                                    'overtime' => 'Lembur',
                                                    'salary_adjustment' => 'Penyesuaian Gaji',
                                                    'other' => 'Lainnya'
                                                ];
                                                echo $types[$submission['type']];
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($submission['title']) ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                $status_text = [
                                                    'pending' => 'Menunggu',
                                                    'approved' => 'Disetujui',
                                                    'rejected' => 'Ditolak'
                                                ];
                                                ?>
                                                <span class="badge badge-<?= $status_class[$submission['status']] ?>">
                                                    <?= $status_text[$submission['status']] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewSubmission(<?= $submission['id'] ?>)">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Belum ada pengajuan yang dibuat</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pengajuan -->
<div class="modal fade" id="submissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="submissionDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewSubmission(id) {
    fetch('../api/get_submission.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const submission = data.submission;
                const types = {
                    'leave': 'Cuti/Izin',
                    'overtime': 'Lembur',
                    'salary_adjustment': 'Penyesuaian Gaji',
                    'other': 'Lainnya'
                };
                
                const statusText = {
                    'pending': 'Menunggu',
                    'approved': 'Disetujui',
                    'rejected': 'Ditolak'
                };
                
                let html = `
                    <div class="row">
                        <div class="col-sm-4"><strong>Jenis:</strong></div>
                        <div class="col-sm-8">${types[submission.type]}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-sm-4"><strong>Judul:</strong></div>
                        <div class="col-sm-8">${submission.title}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-sm-4"><strong>Status:</strong></div>
                        <div class="col-sm-8">${statusText[submission.status]}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-sm-4"><strong>Tanggal:</strong></div>
                        <div class="col-sm-8">${new Date(submission.created_at).toLocaleString('id-ID')}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-sm-4"><strong>Deskripsi:</strong></div>
                        <div class="col-sm-8">${submission.description}</div>
                    </div>
                `;
                
                if (submission.admin_notes) {
                    html += `
                        <div class="row mt-2">
                            <div class="col-sm-4"><strong>Catatan Admin:</strong></div>
                            <div class="col-sm-8">${submission.admin_notes}</div>
                        </div>
                    `;
                }
                
                document.getElementById('submissionDetails').innerHTML = html;
                $('#submissionModal').modal('show');
            }
        });
}
</script>

<?php include '../includes/footer.php'; ?>