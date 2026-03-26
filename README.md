# La Maison Reservations

A restaurant reservation system for "La Maison" restaurant built with Symfony 7, featuring both a public reservation page and an admin dashboard for managing reservations.

## Overview

This application allows restaurant guests to make reservations online and provides restaurant staff with tools to manage bookings efficiently. The system supports both regular dining and private dining reservations with different capacity rules and availability.

## Features

- **Public Reservation System**: Guest-facing form for making reservations
- **Admin Dashboard**: Manage and view all reservations with filtering options
- **Dual Dining Modes**: Regular dining (1-20 guests) and Private dining (6-12 guests)
- **Smart Capacity Management**: Automatic slot availability based on current bookings
- **Reference Code System**: Unique codes (LM-XXXXX) for each reservation
- **Status Tracking**: Pending, Confirmed, Cancelled, Completed states
- **Responsive Design**: Built with Bootstrap and modern CSS

## Requirements

- Docker 20.10+
- Docker Compose 2.0+
- (Optional) PHP 8.3+ and Composer if running outside Docker

## Prerequisites

Make sure you have Docker and Docker Compose installed:

```bash
docker --version   # Should be 20.10+
docker compose version  # Should be 2.0+
```

## Quick Start

The easiest way to get started is to use the setup script:

```bash
chmod +x setup.sh
./setup.sh
```

This script will:
1. Create `.env.local` with default configuration
2. Start Docker containers (PHP 8.4, MySQL 8.0, phpMyAdmin)
3. Install Composer dependencies
4. Create the database
5. Run migrations
6. Load fixtures (seed time slots and table configuration)
7. Compile assets
8. Clear cache

After setup completes, the application will be available at:
- **Web Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8090 (root / pass1234)

## Manual Setup (Alternative)

If you prefer to run commands manually instead of using the setup script:

1. **Create environment file:**

    ```bash
    cp .env .env.local
    # Edit .env.local and set DATABASE_URL
    ```

2. **Start Docker containers:**

    ```bash
    docker compose up -d --build
    ```

3. **Install dependencies:**

    ```bash
    docker compose exec php composer install
    ```

4. **Create database and run migrations:**

    ```bash
    docker compose exec php bin/console doctrine:database:create
    docker compose exec php bin/console doctrine:migrations:migrate
    ```

5. **Load initial data (fixtures):**

    ```bash
    docker compose exec php bin/console doctrine:fixtures:load
    ```

6. **Compile assets:**

    ```bash
    docker compose exec php bin/console asset-map:compile
    ```

## Restaurant Configuration

The restaurant's operating rules are configured in `config/packages/restaurant.yaml`:

### Key Settings

- **Operating Hours**: 12:00 - 22:00
- **Last Reservation**: 21:00 (kitchen closes 1 hour before closing)
- **Time Slot Interval**: 30 minutes
- **Booking Window**: Up to 30 days in advance

### Regular Dining
- **Capacity**: 20 guests per time slot
- **Party Size**: 1-10 guests
- **Days**: Monday to Sunday
- **Hours**: 12:00 - 21:00

### Private Dining
- **Capacity**: 1 reservation per time slot
- **Party Size**: 6-12 guests
- **Days**: Friday and Saturday only
- **Hours**: 18:00 - 21:00


### Initial Data Seeding

Time slots and table configuration are **stored in the database** and automatically seeded during setup:

**Time Slots:**
- 19 regular dining slots (12:00 - 21:00, 30-minute intervals)
- 7 private dining slots (18:00 - 21:00, 30-minute intervals)

This data is loaded via Doctrine Fixtures (see `src/DataFixtures/`) and can be customized by:
1. Modifying fixture class
2. Running: `docker compose exec php bin/console doctrine:fixtures:load`

The application also uses dynamic slot generation from `config/packages/restaurant.yaml` as a fallback.

## Available Commands

Convenience scripts are provided in the project root:

```bash
./setup.sh              # Initial project setup
./up.sh                 # Start Docker containers
./down.sh               # Stop Docker containers
./test.sh               # Run PHPUnit tests
./clear-cache.sh        # Clear Symfony cache
./migrations-migrate.sh # Run database migrations
./asset-map-compile.sh  # Compile frontend assets
./qa.sh                 # Run code quality checks (PHPStan, PHP-CS-Fixer)
```

## Running Tests

Run the PHPUnit test suite:

```bash
./test.sh
# Or directly:
docker compose exec php bin/phpunit
```

Tests cover:
- Reservation validation rules
- Capacity checking logic
- Private dining availability
- Date and time slot validation
- TimeSlot fixtures

## Accessing the Application

### Public Reservation Page
Visit: http://localhost:8080

Features:
- Make a reservation
- Select date and time
- Choose regular or private dining (if applicable)
- Receive unique reference code

### Admin Dashboard
Visit: http://localhost:8080/admin

Features:
- View all reservations
- Filter by date and status
- See total expected guests
- Identify fully-booked slots
- Update reservation status

(Note: Authentication is not implemented per specification)

### Database Management (phpMyAdmin)
Visit: http://localhost:8090

Credentials: `root` / `pass1234`

You can inspect:
- `reservation` table - All reservations
- `time_slot` table - Seeded time slots (26 records)
- `user` table - Admin users (not implemented)

## Notes

- **Time slots** and **table configuration** are seeded in the database during setup
- **Private dining** is only available Friday/Saturday 18:00-21:00
- **Regular dining** capacity is 20 guests per slot (not 20 reservations)
- **Private dining** capacity is 1 reservation per slot (6-12 guests)
- **Reference codes** are automatically generated in format `LM-XXXXX`
- **Cancelled reservations** don't count toward capacity

## License

This project is licensed under the MIT License.

## Acknowledgements

- [Symfony](https://symfony.com/) - PHP framework
- [Doctrine](https://www.doctrine-project.org/) - ORM
- [Bootstrap](https://getbootstrap.com/) - CSS framework
- [Docker](https://www.docker.com/) - Containerization