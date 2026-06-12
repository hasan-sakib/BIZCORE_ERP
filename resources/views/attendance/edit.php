<?php
$pageTitle = 'Edit Attendance Record';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

$val = fn(string $key) => $old[$key] ?? $record[$key] ?? '';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-edit me-2"></i>Edit Attendance Record</h5>
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

                <form method="POST" action="/attendance/<?= (int) $record['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="mb-3">
                        <label for="employee_id" class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                        <select id="employee_id" name="employee_id"
                                class="form-select <?= isset($errors['employee_id']) ? 'is-invalid' : '' ?>" required>
                            <option value="">-- Select Employee --</option>
                            <?php
                            $selEmp = (int) $val('employee_id');
                            foreach ($employeeList as $emp):
                            ?>
                                <option value="<?= (int) $emp['id'] ?>" <?= $selEmp === (int) $emp['id'] ? 'selected' : '' ?>>
                                    <?= sanitize(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '') . ' (' . ($emp['employee_number'] ?? '') . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['employee_id'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['employee_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" id="date" name="date"
                               class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($val('date')) ?>" required>
                        <?php if (isset($errors['date'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="check_in" class="form-label fw-semibold">Check In</label>
                            <input type="time" id="check_in" name="check_in" class="form-control"
                                   value="<?= sanitize($val('check_in')) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="check_out" class="form-label fw-semibold">Check Out</label>
                            <input type="time" id="check_out" name="check_out" class="form-control"
                                   value="<?= sanitize($val('check_out')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select id="status" name="status" class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
                            <?php
                            $statuses  = [
                                'present'  => 'Present',
                                'absent'   => 'Absent',
                                'half_day' => 'Half Day',
                                'late'     => 'Late',
                                'holiday'  => 'Holiday',
                                'leave'    => 'Leave',
                            ];
                            $curStatus = $val('status');
                            foreach ($statuses as $sVal => $sLabel):
                            ?>
                                <option value="<?= $sVal ?>" <?= $curStatus === $sVal ? 'selected' : '' ?>><?= $sLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['status']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-semibold">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="2"><?= sanitize($val('notes')) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                        <a href="/attendance" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
