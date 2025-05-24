# DirectAdmin Email Forwarder Manager

A PHP web application for managing email forwarders through the DirectAdmin API on arrow.mxrouting.net.

## Features

- **Secure Login**: Authenticate with your DirectAdmin username and password
- **Persistent Sessions**: Stay logged in until logout or session timeout (1 hour)
- **Domain Selection**: Choose from all domains in your DirectAdmin account
- **Email Forwarder Management**: 
  - View all email forwarders for a selected domain
  - Add new email forwarders
  - Edit existing forwarders
  - Delete forwarders with confirmation

## Requirements

- PHP 7.0 or higher
- cURL extension enabled
- Web server (Apache, Nginx, etc.)

## Installation

1. Upload all files to your web server directory
2. Ensure your web server has write permissions for session management
3. Access the application through your web browser

## File Structure

```
daforwarders/
├── config.php              # Configuration and session management
├── DirectAdminAPI.php       # DirectAdmin API wrapper class
├── index.php               # Login page
├── dashboard.php           # Main dashboard with domain selection
├── manage_forwarder.php    # Add/edit/delete forwarders
└── README.md              # This documentation
```

## Usage

1. **Login**: Enter your DirectAdmin username and password on the login page
2. **Select Domain**: Choose a domain from the dropdown on the dashboard
3. **Manage Forwarders**: 
   - View existing forwarders in the table
   - Click "Add New Forwarder" to create a new one
   - Click "Edit" to modify an existing forwarder
   - Click "Delete" to remove a forwarder (with confirmation)
4. **Logout**: Click the logout button in the header to end your session

## Security Features

- Session-based authentication with automatic timeout
- CSRF protection through session validation
- Input validation and sanitization
- SQL injection prevention (no direct database queries)
- XSS prevention through proper HTML escaping

## Configuration

The application is pre-configured for arrow.mxrouting.net but you can modify `config.php` to change:

- DirectAdmin server hostname
- Port number (default: 2222)
- Protocol (default: https)
- Session timeout (default: 1 hour)

## API Endpoints Used

The application uses the following DirectAdmin API commands:

- `CMD_API_SHOW_USER_CONFIG` - Validate user credentials
- `CMD_API_SHOW_DOMAINS` - Get list of domains
- `CMD_API_EMAIL_FORWARDERS` - Manage email forwarders

## Troubleshooting

- **Login fails**: Verify your DirectAdmin credentials and ensure the server is accessible
- **Domains not loading**: Check if your account has proper permissions to view domains
- **Forwarders not saving**: Ensure your account has email management permissions
- **Session timeout**: Sessions expire after 1 hour of inactivity for security

## Support

This application is designed specifically for MXrouting customers using the arrow.mxrouting.net DirectAdmin server. For DirectAdmin-related issues, contact MXrouting support.