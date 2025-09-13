<?php
// Dashboard Profesional de Monitoreo de Clientes Individuales - Versión 4
header('Content-Type: text/html; charset=utf-8');

// Incluir la clase ZabbixApi
require_once(__DIR__ . "/src/ZabbixApi.php");

use IntelliTrend\Zabbix\ZabbixApi;
use IntelliTrend\Zabbix\ZabbixApiException;

// Función para obtener datos directamente de la API de Zabbix
function getZabbixData() {
    // Configuración de conexión a Zabbix
    $zabUrl    = 'http://10.80.80.175/zabbix';
    $zabToken  = 'c656ccbf99abd980e6e04d495321be7a755d3626838e02bc82bcd6f5c66c7e69';
    $groupName = 'OLT';
    
    try {
        $zbx = new ZabbixApi();
        $zbx->loginToken($zabUrl, $zabToken);

        // 1) Obtener ID del grupo "OLT"
        $groups = $zbx->call('hostgroup.get', [
            'filter' => ['name' => [$groupName]],
            'output' => ['groupid']
        ]);
        if (empty($groups)) {
            return [];
        }
        $groupid = $groups[0]['groupid'];

        // 2) Obtener hosts del grupo
        $hosts = $zbx->call('host.get', [
            'output'   => ['hostid','host','name'],
            'groupids' => [$groupid]
        ]);
        if (empty($hosts)) {
            return [];
        }
        
        $hostMap = [];
        foreach ($hosts as $h) {
            $hostMap[$h['hostid']] = $h['host'];
        }

        // 3) Definir filtros de tags para diferentes tipos de problemas
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

        // 4) Recoger problemas activos y resueltos con múltiples filtros de tags
        $allProblems = [];
        
        foreach ($hostMap as $hid => $hostName) {
            foreach ($tagFilters as $tagFilterSet) {
                $probs = $zbx->call('problem.get', [
                    'output'    => ['eventid','name','severity','clock','r_clock'],
                    'hostids'   => [$hid],
                    'tags'      => $tagFilterSet,
                    'recent'    => true,
                    'selectTags'=> ['tag','value'],
                ]);
                
                foreach ($probs as $p) {
                    $p['hostid'] = $hid;
                    $p['status'] = !empty($p['r_clock']) ? 'RESOLVED' : 'PROBLEM';
                    $allProblems[] = $p;
                }
            }
        }

        if (empty($allProblems)) {
            return [];
        }

        // 5) Eliminar duplicados basado en eventid
        $uniqueProblems = [];
        $seenEventIds = [];
        foreach ($allProblems as $p) {
            if (!in_array($p['eventid'], $seenEventIds)) {
                $uniqueProblems[] = $p;
                $seenEventIds[] = $p['eventid'];
            }
        }

        // 6) Ordenar por 'clock' descendente (más recientes primero)
        usort($uniqueProblems, fn($a, $b) => $b['clock'] <=> $a['clock']);

        // 7) Transformar a formato JSON solicitado
        $jsonProblems = [];
        foreach ($uniqueProblems as $p) {
            $hid = $p['hostid'];
            $hostName = $hostMap[$hid];
            
            // Extraer PON/LOG
            $ponLogInfo = null;
            if (preg_match('/\((\d+\/\d+\/\d+)\)/', $p['name'], $matches)) {
                $ponLogInfo = $matches[1];
            }
            
            // Extraer DNI
            $dniInfo = null;
            if (preg_match('/DNI\s+\(([^)]+)\)/', $p['name'], $matches)) {
                $dniInfo = $matches[1];
            }
            
            // Limpiar descripción
            $description = $p['name'];
            if (preg_match('/PROBLEMAS DE POTENCIA\s+(\d+)\s+([\d.]+)\s*km/', $p['name'], $matches)) {
                $description = 'DNI: ' . $matches[1] . ' - Distancia: ' . $matches[2] . ' km';
            }
            
            // Determinar tipo de problema
            $problemType = 'OTRO';
            if (strpos($p['name'], 'EQUIPO ALARMADO') !== false) {
                $problemType = 'EQUIPO ALARMADO';
            } elseif (strpos($p['name'], 'PROBLEMAS DE POTENCIA') !== false) {
                $problemType = 'PROBLEMAS DE POTENCIA';
            }
            
            // Calcular TIME con 7 horas menos
            $timeAdjusted = $p['clock'] - (7 * 3600);
            
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

        return $jsonProblems;

    } catch (Exception $e) {
        error_log("Error Zabbix: " . $e->getMessage());
        return [];
    }
}

// Obtener datos reales
$raw_data = getZabbixData();

// Si no hay datos, intentar método alternativo (reintentar conexión)
if (empty($raw_data)) {
    // Intentar una segunda vez con timeout más largo
    sleep(2);
    $raw_data = getZabbixData();
}

// Si aún no hay datos después de los reintentos, mostrar mensaje de error
if (empty($raw_data)) {
    $raw_data = [];
}

// Procesar datos para el dashboard
$dashboard_data = [
    'raw_data' => $raw_data,
    'kpis' => [
        'total_incidents' => count($raw_data),
        'active_incidents' => count(array_filter($raw_data, function($item) { return $item['STATUS'] === 'PROBLEM'; })),
        'resolved_today' => count(array_filter($raw_data, function($item) { return $item['STATUS'] === 'RESOLVED'; })),
        'unique_hosts' => count(array_unique(array_column($raw_data, 'HOST'))),
        'unique_dnis' => count(array_unique(array_column($raw_data, 'DNI'))),
        'equipo_alarmado_count' => count(array_filter($raw_data, function($item) { return $item['TIPO'] === 'EQUIPO ALARMADO'; })),
        'potencia_count' => count(array_filter($raw_data, function($item) { return $item['TIPO'] === 'PROBLEMAS DE POTENCIA'; }))
    ],
    'hosts_data' => [],
    'time_distribution' => [],
    'tipo_distribution' => [],
    'dni_distribution' => []
];

// Procesar datos por host
$hosts_stats = [];
foreach ($raw_data as $item) {
    $host = $item['HOST'];
    if (!isset($hosts_stats[$host])) {
        $hosts_stats[$host] = [
            'host' => $host,
            'total' => 0,
            'active' => 0,
            'resolved' => 0,
            'equipo_alarmado' => 0,
            'potencia' => 0
        ];
    }
    
    $hosts_stats[$host]['total']++;
    if ($item['STATUS'] === 'PROBLEM') {
        $hosts_stats[$host]['active']++;
    } else {
        $hosts_stats[$host]['resolved']++;
    }
    
    if ($item['TIPO'] === 'EQUIPO ALARMADO') {
        $hosts_stats[$host]['equipo_alarmado']++;
    } elseif ($item['TIPO'] === 'PROBLEMAS DE POTENCIA') {
        $hosts_stats[$host]['potencia']++;
    }
}

$dashboard_data['hosts_data'] = array_values($hosts_stats);

// Procesar distribución por hora
$hour_distribution = [];
foreach ($raw_data as $item) {
    $time = $item['TIME'];
    if (preg_match('/(\d+):/', $time, $matches)) {
        $hour = intval($matches[1]);
        $period = ($hour >= 6 && $hour < 12) ? 'Mañana' : 
                 (($hour >= 12 && $hour < 18) ? 'Tarde' : 
                 (($hour >= 18 && $hour < 24) ? 'Noche' : 'Madrugada'));
        
        if (!isset($hour_distribution[$period])) {
            $hour_distribution[$period] = 0;
        }
        $hour_distribution[$period]++;
    }
}

$dashboard_data['time_distribution'] = $hour_distribution;

// Procesar distribución por tipo
$tipo_stats = [];
foreach ($raw_data as $item) {
    $tipo = $item['TIPO'];
    if (!isset($tipo_stats[$tipo])) {
        $tipo_stats[$tipo] = 0;
    }
    $tipo_stats[$tipo]++;
}

$dashboard_data['tipo_distribution'] = $tipo_stats;

// Procesar distribución por DNI (top 10 más problemáticos)
$dni_stats = [];
foreach ($raw_data as $item) {
    $dni = $item['DNI'];
    if (!isset($dni_stats[$dni])) {
        $dni_stats[$dni] = [
            'dni' => $dni,
            'count' => 0,
            'host' => $item['HOST'],
            'pon_log' => $item['PON/LOG'],
            'tipo' => $item['TIPO']
        ];
    }
    $dni_stats[$dni]['count']++;
}

// Ordenar por frecuencia y tomar los top 10
uasort($dni_stats, function($a, $b) {
    return $b['count'] <=> $a['count'];
});
$dashboard_data['dni_distribution'] = array_slice($dni_stats, 0, 10, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Monitoreo de Clientes Individuales</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-purple: #7c3aed;
            --primary-red: #dc2626;
            --primary-green: #059669;
            --primary-orange: #ea580c;
            --primary-cyan: #0891b2;
            --primary-yellow: #eab308;
            --bg-white: #ffffff;
            --bg-gray-50: #f9fafb;
            --bg-gray-100: #f3f4f6;
            --text-gray-900: #111827;
            --text-gray-700: #374151;
            --text-gray-500: #6b7280;
            --border-gray: #e5e7eb;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gray-50);
            color: var(--text-gray-900);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: white;
            padding: 24px 32px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-cyan));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-gray-900);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .time-selector {
            background: var(--bg-gray-100);
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-gray-700);
        }

        .time-selector select {
            background: none;
            border: none;
            font-weight: 600;
            color: var(--primary-blue);
            cursor: pointer;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
            border-left: 4px solid;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .kpi-card.revenue { border-left-color: var(--primary-blue); }
        .kpi-card.profit { border-left-color: var(--primary-purple); }
        .kpi-card.orders { border-left-color: var(--primary-red); }
        .kpi-card.customers { border-left-color: var(--primary-cyan); }
        .kpi-card.quantity { border-left-color: var(--primary-orange); }
        .kpi-card.warning { border-left-color: var(--primary-yellow); }

        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            margin-bottom: 16px;
        }

        .kpi-icon.revenue { background: var(--primary-blue); }
        .kpi-icon.profit { background: var(--primary-purple); }
        .kpi-icon.orders { background: var(--primary-red); }
        .kpi-icon.customers { background: var(--primary-cyan); }
        .kpi-icon.quantity { background: var(--primary-orange); }
        .kpi-icon.warning { background: var(--primary-yellow); }

        .kpi-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-gray-500);
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-gray-900);
            margin-bottom: 4px;
        }

        .kpi-change {
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .kpi-change.positive { color: var(--primary-green); }
        .kpi-change.negative { color: var(--primary-red); }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-gray-900);
        }

        .chart-subtitle {
            font-size: 14px;
            color: var(--text-gray-500);
            margin-top: 4px;
        }

        .chart-actions {
            display: flex;
            gap: 8px;
        }

        .chart-btn {
            background: var(--bg-gray-100);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-gray-700);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .chart-btn:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }

        .chart-canvas {
            height: 300px;
            position: relative;
        }

        /* Tables Section */
        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-gray-900);
        }

        .table-subtitle {
            font-size: 12px;
            color: var(--text-gray-500);
            margin-top: 2px;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .table-btn {
            background: var(--bg-gray-100);
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-gray-700);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .table-btn:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--bg-gray-50);
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-gray-700);
            border-bottom: 1px solid var(--border-gray);
        }

        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-gray);
            font-size: 14px;
            color: var(--text-gray-900);
        }

        .data-table tr:hover {
            background: var(--bg-gray-50);
        }

        .priority-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high { background: #fef2f2; color: var(--primary-red); }
        .priority-medium { background: #fffbeb; color: var(--primary-orange); }
        .priority-low { background: #f0fdf4; color: var(--primary-green); }

        .pon-log-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(6, 182, 212, 0.1));
            color: var(--primary-blue);
            padding: 6px 12px;
            border-radius: 6px;
            font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(59, 130, 246, 0.2);
            display: inline-block;
        }

        .dni-info {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(168, 85, 247, 0.1));
            color: var(--primary-purple);
            padding: 6px 12px;
            border-radius: 6px;
            font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(124, 58, 237, 0.2);
            display: inline-block;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--bg-gray-200);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-blue);
            transition: width 0.3s ease;
        }

        /* Data Status */
        .data-status {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .data-status i {
            color: var(--primary-green);
            font-size: 20px;
        }

        .data-status-text {
            color: var(--text-gray-700);
            font-size: 14px;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .tables-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 16px;
            }
            
            .dashboard-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 16px;
            }
            
            .kpi-card {
                padding: 16px;
            }
            
            .kpi-value {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div>
                    <div class="header-title">Dashboard de Monitoreo de Clientes Individuales</div>
                </div>
            </div>
            <div class="header-right">
                <div class="time-selector">
                    <label>AÑO FISCAL</label>
                    <select>
                        <option value="2024">2025</option>
                        <option value="2023">2024</option>
                        <option value="2022">2023</option>
                        <option value="2021">2022</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Data Status -->
        <div class="data-status" style="<?= empty($raw_data) ? 'background: #fef2f2; border-color: #fca5a5;' : '' ?>">
            <i class="fas fa-<?= !empty($raw_data) ? 'database' : 'exclamation-triangle' ?>" style="<?= empty($raw_data) ? 'color: var(--primary-red);' : '' ?>"></i>
            <div class="data-status-text">
                <?php if (!empty($raw_data)): ?>
                    <strong><?= count($raw_data) ?></strong> incidentes de clientes cargados desde la API de Zabbix
                    - Última actualización: <?= date('Y-m-d H:i:s') ?>
                <?php else: ?>
                    <strong>Sin datos disponibles</strong> - No se pudieron cargar los incidentes desde la API de Zabbix
                    <br><small>Verifique la conexión y configuración de Zabbix</small>
                <?php endif; ?>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <div class="kpi-icon revenue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="kpi-label">Total de Incidentes</div>
                <div class="kpi-value"><?= number_format($dashboard_data['kpis']['total_incidents']) ?></div>
                <div class="kpi-change <?= $dashboard_data['kpis']['total_incidents'] > 0 ? 'negative' : 'positive' ?>">
                    <i class="fas fa-<?= $dashboard_data['kpis']['total_incidents'] > 0 ? 'arrow-up' : 'check' ?>"></i>
                    <span><?= $dashboard_data['kpis']['total_incidents'] > 0 ? 'Clientes Afectados' : 'Sin Problemas' ?></span>
                </div>
            </div>

            <div class="kpi-card profit">
                <div class="kpi-icon profit">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="kpi-label">Incidentes Activos</div>
                <div class="kpi-value"><?= number_format($dashboard_data['kpis']['active_incidents']) ?></div>
                <div class="kpi-change <?= $dashboard_data['kpis']['active_incidents'] > 0 ? 'negative' : 'positive' ?>">
                    <i class="fas fa-<?= $dashboard_data['kpis']['active_incidents'] > 0 ? 'exclamation' : 'check-circle' ?>"></i>
                    <span><?= $dashboard_data['kpis']['active_incidents'] > 0 ? 'Requiere Atención' : 'Todo Resuelto' ?></span>
                </div>
            </div>

            <div class="kpi-card orders">
                <div class="kpi-icon orders">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="kpi-label">Resueltos Hoy</div>
                <div class="kpi-value"><?= number_format($dashboard_data['kpis']['resolved_today']) ?></div>
                <div class="kpi-change positive">
                    <i class="fas fa-check"></i>
                    <span>Reparados Exitosamente</span>
                </div>
            </div>

            <div class="kpi-card customers">
                <div class="kpi-icon customers">
                    <i class="fas fa-server"></i>
                </div>
                <div class="kpi-label">Hosts Afectados</div>
                <div class="kpi-value"><?= number_format($dashboard_data['kpis']['unique_hosts']) ?></div>
                <div class="kpi-change">
                    <i class="fas fa-network-wired"></i>
                    <span>Equipos de Red</span>
                </div>
            </div>

            <div class="kpi-card quantity">
                <div class="kpi-icon quantity">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="kpi-label">Clientes Únicos</div>
                <div class="kpi-value"><?= number_format($dashboard_data['kpis']['unique_dnis']) ?></div>
                <div class="kpi-change">
                    <i class="fas fa-user-friends"></i>
                    <span>DNIs Afectados</span>
                </div>
            </div>

            <div class="kpi-card warning">
                <div class="kpi-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="kpi-label">Equipos Alarmados</div>
                <div class="kpi-value"><?= number_format($dashboard_data['kpis']['equipo_alarmado_count']) ?></div>
                <div class="kpi-change">
                    <i class="fas fa-wifi"></i>
                    <span>Problemas de Conexión</span>
                </div>
            </div>

            <div class="kpi-card revenue">
                <div class="kpi-icon revenue">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="kpi-label">Problemas de Potencia</div>
                <div class="kpi-value"><?= number_format($dashboard_data['kpis']['potencia_count']) ?></div>
                <div class="kpi-change">
                    <i class="fas fa-charging-station"></i>
                    <span>Problemas de Alimentación</span>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Time Distribution Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">Incidentes por Período de Tiempo</div>
                        <div class="chart-subtitle">Distribución de incidentes de clientes a lo largo del día</div>
                    </div>
                    <div class="chart-actions">
                        <button class="chart-btn" onclick="refreshData()">Actualizar</button>
                        <button class="chart-btn">Exportar</button>
                    </div>
                </div>
                <div class="chart-canvas">
                    <canvas id="timeChart"></canvas>
                </div>
            </div>

            <!-- Tipo Distribution Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">Distribución por Tipo de Problema</div>
                        <div class="chart-subtitle">Análisis de tipos de incidentes</div>
                    </div>
                    <div class="chart-actions">
                        <button class="chart-btn" onclick="refreshData()">Actualizar</button>
                        <button class="chart-btn">Exportar</button>
                    </div>
                </div>
                <div class="chart-canvas">
                    <canvas id="tipoChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="tables-grid">
            <!-- Top DNIs Problemáticos -->
            <div class="table-container">
                <div class="table-header">
                    <div>
                        <div class="table-title">Clientes Más Problemáticos</div>
                        <div class="table-subtitle">DNIs con mayor número de incidentes</div>
                    </div>
                    <div class="table-actions">
                        <button class="table-btn">Ver Todo</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Host</th>
                            <th>PON/LOG</th>
                            <th>Incidentes</th>
                            <th>Prioridad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboard_data['dni_distribution'] as $dni_data): ?>
                        <tr>
                            <td><span class="dni-info"><?= htmlspecialchars($dni_data['dni']) ?></span></td>
                            <td><?= htmlspecialchars($dni_data['host']) ?></td>
                            <td><span class="pon-log-info"><?= htmlspecialchars($dni_data['pon_log']) ?></span></td>
                            <td><?= $dni_data['count'] ?></td>
                            <td>
                                <span class="priority-badge priority-<?= $dni_data['count'] > 3 ? 'high' : ($dni_data['count'] > 1 ? 'medium' : 'low') ?>">
                                    <?= $dni_data['count'] > 3 ? 'Alta' : ($dni_data['count'] > 1 ? 'Media' : 'Baja') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Host Performance -->
            <div class="table-container">
                <div class="chart-header">
                    <div>
                        <div class="table-title">Rendimiento por Host</div>
                        <div class="table-subtitle">Distribución de incidentes por equipo</div>
                    </div>
                    <div class="table-actions">
                        <button class="table-btn">Ver Todo</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Host</th>
                            <th>Activos</th>
                            <th>Resueltos</th>
                            <th>Equipo Alarmado</th>
                            <th>Potencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboard_data['hosts_data'] as $host): ?>
                        <tr>
                            <td><?= htmlspecialchars($host['host']) ?></td>
                            <td><?= $host['active'] ?></td>
                            <td><?= $host['resolved'] ?></td>
                            <td><?= $host['equipo_alarmado'] ?></td>
                            <td><?= $host['potencia'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detailed Data Table -->
        <div class="chart-container" style="margin-top: 32px;">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Datos Detallados de Clientes</div>
                    <div class="chart-subtitle">Lista completa de todos los incidentes de clientes individuales</div>
                </div>
                <div class="chart-actions">
                    <button class="chart-btn" onclick="refreshData()">Actualizar</button>
                    <button class="chart-btn" onclick="exportData()">Exportar CSV</button>
                </div>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="data-table">
                    <thead style="position: sticky; top: 0; background: var(--bg-gray-50);">
                        <tr>
                            <th><i class="fas fa-server"></i> Host</th>
                            <th><i class="fas fa-network-wired"></i> PON/LOG</th>
                            <th><i class="fas fa-id-card"></i> DNI</th>
                            <th><i class="fas fa-tag"></i> Tipo</th>
                            <th><i class="fas fa-signal"></i> Estado</th>
                            <th><i class="fas fa-clock"></i> Hora</th>
                            <th><i class="fas fa-info-circle"></i> Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboard_data['raw_data'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['HOST']) ?></td>
                            <td><span class="pon-log-info"><?= htmlspecialchars($item['PON/LOG']) ?></span></td>
                            <td><span class="dni-info"><?= htmlspecialchars($item['DNI']) ?></span></td>
                            <td>
                                <span class="priority-badge priority-<?= $item['TIPO'] === 'EQUIPO ALARMADO' ? 'high' : 'medium' ?>">
                                    <?= htmlspecialchars($item['TIPO']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?= $item['STATUS'] === 'PROBLEM' ? 'high' : 'low' ?>">
                                    <?= htmlspecialchars($item['STATUS']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($item['TIME']) ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($item['DESCRIPCION']) ?>">
                                <?= htmlspecialchars($item['DESCRIPCION'] ?: 'Sin descripción') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($dashboard_data['raw_data'])): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray-500);">
                                <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 8px; color: var(--primary-green);"></i><br>
                                No se encontraron incidentes - Todos los clientes operativos
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Time Distribution Chart
        const timeCtx = document.getElementById('timeChart').getContext('2d');
        new Chart(timeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($dashboard_data['time_distribution'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($dashboard_data['time_distribution'])) ?>,
                    backgroundColor: [
                        '#2563eb',
                        '#7c3aed',
                        '#dc2626',
                        '#059669'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });

        // Tipo Distribution Chart
        const tipoCtx = document.getElementById('tipoChart').getContext('2d');
        new Chart(tipoCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($dashboard_data['tipo_distribution'])) ?>,
                datasets: [{
                    label: 'Cantidad',
                    data: <?= json_encode(array_values($dashboard_data['tipo_distribution'])) ?>,
                    backgroundColor: [
                        '#dc2626',
                        '#ea580c'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Functions
        function refreshData() {
            location.reload();
        }

        function exportData() {
            const data = <?= json_encode($dashboard_data['raw_data']) ?>;
            const headers = ['Host', 'PON/LOG', 'DNI', 'Tipo', 'Estado', 'Hora', 'Descripción'];
            const csvContent = [
                headers.join(','),
                ...data.map(row => [
                    row.HOST,
                    row['PON/LOG'],
                    row.DNI,
                    row.TIPO,
                    row.STATUS,
                    row.TIME,
                    `"${row.DESCRIPCION}"`
                ].join(','))
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'clientes_incidentes_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>