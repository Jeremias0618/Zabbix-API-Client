<?php
// Cabecera para salida JSON
header('Content-Type: application/json; charset=utf-8');

// Suprimir warnings y notices para salida JSON limpia
error_reporting(E_ERROR | E_PARSE);
require_once(__DIR__ . "/../src/ZabbixApi.php");

use IntelliTrend\Zabbix\ZabbixApi;
use IntelliTrend\Zabbix\ZabbixApiException;

/**
 * Script para obtener problemas específicos de Zabbix:
 * - EQUIPO ALARMADO
 * - PROBLEMAS DE POTENCIA
 * 
 * Tags filtrados:
 * - OLT: HUAWEI, ONU: DESCONEXIÓN, ONU: EQUIPO ALARMADO, ONU: ESTADO
 * - OLT: HUAWEI, ONU: POTENCIA TX, ONU: POTENCIA RX, ONU: PROBLEMAS DE POTENCIA
 * 
 * Formato: HOST, PON/LOG, DNI, TIPO, STATUS, TIME, DESCRIPCION
 */

$zabUrl    = 'http://10.80.80.175/zabbix';
$zabToken  = 'c656ccbf99abd980e6e04d495321be7a755d3626838e02bc82bcd6f5c66c7e69';
$groupName = 'OLT';   // Nombre de tu host group

// Definir múltiples filtros de tags para diferentes tipos de problemas
$tagFilters = [
    // Filtro para EQUIPO ALARMADO
    [
        ['tag' => 'OLT', 'value' => 'HUAWEI'],
        ['tag' => 'ONU', 'value' => 'DESCONEXIÓN'],
        ['tag' => 'ONU', 'value' => 'EQUIPO ALARMADO'],
        ['tag' => 'ONU', 'value' => 'ESTADO']
    ],
    // Filtro para PROBLEMAS DE POTENCIA
    [
        ['tag' => 'OLT', 'value' => 'HUAWEI'],
        ['tag' => 'ONU', 'value' => 'POTENCIA TX'],
        ['tag' => 'ONU', 'value' => 'POTENCIA RX'],
        ['tag' => 'ONU', 'value' => 'PROBLEMAS DE POTENCIA']
    ]
];

/**
 * Extrae la información PON/LOG del nombre del problema
 * Ejemplo: "EQUIPO ALARMADO (5/1/3) DNI (70122339_02)" -> "5/1/3"
 */
function extractPONLogInfo($problemName) {
    if (preg_match('/\((\d+\/\d+\/\d+)\)/', $problemName, $matches)) {
        return $matches[1]; // X/Y/Z
    }
    return null;
}

/**
 * Extrae el DNI del nombre del problema
 * Ejemplo: "EQUIPO ALARMADO (5/1/3) DNI (70122339_02)" -> "70122339_02"
 */
function extractDNI($problemName) {
    if (preg_match('/DNI\s+\(([^)]+)\)/', $problemName, $matches)) {
        return $matches[1]; // DNI completo
    }
    return null;
}

/**
 * Limpia la descripción del problema
 * Para EQUIPO ALARMADO: devuelve descripción completa
 * Para PROBLEMAS DE POTENCIA: extrae la distancia
 */
function cleanDescription($problemName) {
    // Para PROBLEMAS DE POTENCIA - extraer distancia
    if (preg_match('/PROBLEMAS DE POTENCIA\s+(\d+)\s+([\d.]+)\s*km/', $problemName, $matches)) {
        return 'DNI: ' . $matches[1] . ' - Distancia: ' . $matches[2] . ' km';
    }
    
    // Para otros casos, devolver el nombre completo limpio
    return trim($problemName);
}

/**
 * Determina el tipo de problema basado en el nombre
 */
function getProblemType($problemName) {
    if (strpos($problemName, 'EQUIPO ALARMADO') !== false) {
        return 'EQUIPO ALARMADO';
    } elseif (strpos($problemName, 'PROBLEMAS DE POTENCIA') !== false) {
        return 'PROBLEMAS DE POTENCIA';
    }
    return 'OTRO';
}

try {
    $zbx = new ZabbixApi();
    $zbx->loginToken($zabUrl, $zabToken);

    // 1) Obtener ID del grupo "OLT"
    $groups = $zbx->call('hostgroup.get', [
        'filter' => ['name' => [$groupName]],
        'output' => ['groupid']
    ]);
    if (empty($groups)) {
        echo json_encode(['error' => "No existe el grupo '{$groupName}'"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $groupid = $groups[0]['groupid'];

    // 2) Obtener hosts del grupo
    $hosts = $zbx->call('host.get', [
        'output'   => ['hostid','host','name'],
        'groupids' => [$groupid]
    ]);
    if (empty($hosts)) {
        echo json_encode(['error' => "El grupo '{$groupName}' (ID {$groupid}) no tiene hosts."], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    $hostMap = [];
    foreach ($hosts as $h) {
        $hostMap[$h['hostid']] = $h['host']; // Solo el nombre del host limpio
    }

    // 3) Recoger problemas activos y resueltos con múltiples filtros de tags
    $allProblems = [];
    
    foreach ($hostMap as $hid => $hostName) {
        // Procesar cada conjunto de filtros de tags
        foreach ($tagFilters as $tagFilterSet) {
            $probs = $zbx->call('problem.get', [
                'output'    => ['eventid','name','severity','clock','r_clock'],
                'hostids'   => [$hid],
                'tags'      => $tagFilterSet,
                'recent'    => true,  // Problemas activos y recientemente resueltos
                'selectTags'=> ['tag','value'],
            ]);
            
            foreach ($probs as $p) {
                $p['hostid'] = $hid;
                // Determinar el status basado en si tiene r_clock (tiempo de resolución)
                $p['status'] = !empty($p['r_clock']) ? 'RESOLVED' : 'PROBLEM';
                $allProblems[] = $p;
            }
        }
    }

    if (empty($allProblems)) {
        echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // 4) Eliminar duplicados basado en eventid
    $uniqueProblems = [];
    $seenEventIds = [];
    foreach ($allProblems as $p) {
        if (!in_array($p['eventid'], $seenEventIds)) {
            $uniqueProblems[] = $p;
            $seenEventIds[] = $p['eventid'];
        }
    }

    // 5) Ordenar por 'clock' descendente (más recientes primero)
    usort($uniqueProblems, fn($a, $b) => $b['clock'] <=> $a['clock']);

    // 6) Transformar a formato JSON solicitado
    $jsonProblems = [];
    foreach ($uniqueProblems as $p) {
        $hid = $p['hostid'];
        $hostName = $hostMap[$hid];
        $ponLogInfo = extractPONLogInfo($p['name']);
        $dniInfo = extractDNI($p['name']);
        $description = cleanDescription($p['name']);
        $problemType = getProblemType($p['name']);
        
        // Calcular TIME con 7 horas menos que TIMESTAMP
        $timeAdjusted = $p['clock'] - (7 * 3600); // 7 horas = 7 * 3600 segundos
        $recoveryTimeAdjusted = !empty($p['r_clock']) ? $p['r_clock'] - (7 * 3600) : null;
        
        $jsonProblems[] = [
            'HOST' => $hostName,
            'PON/LOG' => $ponLogInfo ?: 'N/A',
            'DNI' => $dniInfo ?: 'N/A',
            'TIPO' => $problemType,
            'STATUS' => $p['status'],
            'TIME' => date('g:i:s A', $timeAdjusted),
            'DESCRIPCION' => $description
        ];
    }

    // 7) Mostrar resultado en JSON compacto
    echo json_encode($jsonProblems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (ZabbixApiException $e) {
    echo json_encode(['error' => "ZabbixApiException: {$e->getMessage()}"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['error' => "Exception: {$e->getMessage()}"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
