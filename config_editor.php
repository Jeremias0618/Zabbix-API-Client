<?php
// Página de edición de configuración
require_once 'config.php';

$systemConfig = getSystemConfig();
$errors = [];
$success = '';

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newConfig = [
        'zabUrl' => trim($_POST['zabUrl'] ?? ''),
        'zabToken' => trim($_POST['zabToken'] ?? ''),
        'groupName' => trim($_POST['groupName'] ?? ''),
        'auto_refresh_interval' => intval($_POST['auto_refresh_interval'] ?? 30),
        'timezone_offset' => intval($_POST['timezone_offset'] ?? -7),
    ];
    
    // Validar datos
    if (empty($newConfig['zabUrl'])) {
        $errors[] = 'La URL de Zabbix es requerida';
    }
    
    if (empty($newConfig['zabToken'])) {
        $errors[] = 'El Token de Zabbix es requerido';
    }
    
    if (empty($newConfig['groupName'])) {
        $errors[] = 'El nombre del grupo es requerido';
    }
    
    if ($newConfig['auto_refresh_interval'] < 5 || $newConfig['auto_refresh_interval'] > 300) {
        $errors[] = 'El intervalo de auto-refresh debe estar entre 5 y 300 segundos';
    }
    
    if (empty($errors)) {
        // Crear contenido del archivo config.php actualizado
        $configContent = '<?php
/**
 * Archivo de Configuración Centralizada
 * Configuración para todos los dashboards y scripts de Zabbix
 * Generado automáticamente el ' . date('Y-m-d H:i:s') . '
 */

// Configuración de conexión a Zabbix
$zabUrl    = \'' . addslashes($newConfig['zabUrl']) . '\';
$zabToken  = \'' . addslashes($newConfig['zabToken']) . '\';
$groupName = \'' . addslashes($newConfig['groupName']) . '\';

// Configuración del sistema
$systemConfig = [
    \'title\' => \'Sistema de Monitoreo Zabbix\',
    \'version\' => \'1.0.0\',
    \'author\' => \'CyberCode Labs\',
    \'icon\' => \'img/icon.png\',
    \'auto_refresh_interval\' => ' . $newConfig['auto_refresh_interval'] . ',
    \'timezone_offset\' => ' . $newConfig['timezone_offset'] . ',
];

// Configuración de filtros de tags
$tagFilters = [
    \'caida_hilo\' => [
        \'tag\' => \'PON\',
        \'value\' => \'CAIDA DE HILO\'
    ],
    \'equipo_alarmado\' => [
        [
            [\'tag\' => \'OLT\', \'value\' => \'HUAWEI\'],
            [\'tag\' => \'ONU\', \'value\' => \'DESCONEXIÓN\'],
            [\'tag\' => \'ONU\', \'value\' => \'EQUIPO ALARMADO\'],
            [\'tag\' => \'ONU\', \'value\' => \'ESTADO\']
        ]
    ],
    \'problemas_potencia\' => [
        [
            [\'tag\' => \'OLT\', \'value\' => \'HUAWEI\'],
            [\'tag\' => \'ONU\', \'value\' => \'POTENCIA TX\'],
            [\'tag\' => \'ONU\', \'value\' => \'POTENCIA RX\'],
            [\'tag\' => \'ONU\', \'value\' => \'PROBLEMAS DE POTENCIA\']
        ]
    ]
];

// Configuración de dashboards
$dashboards = [
    \'thread\' => [
        \'title\' => \'Dashboard de Caída de Hilo\',
        \'description\' => \'Monitoreo de problemas de caída de hilo GPON\',
        \'file\' => \'dashboard_thread.php\',
        \'icon\' => \'fas fa-network-wired\',
        \'color\' => \'primary-blue\'
    ],
    \'client\' => [
        \'title\' => \'Dashboard de Clientes Individuales\',
        \'description\' => \'Monitoreo de problemas de equipos alarmados y potencia\',
        \'file\' => \'dashboard_client.php\',
        \'icon\' => \'fas fa-user-cog\',
        \'color\' => \'primary-purple\'
    ]
];

// Configuración de scripts JSON
$jsonScripts = [
    \'caida_hilo\' => [
        \'title\' => \'API - Caída de Hilo\',
        \'description\' => \'Endpoint JSON para problemas de caída de hilo\',
        \'file\' => \'problems_json_scripts/zabbix_caida_de_hilo_json.php\',
        \'icon\' => \'fas fa-code\',
        \'color\' => \'primary-green\'
    ],
    \'individuales\' => [
        \'title\' => \'API - Clientes Individuales\',
        \'description\' => \'Endpoint JSON para problemas de clientes individuales\',
        \'file\' => \'problems_json_scripts/zabbix_individuales_json.php\',
        \'icon\' => \'fas fa-database\',
        \'color\' => \'primary-orange\'
    ]
];

// Función para obtener configuración de Zabbix
function getZabbixConfig() {
    global $zabUrl, $zabToken, $groupName;
    return [
        \'url\' => $zabUrl,
        \'token\' => $zabToken,
        \'group\' => $groupName
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
        $errors[] = \'URL de Zabbix no configurada\';
    }
    
    if (empty($zabToken)) {
        $errors[] = \'Token de Zabbix no configurado\';
    }
    
    if (empty($groupName)) {
        $errors[] = \'Nombre del grupo no configurado\';
    }
    
    return $errors;
}

// Función para formatear tiempo con offset
function formatTimeWithOffset($timestamp, $offset = null) {
    global $systemConfig;
    $offset = $offset ?? $systemConfig[\'timezone_offset\'];
    $adjustedTime = $timestamp + ($offset * 3600);
    return date(\'g:i:s A\', $adjustedTime);
}

// Función para obtener estado de conexión
function getConnectionStatus() {
    try {
        $config = getZabbixConfig();
        return [
            \'status\' => \'connected\',
            \'message\' => \'Conexión activa a Zabbix\',
            \'url\' => $config[\'url\']
        ];
    } catch (Exception $e) {
        return [
            \'status\' => \'error\',
            \'message\' => \'Error de conexión: \' . $e->getMessage(),
            \'url\' => \'\'
        ];
    }
}
?>';

        // Intentar escribir el archivo
        if (file_put_contents('config.php', $configContent)) {
            $success = 'Configuración guardada exitosamente';
        } else {
            $errors[] = 'No se pudo escribir el archivo de configuración. Verifique permisos.';
        }
    }
}

// Obtener configuración actual
$zabConfig = getZabbixConfig();
$currentSystemConfig = getSystemConfig();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - <?= htmlspecialchars($systemConfig['title']) ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($systemConfig['icon']) ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Header */
        .header {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .header-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-cyan));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
            color: white;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-gray-900);
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 16px;
            color: var(--text-gray-500);
        }

        /* Navigation */
        .nav {
            margin-bottom: 24px;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: var(--bg-gray-100);
        }

        /* Form */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: var(--shadow);
            margin-bottom: 32px;
        }

        .form-section {
            margin-bottom: 32px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-gray-900);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-icon {
            width: 24px;
            height: 24px;
            background: var(--primary-blue);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-gray-700);
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-help {
            font-size: 12px;
            color: var(--text-gray-500);
            margin-top: 4px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 32px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--bg-gray-100);
            color: var(--text-gray-700);
            border: 1px solid var(--border-gray);
        }

        .btn-secondary:hover {
            background: var(--bg-gray-200);
        }

        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #059669;
        }

        /* Current Config Display */
        .current-config {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 32px;
        }

        .config-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-gray);
        }

        .config-item:last-child {
            border-bottom: none;
        }

        .config-label {
            font-weight: 500;
            color: var(--text-gray-700);
        }

        .config-value {
            color: var(--text-gray-500);
            font-family: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h1 class="header-title">Configuración del Sistema</h1>
            <p class="header-subtitle">Configure los parámetros de conexión y comportamiento del sistema</p>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php" class="nav-link">
                <i class="fas fa-arrow-left"></i>
                Volver al Panel Principal
            </a>
        </div>

        <!-- Alerts -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Error:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?= htmlspecialchars($success) ?></div>
        </div>
        <?php endif; ?>

        <!-- Current Configuration -->
        <div class="current-config">
            <h3 style="margin-bottom: 16px; color: var(--text-gray-900);">Configuración Actual</h3>
            <div class="config-item">
                <span class="config-label">URL de Zabbix:</span>
                <span class="config-value"><?= htmlspecialchars($zabConfig['url']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Token:</span>
                <span class="config-value"><?= htmlspecialchars(substr($zabConfig['token'], 0, 20)) ?>...</span>
            </div>
            <div class="config-item">
                <span class="config-label">Grupo:</span>
                <span class="config-value"><?= htmlspecialchars($zabConfig['group']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Auto-refresh:</span>
                <span class="config-value"><?= $currentSystemConfig['auto_refresh_interval'] ?>s</span>
            </div>
            <div class="config-item">
                <span class="config-label">Offset de zona horaria:</span>
                <span class="config-value"><?= $currentSystemConfig['timezone_offset'] ?> horas</span>
            </div>
        </div>

        <!-- Configuration Form -->
        <form method="POST" class="form-container">
            <!-- Zabbix Configuration -->
            <div class="form-section">
                <h3 class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    Configuración de Zabbix
                </h3>
                
                <div class="form-group">
                    <label class="form-label" for="zabUrl">URL de Zabbix</label>
                    <input type="url" id="zabUrl" name="zabUrl" class="form-input" 
                           value="<?= htmlspecialchars($_POST['zabUrl'] ?? $zabConfig['url']) ?>" 
                           placeholder="http://10.80.80.175/zabbix" required>
                    <div class="form-help">URL completa del servidor Zabbix incluyendo protocolo y puerto</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="zabToken">Token de Autenticación</label>
                    <input type="text" id="zabToken" name="zabToken" class="form-input" 
                           value="<?= htmlspecialchars($_POST['zabToken'] ?? $zabConfig['token']) ?>" 
                           placeholder="c656ccbf99abd980e6e04d495321be7a755d3626838e02bc82bcd6f5c66c7e69" required>
                    <div class="form-help">Token de autenticación para la API de Zabbix</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="groupName">Nombre del Grupo</label>
                    <input type="text" id="groupName" name="groupName" class="form-input" 
                           value="<?= htmlspecialchars($_POST['groupName'] ?? $zabConfig['group']) ?>" 
                           placeholder="OLT" required>
                    <div class="form-help">Nombre del grupo de hosts en Zabbix</div>
                </div>
            </div>

            <!-- System Configuration -->
            <div class="form-section">
                <h3 class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    Configuración del Sistema
                </h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="auto_refresh_interval">Intervalo de Auto-refresh (segundos)</label>
                        <input type="number" id="auto_refresh_interval" name="auto_refresh_interval" class="form-input" 
                               value="<?= htmlspecialchars($_POST['auto_refresh_interval'] ?? $currentSystemConfig['auto_refresh_interval']) ?>" 
                               min="5" max="300" required>
                        <div class="form-help">Tiempo entre actualizaciones automáticas (5-300s)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="timezone_offset">Offset de Zona Horaria (horas)</label>
                        <input type="number" id="timezone_offset" name="timezone_offset" class="form-input" 
                               value="<?= htmlspecialchars($_POST['timezone_offset'] ?? $currentSystemConfig['timezone_offset']) ?>" 
                               min="-12" max="12" required>
                        <div class="form-help">Diferencia horaria respecto a UTC (-12 a +12)</div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="button-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>

    <script>
        // Validación en tiempo real
        document.getElementById('zabUrl').addEventListener('blur', function() {
            if (this.value && !this.value.match(/^https?:\/\/.+/)) {
                this.style.borderColor = 'var(--primary-red)';
            } else {
                this.style.borderColor = 'var(--border-gray)';
            }
        });

        // Confirmación antes de guardar
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('¿Está seguro de que desea guardar la nueva configuración? Esto afectará todos los dashboards.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
