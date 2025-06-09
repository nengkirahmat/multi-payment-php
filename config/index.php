<?php require_once '../api/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Payment Gateway Configs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="p-4">
  <div class="container">
    <h3 class="mb-4">Payment Gateway Configs</h3>

    <button class="btn btn-primary mb-3" id="btnAdd">Add Config</button>

    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Website</th>
          <th>Gateway</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php
        $stmt = $pdo->query("SELECT * FROM payment_gateways ORDER BY id DESC");
        foreach ($stmt as $row):
      ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= $row['website_id'] ?></td>
          <td><?= $row['payment_gateway_code'] ?></td>
          <td><?= $row['is_active'] ? 'Active' : 'Inactive' ?></td>
          <td>
            <button class="btn btn-sm btn-warning btnEdit" data-id="<?= $row['id'] ?>">Edit</button>
            <button class="btn btn-sm btn-danger btnDelete" data-id="<?= $row['id'] ?>">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog">
      <form id="configForm" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Config Form</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="id">
          <input type="hidden" name="action" id="action" value="create">
          <div class="mb-2">
            <label>Website ID</label>
            <input type="number" class="form-control" name="website_id" id="website_id" required>
          </div>
          <div class="mb-2">
            <label>Gateway Code</label>
            <input type="text" class="form-control" name="payment_gateway_code" id="payment_gateway_code" required>
          </div>
          <div class="mb-2">
            <label>Config (JSON)</label>
            <textarea class="form-control" name="config" id="config" required></textarea>
          </div>
          <div class="mb-2">
            <label>Status</label>
            <select class="form-control" name="is_active" id="is_active">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstrap Bundle (wajib sebelum pakai bootstrap.Modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
let modal = new bootstrap.Modal(document.getElementById('configModal'))

$('#btnAdd').click(() => {
  $('#configForm')[0].reset();
  $('#action').val('create');
  $('#id').val('');
  $('#payment_gateway_code').prop('readonly', false);
  modal.show();
});

$('.btnEdit').click(function () {
  let id = $(this).data('id');
  $.post('action.php', { action: 'get', id }, res => {
    $('#action').val('update');
    $('#id').val(res.id);
    $('#website_id').val(res.website_id);
    $('#payment_gateway_code').val(res.payment_gateway_code).prop('readonly', true);
    $('#config').val(res.config);
    $('#is_active').val(res.is_active);
    modal.show();
  }, 'json');
});

$('#configForm').submit(function (e) {
  e.preventDefault();
  let data = $(this).serialize();
  $.post('action.php', data, res => {
    if (res.success) {
      Swal.fire('Success', 'Data saved successfully', 'success').then(() => location.reload());
    } else {
      Swal.fire('Error', res.message || 'Failed to save data', 'error');
    }
  }, 'json');
});

$('.btnDelete').click(function () {
  let id = $(this).data('id');
  Swal.fire({
    title: 'Are you sure?',
    text: 'This will delete the config.',
    icon: 'warning',
    showCancelButton: true
  }).then(result => {
    if (result.isConfirmed) {
      $.post('action.php', { action: 'delete', id }, res => {
        if (res.success) {
          Swal.fire('Deleted!', 'Config removed', 'success').then(() => location.reload());
        } else {
          Swal.fire('Error', 'Delete failed', 'error');
        }
      }, 'json');
    }
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
