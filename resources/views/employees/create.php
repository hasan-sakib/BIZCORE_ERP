<?php
$pageTitle = 'Add Employee';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i>Add Employee</h5>
            </div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $field => $msg): ?>
                                <li><?= sanitize($msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/hr/employees">
                    <?= csrf_field() ?>

                    <!-- Personal Information -->
                    <h6 class="text-muted text-uppercase small fw-bold mb-3 mt-2">Personal Information</h6>
                    <div class="row g-3 mb-4">

                        <div class="col-12 col-md-6">
                            <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" id="first_name" name="first_name"
                                   class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['first_name'] ?? '') ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['first_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" id="last_name" name="last_name"
                                   class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['last_name'] ?? '') ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['last_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['email'] ?? '') ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control"
                                   value="<?= sanitize($old['phone'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="date_of_birth" class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                                   value="<?= sanitize($old['date_of_birth'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="gender" class="form-label fw-semibold">Gender</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="male"   <?= ($old['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($old['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other"  <?= ($old['gender'] ?? '') === 'other'  ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="2"><?= sanitize($old['address'] ?? '') ?></textarea>
                        </div>

                    </div>

                    <hr>

                    <!-- Employment Details -->
                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Employment Details</h6>
                    <div class="row g-3 mb-4">

                        <div class="col-12 col-md-6">
                            <label for="employee_number" class="form-label fw-semibold">Employee Number</label>
                            <input type="text" id="employee_number" name="employee_number" class="form-control"
                                   value="<?= sanitize($old['employee_number'] ?? '') ?>"
                                   placeholder="Auto-generated if left blank">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="department_id" class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select id="department_id" name="department_id"
                                    class="form-select <?= isset($errors['department_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= (int) $dept['id'] ?>"
                                        <?= ((int) ($old['department_id'] ?? 0)) === (int) $dept['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['department_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['department_id']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="designation_id" class="form-label fw-semibold">Designation <span class="text-danger">*</span></label>
                            <select id="designation_id" name="designation_id"
                                    class="form-select <?= isset($errors['designation_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select Designation --</option>
                                <?php foreach ($designations as $des): ?>
                                    <option value="<?= (int) $des['id'] ?>"
                                        <?= ((int) ($old['designation_id'] ?? 0)) === (int) $des['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($des['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['designation_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['designation_id']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="join_date" class="form-label fw-semibold">Date of Joining <span class="text-danger">*</span></label>
                            <input type="date" id="join_date" name="join_date"
                                   class="form-control <?= isset($errors['join_date']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['join_date'] ?? '') ?>" required>
                            <?php if (isset($errors['join_date'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['join_date']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select id="status" name="status" class="form-select">
                                <?php
                                $statuses  = ['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave', 'terminated' => 'Terminated'];
                                $curStatus = $old['status'] ?? 'active';
                                foreach ($statuses as $val => $label):
                                ?>
                                    <option value="<?= $val ?>" <?= $curStatus === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Employee</button>
                        <a href="/hr/employees" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
