<?php
require_once "Class_users.php";
require_once "pagination.php";

$DB = new Database();
$conn = $DB->getConnection();
$users = new Users($conn);

$records_per_page = 20;
// Retrieve pagination data
$pagination = getPaginationData($users, $records_per_page);
$userList = $pagination['dataList'];
?>

<table class="table table-striped table-bordered">
    <thead class="table-dark">
        <tr>
            <?php foreach ($userList[0] as $field => $value): ?>
                <th><?= ucfirst(preg_replace('/([a-z])([A-Z])/','$1 $2', str_replace("_", " ", $field))) ?></th>
            <?php endforeach; ?>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($userList as $user): ?>
            <tr id="user-<?= $user['user_id'] ?>">
                <?php foreach ($user as $key => $value): ?>
                    <td><?= htmlspecialchars($value) ?></td>
                <?php endforeach; ?>
                <td>
                    <button type="button" class="btn btn-sm btn-warning" onclick="editRecord(event, 'user', <?= $user['user_id'] ?>)">
                        <i class="bi bi-pen"></i><br> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['user_id'] ?>)">
                        <i class="bi bi-trash3"></i><br> Delete
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
displayPagination($pagination);
?>