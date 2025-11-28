# Work From Office/Home Calendar

## If you liked it you can support my work
[!["Buy Me A Coffee"](https://raw.githubusercontent.com/michal-repo/random_stuff/refs/heads/main/bmac_small.png)](https://buymeacoffee.com/michaldev)



![WFO-1](Screenshot%20WFO%20Calendar.png?raw=true)

<hr>

![WFO-2](Screenshot%20WFO%20Calendar%202.png?raw=true)

## env
```
cd api
cp .env.example .env
```

Update .env file

## Composer
```
cd api
composer install
cp client/.htaccess vendor/
```

# DB
## Create DB
```
CREATE DATABASE `wfo_cal` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
```

Create user and grant access to new DB.

## Migrate DB

### Windows:
```
.\vendor\bin\doctrine-migrations.bat migrate
```

### Linux:
```
./vendor/bin/doctrine-migrations migrate
```
