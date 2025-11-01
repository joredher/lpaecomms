# LPA eComms

LPA eComms is a proof-of-concept e-commerce platform created to validate the user experience and functional requirements defined in the CTI capstone project brief. The application demonstrates a complete purchasing journey across the web, mobile, and desktop touchpoints described in the specification, providing a realistic environment for testing business processes and technical integrations.

## Key Features
- **Guided purchasing flows** that mirror the storyboard provided in the CTI documentation, including login, stock management, sales, and invoicing journeys.
- **Modular PHP architecture** with controllers, services, repositories, and middleware layers to keep business logic separated and maintainable.
- **Reusable UI components** (layouts, navigation, and forms) that ensure a consistent look and feel across the different mock storefront experiences.
- **Database-backed entities** for products, clients, and transactions so the team can validate real data interactions during assessments.

## Technology Stack
- PHP 8+ with Composer for dependency management
- Custom lightweight MVC-style structure (no full-stack framework required)
- MySQL or MariaDB for persistence
- Bootstrap for rapid UI prototyping

## Getting Started
1. Clone the repository and install PHP dependencies:
   ```bash
   composer install
   ```
2. Configure your environment variables (database credentials, app URL, etc.) in `.env` or the appropriate configuration files under `includes/`.
3. Run the database migrations or import the provided schema in `db/`.
4. Serve the project with your preferred PHP web server (e.g., `php -S localhost:8000 -t public`).

## Project Goals
This repository exists to support usability studies and requirement validation for the CTI assessment. The goal is to iterate quickly on feedback, refine user stories, and ensure every required workflow is implemented before transitioning to a production-ready solution.

## Contributing
Contributions that improve documentation, clarify assessment workflows, or enhance the demo features are welcome. Please open an issue describing the proposed change before submitting a pull request.
