# EventHub API

A robust RESTful API for managing events, built with Laravel and PostgreSQL. EventHub allows users to create, join, and manage events with comprehensive features for both physical and virtual gatherings.

## Features

- **User Authentication**

  - JWT-based authentication
  - Registration, login, and password reset
  - User profile management

- **Event Management**

  - Create, read, update, and delete events
  - Support for physical, virtual, and hybrid events
  - Event categorization and filtering
  - Location management with geocoding
  - Participant limits and age restrictions
  - Paid and free event options

- **Notifications**

  - Real-time notifications for event updates
  - Mark notifications as read/unread
  - Notification management

- **File Uploads**
  - Image upload and management
  - Cloud storage integration

## Tech Stack

- **Backend**: Laravel 11
- **Database**: PostgreSQL
- **Authentication**: JWT (JSON Web Tokens)
- **Deployment**: Docker, Railway, Render

## API Endpoints

### Authentication

- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - Login and get JWT token
- `POST /api/auth/logout` - Logout (invalidate token)
- `POST /api/auth/refresh` - Refresh JWT token
- `GET /api/auth/me` - Get authenticated user info
- `POST /api/auth/request-password-reset` - Request password reset
- `POST /api/auth/reset-password` - Reset password

### Events

- `GET /api/events` - List all events (with filtering options)
- `GET /api/events/{id}` - Get event details
- `POST /api/events` - Create a new event
- `PUT /api/events/{id}` - Update an event
- `DELETE /api/events/{id}` - Delete an event
- `POST /api/events/{id}/join` - Join an event
- `POST /api/events/{event}/leave` - Leave an event

### Notifications

- `GET /api/notifications` - Get user notifications
- `PATCH /api/notifications/{id}/read` - Mark notification as read
- `PATCH /api/notifications/read-all` - Mark all notifications as read

### Users

- `GET /api/users` - List all users
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

### Uploads

- `POST /api/uploads` - Upload a file
- `GET /api/uploads/{id}` - Get file details
- `DELETE /api/uploads/{id}` - Delete a file
- `GET /api/uploads/image/{id}` - Get image

## Installation

### Prerequisites

- PHP 8.3+
- Composer
- PostgreSQL
- Docker (optional)

### Local Setup

1. Clone the repository

   ```bash
   git clone https://github.com/osallak/Eventhub-api.git
   cd Eventhub-api
   ```

2. Install dependencies

   ```bash
   composer install
   ```

3. Set up environment variables

   ```bash
   cp .env.example .env
   # Edit .env with your database credentials and other settings
   ```

4. Generate application key

   ```bash
   php artisan key:generate
   ```

5. Run migrations

   ```bash
   php artisan migrate
   ```

6. Start the development server
   ```bash
   php artisan serve
   ```

### Docker Setup

1. Build and start containers

   ```bash
   docker-compose up -d
   ```

2. Run migrations
   ```bash
   docker-compose exec app php artisan migrate
   ```

## License

This project is licensed under the MIT License.
