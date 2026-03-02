Here is a version written from your perspective, giving it a much more personal, authentic, and developer-to-developer tone.

---

# KnowIsaack - My Developer Portfolio

Welcome to the source code for my personal developer portfolio! 👋

Instead of using a traditional database or a heavy framework, I built this using **PHP** and **Vanilla JavaScript**, and I'm using the **GitHub API as a headless CMS** to manage my content.

It also includes a custom-built, secure admin dashboard so I can add or update my projects on the fly without having to touch the codebase.

## 🚀 What I Built (Key Features)

* **GitHub API as a Headless CMS:** I keep my project data in a JSON file inside a private GitHub repo. The portfolio fetches and displays this dynamically, meaning I don't need a traditional SQL database to manage my content.
* **Custom Admin Dashboard:** I built a protected `/admin` route that lets me perform full CRUD operations (Create, Read, Update, Delete) on my portfolio projects directly from the browser.
* **GitHub OAuth Authentication:** To make sure nobody else can mess with my portfolio, the admin panel is locked down. It authenticates via GitHub OAuth and verifies against my specific username.
* **Working Contact Form:** I integrated the `resend/resend-php` SDK so that any messages sent through the contact form land straight in my inbox.
* **Vanilla JS Components:** I didn't want the overhead of React/Vue for this, so I wrote a custom Vanilla JS component loader to dynamically inject reusable UI parts (like the navbar and footer).
* **Modern, Responsive UI:** Built from scratch using CSS grid/flexbox, complete with scroll-reveal animations, custom mesh gradients, and interactive mouse-tracking spotlight effects on the project cards.
* **Fully Dockerized:** I set up Docker and Docker Compose to make local development completely frictionless.

## 🛠️ The Tech Stack

Here is what I used to bring this to life:

**Frontend:**

* HTML5 & CSS3 (Custom variables, animations)
* Vanilla JavaScript (ES6+, DOM Manipulation, Fetch API)
* FontAwesome (Icons)

**Backend:**

* PHP 8.2 (Using a custom MVC-like routing architecture)
* Composer (For dependency management)

**Infrastructure & APIs:**

* Docker & Docker Compose (`php:8.2-apache`)
* GitHub REST API (For OAuth & File Storage)
* Resend API (For reliable email delivery)

## 📁 How I Organized the Code

```text
├── backend/
│   ├── Controllers/      # Handles API routes, GitHub Auth, and Form submissions
│   ├── Domain/           # Interfaces (e.g., EmailServiceInterface)
│   ├── Infrastructure/   # Mailer implementations (ResendMailer)
│   ├── config/           # App configuration and route guards
│   └── routes.php        # Centralized router for the whole app
├── frontend/
│   ├── assets/           # Images, graphics, and static media
│   ├── components/       # Reusable HTML partials (navbar.html, footer.html)
│   ├── css/              # Global styles, variables, and animations
│   ├── js/               # My custom component loader, animations, and API logic
│   └── pages/            # Public pages and Admin dashboard views
├── .env.example          # Template for required environment variables
├── docker-compose.yml    # Docker services configuration
├── Dockerfile            # PHP Apache image setup
└── index.php             # The main entry point

```

## ⚙️ Want to run it locally?

If you want to spin this up on your own machine, here's how to do it.

### Prerequisites

* [Docker](https://www.docker.com/) and Docker Compose installed.
* A [GitHub OAuth App](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/creating-an-oauth-app) configured.
* A [Resend](https://resend.com/) API Key.

### 1. Clone the repo

```bash
git clone https://github.com/kirrer-izo/knowisaack.git
cd knowisaack

```

### 2. Set up your environment variables

Copy the example `.env` file and plug in your own credentials:

```bash
cp .env.example .env

```

Update `.env` with your specific keys:

```ini
RESEND_API_KEY=your_resend_api_key

# GITHUB OAUTH APP CREDENTIALS
GITHUB_CLIENT_ID=your_oauth_client_id
GITHUB_CLIENT_SECRET=your_oauth_client_secret
GITHUB_PERSONAL_ACCESS_TOKEN=your_pat_with_repo_access
GITHUB_USERNAME=your_github_username
GITHUB_REPO=your_private_repo_name
GITHUB_FILE_PATH=projects.json
SESSION_SECRET=a_random_secure_string
OAUTH_CALLBACK_URL=http://localhost:8080/auth/callback

```

### 3. Build and Run

Fire it up using Docker Compose (Docker will automatically install the Composer dependencies during the build):

```bash
docker-compose up -d --build

```

### 4. Check it out

* **Public Site:** `http://localhost:8080/`
* **Admin Login:** `http://localhost:8080/auth/login`

## 🔒 How the Auth Flow Works

If you're curious about how I secured the `/admin` dashboard: when you click login, the app kicks off an OAuth 2.0 flow with GitHub. Once authorized, my PHP backend checks the authenticated `GITHUB_USERNAME` against the one stored in the `.env` file. If they match, a secure native PHP session is created. If not, access is denied.

## 📝 License

Feel free to fork this or use it as inspiration! It's open-source and available under the [MIT License](https://www.google.com/search?q=LICENSE).
