# Sistema de Gestión de Eventos Académicos y Certificaciones — UAB Eventos

Plataforma web integral para la administración, control de cupos en tiempo real, registro de asistencia por sesiones y emisión criptográfica de certificados académicos. Desarrollada bajo el patrón arquitectónico **MVC (Modelo-Vista-Controlador)** en **PHP puro orientado a objetos** y **MySQL**, con una interfaz moderna y adaptativa implementada en **Bootstrap 5.3**.

---

## Características Principales

* **Control de Acceso Basado en Roles (RBAC):** Espacios de trabajo y permisos independientes para `ADMINISTRADOR`, `EXPOSITOR` y `PARTICIPANTE`.
* **Transaccionalidad y Bloqueo Pesimista:** Control de cupos con concurrencia segura mediante `SELECT ... FOR UPDATE` en MySQL para evitar sobreinscripciones simultáneas.
* **Acreditación Criptográfica y Verificación Pública:** Emisión automatizada de certificados con folios y códigos únicos verificables mediante consulta pública o código QR sin requerir inicio de sesión.
* **Asistencia Modular por Sesiones:** Registro de presencias por fecha y hora con recálculo automático del porcentaje de asistencia y validación contra el umbral mínimo del evento.
* **Motor Analítico y Reportería:** Exportación de padrones, listas de asistencia y eventos a archivos CSV compatibles con Microsoft Excel mediante streaming HTTP y codificación UTF-8 BOM.
* **Diseño Responsivo e Institucional:** Interfaz adaptable a pantallas móviles y tablets (`offcanvas-lg`), navegación horizontal superior en tono oscuro (`#0b0f19`) y panel lateral de navegación vertical optimizado.

---

## Stack Tecnológico

| Componente | Detalle Técnico |
| :--- | :--- |
| **Lenguaje Backend** | PHP 8.1+ (Tipado estricto, Programación Orientada a Objetos) |
| **Base de Datos** | MySQL 8.0 / MariaDB 10.4+ (Motor InnoDB, Llaves Foráneas) |
| **Capa Frontend** | HTML5, Bootstrap 5.3, Bootstrap Icons |
| **Tipografía** | Google Fonts (*Poppins* y *Open Sans*) |
| **Arquitectura** | MVC Nativo + Front Controller + Singleton PDO |
| **Seguridad** | Hashing con `Bcrypt` (`password_hash`), Sentencias Preparadas contra SQLi |

---

## Estructura del Repositorio

```text
gestion_eventos/
├── config/
│   └── Database.php                 # Conexión persistente PDO (Patrón Singleton)
├── controllers/
│   ├── AdminController.php          # KPIs, gestión de usuarios, roles y auditoría
│   ├── AuthController.php           # Login, registro, sesiones y redirección por rol
│   ├── CertificadoController.php     # Generación, impresión y verificación de certificados
│   ├── EventoController.php         # Catálogo público, detalle e inscripción transaccional
│   ├── ExpositorController.php      # Sesiones asignadas y control de asistencia
│   └── ReporteController.php        # Consultas analíticas y exportación nativa a CSV
├── helpers/
│   └── AuthHelper.php               # Middleware de autenticación y verificación RBAC
├── models/
│   ├── Asistencia.php               # Registro de sesiones y cálculo de porcentajes
│   ├── Auditoria.php                # Bitácora inmutable de eventos del sistema
│   ├── Certificado.php              # Folios criptográficos y validación de autenticidad
│   ├── Evento.php                   # Consultas de eventos y cupos disponibles
│   ├── Inscripcion.php              # Transacciones con bloqueo pesimista de plazas
│   ├── SolicitudReimpresion.php     # Flujo administrativo de solicitudes de reimpresión
│   └── Usuario.php                  # Perfiles, contraseñas y estado de cuentas
├── public/
│   ├── assets/
│   │   └── css/
│   │       └── styles.css           # Hoja de estilos institucional unificada
│   └── index.php                    # Front Controller y enrutador central
├── views/
│   ├── admin/                       # Dashboard, gestión de usuarios, eventos y reportes
│   ├── auth/                        # Vistas de autenticación y registro
│   ├── expositor/                   # Planillas de asistencia por sesión
│   ├── layouts/                     # Header, navbar, footer y sidebar responsivo
│   ├── participante/                # Dashboard de convocatorias y mis inscripciones
│   ├── publico/                     # Catálogo general y verificación de folios
│   └── usuario/                     # Perfil personal y cambio de contraseña
├── index.php                        # Redireccionador raíz hacia public/index.php
└── README.md                        # Documentación técnica del proyecto