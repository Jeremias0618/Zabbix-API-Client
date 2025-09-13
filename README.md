# 📊 Sistema de Monitoreo Zabbix API Client

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/cybercodelabs/zabbix-api-client)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/status-Active-success.svg)](https://github.com/cybercodelabs/zabbix-api-client)

> **Sistema profesional de monitoreo y análisis de problemas de red GPON basado en la API de Zabbix, desarrollado para empresas de telecomunicaciones.**

## 🏢 Información del Proyecto

| **Detalle** | **Información** |
|-------------|-----------------|
| **Proyecto** | Zabbix API Client |
| **Desarrollador** | Yeremi Tantaraico |
| **Cargo** | Project Manager |
| **Empresa** | CyberCode Labs |
| **Correo** | yeremitantaraico@gmail.com |
| **Versión** | 1.0.0 |
| **Licencia** | MIT |
| **Tecnologías** | PHP, JavaScript, HTML5, CSS3, Chart.js |

## 📋 Tabla de Contenidos

- [Características Principales](#-características-principales)
- [Arquitectura del Sistema](#-arquitectura-del-sistema)
- [Instalación y Configuración](#-instalación-y-configuración)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Guía de Uso](#-guía-de-uso)
- [APIs y Endpoints](#-apis-y-endpoints)
- [Configuración Avanzada](#-configuración-avanzada)
- [Personalización](#-personalización)
- [Requisitos del Sistema](#-requisitos-del-sistema)
- [Solución de Problemas](#-solución-de-problemas)
- [Contribución](#-contribución)
- [Licencia](#-licencia)
- [Contacto](#-contacto)

## ✨ Características Principales

### 🎯 **Monitoreo en Tiempo Real**
- **Dashboards Profesionales**: Interfaz moderna y responsive para monitoreo en tiempo real
- **Auto-refresh Configurable**: Actualización automática personalizable (5-300 segundos)
- **Indicadores de Estado**: Monitoreo de salud de conexión con Zabbix
- **Métricas en Vivo**: KPIs actualizados automáticamente

### 📊 **Análisis de Datos Avanzado**
- **Múltiples Tipos de Problemas**: Caída de hilo, equipos alarmados, problemas de potencia
- **Gráficos Interactivos**: Chart.js para visualización de datos
- **Tablas Detalladas**: Información completa de incidentes y clientes
- **Filtros Inteligentes**: Búsqueda y filtrado por múltiples criterios

### 🔧 **Configuración Centralizada**
- **Gestión Unificada**: Configuración centralizada de todos los parámetros
- **Editor Web**: Interfaz gráfica para modificar configuración
- **Validación Automática**: Verificación de parámetros en tiempo real
- **Backup Automático**: Respaldo de configuración antes de cambios

### 🚀 **APIs y Integración**
- **Endpoints JSON**: APIs REST para integración con otros sistemas
- **Formato Estándar**: Respuestas JSON estructuradas
- **Documentación API**: Especificaciones completas de endpoints
- **Compatibilidad**: Integración con sistemas externos

## 🏗️ Arquitectura del Sistema

### **Diagrama de Arquitectura**

```
┌─────────────────────────────────────────────────────────────┐
│                    SISTEMA DE MONITOREO ZABBIX              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐    ┌─────────────────┐                │
│  │   INDEX.PHP     │    │  CONFIG.PHP     │                │
│  │  Panel Principal│    │  Configuración  │                │
│  └─────────────────┘    └─────────────────┘                │
│           │                       │                        │
│           │                       │                        │
│  ┌─────────────────┐    ┌─────────────────┐                │
│  │DASHBOARD_THREAD │    │DASHBOARD_CLIENT │                │
│  │  Caída de Hilo  │    │Clientes Indiv.  │                │
│  └─────────────────┘    └─────────────────┘                │
│           │                       │                        │
│           │                       │                        │
│  ┌─────────────────┐    ┌─────────────────┐                │
│  │  ZABBIX API     │    │  JSON SCRIPTS   │                │
│  │   Conexión      │    │   Endpoints     │                │
│  └─────────────────┘    └─────────────────┘                │
│           │                       │                        │
│           └───────────────────────┘                        │
│                           │                                │
│  ┌─────────────────────────────────────────────────────────┤
│  │                SERVIDOR ZABBIX                          │
│  │            (10.80.80.175/zabbix)                       │
│  └─────────────────────────────────────────────────────────┘
```

### **Componentes Principales**

1. **Capa de Presentación**: HTML5, CSS3, JavaScript
2. **Capa de Lógica**: PHP 7.4+, Zabbix API Client
3. **Capa de Datos**: API de Zabbix, JSON
4. **Capa de Configuración**: Sistema centralizado de parámetros

## 🚀 Instalación y Configuración

### **Requisitos Previos**

```bash
# Servidor Web
- Apache 2.4+ / Nginx 1.18+
- PHP 7.4 o superior
- Extensiones PHP: cURL, JSON, OpenSSL

# Servidor Zabbix
- Zabbix 5.0+
- API habilitada
- Token de autenticación válido
```

### **Instalación Paso a Paso**

#### **1. Descarga del Proyecto**
```bash
# Clonar repositorio
git clone https://github.com/cybercodelabs/zabbix-api-client.git

# O descargar ZIP
wget https://github.com/cybercodelabs/zabbix-api-client/archive/main.zip
```

#### **2. Configuración del Servidor Web**
```apache
# Virtual Host Apache
<VirtualHost *:80>
    ServerName zabbix-monitor.local
    DocumentRoot /var/www/html/zabbix-api-client
    DirectoryIndex index.php
    
    <Directory /var/www/html/zabbix-api-client>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### **3. Configuración de PHP**
```ini
# php.ini
extension=curl
extension=json
extension=openssl
memory_limit=256M
max_execution_time=300
```

#### **4. Configuración Inicial**
```bash
# Permisos de archivos
chmod 755 /var/www/html/zabbix-api-client
chmod 644 /var/www/html/zabbix-api-client/*.php

# Crear directorio de logs
mkdir -p /var/log/zabbix-monitor
chmod 755 /var/log/zabbix-monitor
```

### **Configuración de Zabbix**

#### **1. Crear Token de API**
```bash
# En Zabbix Web Interface
Administration → API tokens → Create API token
```

#### **2. Configurar Grupo de Hosts**
```bash
# Crear grupo "OLT" en Zabbix
Configuration → Host groups → Create host group
```

## 📁 Estructura del Proyecto

```
zabbix-api-client/
├── 📄 index.php                          # Panel principal del sistema
├── ⚙️ config.php                         # Configuración centralizada
├── 🔧 config_editor.php                  # Editor de configuración web
├── 📊 dashboard_thread.php               # Dashboard de caída de hilo
├── 👥 dashboard_client.php               # Dashboard de clientes individuales
├── 📋 README.md                          # Documentación del proyecto
├── 🖼️ icon.png                           # Icono del sistema
├── 📁 problems_json_scripts/             # Scripts de APIs JSON
│   ├── 📄 zabbix_caida_de_hilo_json.php  # API - Caída de hilo
│   └── 📄 zabbix_individuales_json.php   # API - Clientes individuales
├── 📁 src/                               # Código fuente
│   └── 📄 ZabbixApi.php                  # Cliente de API de Zabbix
├── 📁 docs/                              # Documentación adicional
│   ├── 📄 API_REFERENCE.md               # Referencia de APIs
│   ├── 📄 INSTALLATION.md                # Guía de instalación
│   └── 📄 TROUBLESHOOTING.md             # Solución de problemas
├── 📁 assets/                            # Recursos estáticos
│   ├── 📁 css/                           # Estilos CSS
│   ├── 📁 js/                            # Scripts JavaScript
│   └── 📁 images/                        # Imágenes
└── 📁 logs/                              # Archivos de log
    └── 📄 system.log                     # Log del sistema
```

### **Descripción de Archivos**

| **Archivo** | **Función** | **Tecnología** |
|-------------|-------------|----------------|
| `index.php` | Panel principal y navegación | PHP, HTML5, CSS3 |
| `config.php` | Configuración centralizada | PHP |
| `config_editor.php` | Editor de configuración | PHP, HTML5, CSS3 |
| `dashboard_thread.php` | Dashboard de caída de hilo | PHP, Chart.js |
| `dashboard_client.php` | Dashboard de clientes | PHP, Chart.js |
| `ZabbixApi.php` | Cliente de API de Zabbix | PHP |

## 📖 Guía de Uso

### **Acceso al Sistema**

#### **1. Panel Principal**
```bash
# URL de acceso
http://tu-servidor/zabbix-api-client/

# O con dominio personalizado
http://zabbix-monitor.local/
```

#### **2. Configuración Inicial**
```bash
# Acceder al editor de configuración
http://tu-servidor/zabbix-api-client/config_editor.php

# Configurar parámetros:
- URL de Zabbix: http://10.80.80.175/zabbix
- Token: [Tu token de API]
- Grupo: OLT
- Auto-refresh: 30 segundos
- Zona horaria: -7 horas
```

### **Navegación del Sistema**

#### **Dashboard de Caída de Hilo**
- **Métricas**: Total de incidentes, activos, resueltos
- **Gráficos**: Distribución por tiempo y host
- **Tablas**: Problemas GPON y rendimiento

#### **Dashboard de Clientes Individuales**
- **Métricas**: Clientes únicos, equipos alarmados
- **Gráficos**: Distribución por tipo de problema
- **Tablas**: Clientes problemáticos y rendimiento

### **Funcionalidades Avanzadas**

#### **Exportación de Datos**
```javascript
// Exportar a CSV
function exportData() {
    // Implementación en dashboard
}
```

#### **Filtrado de Datos**
```javascript
// Filtros en tiempo real
function filterData(criteria) {
    // Implementación de filtros
}
```

## 🔌 APIs y Endpoints

### **API de Caída de Hilo**

#### **Endpoint**
```http
GET /problems_json_scripts/zabbix_caida_de_hilo_json.php
```

#### **Respuesta**
```json
{
  "status": "success",
  "data": [
    {
      "HOST": "LO2-15",
      "GPON": "17/2",
      "DESCRIPCION": "LOSMP-CALLAO1-48-ODF:10-HILO:38",
      "TIPO": "CAIDA DE HILO",
      "STATUS": "PROBLEM",
      "TIME": "10:13:50 AM"
    }
  ],
  "timestamp": "2024-01-15 10:13:50",
  "count": 1
}
```

#### **Parámetros**
| **Parámetro** | **Tipo** | **Descripción** |
|---------------|----------|-----------------|
| `format` | string | Formato de respuesta (json) |
| `limit` | integer | Límite de resultados |
| `filter` | string | Filtro por estado |

### **API de Clientes Individuales**

#### **Endpoint**
```http
GET /problems_json_scripts/zabbix_individuales_json.php
```

#### **Respuesta**
```json
{
  "status": "success",
  "data": [
    {
      "HOST": "INC-5",
      "PON/LOG": "5/1/3",
      "DNI": "70122339_02",
      "TIPO": "EQUIPO ALARMADO",
      "STATUS": "PROBLEM",
      "TIME": "4:44:00 PM",
      "DESCRIPCION": "EQUIPO ALARMADO (5/1/3) DNI (70122339_02)"
    }
  ],
  "timestamp": "2024-01-15 16:44:00",
  "count": 1
}
```

## ⚙️ Configuración Avanzada

### **Parámetros de Sistema**

#### **Configuración de Zabbix**
```php
// config.php
$zabUrl = 'http://10.80.80.175/zabbix';
$zabToken = 'c656ccbf99abd980e6e04d495321be7a755d3626838e02bc82bcd6f5c66c7e69';
$groupName = 'OLT';
```

#### **Configuración de Sistema**
```php
$systemConfig = [
    'title' => 'Sistema de Monitoreo Zabbix',
    'version' => '1.0.0',
    'auto_refresh_interval' => 30,
    'timezone_offset' => -7,
];
```

### **Filtros de Tags**

#### **Caída de Hilo**
```php
'caida_hilo' => [
    'tag' => 'PON',
    'value' => 'CAIDA DE HILO'
]
```

#### **Equipo Alarmado**
```php
'equipo_alarmado' => [
    ['tag' => 'OLT', 'value' => 'HUAWEI'],
    ['tag' => 'ONU', 'value' => 'DESCONEXIÓN'],
    ['tag' => 'ONU', 'value' => 'EQUIPO ALARMADO'],
    ['tag' => 'ONU', 'value' => 'ESTADO']
]
```

### **Personalización de Dashboards**

#### **Colores y Tema**
```css
:root {
    --primary-blue: #2563eb;
    --primary-purple: #7c3aed;
    --primary-red: #dc2626;
    --primary-green: #059669;
}
```

#### **Configuración de Gráficos**
```javascript
// Chart.js configuration
const chartConfig = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' }
    }
};
```

## 🎨 Personalización

### **Temas Personalizados**

#### **Crear Nuevo Tema**
```css
/* themes/custom.css */
:root {
    --primary-color: #your-color;
    --secondary-color: #your-color;
    --accent-color: #your-color;
}
```

#### **Aplicar Tema**
```html
<link rel="stylesheet" href="themes/custom.css">
```

### **Iconos Personalizados**

#### **Reemplazar Icono Principal**
```bash
# Reemplazar icon.png con tu icono
cp tu-icono.png icon.png
```

#### **Iconos de Dashboard**
```html
<i class="fas fa-custom-icon"></i>
```

### **Métricas Personalizadas**

#### **Agregar Nueva Métrica**
```php
// En dashboard
$customMetric = calculateCustomMetric($data);
```

#### **Gráfico Personalizado**
```javascript
// Nuevo gráfico
const customChart = new Chart(ctx, {
    type: 'line',
    data: customData,
    options: customOptions
});
```

## 🔧 Requisitos del Sistema

### **Servidor Web**

| **Componente** | **Versión Mínima** | **Recomendada** |
|----------------|-------------------|-----------------|
| **PHP** | 7.4 | 8.0+ |
| **Apache** | 2.4 | 2.4.41+ |
| **Nginx** | 1.18 | 1.20+ |
| **MySQL** | 5.7 | 8.0+ |

### **Extensiones PHP**

```bash
# Extensiones requeridas
php-curl
php-json
php-openssl
php-mbstring
php-xml
php-zip
```

### **Recursos del Sistema**

| **Recurso** | **Mínimo** | **Recomendado** |
|-------------|------------|-----------------|
| **RAM** | 512MB | 2GB+ |
| **CPU** | 1 core | 2 cores+ |
| **Disco** | 100MB | 1GB+ |
| **Red** | 10Mbps | 100Mbps+ |

### **Navegadores Soportados**

| **Navegador** | **Versión Mínima** | **Soporte** |
|---------------|-------------------|-------------|
| **Chrome** | 80+ | ✅ Completo |
| **Firefox** | 75+ | ✅ Completo |
| **Safari** | 13+ | ✅ Completo |
| **Edge** | 80+ | ✅ Completo |
| **IE** | 11 | ⚠️ Limitado |

## 🚨 Solución de Problemas

### **Problemas Comunes**

#### **Error de Conexión a Zabbix**
```bash
# Verificar conectividad
curl -I http://10.80.80.175/zabbix

# Verificar token
curl -X POST http://10.80.80.175/zabbix/api_jsonrpc.php \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","method":"user.checkAuthentication","params":{"token":"tu-token"},"id":1}'
```

#### **Datos No Aparecen**
```bash
# Verificar logs
tail -f /var/log/zabbix-monitor/system.log

# Verificar permisos
ls -la /var/www/html/zabbix-api-client/
```

#### **Problemas de Rendimiento**
```bash
# Monitorear recursos
htop
iostat -x 1

# Optimizar PHP
php -m | grep -E "(curl|json|openssl)"
```

### **Logs y Debugging**

#### **Habilitar Logs Detallados**
```php
// En config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/zabbix-monitor/php_errors.log');
```

#### **Logs del Sistema**
```bash
# Ver logs en tiempo real
tail -f /var/log/zabbix-monitor/system.log

# Buscar errores específicos
grep "ERROR" /var/log/zabbix-monitor/system.log
```

### **Comandos de Diagnóstico**

#### **Verificar Instalación**
```bash
# Script de verificación
php -f verify_installation.php
```

#### **Test de Conectividad**
```bash
# Test de API
curl -X POST http://10.80.80.175/zabbix/api_jsonrpc.php \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","method":"apiinfo.version","params":{},"id":1}'
```

## 🤝 Contribución

### **Cómo Contribuir**

#### **1. Fork del Proyecto**
```bash
# Fork en GitHub
# Clonar tu fork
git clone https://github.com/tu-usuario/zabbix-api-client.git
```

#### **2. Crear Rama de Desarrollo**
```bash
# Crear rama para feature
git checkout -b feature/nueva-funcionalidad

# Crear rama para bugfix
git checkout -b bugfix/correccion-error
```

#### **3. Desarrollar y Testear**
```bash
# Desarrollar funcionalidad
# Ejecutar tests
php -f tests/run_tests.php
```

#### **4. Commit y Push**
```bash
# Commit con mensaje descriptivo
git commit -m "feat: agregar nueva funcionalidad de exportación"

# Push a tu fork
git push origin feature/nueva-funcionalidad
```

#### **5. Pull Request**
```bash
# Crear PR en GitHub
# Describir cambios
# Asignar reviewers
```

### **Estándares de Código**

#### **PHP**
```php
<?php
/**
 * Descripción de la función
 * @param string $parametro Descripción del parámetro
 * @return array Descripción del retorno
 */
function miFuncion($parametro) {
    // Código aquí
    return $resultado;
}
```

#### **JavaScript**
```javascript
/**
 * Descripción de la función
 * @param {string} parametro - Descripción del parámetro
 * @returns {Array} Descripción del retorno
 */
function miFuncion(parametro) {
    // Código aquí
    return resultado;
}
```

#### **CSS**
```css
/* Descripción del estilo */
.selector {
    /* Propiedades organizadas alfabéticamente */
    background-color: #fff;
    border: 1px solid #ccc;
    color: #333;
}
```

### **Testing**

#### **Tests Unitarios**
```bash
# Ejecutar tests
php -f tests/unit_tests.php

# Tests específicos
php -f tests/test_zabbix_api.php
```

#### **Tests de Integración**
```bash
# Tests de API
php -f tests/integration_tests.php

# Tests de UI
npm test
```

## 📄 Licencia

### **MIT License**

```
MIT License

Copyright (c) 2024 CyberCode Labs

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## 📞 Contacto

### **Información del Desarrollador**

| **Campo** | **Información** |
|-----------|-----------------|
| **Nombre** | Yeremi Tantaraico |
| **Cargo** | Project Manager |
| **Empresa** | CyberCode Labs |
| **Email** | yeremitantaraico@gmail.com |
| **LinkedIn** | [Yeremi Tantaraico](https://linkedin.com/in/yeremi-tantaraico) |
| **GitHub** | [@yeremitantaraico](https://github.com/yeremitantaraico) |

### **Información de la Empresa**

| **Campo** | **Información** |
|-----------|-----------------|
| **Empresa** | CyberCode Labs |
| **Sitio Web** | [www.cybercodelabs.com](https://www.cybercodelabs.com) |
| **Email** | info@cybercodelabs.com |
| **Teléfono** | +1 (555) 123-4567 |
| **Dirección** | 123 Tech Street, Silicon Valley, CA 94000 |

### **Soporte Técnico**

#### **Canales de Soporte**
- **Email**: support@cybercodelabs.com
- **GitHub Issues**: [Reportar Bug](https://github.com/cybercodelabs/zabbix-api-client/issues)
- **Documentación**: [Wiki del Proyecto](https://github.com/cybercodelabs/zabbix-api-client/wiki)
- **Chat**: [Discord Server](https://discord.gg/cybercodelabs)

#### **Horarios de Soporte**
- **Lunes - Viernes**: 9:00 AM - 6:00 PM PST
- **Sábados**: 10:00 AM - 2:00 PM PST
- **Domingos**: Cerrado

### **Comunidad**

#### **Redes Sociales**
- **Twitter**: [@CyberCodeLabs](https://twitter.com/cybercodelabs)
- **LinkedIn**: [CyberCode Labs](https://linkedin.com/company/cybercode-labs)
- **YouTube**: [CyberCode Labs Channel](https://youtube.com/cybercodelabs)

#### **Contribuidores**
- **Yeremi Tantaraico** - Project Manager & Lead Developer
- **Equipo CyberCode Labs** - Desarrollo y Testing
- **Comunidad Open Source** - Contribuciones y Feedback

---

## 🎯 Roadmap del Proyecto

### **Versión 1.1.0 (Próxima)**
- [ ] Dashboard de alertas en tiempo real
- [ ] Sistema de notificaciones por email
- [ ] API REST completa con autenticación
- [ ] Soporte para múltiples servidores Zabbix

### **Versión 1.2.0 (Futuro)**
- [ ] Aplicación móvil nativa
- [ ] Integración con Slack/Teams
- [ ] Machine Learning para predicción de problemas
- [ ] Sistema de reportes automatizados

### **Versión 2.0.0 (Largo Plazo)**
- [ ] Arquitectura de microservicios
- [ ] Kubernetes deployment
- [ ] Multi-tenant support
- [ ] Advanced analytics y BI

---

**Desarrollado con ❤️ por [CyberCode Labs](https://www.cybercodelabs.com)**

*Última actualización: Enero 2024*
