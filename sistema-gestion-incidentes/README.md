# Sistema de Gestion de Incidentes (FURD) — Bomberos

Plataforma web para registrar, consultar, analizar y generar reportes de
incidentes atendidos, construida a partir del analisis del formulario
`form.xlsx` (Formato Unico de Recoleccion de Datos - FURD, usado con
ArcGIS Survey123).

---

## 1. Analisis de `form.xlsx` (resumen)

El archivo es un **formulario XLSForm** con:

- **249 campos** de datos, organizados en **33 grupos**.
- **4 grupos repetibles**: personas afectadas (hasta 8), animales afectados
  (hasta 4), edificaciones/vehiculos afectados (hasta 3) y personal
  (bomberos) que atendio la emergencia.
- **95 listas de seleccion** (`choices`) con **2.243 opciones** (servicios,
  barrios, comunas, tipos de vehiculo, causas de incendio, etc.).
- Sub-formulario **SCI** (Sistema de Comando de Incidentes) y una seccion
  de **cierre/firma del responsable**.

### Decision de arquitectura de datos

Con 249 campos, crear una columna SQL por campo habria producido una tabla
inmanejable y muy fragil ante cualquier cambio futuro del formulario. Se
opto por un modelo **hibrido**:

| Tipo de dato | Donde vive | Por que |
|---|---|---|
| Identificacion, fechas, ubicacion principal, estado, prioridad, responsable | Columnas reales en `cases` (indexadas) | Se usan constantemente para filtrar, buscar y calcular KPIs — necesitan ser rapidas de consultar. |
| El resto de los ~200 campos del formulario original (forestal, causa, SCI, etc.) | Columna `cases.form_data` (JSON) | Se mantienen **exactamente** con los nombres del Excel, sin inventar estructura, y permiten que el formulario evolucione sin migraciones de base de datos. |
| Los 4 grupos repetibles | Tablas hijas `case_persons`, `case_animals`, `case_buildings`, `case_firefighters` | Se necesitan **contar y filtrar** (ej. "personas rescatadas"), algo que un JSON no permite hacer eficientemente en SQL. |
| Las 95 listas de opciones | Tabla generica `catalog_items` | Editable desde **Configuracion → Categorias** sin tocar codigo ni base de datos. |

Ningun dato del Excel fue descartado ni renombrado sin razon: los nombres
de campo (`field_349`, `direccion_del_incidente`, etc.) se conservan tal
cual para trazabilidad total con el formulario original.

---

## 2. Arquitectura del sistema

- **Backend:** PHP 8.2+ puro (sin framework pesado), organizado en
  MVC simple: `app/controllers`, `app/models`, `app/helpers`,
  `app/services`. Router propio (`app/Router.php`), sin dependencias
  externas para el core (Composer solo se usa para PDF/Excel).
- **Base de datos:** MySQL / MariaDB (InnoDB, utf8mb4, FULLTEXT para
  busqueda global).
- **Frontend:** Bootstrap 5 + Bootstrap Icons + Chart.js + DataTables,
  cargados por CDN. Diseño tipo dashboard empresarial (sidebar, KPIs,
  tabs, tablas con filtros).
- **PDF:** Dompdf (via Composer). Si no esta instalado, el sistema entrega
  el reporte en HTML imprimible como respaldo automatico.
- **Excel:** PhpSpreadsheet (via Composer). Si no esta instalado, el
  sistema exporta automaticamente en CSV (abre igual en Excel).
- **Seguridad:** PDO con prepared statements en el 100% de las consultas,
  password hashing (`password_hash`/bcrypt), tokens CSRF en todos los
  formularios, sesiones con cookies `HttpOnly`/`SameSite`, bloqueo tras 5
  intentos fallidos de login, control de acceso por rol en cada
  controlador, `.htaccess` que bloquea el acceso directo a `app/`,
  `config/`, `database/` si el DocumentRoot se configura mal.

### Estructura de carpetas

```
/sistema
├── app/
│   ├── controllers/     Controladores (Auth, Dashboard, Case, Report, Export, User, Audit, Settings, Analytics)
│   ├── models/           CaseRecord, User, Catalog, Setting, AuditLog, CaseHistory
│   ├── helpers/           Database, Auth, Csrf, Helpers, View, FormRenderer
│   ├── services/          ExcelExporter
│   ├── Router.php
│   └── bootstrap.php
├── config/
│   ├── config.php          Carga .env
│   ├── form_sections.php   Campos del FURD generados desde form.xlsx
│   └── repeat_templates.php Plantillas de los grupos repetibles
├── database/
│   ├── schema.sql           Esquema completo + roles + estados
│   ├── seed_catalogs.sql    2.243 opciones de las 95 listas del Excel
│   ├── seed_admin.php       Crea el usuario administrador inicial
│   └── seed_demo.php        Carga datos de demostracion (is_demo = 1)
├── public/                  Document root del servidor
│   ├── index.php            Front controller
│   ├── .htaccess
│   ├── css/app.css
│   └── js/app.js
├── views/                   Vistas PHP (layout, login, dashboard, cases, reports, users, audit, settings)
├── storage/                 Logs, exportaciones temporales (con permisos de escritura)
├── composer.json
├── .env.example
└── README.md (este archivo)
```

---

## 3. Instalacion en servidor (Apache + PHP + MySQL)

### Requisitos

- PHP **8.2 o superior** con extensiones: `pdo_mysql`, `mbstring`, `json`,
  `mysqli` (opcional), `gd` (para Dompdf).
- MySQL 5.7+ o MariaDB 10.4+.
- Apache 2.4+ con `mod_rewrite` habilitado.
- Composer (para PDF y Excel con formato nativo; el sistema funciona sin
  el, con las alternativas en HTML/CSV descritas arriba).

### Paso a paso

1. **Subir el proyecto** al servidor (por ejemplo `/var/www/sistema`).

2. **Configurar el DocumentRoot de Apache** apuntando a la carpeta
   `public/` del proyecto (no a la raiz):

   ```apache
   <VirtualHost *:80>
       ServerName incidentes.miempresa.local
       DocumentRoot /var/www/sistema/public
       <Directory /var/www/sistema/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   Habilite `mod_rewrite`:
   ```bash
   a2enmod rewrite
   systemctl restart apache2
   ```

3. **Crear la base de datos:**
   ```sql
   CREATE DATABASE sistema_incidentes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'sistema_user'@'localhost' IDENTIFIED BY 'UnaClaveSegura#2026';
   GRANT ALL PRIVILEGES ON sistema_incidentes.* TO 'sistema_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. **Ejecutar el esquema y los catalogos:**
   ```bash
   mysql -u sistema_user -p sistema_incidentes < database/schema.sql
   mysql -u sistema_user -p sistema_incidentes < database/seed_catalogs.sql
   ```

5. **Configurar el archivo `.env`** (copie `.env.example` y edite):
   ```bash
   cp .env.example .env
   ```
   ```
   APP_NAME="Sistema de Gestion de Incidentes"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://incidentes.miempresa.local

   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sistema_incidentes
   DB_USERNAME=sistema_user
   DB_PASSWORD=UnaClaveSegura#2026
   ```

6. **Instalar dependencias de Composer** (PDF y Excel nativos):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   > Si el servidor no tiene Composer/Internet, el sistema sigue siendo
   > 100% funcional: los reportes se entregan en HTML imprimible y las
   > exportaciones en CSV.

7. **Crear el usuario administrador:**
   ```bash
   php database/seed_admin.php admin admin@miempresa.com "ClaveSegura#2026" "Administrador del Sistema"
   ```
   Guarde el usuario y contraseña que se muestran en pantalla.

8. **Permisos de escritura** en la carpeta `storage/`:
   ```bash
   chown -R www-data:www-data storage
   chmod -R 775 storage
   ```

9. **(Opcional) Cargar datos de demostracion** para ver el dashboard,
   graficos y reportes con informacion de ejemplo:
   ```bash
   php database/seed_demo.php 60
   ```
   Estos registros quedan marcados (`is_demo = 1`) y se pueden borrar en
   cualquier momento con:
   ```sql
   DELETE FROM cases WHERE is_demo = 1;
   ```

10. **Abrir el sistema:** `https://incidentes.miempresa.local/login` e
    ingresar con el usuario administrador creado en el paso 7.

---

## 4. Uso rapido

- **Casos → Nuevo Caso:** formulario dividido en pestañas (Informacion
  General, Edificaciones/Vehiculos, Incendios/Causa, Acciones, SCI,
  Evidencias, Personas, Animales, Personal, Gestion del Caso) que refleja
  el 100% de las secciones del FURD original.
- **Numeracion automatica:** cada caso recibe un consecutivo
  `CAS-2026-000001`, generado de forma segura (transaccion con bloqueo).
- **Dashboard:** KPIs en vivo, graficos por mes/estado/servicio/comuna,
  filtros rapidos (hoy, 7 dias, 30 dias, mes, año, rango personalizado).
- **Analitica:** tendencias, comparativos mes/año y porcentajes,
  **siempre calculados en tiempo real desde la base de datos** (nunca
  inventados).
- **Reportes:** por fechas, categoria, estado, responsable, ubicacion o
  personalizado (seleccionando que columnas incluir) → exporta a Excel.
- **Exportaciones:** exportacion rapida (todos, abiertos, cerrados, este
  mes) o con filtros personalizados.
- **Ficha individual + PDF:** boton "Generar Reporte" en cada caso
  produce un PDF con toda la informacion, historial y linea de tiempo.
- **Configuracion → Categorias:** edite cualquiera de las 95 listas
  desplegables (agregar, renombrar, desactivar opciones) sin tocar
  codigo.
- **Auditoria:** registra automaticamente login/logout, creacion/edicion/
  eliminacion de casos, generacion de reportes/backups, cambios de
  usuarios y de configuracion (usuario, fecha, IP, accion, entidad).

---

## 5. Alcance de esta entrega y siguientes pasos

Este sistema cubre de forma **completa y funcional** (no fragmentos):
base de datos, autenticacion y roles, CRUD de casos con los 249 campos
del formulario original, dashboard, analitica, reportes, exportacion a
Excel/CSV, generacion de PDF, gestion de usuarios, auditoria y
configuracion de catalogos.

Quedan como extensiones opcionales para una siguiente iteracion (no
bloquean el uso del sistema):

- **Instalador web (`/install`):** hoy la instalacion se hace por linea
  de comandos (pasos 4–7), lo cual es igual de rapido y mas seguro para
  un primer despliegue. Se puede agregar un asistente web si se requiere.
- **Importador de Excel** (`IMPORTAR DATOS`): el catalogo de categorias
  ya es editable desde Configuracion; un importador masivo de *casos*
  historicos desde Excel puede añadirse reutilizando `ExcelExporter`
  como referencia de mapeo de columnas.
- **Backup desde la interfaz:** ya implementado en Configuracion
  (`/settings/backup`) usando `mysqldump`; en hosting compartido sin
  acceso a `mysqldump`, generar el respaldo desde phpMyAdmin.

## 6. Credenciales de prueba

Tras ejecutar `database/seed_admin.php` como se indica en el paso 7,
use esas credenciales para el primer ingreso. Por seguridad, cree
inmediatamente un segundo usuario administrador nominal desde
**Usuarios** y considere desactivar la cuenta genérica `admin`.
