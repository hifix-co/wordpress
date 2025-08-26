<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HPT_Base_CRUD {

    protected $table;        // Tabla en la BD
    protected $primary_key;  // Clave primaria
    protected $fields;       // Campos que se pueden guardar
    protected $wpdb;         // Acceso a WordPress DB

    public function __construct( $table, $primary_key, $fields ) {
        global $wpdb;
        $this->wpdb        = $wpdb;
        $this->table       = $table;
        $this->primary_key = $primary_key;
        $this->fields      = $fields;
    }

    /**
     * Obtener todos los registros
     */
    public function get_all( $limit = 50, $offset = 0 ) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} ORDER BY {$this->primary_key} DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        );
        return $this->wpdb->get_results( $sql );
    }

    /**
     * Obtener un registro por ID
     */
    public function get_by_id( $id ) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE {$this->primary_key} = %s",
                $id
            )
        );
    }

    /**
     * Guardar (insertar o actualizar)
     */
    public function save( $data ) {
        $filtered = [];
        foreach ( $this->fields as $field ) {
            if ( isset( $data[$field] ) ) {
                $filtered[$field] = sanitize_text_field( $data[$field] );
            }
        }

        if ( isset( $data[$this->primary_key] ) && !empty( $data[$this->primary_key] ) ) {
            // UPDATE
            return $this->wpdb->update(
                $this->table,
                $filtered,
                [ $this->primary_key => $data[$this->primary_key] ]
            );
        } else {
            // INSERT
            return $this->wpdb->insert(
                $this->table,
                $filtered
            );
        }
    }

    /**
     * Eliminar registro por ID
     */
    public function delete( $id ) {
        return $this->wpdb->delete(
            $this->table,
            [ $this->primary_key => $id ]
        );
    }
}
