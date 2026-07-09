# Sistema de Gestión de Asistencias

Aplicación para el registro, supervisión y reporte de asistencias de personal de seguridad, construida con Laravel 13, Inertia.js y Vue 3.

## Requisitos

- PHP 8.3 o superior, con extensiones: `mbstring`, `pdo_mysql`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`
- Composer 2.x
- Node.js 20 LTS o superior y npm
- MySQL 8.x (o MariaDB 10.6+)
- Servidor web (Nginx recomendado en producción; WAMP/`php artisan serve` en desarrollo)

## Instalación local

```bash
git clone <url-del-repositorio> Asistencias
cd Asistencias

composer install
npm install

cp .env.example .env
php artisan key:generate
```

## Configuración de `.env`

Edita el archivo `.env` con los datos de tu entorno. Los valores mínimos a revisar:

```env
APP_NAME="Sistema de Gestión de Asistencias"
APP_URL=http://localhost
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asistencias
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base de datos vacía antes de migrar:

```sql
CREATE DATABASE asistencias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Migraciones y seeders

```bash
php artisan migrate --seed
```

Esto crea el esquema completo (usuarios, roles y permisos, empresas, puntos de servicio, turnos, colaboradores, asistencias, eventos y auditoría) y ejecuta el `RolesAndPermissionsSeeder`.

### Usuario administrador inicial

El seeder crea automáticamente un usuario administrador:

- **Email:** `admin@asistencias.com`
- **Contraseña:** `Admin2024!`

Cambia esta contraseña inmediatamente después del primer inicio de sesión en un entorno productivo.

## Comandos npm

```bash
npm run dev      # servidor de desarrollo de Vite con recarga en caliente
npm run build    # compilación de producción (assets en public/build)
npm run lint     # ESLint con autofix
npm run format   # Prettier
```

Para levantar backend, worker de colas y Vite juntos en desarrollo:

```bash
composer run dev
```

## Comandos de producción

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Tras cualquier despliegue nuevo, limpia y regenera cachés:

```bash
php artisan optimize:clear
php artisan optimize
```

## Configuración de VPS con Nginx

Ejemplo de bloque de servidor (ajusta dominio y rutas):

```nginx
server {
    listen 80;
    server_name asistencias.midominio.com;
    root /var/www/Asistencias/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Habilita HTTPS con Certbot (Let's Encrypt):

```bash
sudo certbot --nginx -d asistencias.midominio.com
```

Permisos recomendados para `storage` y `bootstrap/cache`:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Scheduler

Laravel necesita una única entrada de cron que dispare el scheduler cada minuto:

```cron
* * * * * cd /var/www/Asistencias && php artisan schedule:run >> /dev/null 2>&1
```

## Queue worker

Las asistencias, reportes y correos usan la cola `database`. En producción, ejecútala con Supervisor para que se reinicie automáticamente:

```ini
[program:asistencias-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/Asistencias/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/Asistencias/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start asistencias-worker:*
```

## Respaldos básicos

Respaldo diario de base de datos vía cron (ajusta credenciales y ruta de destino):

```bash
0 3 * * * mysqldump -u root -p'TU_PASSWORD' asistencias | gzip > /var/backups/asistencias/db_$(date +\%Y\%m\%d).sql.gz
```

Conserva también respaldo del directorio `storage/app` (archivos subidos, imports) y del archivo `.env`:

```bash
tar -czf /var/backups/asistencias/storage_$(date +%Y%m%d).tar.gz storage/app
```

Recomendado: retener al menos 14 días de respaldos y verificar periódicamente que el archivo restaurado sea íntegro (`mysql < backup.sql`).
