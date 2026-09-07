# NaduFind - Lost and Found Management System

A comprehensive web-based platform for reporting, searching, and managing lost and found items with secure user authentication, admin verification, and status tracking.

## Features

✨ **User Authentication**
- Secure registration and login system
- Password encryption and session management

📝 **Item Reporting**
- Report lost and found items with detailed information
- Upload photos for items
- Category classification

🔍 **Search & Filter**
- Advanced search functionality
- Filter by category, location, date range, and status
- Real-time search results

📋 **Claims Management**
- Users can submit claims for found items
- Admin verification of claims
- Claim status tracking

👨‍💼 **Admin Dashboard**
- Verify and manage item reports
- Review and approve claims
- User and item statistics
- System management tools

📊 **Status Tracking**
- Lost, Found, Claimed, Returned status options
- Real-time status updates
- History tracking

💾 **Database Management**
- Organized database for item records
- User profile management
- Audit logging

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Backend:** Node.js, Express.js
- **Database:** MongoDB
- **Authentication:** JWT, bcrypt
- **File Upload:** Multer

## Project Structure

```
NaduFind-LostAndFound/
├── frontend/
│   ├── index.html
│   ├── css/
│   │   ├── style.css
│   │   └── responsive.css
│   ├── js/
│   │   ├── auth.js
│   │   ├── app.js
│   │   └── dashboard.js
│   └── images/
├── backend/
│   ├── server.js
│   ├── config/
│   │   └── db.js
│   ├── models/
│   │   ├── User.js
│   │   ├── Item.js
│   │   └── Claim.js
│   ├── routes/
│   │   ├── auth.js
│   │   ├── items.js
│   │   └── claims.js
│   ├── middleware/
│   │   └── auth.js
│   └── controllers/
│       ├── authController.js
│       ├── itemController.js
│       └── claimController.js
├── .gitignore
├── .env.example
└── package.json
```

## Getting Started

### Prerequisites
- Node.js (v14 or higher)
- MongoDB
- npm or yarn

### Installation

1. Clone the repository
```bash
git clone https://github.com/Drr25/NaduFind-LostAndFound.git
cd NaduFind-LostAndFound
```

2. Install dependencies
```bash
npm install
```

3. Create `.env` file
```bash
cp .env.example .env
```

4. Configure your environment variables
```
MONGODB_URI=mongodb://localhost:27017/nadufind
JWT_SECRET=your_jwt_secret_key
PORT=5000
NODE_ENV=development
```

5. Start the server
```bash
npm start
```

6. Open your browser and navigate to `http://localhost:3000`

## Usage

### For Users
1. Register a new account
2. Log in with your credentials
3. Report lost or found items with photos and details
4. Search for items using filters
5. Submit claims for found items
6. Track the status of your reports and claims

### For Administrators
1. Log in with admin credentials
2. View all item reports and claims
3. Verify reports and approve/reject claims
4. Manage user accounts
5. View system statistics

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout

### Items
- `GET /api/items` - Get all items
- `POST /api/items` - Report new item
- `GET /api/items/:id` - Get item details
- `PUT /api/items/:id` - Update item
- `DELETE /api/items/:id` - Delete item

### Claims
- `POST /api/claims` - Submit new claim
- `GET /api/claims` - Get claims
- `PUT /api/claims/:id` - Update claim status

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License.

## Contact

For questions or support, please open an issue on GitHub.