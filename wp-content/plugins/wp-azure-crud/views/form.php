<?php settings_errors('az_crud'); ?>
<div class="wrap">
  <h1><?php echo $row ? 'Editar' : 'Nuevo'; ?> ClientChannel</h1>
  <form method="post">
    <?php wp_nonce_field('az_save_'.$slug,'az_nonce'); ?>
    <?php if ($row): ?>
      <input type="hidden" name="<?php echo esc_attr($cfg['pk']); ?>" value="<?php echo esc_attr($row[$cfg['pk']]); ?>">
    <?php endif; ?>

    <table class="form-table">
      <?php foreach ($cfg['fields'] as $label=>$meta): 
          $name = $meta['col'];
          $val  = $row[$name] ?? '';
      ?>
        <tr>
          <th><label><?php echo esc_html($label); ?></label></th>
          <td>
            <?php if (($meta['type'] ?? 'text')==='select'): ?>
              <select name="<?php echo esc_attr($name); ?>">
                <?php foreach (($meta['options'] ?? []) as $opt): ?>
                  <option value="<?php echo esc_attr($opt); ?>" <?php selected($val==$opt); ?>><?php echo esc_html($opt); ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif (($meta['type'] ?? 'text')==='number'): ?>
              <input type="number" step="0.01" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($val); ?>">
            <?php else: ?>
              <input type="text" class="regular-text" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($val); ?>">
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>

    <?php submit_button($row ? 'Guardar cambios' : 'Crear'); ?>
  </form>
</div>
