<?php
// Cabecera para salida JSON
header('Content-Type: application/json; charset=utf-8');

// Suprimir warnings y notices para salida JSON limpia
error_reporting(E_ERROR | E_PARSE);
require_once(__DIR__ . "/../src/ZabbixApi.php");

use IntelliTrend\Zabbix\ZabbixApi;
use IntelliTrend\Zabbix\ZabbixApiException;

/**
 * Script principal para obtener problemas de CAIDA DE HILO de Zabbix y convertirlos a JSON
 * Incluye STATUS, TIME y TIPO
 * Formato: HOST, GPON, DESCRIPCION, TIPO, STATUS, TIME
 */

$zabUrl    = 'http://10.80.80.175/zabbix';
$zabToken  = 'c656ccbf99abd980e6e04d495321be7a755d3626838e02bc82bcd6f5c66c7e69';
$groupName = 'OLT';   // Nombre de tu host group
$tagFilter = ['tag' => 'PON', 'value' => 'CAIDA DE HILO'];

/**
 * Extrae la información GPON del nombre del problema
 * Ejemplo: "CAIDA DE HILO GPON 0/11/5" -> "11/5"
 */
function extractGPONInfo($problemName) {
    if (preg_match('/GPON\s+(\d+)\/(\d+)\/(\d+)/', $problemName, $matches)) {
        return $matches[2] . '/' . $matches[3]; // Y/Z
    }
    return null;
}

/**
 * Limpia la descripción eliminando paréntesis y dos puntos iniciales
 * Ejemplo: "(:JIC2-MARIATEGUI11-48-ODF:12-HILO:33)" -> "JIC2-MARIATEGUI11-48-ODF:12-HILO:33"
 */
function cleanDescription($problemName) {
    // Buscar texto entre paréntesis con dos puntos al inicio
    if (preg_match('/\(:([^)]+)\)/', $problemName, $matches)) {
        return $matches[1];
    }
    
    // Si no hay paréntesis, buscar patrón después de GPON
    if (preg_match('/GPON\s+\d+\/\d+\/\d+\s+(.*)/', $problemName, $matches)) {
        $desc = trim($matches[1]);
        // Remover paréntesis si existen
        $desc = preg_replace('/^\(:?([^)]*)\)?$/', '$1', $desc);
        return $desc;
    }
    
    return '';
}

/**
 * Determina el tipo de problema basado en el nombre
 */
function getProblemType($problemName) {
    if (strpos($problemName, 'CAIDA DE HILO') !== false) {
        return 'CAIDA DE HILO';
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

    // 3) Recoger problemas activos y resueltos
    $allProblems = [];
    foreach ($hostMap as $hid => $hostName) {
        $probs = $zbx->call('problem.get', [
            'output'    => ['eventid','name','severity','clock','r_clock'],
            'hostids'   => [$hid],
            'tags'      => [$tagFilter],
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

    if (empty($allProblems)) {
        echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // 4) Ordenar por 'clock' descendente (más recientes primero)
    usort($allProblems, fn($a, $b) => $b['clock'] <=> $a['clock']);

    // 5) Transformar a formato JSON solicitado
    $jsonProblems = [];
    foreach ($allProblems as $p) {
        $hid = $p['hostid'];
        $hostName = $hostMap[$hid];
        $gponInfo = extractGPONInfo($p['name']);
        $description = cleanDescription($p['name']);
        $problemType = getProblemType($p['name']);
        
        // Calcular TIME con 7 horas menos que TIMESTAMP
        $timeAdjusted = $p['clock'] - (7 * 3600); // 7 horas = 7 * 3600 segundos
        
        if ($gponInfo !== null) {
            $jsonProblems[] = [
                'HOST' => $hostName,
                'GPON' => $gponInfo,
                'DESCRIPCION' => $description ?: '',
                'TIPO' => $problemType,
                'STATUS' => $p['status'],
                'TIME' => date('g:i:s A', $timeAdjusted)
            ];
        }
    }

    // 6) Mostrar resultado en JSON compacto
    echo json_encode($jsonProblems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (ZabbixApiException $e) {
    echo json_encode(['error' => "ZabbixApiException: {$e->getMessage()}"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['error' => "Exception: {$e->getMessage()}"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
