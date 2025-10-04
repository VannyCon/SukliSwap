# SukliSwap - Coin Exchange Platform

SukliSwap is a community-driven coin exchange platform that connects small business owners and community members to facilitate coin swapping through a secure, location-based matching system.

## Features

### For Users (Community & Small Business Owners)
- **User Registration & Authentication** - Secure account creation and login
- **Coin Requests** - Request specific coin denominations you need
- **Coin Offers** - Offer coins you have in excess
- **Location-Based Matching** - Find nearby users for easy meetups
- **QR Code Transactions** - Secure transaction completion with QR codes
- **Rating System** - Rate and review other users
- **Real-time Notifications** - Get notified of matches and transaction updates
- **Transaction History** - Track all your coin exchanges
- **Profile Management** - Manage your business information and preferences

### For Admins
- **User Management** - Manage user accounts and permissions
- **Transaction Monitoring** - Oversee all platform transactions
- **Report Management** - Handle user reports and disputes
- **Analytics Dashboard** - View platform statistics and trends
- **System Settings** - Configure platform parameters
- **Activity Logging** - Monitor system activity and user actions

## Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Maps**: MapLibre GL JS for location services
- **Authentication**: JWT (JSON Web Tokens)
- **HTTP Client**: Axios for API communication
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome

## Installation

### Prerequisites
- XAMPP/WAMP/LAMP stack
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   git clone <repository-url>
   cd sukliswap
   ```

2. **Database Setup**
   - Create a new MySQL database named `sukliswap_db`
   - Import the SQL schema from `database/sukliswap_schema.sql`
   - Update database credentials in `connection/config.php`

3. **Configure the application**
   - Update the database connection settings in `connection/config.php`
   - Set the correct URL and file paths for your environment

4. **Install dependencies**
   ```bash
   composer install
   ```

5. **Set up the web server**
   - Point your web server document root to the project directory
   - Ensure the `data/` directory is writable for file uploads

6. **Access the application**
   - Navigate to `http://localhost/sukliswap/`
   - Register a new account or use the default admin account

## Database Schema

The system includes the following main tables:

- **tbl_users** - User accounts and authentication
- **tbl_user_profiles** - Extended user information and business details
- **tbl_coin_types** - Available coin denominations
- **tbl_coin_requests** - User coin requests
- **tbl_coin_offers** - User coin offers
- **tbl_matches** - System-generated matches between requests and offers
- **tbl_transactions** - Completed transactions with QR codes
- **tbl_notifications** - User notifications
- **tbl_messages** - User-to-user messaging
- **tbl_reports** - User reports and disputes
- **tbl_system_settings** - Platform configuration
- **tbl_activity_logs** - System activity logging
- **tbl_analytics_summary** - Analytics data

## API Endpoints

### Authentication
- `POST /auth/auth.php` - User login/register
- `GET /auth/auth.php?action=validate` - Validate token

### Coin Exchange
- `GET /api/coin_exchange.php?action=getCoinTypes` - Get available coin types
- `GET /api/coin_exchange.php?action=getActiveRequests` - Get active requests
- `GET /api/coin_exchange.php?action=getActiveOffers` - Get active offers
- `POST /api/coin_exchange.php` - Create/update requests and offers
- `GET /api/coin_exchange.php?action=getMyMatches` - Get user matches
- `POST /api/coin_exchange.php` - Accept matches and complete transactions

### User Profile
- `GET /api/user_profile.php?action=getProfile` - Get user profile
- `POST /api/user_profile.php` - Update profile
- `GET /api/user_profile.php?action=getNotifications` - Get notifications
- `POST /api/user_profile.php` - Send messages and rate users

### Admin
- `GET /api/admin.php?action=getDashboardStats` - Get dashboard statistics
- `GET /api/admin.php?action=getAllUsers` - Get all users
- `POST /api/admin.php` - Manage users and transactions
- `GET /api/admin.php?action=getSystemSettings` - Get system settings

## Usage Examples

### Creating a Coin Request
```javascript
const formData = new FormData();
formData.append('action', 'createCoinRequest');
formData.append('coin_type_id', '2'); // ₱5 coins
formData.append('quantity', '10');
formData.append('preferred_meeting_location', 'SM Mall, Quezon City');
formData.append('meeting_latitude', '14.5995');
formData.append('meeting_longitude', '120.9842');

const response = await axios.post('api/coin_exchange.php', formData, {
    headers: authManager.API_CONFIG.getHeaders()
});
```

### Accepting a Match
```javascript
const formData = new FormData();
formData.append('action', 'acceptMatch');
formData.append('match_id', '123');

const response = await axios.post('api/coin_exchange.php', formData, {
    headers: authManager.API_CONFIG.getHeaders()
});
```

### Completing a Transaction
```javascript
const formData = new FormData();
formData.append('action', 'completeTransaction');
formData.append('qr_code', 'SUKLI_abc123_1234567890');

const response = await axios.post('api/coin_exchange.php', formData, {
    headers: authManager.API_CONFIG.getHeaders()
});
```

## Map Integration

The system uses MapLibre GL JS for location-based features:

- **Interactive Map** - View requests and offers on a map
- **Location Services** - Get user's current location
- **Radius Search** - Find users within a specified radius
- **Meeting Point Selection** - Choose meeting locations on the map

## Security Features

- **JWT Authentication** - Secure token-based authentication
- **Input Validation** - Server-side validation of all inputs
- **SQL Injection Prevention** - Prepared statements for database queries
- **XSS Protection** - Output encoding and sanitization
- **CSRF Protection** - Token-based request validation
- **Rate Limiting** - API rate limiting to prevent abuse

## File Structure

```
sukliswap/
├── api/                    # API endpoints
│   ├── admin.php          # Admin API
│   ├── coin_exchange.php  # Coin exchange API
│   └── user_profile.php   # User profile API
├── auth/                  # Authentication
├── connection/            # Database connection
├── database/              # Database schema
├── middleware/            # Authentication middleware
├── services/              # Business logic services
├── view/                  # Frontend files
│   ├── components/        # Reusable components
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── pages/            # Page templates
└── data/                 # User uploads and data
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions, please contact the development team or create an issue in the repository.

## Changelog

### Version 1.0.0
- Initial release
- User registration and authentication
- Coin request and offer system
- Location-based matching
- QR code transactions
- Admin dashboard
- Map integration
- Rating system
- Notification system

## Roadmap

- [ ] Mobile app development
- [ ] Push notifications
- [ ] Advanced analytics
- [ ] Multi-language support
- [ ] Payment integration
- [ ] Social features
- [ ] API documentation
- [ ] Automated testing
- [ ] Performance optimization