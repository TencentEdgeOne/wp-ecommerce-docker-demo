

## Setting up Woocommerce Backend Environment
#### Step 1: Configure Environment Variables
Create .env file
```
WORDPRESS_DB_PASSWORD=wordpress123
MYSQL_ROOT_PASSWORD=somewordpress
MYSQL_PASSWORD=wordpress123
GRAPHQL_JWT_AUTH_SECRET_KEY=your-hwt-auth-secret-key
WP_HOME_URL=http://localhost // your site url
WP_DOMAIN=localhost // your site domain
WP_HOST=8080 // your site port
```

#### Step 2: Start Docker Containers
`docker compose up -d`
This project includes all template-adapted data that will be automatically imported during Docker startup.

#### Step 3: Verify WordPress Installation
Access `http://your.site.ip/wp-admin/admin.php` to verify if the service is running successfully.
The default admin login info is
UserName: admin, Password: 12345
Remember to change your password after logged in to ensure safety of your site.
