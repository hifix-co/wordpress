<?php settings_errors('az_crud'); ?>
<div class="wrap">
  <h1 class="wp-heading-inline">ClientChannels</h1>
  <a href="<?php echo esc_url(admin_url('admin.php?page='.$_GET['page'].'-edit')); ?>" class="page-title-action">Añadir nuevo</a>
  
  <form method="get" class="search-form">
    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
    <p class="search-box">
      <label class="screen-reader-text">Buscar:</label>
      <input type="search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>">
      <input type="submit" class="button" value="Buscar">
    </p>
  </form>

  <table class="widefat striped">
    <thead><tr>
      <?php foreach (($cfg['list'] ?? []) as $c): ?>
        <th><?php echo esc_html($c); ?></th>
      <?php endforeach; ?>
      <th>Acciones</th>
    </tr></thead>
    <tbody>
      <?php foreach ($data as $r): ?>
        <tr>
          <?php foreach (($cfg['list'] ?? []) as $c): ?>
            <td><?php echo esc_html($r[$c] ?? ''); ?></td>
          <?php endforeach; ?>
          <td>
            <a href="<?php echo admin_url('admin.php?page='.$_GET['page'].'-edit&id='.urlencode($r[$cfg['pk']])); ?>">Editar</a> |
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=az_delete&table='.urlencode($cfg['table']).'&pk='.$cfg['pk'].'&id='.urlencode($r[$cfg['pk']])), 'az_delete', 'az_nonce'); ?>" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
