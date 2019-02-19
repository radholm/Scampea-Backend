# Laravel 5.5 PHP Framework

## Intallation

### Prerequisite

* [XAMPP (PHP and MySQL)](https://www.apachefriends.org/download.html)
* [Composer](https://getcomposer.org/download/)

### Actual installation
```bash
# Clone the project

# Go to the cloned folder
cd Scampea-Backend

# Copy ".env.example" and name the copy ".env"
cp .env.example .env

# Update the database info in the ".env" file

# Install Laravel and its dependencies
composer install

# Update the project key
php artisan key:generate

# Create the database tables
php artisan migrate

# You can now start the development server
php artisan serve
```

### Start the development server
```bash
cd Scampea-Backend
php artisan serve
```

### Seed database
```bash
# Create dummy data in the database
php artisan migrate:refresh --seed

# Create login secrets
php artisan passport:install
```

### Run tests
```bash
./vendor/bin/phpunit
```

## Official Documentation

Documentation for the framework can be found on the [Laravel website](http://laravel.com/docs).
