<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/UserController.php';
require_once __DIR__ . '/../src/Controller/AdminController.php';

$uc = new UserController($pdo);
$adminController = new AdminController($pdo);

// viewing ?id= or own profile
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user_id'] ?? null);
if (!$viewId) { header('Location: login.php'); exit(); }

$isOwnProfile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $viewId;

// Handle profile edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
  if (!$isOwnProfile) {
    header('Location: profile.php'); exit();
  }
  
  $result = $uc->editProfile($viewId, $_POST);
  $message = $result['status'] === 'success' ? 'Profile updated successfully!' : 'Error updating profile: ' . ($result['message'] ?? 'Unknown error');
  $messageType = $result['status'] === 'success' ? 'success' : 'error';
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
  if (!$isOwnProfile) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => 'unauthorized']);
      exit();
    }
    header('Location: profile.php'); exit();
  }

  $response = ['success' => false];
  if (!empty($_FILES['avatar']['name'])) {
    $up = $_FILES['avatar'];
    $targetDir = __DIR__ . '/uploads';
    
    // Ensure uploads directory exists with proper permissions
    if (!is_dir($targetDir)) {
      if (!mkdir($targetDir, 0755, true)) {
        $response['error'] = 'directory_creation_failed';
        error_log("Failed to create uploads directory: " . $targetDir);
      }
    }
    
    // Check if directory is writable
    if (!is_writable($targetDir)) {
      $response['error'] = 'directory_not_writable';
      error_log("Uploads directory not writable: " . $targetDir);
    } else {
      // Validate file upload
      if ($up['error'] !== UPLOAD_ERR_OK) {
        $response['error'] = 'upload_error_' . $up['error'];
        error_log("File upload error: " . $up['error']);
      } else {
        $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($up['size'] > $maxSize) {
          $response['error'] = 'file_too_large';
        } elseif (!in_array($ext, $allowed)) {
          $response['error'] = 'invalid_extension';
        } else {
          $safe = 'avatar_' . $viewId . '.' . $ext;
          $dest = $targetDir . '/' . $safe;
          
          // Remove previous avatars for this user
          foreach (glob($targetDir . '/avatar_' . $viewId . '.*') as $f) {
            if (file_exists($f)) {
              @unlink($f);
            }
          }
          
          // Move uploaded file
          if (move_uploaded_file($up['tmp_name'], $dest)) {
            // Set proper file permissions
            chmod($dest, 0644);
            $response['success'] = true;
            $response['avatar'] = 'uploads/' . basename($dest);
            error_log("Avatar uploaded successfully: " . $dest);
          } else {
            $response['error'] = 'move_failed';
            error_log("Failed to move uploaded file to: " . $dest);
          }
        }
      }
    }
  } else {
    $response['error'] = 'no_file';
  }

  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
  }

  // non-AJAX fallback
  if (!empty($response['success'])) {
    header('Location: profile.php' . ($viewId ? '?id='.$viewId : '')); exit();
  }
  header('Location: profile.php' . ($viewId ? '?id='.$viewId : '')); exit();
}

$profile = $uc->viewProfile($viewId);
$avatar = null;
$files = @glob(__DIR__ . '/uploads/avatar_' . $viewId . '.*');
if ($files && count($files) > 0) { $avatar = 'uploads/' . basename($files[0]); }

// Check if new profile fields are available
$hasNewFields = !is_null($profile['student_id'] ?? null) || !empty($profile['bio'] ?? '') || !empty($profile['course'] ?? '');
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'student_id'");
    $newFieldsExist = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $newFieldsExist = false;
}

// fetch warnings for this user (if any)
$warnings = [];
try { $warnings = $adminController->getUserWarnings($viewId, 50); } catch (Exception $e) { $warnings = []; }
$warningsCount = is_array($warnings) ? count($warnings) : 0;

?>
<?php require_once __DIR__ . '/../src/View/header.php'; ?>

<style>
.profile-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-field {
  margin-bottom: 15px;
}

.profile-field label {
  display: block;
  font-weight: 600;
  margin-bottom: 5px;
  color: #374151;
}

.profile-field .value {
  color: #6b7280;
  font-size: 14px;
}

.edit-form {
  display: none;
}

.edit-form.active {
  display: block;
}

.profile-view.editing {
  display: none;
}

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.form-row .form-group {
  flex: 1;
}

.message {
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
}

.message.success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.message.error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.profile-header {
  display: flex;
  gap: 20px;
  align-items: flex-start;
  margin-bottom: 30px;
}

.avatar-section {
  flex-shrink: 0;
}

.profile-info {
  flex: 1;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 15px;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 8px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalSlideIn 0.3s ease-out;
}

.avatar-modal {
  max-width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-lg) var(--space-xl);
  border-bottom: 1px solid var(--gray-200);
  background: linear-gradient(135deg, var(--primary-green-lighter), var(--bg-primary));
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--primary-green-dark);
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.modal-header h3 i {
  color: var(--primary-green);
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--text-tertiary);
  cursor: pointer;
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.modal-close:hover {
  background: var(--gray-100);
  color: var(--text-primary);
}

.modal-body {
  padding: var(--space-xl);
  text-align: center;
}

.modal-body p {
  margin-bottom: var(--space-lg);
  color: var(--text-secondary);
}

.avatar-preview {
  width: 120px;
  height: 120px;
  margin: 0 auto var(--space-lg);
  border-radius: 50%;
  overflow: hidden;
  background: var(--gray-100);
  border: 3px solid var(--primary-green-lighter);
  position: relative;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: none;
}

.preview-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-tertiary);
  gap: var(--space-sm);
}

.preview-placeholder i {
  font-size: 2rem;
  color: var(--primary-green-light);
}

.modal-footer {
  display: flex;
  gap: var(--space-md);
  justify-content: flex-end;
  padding: var(--space-lg) var(--space-xl);
  border-top: 1px solid var(--gray-200);
  background: var(--gray-50);
}

.btn {
  padding: var(--space-sm) var(--space-lg);
  border: none;
  border-radius: var(--radius-lg);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
  text-decoration: none;
  font-size: 0.9rem;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
  color: var(--text-inverse);
  box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-secondary {
  background: var(--bg-primary);
  color: var(--text-secondary);
  border: 1px solid var(--gray-300);
}

.btn-secondary:hover {
  background: var(--gray-50);
  border-color: var(--primary-green);
  color: var(--text-primary);
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
  }
  
  .form-row {
    flex-direction: column;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    width: 95%;
    margin: var(--space-md);
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .modal-footer .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>

<main class="container">
  <?php if (isset($message)): ?>
    <div class="message <?php echo $messageType; ?>">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>
  
  <?php if (!$newFieldsExist && $isOwnProfile): ?>
    <div class="message" style="background: #fef3c7; color: #92400e; border: 1px solid #fbbf24;">
      <strong>📋 Enhanced Profile Features Available!</strong><br>
      To unlock all the new student profile features (bio, academic info, interests, etc.), 
      <a href="setup_profile_fields.php" style="color: #92400e; text-decoration: underline;">click here to run the database migration</a>.
    </div>
  <?php endif; ?>

  <div class="profile-section">
    <div class="profile-header">
      <div class="avatar-section">
        <div id="avatarContainer" style="width:96px;height:96px;border-radius:50%;overflow:hidden;background:#eee">
          <?php if ($avatar): ?>
            <img id="currentAvatar" src="<?php echo $avatar; ?>?t=<?php echo time(); ?>" style="width:100%;height:100%;object-fit:cover" alt="avatar">
          <?php else: ?>
            <div id="currentAvatarPlaceholder" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#777;font-size:12px">No avatar</div>
          <?php endif; ?>
        </div>
        
        <?php if ($isOwnProfile): ?>
          <form method="POST" enctype="multipart/form-data" id="avatarForm" style="margin-top:10px">
            <input type="hidden" name="action" value="upload_avatar">
            <label class="btn small avatar-upload-btn" style="cursor:pointer;display:flex;align-items:center;gap:6px;">
              <i class="fas fa-camera"></i>
              <span>Change Avatar</span>
              <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none">
            </label>
            <div class="upload-hint" style="font-size:11px;color:#666;margin-top:4px;text-align:center;">JPG, PNG, GIF, WebP (max 5MB)</div>
          </form>
        <?php endif; ?>
      </div>
      
      <div class="profile-info">
        <h2 style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 10px 0">
          <span><?php echo htmlspecialchars($profile['name'] ?? ''); ?></span>
          <?php if ($warningsCount > 0 && (($_SESSION['role'] ?? '') === 'admin' || $isOwnProfile)): ?>
            <span style="background:#f59e0b;color:white;padding:4px 8px;border-radius:12px;font-size:13px">🔔 Warnings: <?php echo $warningsCount; ?></span>
          <?php elseif (($_SESSION['role'] ?? '') === 'admin' || $isOwnProfile): ?>
            <span style="background:#eef2ff;color:#6366f1;padding:4px 8px;border-radius:12px;font-size:13px">🔔 Warnings: <?php echo $warningsCount; ?></span>
          <?php endif; ?>
          <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $viewId): ?>
            <button class="btn danger small" onclick="openReportOnProfile(<?php echo $viewId; ?>, '<?php echo htmlspecialchars($profile['name'] ?? ''); ?>')" style="font-size:12px;padding:6px 12px">📋 Report</button>
          <?php endif; ?>
        </h2>
        
        <div class="kv" style="margin-bottom:5px"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></div>
        <?php if (!empty($profile['student_id'])): ?>
          <div class="kv">Student ID: <?php echo htmlspecialchars($profile['student_id']); ?></div>
        <?php endif; ?>
        <?php if (!empty($profile['course'])): ?>
          <div class="kv"><?php echo htmlspecialchars($profile['course']); ?><?php if (!empty($profile['major'])): ?> - <?php echo htmlspecialchars($profile['major']); ?><?php endif; ?></div>
        <?php endif; ?>
        <?php if (!empty($profile['year_level'])): ?>
          <div class="kv"><?php echo ucfirst(str_replace('_', ' ', $profile['year_level'])); ?></div>
        <?php endif; ?>
        
        <?php if ($isOwnProfile): ?>
          <button class="btn" onclick="toggleEditMode()" id="editToggleBtn" style="margin-top:15px">Edit Profile</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Profile View Mode -->
    <div id="profileView" class="profile-view">
      <div class="info-grid">
        <!-- Personal Information -->
        <div>
          <h3 class="section-title">Personal Information</h3>
          
          <?php if (!empty($profile['bio'])): ?>
            <div class="profile-field">
              <label>Bio</label>
              <div class="value"><?php echo nl2br(htmlspecialchars($profile['bio'])); ?></div>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($profile['date_of_birth'])): ?>
            <div class="profile-field">
              <label>Date of Birth</label>
              <div class="value"><?php echo date('F j, Y', strtotime($profile['date_of_birth'])); ?></div>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($profile['gender'])): ?>
            <div class="profile-field">
              <label>Gender</label>
              <div class="value"><?php echo ucfirst(str_replace('_', ' ', $profile['gender'])); ?></div>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($profile['hometown'])): ?>
            <div class="profile-field">
              <label>Hometown</label>
              <div class="value"><?php echo htmlspecialchars($profile['hometown']); ?></div>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($profile['interests'])): ?>
            <div class="profile-field">
              <label>Interests</label>
              <div class="value"><?php echo nl2br(htmlspecialchars($profile['interests'])); ?></div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Academic Information -->
        <div>
          <h3 class="section-title">Academic Information</h3>
          
          <div class="profile-field">
            <label>Contact Number</label>
            <div class="value"><?php echo htmlspecialchars($profile['contact_number'] ?? 'Not provided'); ?></div>
          </div>
          
          <?php if (!empty($profile['course'])): ?>
            <div class="profile-field">
              <label>Course</label>
              <div class="value"><?php echo htmlspecialchars($profile['course']); ?></div>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($profile['major'])): ?>
            <div class="profile-field">
              <label>Major/Specialization</label>
              <div class="value"><?php echo htmlspecialchars($profile['major']); ?></div>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($profile['year_level'])): ?>
            <div class="profile-field">
              <label>Year Level</label>
              <div class="value"><?php echo ucfirst(str_replace('_', ' ', $profile['year_level'])); ?></div>
            </div>
          <?php endif; ?>
          
          <?php if ($isOwnProfile && (!empty($profile['emergency_contact_name']) || !empty($profile['emergency_contact_phone']))): ?>
            <h4 style="margin-top:20px;margin-bottom:10px;color:#374151">Emergency Contact</h4>
            <?php if (!empty($profile['emergency_contact_name'])): ?>
              <div class="profile-field">
                <label>Emergency Contact Name</label>
                <div class="value"><?php echo htmlspecialchars($profile['emergency_contact_name']); ?></div>
              </div>
            <?php endif; ?>
            <?php if (!empty($profile['emergency_contact_phone'])): ?>
              <div class="profile-field">
                <label>Emergency Contact Phone</label>
                <div class="value"><?php echo htmlspecialchars($profile['emergency_contact_phone']); ?></div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Edit Mode -->
    <?php if ($isOwnProfile): ?>
      <div id="editForm" class="edit-form">
        <form method="POST">
          <input type="hidden" name="action" value="edit_profile">
          
          <h3 class="section-title">Edit Profile</h3>
          
          <!-- Basic Information -->
          <div class="form-row">
            <div class="form-group">
              <label for="name">Full Name *</label>
              <input type="text" id="name" name="name" class="input" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="email">Email *</label>
              <input type="email" id="email" name="email" class="input" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="student_id">Student ID</label>
              <input type="text" id="student_id" name="student_id" class="input" value="<?php echo htmlspecialchars($profile['student_id'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="contact_number">Contact Number</label>
              <input type="text" id="contact_number" name="contact_number" class="input" value="<?php echo htmlspecialchars($profile['contact_number'] ?? ''); ?>">
            </div>
          </div>
          
          <!-- Personal Information -->
          <div class="form-row">
            <div class="form-group">
              <label for="date_of_birth">Date of Birth</label>
              <input type="date" id="date_of_birth" name="date_of_birth" class="input" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="gender">Gender</label>
              <select id="gender" name="gender" class="input">
                <option value="">Select Gender</option>
                <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                <option value="other" <?php echo ($profile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                <option value="prefer_not_to_say" <?php echo ($profile['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
              </select>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="hometown">Hometown</label>
              <input type="text" id="hometown" name="hometown" class="input" value="<?php echo htmlspecialchars($profile['hometown'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="profile_visibility">Profile Visibility</label>
              <select id="profile_visibility" name="profile_visibility" class="input">
                <option value="students_only" <?php echo ($profile['profile_visibility'] ?? 'students_only') === 'students_only' ? 'selected' : ''; ?>>Students Only</option>
                <option value="public" <?php echo ($profile['profile_visibility'] ?? '') === 'public' ? 'selected' : ''; ?>>Public</option>
                <option value="private" <?php echo ($profile['profile_visibility'] ?? '') === 'private' ? 'selected' : ''; ?>>Private</option>
              </select>
            </div>
          </div>
          
          <!-- Academic Information -->
          <div class="form-row">
            <div class="form-group">
              <label for="course">Course/Program</label>
              <input type="text" id="course" name="course" class="input" value="<?php echo htmlspecialchars($profile['course'] ?? ''); ?>" placeholder="e.g., Computer Science, Engineering">
            </div>
            <div class="form-group">
              <label for="major">Major/Specialization</label>
              <input type="text" id="major" name="major" class="input" value="<?php echo htmlspecialchars($profile['major'] ?? ''); ?>" placeholder="e.g., Software Engineering, Data Science">
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="year_level">Year Level</label>
              <select id="year_level" name="year_level" class="input">
                <option value="">Select Year Level</option>
                <option value="1st_year" <?php echo ($profile['year_level'] ?? '') === '1st_year' ? 'selected' : ''; ?>>1st Year</option>
                <option value="2nd_year" <?php echo ($profile['year_level'] ?? '') === '2nd_year' ? 'selected' : ''; ?>>2nd Year</option>
                <option value="3rd_year" <?php echo ($profile['year_level'] ?? '') === '3rd_year' ? 'selected' : ''; ?>>3rd Year</option>
                <option value="4th_year" <?php echo ($profile['year_level'] ?? '') === '4th_year' ? 'selected' : ''; ?>>4th Year</option>
                <option value="5th_year" <?php echo ($profile['year_level'] ?? '') === '5th_year' ? 'selected' : ''; ?>>5th Year</option>
                <option value="graduate" <?php echo ($profile['year_level'] ?? '') === 'graduate' ? 'selected' : ''; ?>>Graduate</option>
              </select>
            </div>
          </div>
          
          <!-- Bio and Interests -->
          <div class="profile-field">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" class="input" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
          </div>
          
          <div class="profile-field">
            <label for="interests">Interests & Hobbies</label>
            <textarea id="interests" name="interests" class="input" rows="3" placeholder="What are your interests and hobbies?"><?php echo htmlspecialchars($profile['interests'] ?? ''); ?></textarea>
          </div>
          
          <!-- Emergency Contact -->
          <h4 style="margin-top:20px;margin-bottom:15px;color:#374151">Emergency Contact Information</h4>
          <div class="form-row">
            <div class="form-group">
              <label for="emergency_contact_name">Emergency Contact Name</label>
              <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="input" value="<?php echo htmlspecialchars($profile['emergency_contact_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="emergency_contact_phone">Emergency Contact Phone</label>
              <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="input" value="<?php echo htmlspecialchars($profile['emergency_contact_phone'] ?? ''); ?>">
            </div>
          </div>
          
          <div style="margin-top:20px;display:flex;gap:10px">
            <button type="submit" class="btn">Save Changes</button>
            <button type="button" class="btn secondary" onclick="toggleEditMode()">Cancel</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <!-- Avatar Change Confirmation Modal -->
  <?php if ($isOwnProfile): ?>
    <div id="avatarConfirmModal" class="modal-overlay" style="display:none;">
      <div class="modal-content avatar-modal">
        <div class="modal-header">
          <h3><i class="fas fa-camera"></i> Confirm Avatar Change</h3>
          <button class="modal-close" onclick="cancelAvatarChange()">&times;</button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to change your avatar to this image?</p>
          <div id="avatarPreview" class="avatar-preview">
            <img id="previewImage" class="preview-image">
            <div class="preview-placeholder" id="previewPlaceholder">
              <i class="fas fa-image"></i>
              <span>Preview</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" onclick="cancelAvatarChange()">Cancel</button>
          <button class="btn btn-primary" onclick="confirmAvatarChange()">
            <i class="fas fa-check"></i> Confirm Change
          </button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Report Modal (profile) -->
  <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $viewId): ?>
    <div id="profileReportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
      <div style="background:white; padding:20px; border-radius:8px; max-width:520px; width:90%;">
        <h3 style="margin:0 0 12px 0">Report <?php echo htmlspecialchars($profile['name'] ?? ''); ?></h3>
        <form method="POST" action="admin_user_management.php" id="profileReportForm">
          <input type="hidden" name="action" value="report_user">
          <input type="hidden" name="reported_user_id" id="profileReportUserId" value="<?php echo $viewId; ?>">
          <div style="margin-bottom:10px">
            <label for="profileReportReason" style="display:block;margin-bottom:6px;font-weight:600">Reason</label>
            <select name="reason" id="profileReportReason" class="input" required>
              <option value="">Select a reason</option>
              <option value="spam">Spam</option>
              <option value="harassment">Harassment</option>
              <option value="inappropriate_content">Inappropriate Content</option>
              <option value="fake_account">Fake Account</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div style="margin-bottom:10px">
            <label for="profileReportDescription" style="display:block;margin-bottom:6px;font-weight:600">Description (optional)</label>
            <textarea name="description" id="profileReportDescription" class="input" rows="3"></textarea>
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn secondary" onclick="closeProfileReport()">Cancel</button>
            <button type="submit" class="btn danger">Submit Report</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- Admin Warnings Section -->
  <?php if ((($_SESSION['role'] ?? '') === 'admin')): ?>
    <div class="profile-section">
      <h4 style="margin:6px 0">Recent Warnings</h4>
      <?php if (!$warningsCount): ?>
        <div class="kv">No warnings for this user.</div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($warnings as $w): ?>
            <div style="padding:10px;border-radius:6px;background:#fff7ed;border:1px solid #fef3c7">
              <div style="font-weight:600"><?php echo htmlspecialchars($w['warned_by_name'] ?? 'System'); ?> • <small style="color:#666"><?php echo date('M j, Y g:i A', strtotime($w['created_at'])); ?></small></div>
              <div style="margin-top:6px">Reason: <strong><?php echo htmlspecialchars($w['reason'] ?? ''); ?></strong></div>
              <?php if (!empty($w['notes'])): ?>
                <div style="margin-top:6px;color:#333"><?php echo nl2br(htmlspecialchars($w['notes'])); ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<script>
// Edit mode toggle
function toggleEditMode() {
  const profileView = document.getElementById('profileView');
  const editForm = document.getElementById('editForm');
  const editBtn = document.getElementById('editToggleBtn');
  
  if (editForm.classList.contains('active')) {
    // Switch to view mode
    editForm.classList.remove('active');
    profileView.classList.remove('editing');
    editBtn.textContent = 'Edit Profile';
  } else {
    // Switch to edit mode
    editForm.classList.add('active');
    profileView.classList.add('editing');
    editBtn.textContent = 'Cancel Edit';
  }
}

// Avatar functionality
<?php if ($isOwnProfile): ?>
(function(){
  const avatarInput = document.getElementById('avatarInput');
  const modal = document.getElementById('avatarConfirmModal');
  const previewImage = document.getElementById('previewImage');
  const previewPlaceholder = document.getElementById('previewPlaceholder');
  let objectUrl = null;

  window.cancelAvatarChange = function() {
    if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
    previewImage.style.display = 'none';
    previewPlaceholder.style.display = 'flex';
    avatarInput.value = '';
    modal.style.display = 'none';
  }

  window.confirmAvatarChange = function() {
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) return cancelAvatarChange();
    const form = document.getElementById('avatarForm');
    const fd = new FormData(form);
    fd.append('avatar', file);
    fetch(window.location.pathname + (window.location.search || ''), {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    }).then(r => r.json()).then(j => {
      if (j && j.success) {
        // update avatar image
        const container = document.getElementById('avatarContainer');
        container.innerHTML = '';
        const img = document.createElement('img');
        img.id = 'currentAvatar';
        img.src = j.avatar + '?t=' + Date.now();
        img.style.width = '100%'; img.style.height = '100%'; img.style.objectFit = 'cover';
        container.appendChild(img);
        cancelAvatarChange();
      } else {
        let errorMsg = 'Upload failed';
        if (j && j.error) {
          switch(j.error) {
            case 'directory_creation_failed':
              errorMsg = 'Failed to create uploads directory. Please check server permissions.';
              break;
            case 'directory_not_writable':
              errorMsg = 'Uploads directory is not writable. Please check server permissions.';
              break;
            case 'file_too_large':
              errorMsg = 'File is too large. Maximum size is 5MB.';
              break;
            case 'invalid_extension':
              errorMsg = 'Invalid file type. Please use JPG, PNG, GIF, or WebP images.';
              break;
            case 'move_failed':
              errorMsg = 'Failed to save the uploaded file. Please try again.';
              break;
            case 'no_file':
              errorMsg = 'No file was selected for upload.';
              break;
            default:
              errorMsg = 'Upload failed: ' + j.error;
          }
        }
        alert(errorMsg);
      }
    }).catch(err => { alert('Upload error'); console.error(err); });
  }

  avatarInput.addEventListener('change', function(){
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) return;
    if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
    objectUrl = URL.createObjectURL(file);
    previewImage.src = objectUrl;
    previewImage.style.display = 'block';
    previewPlaceholder.style.display = 'none';
    modal.style.display = 'flex';
  });
})();
<?php endif; ?>

// Report functionality
<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $viewId): ?>
function openReportOnProfile(id, name) {
  document.getElementById('profileReportUserId').value = id;
  document.getElementById('profileReportModal').style.display = 'flex';
}

function closeProfileReport() {
  document.getElementById('profileReportModal').style.display = 'none';
  document.getElementById('profileReportForm').reset();
}

window.addEventListener('click', function(e){
  const modal = document.getElementById('profileReportModal');
  if (e.target === modal) closeProfileReport();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../src/View/footer.php'; ?>