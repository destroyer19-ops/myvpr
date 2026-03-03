# Virtual Praise Room

This is a web-based virtual meeting platform that allows users to create, join, and manage online meetings. The platform is built using PHP, MySQL, and the Jitsi Meet API.

## Features

*   User registration and login
*   Create and join meetings
*   Real-time video and audio conferencing
*   Screen sharing
*   Chat
*   Participant management

## Project Structure

```
/virtual-praise-room
├── /includes
│   └── db.php
├── /logs
├── create_meeting.php
├── database.sql
├── end_meeting.php
├── index.php
├── join.php
├── login.php
├── logout.php
├── meeting-room.php
├── register.php
└── start_meeting.php
```

*   `/includes`: Contains the database connection and other helper functions.
*   `/logs`: Contains the database error logs.
*   `create_meeting.php`: Creates a new meeting.
*   `database.sql`: Contains the database schema.
*   `end_meeting.php`: Ends a meeting.
*   `index.php`: The home page.
*   `join.php`: Joins a meeting.
*   `login.php`: The login page.
*   `logout.php`: Logs the user out.
*   `meeting-room.php`: The meeting room.
*   `register.php`: The registration page.
*   `start_meeting.php`: Starts a meeting.

## Setup

1.  Clone the repository.
2.  Create a new MySQL database.
3.  Import the `database.sql` file into your database.
4.  Update the `includes/db.php` file with your database credentials.
5.  Start the application.

## Contributing

Contributions are welcome! Please feel free to submit a pull request.
# myvpr
