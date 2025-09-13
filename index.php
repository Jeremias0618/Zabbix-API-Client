<?php
// Incluir configuración
require_once 'config.php';

$systemConfig = getSystemConfig();
$dashboards = getDashboards();
$jsonScripts = getJsonScripts();
$connectionStatus = getConnectionStatus();

// Verificar que las variables no sean null y asignar valores por defecto
if ($dashboards === null) {
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
}

if ($jsonScripts === null) {
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
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($systemConfig['title']) ?> - Panel Principal</title>
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
            max-width: 1200px;
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
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-cyan));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
            color: white;
        }

        .header-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-gray-900);
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 16px;
            color: var(--text-gray-500);
            margin-bottom: 24px;
        }

        .header-info {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-gray-600);
        }

        .info-item i {
            color: var(--primary-blue);
        }

        /* Status Bar */
        .status-bar {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 32px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .connection-status {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: <?= $connectionStatus['status'] === 'connected' ? 'var(--primary-green)' : 'var(--primary-red)' ?>;
        }

        .status-text {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-gray-700);
        }

        .system-actions {
            display: flex;
            gap: 12px;
        }

        .action-btn {
            background: var(--bg-gray-100);
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-gray-700);
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .action-btn:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            transform: translateY(-1px);
        }

        /* Sections */
        .section {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-gray-900);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            background: var(--primary-blue);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .card.primary-blue { border-left-color: var(--primary-blue); }
        .card.primary-purple { border-left-color: var(--primary-purple); }
        .card.primary-green { border-left-color: var(--primary-green); }
        .card.primary-orange { border-left-color: var(--primary-orange); }

        .card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .card-icon.primary-blue { background: var(--primary-blue); }
        .card-icon.primary-purple { background: var(--primary-purple); }
        .card-icon.primary-green { background: var(--primary-green); }
        .card-icon.primary-orange { background: var(--primary-orange); }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-gray-900);
            margin-bottom: 4px;
        }

        .card-description {
            font-size: 14px;
            color: var(--text-gray-500);
            line-height: 1.5;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-gray);
        }

        .card-status {
            font-size: 12px;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            background: var(--bg-gray-100);
            color: var(--text-gray-600);
        }

        .card-arrow {
            color: var(--text-gray-400);
            font-size: 14px;
        }

        /* Footer */
        .footer {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
            text-align: center;
            color: var(--text-gray-500);
            font-size: 14px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .footer-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .footer-link:hover {
            color: var(--primary-purple);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            
            .header {
                padding: 24px;
            }
            
            .header-title {
                font-size: 24px;
            }
            
            .header-info {
                flex-direction: column;
                gap: 16px;
            }
            
            .status-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .system-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="header-title"><?= htmlspecialchars($systemConfig['title']) ?></h1>
            <p class="header-subtitle">Sistema de monitoreo y análisis de problemas de red GPON</p>
            <div class="header-info">
                <div class="info-item">
                    <i class="fas fa-code-branch"></i>
                    <span>Versión <?= htmlspecialchars($systemConfig['version']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($systemConfig['author']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span>Auto-refresh: <?= $systemConfig['auto_refresh_interval'] ?>s</span>
                </div>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="status-bar">
            <div class="connection-status">
                <div class="status-indicator"></div>
                <div class="status-text">
                    <?= $connectionStatus['status'] === 'connected' ? 'Conectado a Zabbix' : 'Error de conexión' ?>
                    <?php if ($connectionStatus['url']): ?>
                        - <?= htmlspecialchars($connectionStatus['url']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="system-actions">
                <a href="config_editor.php" class="action-btn">
                    <i class="fas fa-cog"></i>
                    Configuración
                </a>
                <a href="index.php" class="action-btn">
                    <i class="fas fa-sync-alt"></i>
                    Actualizar
                </a>
            </div>
        </div>

        <!-- Dashboards Section -->
        <div class="section">
            <h2 class="section-title">
                <div class="section-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                Dashboards de Monitoreo
            </h2>
            <div class="cards-grid">
                <?php foreach ($dashboards as $key => $dashboard): ?>
                <a href="<?= htmlspecialchars($dashboard['file']) ?>" class="card <?= htmlspecialchars($dashboard['color']) ?>">
                    <div class="card-header">
                        <div class="card-icon <?= htmlspecialchars($dashboard['color']) ?>">
                            <i class="<?= htmlspecialchars($dashboard['icon']) ?>"></i>
                        </div>
                        <div>
                            <h3 class="card-title"><?= htmlspecialchars($dashboard['title']) ?></h3>
                            <p class="card-description"><?= htmlspecialchars($dashboard['description']) ?></p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="card-status">Dashboard</span>
                        <i class="fas fa-arrow-right card-arrow"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- JSON Scripts Section -->
        <div class="section">
            <h2 class="section-title">
                <div class="section-icon">
                    <i class="fas fa-code"></i>
                </div>
                APIs JSON
            </h2>
            <div class="cards-grid">
                <?php foreach ($jsonScripts as $key => $script): ?>
                <a href="<?= htmlspecialchars($script['file']) ?>" class="card <?= htmlspecialchars($script['color']) ?>">
                    <div class="card-header">
                        <div class="card-icon <?= htmlspecialchars($script['color']) ?>">
                            <i class="<?= htmlspecialchars($script['icon']) ?>"></i>
                        </div>
                        <div>
                            <h3 class="card-title"><?= htmlspecialchars($script['title']) ?></h3>
                            <p class="card-description"><?= htmlspecialchars($script['description']) ?></p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="card-status">API Endpoint</span>
                        <i class="fas fa-arrow-right card-arrow"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-links">
                <a href="config_editor.php" class="footer-link">Configuración</a>
                <a href="index.php" class="footer-link">Panel Principal</a>
                <a href="#" class="footer-link">Documentación</a>
                <a href="#" class="footer-link">Soporte</a>
            </div>
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($systemConfig['author']) ?>. Sistema de Monitoreo Zabbix v<?= htmlspecialchars($systemConfig['version']) ?></p>
        </div>
    </div>

    <script>
        // Auto refresh cada 5 minutos
        setTimeout(() => {
            location.reload();
        }, 300000);

        // Agregar efectos de hover mejorados
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
