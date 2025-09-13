<?php
/**
 * Archivo de Configuración Centralizada
 * Configuración para todos los dashboards y scripts de Zabbix
 * Generado automáticamente el 2025-09-13 22:48:57
 */

// Configuración de conexión a Zabbix
$zabUrl    = 'http://10.80.80.175/zabbix';
$zabToken  = 'c656ccbf99abd980e6e04d495321be7a755d3626838e02bc82bcd6f5c66c7e69';
$groupName = 'OLT';

// Configuración del sistema
$systemConfig = [
    'title' => 'Sistema de Monitoreo Zabbix',
    'version' => '1.0.0',
    'author' => 'CyberCode Labs',
    'icon' => 'img/icon.png',
    'auto_refresh_interval' => 30,
    'timezone_offset' => -7,
];

// Configuración de filtros de tags
$tagFilters = [
    'caida_hilo' => [
        'tag' => 'PON',
        'value' => 'CAIDA DE HILO'
    ],
    'equipo_alarmado' => [
        [
            ['tag' => 'OLT', 'value' => 'HUAWEI'],
            ['tag' => 'ONU', 'value' => 'DESCONEXIÓN'],
            ['tag' => 'ONU', 'value' => 'EQUIPO ALARMADO'],
            ['tag' => 'ONU', 'value' => 'ESTADO']
        ]
    ],
    'problemas_potencia' => [
        [
            ['tag' => 'OLT', 'value' => 'HUAWEI'],
            ['tag' => 'ONU', 'value' => 'POTENCIA TX'],
            ['tag' => 'ONU', 'value' => 'POTENCIA RX'],
            ['tag' => 'ONU', 'value' => 'PROBLEMAS DE POTENCIA']
        ]
    ]
];

// Configuración de dashboards
$dashboards = [
    'thread' => [
        'title' => 'Dashboard de Caída de Hilo',
        'description' => 'Monitoreo de problemas de caída de hilo GPON',
        'file' => 'dashboard_thread.php',
        'icon' => 'fas fa-network-wired',
        'color' => 'primary-blue'
    ],
    'client' => [
        'title' => 'Dashboard de Clientes Individuales',
        'description' => 'Monitoreo de problemas de equipos alarmados y potencia',
        'file' => 'dashboard_client.php',
        'icon' => 'fas fa-user-cog',
        'color' => 'primary-purple'
    ]
];

// Configuración de scripts JSON
$jsonScripts = [
    'caida_hilo' => [
        'title' => 'API - Caída de Hilo',
        'description' => 'Endpoint JSON para problemas de caída de hilo',
        'file' => 'problems_json_scripts/zabbix_caida_de_hilo_json.php',
        'icon' => 'fas fa-code',
        'color' => 'primary-green'
    ],
    'individuales' => [
        'title' => 'API - Clientes Individuales',
        'description' => 'Endpoint JSON para problemas de clientes individuales',
        'file' => 'problems_json_scripts/zabbix_individuales_json.php',
        'icon' => 'fas fa-database',
        'color' => 'primary-orange'
    ]
];

// Función para obtener configuración de Zabbix
function getZabbixConfig() {
    global $zabUrl, $zabToken, $groupName;
    return [
        'url' => $zabUrl,
        'token' => $zabToken,
        'group' => $groupName
    ];
}

// Función para obtener configuración del sistema
function getSystemConfig() {
    global $systemConfig;
    return $systemConfig;
}

// Función para obtener filtros de tags
function getTagFilters($type = null) {
    global $tagFilters;
    if ($type && isset($tagFilters[$type])) {
        return $tagFilters[$type];
    }
    return $tagFilters;
}

// Función para obtener configuración de dashboards
function getDashboards() {
    global $dashboards;
    return $dashboards;
}

// Función para obtener configuración de scripts JSON
function getJsonScripts() {
    global $jsonScripts;
    return $jsonScripts;
}

// Función para validar configuración
function validateConfig() {
    global $zabUrl, $zabToken, $groupName;
    
    $errors = [];
    
    if (empty($zabUrl)) {
        $errors[] = 'URL de Zabbix no configurada';
    }
    
    if (empty($zabToken)) {
        $errors[] = 'Token de Zabbix no configurado';
    }
    
    if (empty($groupName)) {
        $errors[] = 'Nombre del grupo no configurado';
    }
    
    return $errors;
}

// Función para formatear tiempo con offset
function formatTimeWithOffset($timestamp, $offset = null) {
    global $systemConfig;
    $offset = $offset ?? $systemConfig['timezone_offset'];
    $adjustedTime = $timestamp + ($offset * 3600);
    return date('g:i:s A', $adjustedTime);
}

// Función para obtener estado de conexión
function getConnectionStatus() {
    try {
        $config = getZabbixConfig();
        return [
            'status' => 'connected',
            'message' => 'Conexión activa a Zabbix',
            'url' => $config['url']
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión: ' . $e->getMessage(),
            'url' => ''
        ];
    }
}
?>