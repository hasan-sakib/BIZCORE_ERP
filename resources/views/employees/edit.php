<?php
$pageTitle = 'Edit Employee';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

$val = fn(string $key) => $old[$key] ?? $employee[$key] ?? '';
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user-edit me-2"></i>Edit Employee</h5>
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

                <form method="POST" action="/hr/employees/<?= (int) $employee['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <!-- Personal Information -->
                    <h6 class="text-muted text-uppercase small fw-bold mb-3 mt-2">Personal Information</h6>
                    <div class="row g-3 mb-4">

                        <div class="col-12 col-md-6">
                            <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" id="first_name" name="first_name"
                                   class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($val('first_name')) ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['first_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" id="last_name" name="last_name"
                                   class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($val('last_name')) ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['last_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($val('email')) ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control"
                                   value="<?= sanitize($val('phone')) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="date_of_birth" class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                                   value="<?= sanitize($val('date_of_birth')) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="gender" class="form-label fw-semibold">Gender</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="">-- Select --</option>
                                <?php
                                $genders = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
                                $curGender = $val('gender');
                                foreach ($genders as $gVal => $gLabel):
                                ?>
                                    <option value="<?= $gVal ?>" <?= $curGender === $gVal ? 'selected' : '' ?>><?= $gLabel ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="2"><?= sanitize($val('address')) ?></textarea>
                        </div>

                    </div>

                    <hr>

                    <!-- Employment Details -->
                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Employment Details</h6>
                    <div class="row g-3 mb-4">

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Employee Number</label>
                            <input type="text" class="form-control bg-light" value="<?= sanitize($employee['employee_number'] ?? '') ?>" readonly>
                            <div class="form-text">Employee number cannot be changed.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="department_id" class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select id="department_id" name="department_id"
                                    class="form-select <?= isset($errors['department_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Select Department --</option>
                                <?php
                                $selDept = (int) $val('department_id');
                                foreach ($departments as $dept):
                                ?>
                                    <option value="<?= (int) $dept['id'] ?>" <?= $selDept === (int) $dept['id'] ? 'selected' : '' ?>>
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
                                <?php
                                $selDes = (int) $val('designation_id');
                                foreach ($designations as $des):
                                ?>
                                    <option value="<?= (int) $des['id'] ?>" <?= $selDes === (int) $des['id'] ? 'selected' : '' ?>>
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
                                   value="<?= sanitize($val('join_date')) ?>" required>
                            <?php if (isset($errors['join_date'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['join_date']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select id="status" name="status" class="form-select">
                                <?php
                                $statuses  = ['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave', 'terminated' => 'Terminated'];
                                $curStatus = $val('status');
                                foreach ($statuses as $sVal => $sLabel):
                                ?>
                                    <option value="<?= $sVal ?>" <?= $curStatus === $sVal ? 'selected' : '' ?>><?= $sLabel ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                        <a href="/hr/employees/<?= (int) $employee['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
