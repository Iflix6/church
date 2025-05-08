# Church Management System

<p align="center">
  <img width=200px height=200px src="https://i.imgur.com/6wj0hh6.jpg" alt="Church Management System Logo">
</p>

<div align="center">

[![Status](https://img.shields.io/badge/status-active-success.svg)]()
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)

</div>

---

<p align="center"> A comprehensive church management system designed to streamline church operations, member management, and administrative tasks.
    <br> 
</p>

## 📝 Table of Contents

- [About](#about)
- [Getting Started](#getting_started)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Built Using](#built_using)
- [Contributing](#contributing)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)

## 🧐 About <a name = "about"></a>

The Church Management System is a comprehensive web-based solution designed to help churches manage their operations efficiently. It provides tools for member management, event scheduling, resource management, and administrative tasks. The system aims to enhance communication between church staff and members while maintaining organized records and facilitating smooth church operations.

## 🏁 Getting Started <a name = "getting_started"></a>

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for PHP dependencies)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/Iflix6/church-management-system.git
```

2. Set up your web server to point to the project directory

3. Import the database schema:
```bash
mysql -u Iflix6 -p church_management < database.sql
```

4. Configure the database connection in `config/database.php`

5. Set up the required permissions for the uploads directory:
```bash
chmod 755 uploads/
```

## 🎯 Features <a name="features"></a>

- Member Management
  - Member registration and profiles
  - Attendance tracking
  - Family group management
  
- Administrative Tools
  - User roles and permissions
  - Resource management
  - Event scheduling
  
- Communication
  - Announcements
  - Event notifications
  - Member directory

## 🎈 Usage <a name="usage"></a>

1. Access the system through your web browser
2. Log in with your credentials
3. Navigate through the different modules using the sidebar menu
4. Use the admin panel for system configuration and management

## ⛏️ Built Using <a name = "PHP, JS, HTML, CSS, MYSQL"></a>

- [PHP](https://www.php.net/) - Server-side scripting
- [MySQL](https://www.mysql.com/) - Database
- [HTML5/CSS3](https://www.w3.org/) - Frontend structure and styling
- [JavaScript](https://www.javascript.com/) - Client-side functionality
- [Bootstrap](https://getbootstrap.com/) - Frontend framework

## ✍️ Authors <a name = "authors"></a>

- Israel Inakhe - Initial work

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- Thanks to all contributors who have helped shape this project
- Inspired by the need for efficient church management solutions
- Special thanks to the open-source community for their invaluable tools and resources
