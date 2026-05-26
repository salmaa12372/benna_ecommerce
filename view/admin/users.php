<?php
$pageTitle = "Utilisateurs";
include "partials/admin_header.php";

$roleFilter = $_GET['role'] ?? '';
$search     = $_GET['search'] ?? '';

// Récupération des utilisateurs (tous rôles sauf admin)
$usersRaw = $search ? searchUsers($cnx, $search) : getAllUsers($cnx, $roleFilter ?: null);
// Exclure les administrateurs
$users = array_filter($usersRaw, function($u) {
    return $u['role'] !== 'admin';
});

// Liste des rôles autorisés (sans admin)
$roles = ['client', 'nutritionniste', 'usine', 'livreur'];
$roleLabels = [
    'client'         => 'Client',
    'nutritionniste' => 'Nutritionniste',
    'usine'          => 'Usine',
    'livreur'        => 'Livreur'
];
$roleColors = [
    'client'         => 'badge-secondary',
    'nutritionniste' => 'badge-success',
    'usine'          => 'badge-info',
    'livreur'        => 'badge-info'
];
?>

<div class="topbar">
  <h1><i class="fas fa-users"></i> Gestion des Utilisateurs</h1>
  <button class="btn btn-green" onclick="document.getElementById('addUserModal').style.display='flex'">
    <i class="fas fa-plus"></i> Ajouter
  </button>
</div>

<!-- Filtres (sans admin) -->
<div class="card filter-card">
  <form method="GET" class="filter-form">
    <input class="form-control filter-input" name="search" placeholder="Nom ou email..." value="<?= htmlspecialchars($search) ?>"/>
    <select class="form-control filter-select" name="role">
      <option value="">Tous les rôles</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= $r ?>" <?= $r === $roleFilter ? 'selected' : '' ?>><?= $roleLabels[$r] ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-green"><i class="fas fa-search"></i> Filtrer</button>
    <a href="<?= BASE ?>/view/admin/users.php" class="btn btn-gray"><i class="fas fa-undo"></i> Reset</a>
  </form>
</div>

<!-- Tableau utilisateurs -->
<div class="card">
  <div class="card-title">
    <i class="fas fa-list"></i> Utilisateurs
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Téléphone</th>
          <th>Inscrit le</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr class="user-row">
        <td><?= $u['id'] ?></td>
        <td><strong><?= htmlspecialchars($u['nom']) ?></strong></td>
        <td class="user-email"><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <span class="badge <?= $roleColors[$u['role']] ?? 'badge-secondary' ?>">
            <?= $roleLabels[$u['role']] ?? $u['role'] ?>
          </span>
        </td>
        <td class="user-phone"><?= htmlspecialchars($u['telephone'] ?? '–') ?></td>
        <td class="user-date"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
        <td>
          <span class="badge <?= $u['actif'] ? 'badge-success' : 'badge-danger' ?>">
            <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
          </span>
        </td>
        <td class="user-actions">
          <button class="btn-edit-user" onclick="openEditUser(<?= htmlspecialchars(json_encode($u)) ?>)">
            <i class="fas fa-edit"></i> Modifier
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (empty($users)): ?>
    <div class="empty-state">
      <i class="fas fa-inbox"></i>
      Aucun utilisateur trouvé.
    </div>
  <?php endif; ?>
</div>

<!-- MODAL AJOUTER (sans admin) -->
<div id="addUserModal" class="user-modal">
  <div class="user-modal-content">
    <div class="user-modal-header">
      <h2><i class="fas fa-user-plus"></i> Nouvel utilisateur</h2>
      <button class="user-modal-close" onclick="document.getElementById('addUserModal').style.display='none'">&times;</button>
    </div>
    <form action="<?= BASE ?>/controller/auth_controller.php?action=register_admin" method="POST">
      <div class="form-group">
        <label>Nom complet</label>
        <input class="form-control" name="user_name" required/>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input class="form-control" name="email" type="email" required/>
      </div>
      <div class="form-group">
        <label>Téléphone</label>
        <input class="form-control" name="telephone"/>
      </div>
      <div class="form-group">
        <label>Rôle</label>
        <select class="form-control" name="role">
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r ?>"><?= $roleLabels[$r] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input class="form-control" name="password" type="password" required/>
      </div>
      <div class="user-modal-actions">
        <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Créer</button>
        <button type="button" onclick="document.getElementById('addUserModal').style.display='none'" class="btn btn-gray"><i class="fas fa-times"></i> Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL MODIFIER (sans admin) -->
<div id="editUserModal" class="user-modal">
  <div class="user-modal-content">
    <div class="user-modal-header">
      <h2><i class="fas fa-edit"></i> Modifier l'utilisateur</h2>
      <button class="user-modal-close" onclick="document.getElementById('editUserModal').style.display='none'">&times;</button>
    </div>
    <form action="<?= BASE ?>/controller/auth_controller.php?action=update_user" method="POST">
      <input type="hidden" name="idu" id="eu_id"/>
      <div class="form-group">
        <label>Nom</label>
        <input class="form-control" name="user_name" id="eu_nom" required/>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input class="form-control" name="email" id="eu_email" type="email" required/>
      </div>
      <div class="form-group">
        <label>Téléphone</label>
        <input class="form-control" name="telephone" id="eu_tel"/>
      </div>
      <div class="form-group">
        <label>Rôle</label>
        <select class="form-control" name="role" id="eu_role">
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r ?>"><?= $roleLabels[$r] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Nouveau mot de passe <small>(vide = inchangé)</small></label>
        <input class="form-control" name="password" type="password"/>
      </div>
      <div class="user-modal-actions">
        <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Enregistrer</button>
        <button type="button" onclick="document.getElementById('editUserModal').style.display='none'" class="btn btn-gray"><i class="fas fa-times"></i> Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditUser(u){
  document.getElementById('eu_id').value    = u.id;
  document.getElementById('eu_nom').value   = u.nom;
  document.getElementById('eu_email').value = u.email;
  document.getElementById('eu_tel').value   = u.telephone || '';
  document.getElementById('eu_role').value  = u.role;
  document.getElementById('editUserModal').style.display = 'flex';
}
</script>

<?php include "partials/admin_footer.php"; ?>