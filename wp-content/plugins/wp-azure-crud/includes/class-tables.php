<?php
if (!defined('ABSPATH')) exit;

class AZ_Tables {
  public static function config() {
    return [
      'ClientChannels' => [
        'table' => 'dbo.ClientChannels',     // nombre completo de la tabla en Azure SQL
        'pk'    => 'Id',                      // clave primaria
        'fields'=> [                          // campos que vas a poder crear/editar
          'ClientId' => [
            'col'      => 'ClientId',
            'type'     => 'text',
            'required' => true,
            'max'      => 36                  // GUID => 36 caracteres
          ],
          'ChannelId' => [
            'col'      => 'ChannelId',
            'type'     => 'text',
            'required' => true,
            'max'      => 36
          ],
          'ChannelIdentifier' => [
            'col'      => 'ChannelIdentifier',
            'type'     => 'text',
            'required' => true,
            'max'      => 150
          ],
          'CurrencyCode' => [
            'col'      => 'CurrencyCode',
            'type'     => 'text',
            'required' => true,
            'max'      => 3
          ],
          'Price' => [
            'col'      => 'Price',
            'type'     => 'text',             // podrías hacer un input number si quieres
            'required' => true
          ],
        ],
        'list'  => [                          // columnas que aparecen en la lista
          'Id',
          'ClientId',
          'ChannelId',
          'ChannelIdentifier',
          'CurrencyCode',
          'Price',
          'Created'
        ],
        'order' => [                          // orden por defecto en listados
          'Created' => 'DESC'
        ],
        'search'=> [                          // campos que serán usados en búsqueda
          'ChannelIdentifier',
          'CurrencyCode'
        ],
      ],
    ];
  }
}
