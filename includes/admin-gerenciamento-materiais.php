<?php
// Verifica se o arquivo está sendo acessado diretamente
if (!defined('ABSPATH')) {
    exit;
}

function gma_adicionar_menu_gerenciamento_materiais() {
    add_menu_page(
        __('Material Management', 'gma-plugin'),
        __('Manage Materials', 'gma-plugin'),
        'manage_options',
        'gma-gerenciar-materiais',
        'gma_exibir_pagina_gerenciamento_materiais',
        'dashicons-format-gallery',
        30
    );
}

function gma_exibir_pagina_gerenciamento_materiais() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        return;
    }

    // Processar exclusão de material, se solicitado
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $material_id = intval($_GET['id']);
        check_admin_referer('delete_material_' . $material_id);
        
        if (gma_excluir_material($material_id)) {
            add_settings_error('gma_messages', 'gma_message', __('Material deleted successfully.', 'gma-plugin'), 'updated');
        } else {
            add_settings_error('gma_messages', 'gma_message', __('Error deleting material.', 'gma-plugin'), 'error');
        }
    }

    // Exibir mensagens de erro/sucesso
    settings_errors('gma_messages');

    // Obter o filtro atual
    $filtro_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'todos';

    // Obter os materiais baseados no filtro
    $materiais = gma_obter_materiais_por_status($filtro_status);

    ?>
    <div class="wrap">
        <h1><?php _e('Material Management', 'gma-plugin'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gma-novo-material')); ?>" class="page-title-action"><?php _e('Add New Material', 'gma-plugin'); ?></a>

        <ul class="subsubsub">
            <li><a href="?page=gma-gerenciar-materiais&status=todos" <?php echo $filtro_status === 'todos' ? 'class="current"' : ''; ?>><?php _e('All', 'gma-plugin'); ?></a> |</li>
            <li><a href="?page=gma-gerenciar-materiais&status=pendente" <?php echo $filtro_status === 'pendente' ? 'class="current"' : ''; ?>><?php _e('Pending', 'gma-plugin'); ?></a> |</li>
            <li><a href="?page=gma-gerenciar-materiais&status=aprovado" <?php echo $filtro_status === 'aprovado' ? 'class="current"' : ''; ?>><?php _e('Approved', 'gma-plugin'); ?></a> |</li>
            <li><a href="?page=gma-gerenciar-materiais&status=reprovado" <?php echo $filtro_status === 'reprovado' ? 'class="current"' : ''; ?>><?php _e('Rejected', 'gma-plugin'); ?></a></li>
        </ul>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'gma-plugin'); ?></th>
                    <th><?php _e('Image', 'gma-plugin'); ?></th>
                    <th><?php _e('Copy', 'gma-plugin'); ?></th>
                    <th><?php _e('Status', 'gma-plugin'); ?></th>
                    <th><?php _e('Campaign', 'gma-plugin'); ?></th>
                    <th><?php _e('Created On', 'gma-plugin'); ?></th>
                    <th><?php _e('Actions', 'gma-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materiais as $material): ?>
                    <tr>
                        <td><?php echo esc_html($material->id); ?></td>
                        <td><img src="<?php echo esc_url($material->imagem_url); ?>" alt="Material" style="max-width: 100px; max-height: 100px;"></td>
                        <td><?php echo wp_kses_post($material->copy); ?></td>
                        <td><?php echo esc_html($material->status_aprovacao); ?></td>
                        <td><?php echo esc_html(gma_obter_nome_campanha($material->campanha_id)); ?></td>
                        <td>
    <?php
    if (isset($material->data_criacao) && $material->data_criacao) {
        echo esc_html(date_i18n(get_option('date_format'), strtotime($material->data_criacao)));
    } else {
        echo 'N/A';
    }
    ?>
</td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=gma-editar-material&id=' . $material->id)); ?>" class="button"><?php _e('Edit', 'gma-plugin'); ?></a>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gma-gerenciar-materiais&action=delete&id=' . $material->id), 'delete_material_' . $material->id)); ?>" class="button" onclick="return confirm('<?php _e('Are you sure you want to delete this material?', 'gma-plugin'); ?>');"><?php _e('Delete', 'gma-plugin'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Adiciona a ação para o menu
add_action('admin_menu', 'gma_adicionar_menu_gerenciamento_materiais');

// Função para excluir material (deve ser implementada em includes/materiais.php)
if (!function_exists('gma_excluir_material')) {
    function gma_excluir_material($material_id) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'gma_materiais';
        return $wpdb->delete($tabela, array('id' => $material_id), array('%d'));
    }
}

// Certifique-se de que estas funções estão definidas em seus respectivos arquivos
if (!function_exists('gma_obter_materiais_por_status')) {
    function gma_obter_materiais_por_status($status) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'gma_materiais';
        
        if ($status === 'todos') {
            return $wpdb->get_results("SELECT * FROM $tabela ORDER BY id DESC");
        } else {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabela WHERE status_aprovacao = %s ORDER BY id DESC", $status));
        }
    }
}

if (!function_exists('gma_obter_nome_campanha')) {
    function gma_obter_nome_campanha($campanha_id) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'gma_campanhas';
        $campanha = $wpdb->get_row($wpdb->prepare("SELECT nome FROM $tabela WHERE id = %d", $campanha_id));
        return $campanha ? $campanha->nome : 'N/A';
    }
}