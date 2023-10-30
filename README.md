# gimmenow
## Installation requirements:
====================================================================================
- Operating system
Distributions of Linux, including RedHat Enterprise Linux (RHEL), CentOS, Ubuntu, Debian, macOS.
- Memory requirement
Magento2 requires 2GB or higher RAM.
- Composer
Composer 2.x cannot be supported by Magento.
- Web server
Apache 2.4
Nginx 1.x
- Database
MySQL 8.0
MariaDB 10.4
- PHP
Magento supports PHP 7.4.0 and later.
Opensearch
- Opensearch 2.11.0

## Installation on MacOS locally:
====================================================================================
**Note:**
**MacOs with _M1_ _chip_ can use command "arch -x86_64" before "brew install" command.**
**Ex: arch -x86_64 brew install httpd**

### STEP 1: Installing Homebrew By Execute The Command Below:
`/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install.sh)" git -C /usr/local/Homebrew/Library/Taps/homebrew/homebrew-core fetch –unshallow /usr/local/bin/brew update`

### STEP 2: Install Apache2
- `brew install httpd`
- `brew services start httpd`

Apache Configuration to support the web pages, configure apache.
use command: `nano /usr/local/etc/httpd/httpd.conf`
- change Listen 8080 to Listen 80
- made some changes to the DocumentRoot directory
DocumentRoot "/Users/admin/magento3"
<Directory "/Users/admin/magento3">
- change AllowOverrides none to AllowOverrides all
- enable rewrite_module (LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so) by uncomment it
- under "<IfModule unixd_module>" change user and group to:
User admin
Group admin

Save and exit, httpd.config file and then run the command below:
brew services restart httpd

### STEP 3: Install MYSQL Server For Database Creation By Executing The Following Commands And When A Password Is Asked After The Below Three Commands, Simply Press ENTER
`brew install mysql`
`brew services start mysql`
`/usr/local/mysql/bin/mysql -u root -p`
`create database <databasename>;`
`CREATE USER '<username>'@'localhost' IDENTIFIED BY '<passwordforyourusername>';` //create user in mysql
`GRANT ALL PRIVILEGES ON *.* to '<username>'@'localhost';` //grant user all access to mysql
`flush privileges;`
`exit;`

### STEP 4: Setting And Installing PHP 8.1 And Its Modules
`brew install php@8.1`
`brew services start php@8.1`

PHP Configuration
In order to run PHP properly. open again httpd.config file by executing command below:
`nano /usr/local/etc/httpd/httpd.conf`

adding up the path to PHP 8.1 module in httpd.conf file.
add 2 modules below after rewrite_module: 
LoadModule php_module /usr/local/opt/php@8.1/lib/httpd/modules/libphp.so
LoadModule php_module /usr/local/lib/httpd/modules/libphp.so

update directory index:
change to DirectoryIndex index.php index.html
and add the following:
<FilesMatch \.php$>
SetHandler application/x-httpd-php
</FilesMatch>

Save the file and restart the apache service
brew services restart httpd

In the ~/.zshrc file, add the path to PHP 8.1 installation with the command line:
echo 'export PATH="/usr/local/opt/php@8.1/bin:$PATH"' >> ~/.zshrc
echo 'export PATH="/usr/local/opt/php@8.1/sbin:$PATH"' >> ~/.zshrc

Meanwhile, make some changes to php.ini file
`nano /usr/local/etc/php/8.1/php.ini`

change as follow:
max_execution_time = 360
memory_limit = -1
upload_max_filesize = 1024M
short_open_tag = On

Save and exit, php.ini file and then run the command below:
`brew services restart httpd`

### STEP 5: After The Above Steps, Install The Composer
`brew install composer`
`brew install wget`
`/usr/local/Cellar/composer/2.0.8/bin`
`wget https://getcomposer.org/download/1.10.17/composer.phar`
`mv composer.phar composer`
`chmod -R 755 composer`

### STEP 6: Download Magento By Using The Composer:
To install magento2 with composer, first, create the Access keys of Magento marketplace.
If you have an account on Magento Marketplace, simply sign in and generate the access keys.
For generating Access keys, login into Magento marketplace, and from the top right corner of
the page where your user name is displayed, navigate to My profile > Marketplace > My products > Access Keys.

`sudo composer create-project --repository=https://repo.magento.com/ magento/project-community-edition <your_project_name`

### STEP 7: In addition, Enable requisite permissions and restart Apache2

`sudo chmod -R 755 /Users/admin/project_directory`
`sudo chown -R admin:admin /Users/admin/project_directory`

### STEP 8: Similarly, Install The Open Search To Move Further
`brew install opensearch`
`brew services start opensearch`
`brew services restart --all`

### STEP 9: Likewise, Execute The Commands Below To Install Magento 2 By Using The Required Variables Such As Host, Database Name, Username, And Password, Etc.
`cd /Users/admin/your_project_root_directory` //go to magento root directory

`bin/magento setup:install --base-url=http:// Public IP Address or Domain Name --db-host=localhost --db-name=your_db_name --db-user=your_db_user --db-password=your_db_user --admin-firstname=Admin --admin-lastname=User --admin-email=admin@example.com --admin-user=admin --admin-password=your_admin_password --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1`

after Magento installation complete, now access the admin panel and frontend.
 
