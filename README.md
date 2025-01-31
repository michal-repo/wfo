# Work From Office/Home Calendar

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
```

# Migrate DB

## Windows:
```
.\vendor\bin\doctrine-migrations.bat migrate
```

## Linux:
```
./vendor/bin/doctrine-migrations migrate
```