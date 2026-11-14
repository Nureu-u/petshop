<?php
/* ==============================================================================
   VIEW: DANH SACH NGUOI DUNG
   File    : views/admin/users/index.php
   Dia chi  : ?r=admin/users
   Controller: controllers/AdminUserController.php  (ham index)
   Model   : models/AdminUser.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [11] TAB LOC NHANH; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG; [14] CHI
           TIET DON HANG VA CAC KHOI PHU.
   Cac khoi: 3 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $result @var array $flt */
$meId = Auth::id();
$fmt = fn($d) => date('d/m/Y', strtotime((string) $d));
$stClass = ['active' => 'gr', 'blocked' => 'rd', 'deleted' => 'gy'];
?>
<?php /* ----- KHOI 1: Tieu de trang va nut hanh dong ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.users')) ?></h1>
        <div class="sub"><?= e(t('user.sub')) ?></div>
    </div>
</div>

<?php /* ----- KHOI 2: Thanh bo loc va tim kiem ----- */ ?>
<form class="c" method="get" action="<?= e(config('base_url')) ?>/index.php">
    <input type="hidden" name="r" value="admin/users">
    <div class="fl">
        <input class="grow" type="search" name="q" value="<?= e($flt['q']) ?>" placeholder="<?= e(t('user.search_ph')) ?>" aria-label="<?= e(t('user.search_ph')) ?>">
        <select name="role" aria-label="<?= e(t('user.col.role')) ?>">
            <option value=""><?= e(t('user.role.all')) ?></option>
            <?php foreach (AdminUser::ROLES as $r): ?>
                <option value="<?= e($r) ?>"<?= sel($flt['role'], $r) ?>><?= e(t('user.role.' . $r)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="<?= e(t('user.col.status')) ?>">
            <option value=""><?= e(t('user.status.all')) ?></option>
            <?php foreach (AdminUser::STATUSES as $s): ?>
                <option value="<?= e($s) ?>"<?= sel($flt['status'], $s) ?>><?= e(t('user.status.' . $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="b1" type="submit"><?= e(t('order.filter')) ?></button>
        <a class="b2" href="<?= e(url('admin/users')) ?>"><?= e(t('order.clear')) ?></a>
    </div>
</form>

<?php /* ----- KHOI 3: Bang danh sach ----- */ ?>
<div class="c">
    <table class="tb">
        <thead>
        <tr><th class="no"><?= e(t('common.no')) ?></th>
            <th><?= e(t('user.col.name')) ?></th><th><?= e(t('user.col.phone')) ?></th><th><?= e(t('user.col.role')) ?></th>
            <th><?= e(t('user.col.status')) ?></th><th><?= e(t('user.col.created')) ?></th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result['items'] as $i => $u): ?>
            <tr><td class="no" data-label="<?= e(t('common.no')) ?>"><?= row_no($result, $i) ?></td>
                <td data-label="<?= e(t('user.col.name')) ?>">
                    <b><?= e($u['full_name']) ?></b>
                    <?php if ($u['id'] === $meId): ?> <span class="p bl"><?= e(t('user.you')) ?></span><?php endif; ?>
                    <br><span class="hint"><?= e($u['email']) ?></span>
                </td>
                <td data-label="<?= e(t('user.col.phone')) ?>"><?= e($u['phone'] ?: '—') ?></td>
                <td data-label="<?= e(t('user.col.role')) ?>"><span class="p <?= $u['role'] === 'admin' ? 'bl' : 'gy' ?>"><?= e(t('user.role.' . $u['role'])) ?></span></td>
                <td data-label="<?= e(t('user.col.status')) ?>"><span class="p <?= e($stClass[$u['status']] ?? 'gy') ?>"><?= e(t('user.status.' . $u['status'])) ?></span></td>
                <td data-label="<?= e(t('user.col.created')) ?>"><?= e($fmt($u['created_at'])) ?></td>
                <td data-label="">
                    <div class="rr">
                        <a class="lk" href="<?= e(url('admin/users/show', ['id' => $u['id']])) ?>"><?= e(t('user.view')) ?></a>
                        <?php if ($u['id'] !== $meId && $u['status'] !== 'deleted'): ?>
                            <form method="post" action="<?= e(url('admin/users/status')) ?>" style="margin:0"
                                  <?= $u['status'] === 'active' ? 'data-confirm="' . e(t('user.lock_confirm')) . '"' : '' ?>>
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'blocked' : 'active' ?>">
                                <button class="<?= $u['status'] === 'active' ? 'b3' : 'b2' ?> sm" type="submit"><?= e(t($u['status'] === 'active' ? 'user.lock' : 'user.unlock')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$result['items']): ?><tr><td colspan="7" class="hint"><?= e(t('user.empty')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php require BASE_PATH . '/views/admin/layout/pagination.php'; ?>
</div>
<div class="note"><?= e(t('user.note')) ?></div>
