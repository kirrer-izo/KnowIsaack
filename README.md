# KnowIsaack - My Developer Portfolio

Welcome to the source code for my personal developer portfolio! 👋

This project is a full-stack PHP application that has evolved from a GitHub-based CMS to a robust, self-hosted architecture. It uses **PostgreSQL** for data persistence and features a custom-built administrative ecosystem.

## 🚀 Key Features

* **PostgreSQL Backend**: All project data, user accounts, and system logs are stored in a relational PostgreSQL database, replacing the previous headless CMS approach.
* **Comprehensive Admin Dashboard**: A secure `/admin` area providing full CRUD capabilities for projects and users, along with real-time system monitoring.
* **Advanced Authentication**:
    * **GitHub OAuth**: Secure login integration for administrative access.
    * **Native Auth**: Complete user lifecycle management including registration, email verification, and password resets.
    * **Session Management**: Implementation of "Remember Me" functionality using database-backed tokens.
* **System Security & Monitoring**:
    * **Rate Limiting**: Integrated service to prevent brute-force attacks on sensitive endpoints.
    * **Audit Logs**: Detailed tracking of login activities and system events.
* **Automated Emailing**: Integration with the **Resend PHP SDK** for contact form submissions and transactional system emails.
* **Modern Frontend**: A responsive UI built with Vanilla JavaScript and CSS, featuring a custom component loader for modularity.

## 🛠️ The Tech Stack

**Backend:**
* **PHP 8.2**: Custom MVC-like architecture with a regex-based router.
* **PostgreSQL 16**: Relational database for all application data.
* **PDO**: Secure database interactions using a Singleton connection pattern.
* **Composer**: Dependency management for libraries like `resend-php` and `phpmailer`.

**Frontend:**
* **Vanilla JavaScript**: ES6+ modules for dynamic UI updates and API interaction.
* **HTML5 & CSS3**: Modern layouts using Grid and Flexbox with custom animations.

**Infrastructure:**
* **Docker & Docker Compose**: Containerized environment ensuring consistency across development and production.
* **Apache**: Web server configuration for clean URL routing.

## 📁 Project Structure

```text
├── backend/
│   ├── Controllers/      # Handles Auth, Admin, Projects, and User logic
│   ├── Domain/           # Interfaces and abstractions
│   ├── Infrastructure/   # DB connections, Repositories, and Mailer implementations
│   ├── Services/         # Business logic (Rate limiting, Auth tokens, User services)
│   ├── config/           # App configuration and Route Guards
│   └── routes.php        # Centralized router for all API and Page requests
├── frontend/
│   ├── assets/           # Images and static media
│   ├── components/       # Reusable HTML partials (navbar, footer)
│   ├── css/              # Global styles and animations
│   ├── js/               # Frontend logic and component loaders
│   └── pages/            # Public and Administrative views
├── docker-compose.yml    # Docker services (PHP & PostgreSQL)
├── Dockerfile            # Custom PHP-Apache image
└── index.php             # Main application entry point
